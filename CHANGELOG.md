# Changelog

All notable changes to `mindtwo/server-monitoring` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 1.0.0 - 2026-06-13

Initial release.

### Added

- Standalone server project: Docker and load/uptime collectors on top of the base catalog, dependency-free .env loader, bin/monitor CLI (push/show/snapshot/collectors), single-file signed pull endpoint, crontab example.
- HMAC-SHA256 request authentication with replay protection, shared with the whole suite.
