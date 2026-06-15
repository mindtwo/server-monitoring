# mindtwo/server-monitoring

[![Tests](https://github.com/mindtwo/server-monitoring/actions/workflows/tests.yml/badge.svg)](https://github.com/mindtwo/server-monitoring/actions/workflows/tests.yml)
[![PHPStan Level 8](https://img.shields.io/badge/PHPStan-level%208-brightgreen)](phpstan.neon.dist)
[![PHP 8.0+](https://img.shields.io/badge/php-%5E8.0-blue)](composer.json)
[![License: MIT](https://img.shields.io/badge/license-MIT-lightgrey)](LICENSE.md)

Standalone server-level monitoring of the mindtwo suite — a self-contained PHP project that
runs directly on a host, **no application integration required**. On top of
[`mindtwo/base-monitoring`](https://github.com/mindtwo/base-monitoring) (OS, web server,
database, Node.js, system stats, Composer/npm packages, audits, git) it adds:

- **Docker Engine** version detection,
- **load averages and uptime**,
- a tiny **CLI** (`bin/monitor`) for cron-driven pushes and local inspection,
- a single-file **pull endpoint** (`public/index.php`) serving signed snapshots at
  `GET /api/m2-monitoring`.

## Installation

```bash
git clone https://github.com/mindtwo/server-monitoring /opt/mindtwo/server-monitoring
cd /opt/mindtwo/server-monitoring
composer install --no-dev
cp .env.example .env   # add MONITORING_PROJECT_KEY + MONITORING_SECRET
```

### Scheduled push (cron)

```cron
0 3 * * * /usr/bin/php /opt/mindtwo/server-monitoring/bin/monitor push >> /var/log/mindtwo-monitoring.log 2>&1
```

See [crontab.example](crontab.example).

### Pull endpoint (optional)

Point a vhost/subdomain document root at `public/` — requests to
`GET /api/m2-monitoring` are answered with the snapshot after IP allow-list, configuration
and HMAC signature checks (same protocol as the whole suite: signature over
`"{timestamp}.{raw body}"`, 300 s replay window).

## CLI

```text
monitor push [--dry-run]      Build and deliver a snapshot (cron this daily)
monitor show                  Human-readable snapshot overview
monitor snapshot [--compact]  Print the snapshot JSON
monitor collectors            List collectors and their support status
```

## Configuration (.env)

Real environment variables always win over `.env` file values.

| Variable | Default | Purpose |
| --- | --- | --- |
| `MONITORING_PROJECT_KEY` | – | Project key from the dashboard |
| `MONITORING_SECRET` | – | Shared secret (never transmitted) |
| `MONITORING_ENDPOINT` | central endpoint | Push target |
| `MONITORING_ENVIRONMENT` | `production` | Reported environment |
| `MONITORING_IP_ALLOW_LIST` | – | Comma-separated IPs / CIDR ranges for pull |
| `MONITORING_ROUTE_ENABLED` | `true` | Serve the pull endpoint |
| `MONITORING_SIGNATURE_TOLERANCE` | `300` | Signature timestamp window (seconds) |
| `MONITORING_TIMEOUT` | `15` | Push timeout (seconds) |
| `MONITORING_PROJECT_ROOT` | project dir | Where lockfiles/git are inspected |

## Development

```bash
composer install
composer check    # pint --test + phpstan (level 8, PHP 8.0 mode) + pest
```

## Security

If you discover a security issue, please email [info@mindtwo.de](mailto:info@mindtwo.de)
instead of opening a public issue.

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md).
