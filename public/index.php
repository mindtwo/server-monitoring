<?php

declare(strict_types=1);

use Mindtwo\Monitoring\Http\PullRequestHandler;
use Mindtwo\Monitoring\Server\Env;
use Mindtwo\Monitoring\Server\MonitorFactory;
use Mindtwo\Monitoring\Server\ServerConfigurationRepository;
use Mindtwo\Monitoring\Transport\HmacSignatureVerifier;

require dirname(__DIR__).'/vendor/autoload.php';

/**
 * The standalone pull endpoint: GET /api/m2-monitoring, HMAC-signed.
 * Point a (sub)domain's document root at this directory.
 */
$emit = static function (int $status, array $payload): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, private');

    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
};

$path = rtrim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH), '/');

if ($path !== '/api/m2-monitoring') {
    $emit(404, ['message' => 'Not found.']);

    return;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    $emit(405, ['message' => 'Method not allowed.']);

    return;
}

$config = new ServerConfigurationRepository(Env::load(MonitorFactory::projectDir().'/.env'));

if (! $config->routeEnabled()) {
    $emit(404, ['message' => 'Not found.']);

    return;
}

$headers = [];

foreach ($_SERVER as $key => $value) {
    if (is_string($value) && str_starts_with((string) $key, 'HTTP_')) {
        $headers[str_replace('_', '-', strtolower(substr((string) $key, 5)))] = $value;
    }
}

$handler = new PullRequestHandler(
    $config,
    new HmacSignatureVerifier(max(0, $config->integer('signature_tolerance')))
);

[$status, $payload] = $handler->handle(
    isset($_SERVER['REMOTE_ADDR']) && is_string($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
    $headers,
    (string) file_get_contents('php://input'),
    static fn (): array => MonitorFactory::make($config)->snapshot()->toArray()
);

$emit($status, $payload);
