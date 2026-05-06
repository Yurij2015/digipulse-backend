# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

DigiPulse is a website uptime, SSL certificate, and domain monitoring platform. The backend is Laravel 13 / PHP 8.5 running on FrankenPHP + Octane 2, with a companion Go monitor service that performs actual checks. All commands must be run through Laravel Sail.

## Commands

```bash
# Start all services (app, pgsql, redis, mailpit, monitor, redisinsight)
vendor/bin/sail up -d

# Run migrations
vendor/bin/sail artisan migrate

# Run all tests
vendor/bin/sail artisan test --compact

# Run a single test or filter
vendor/bin/sail artisan test --compact --filter=testName

# Format PHP (required after any PHP edits)
vendor/bin/sail bin pint --dirty --format agent

# List routes
vendor/bin/sail artisan route:list --except-vendor

# Tinker (always use single quotes to prevent shell expansion)
vendor/bin/sail artisan tinker --execute 'User::count();'

# Manually consume one monitor result from Redis
vendor/bin/sail artisan app:consume-monitor-results --once

# Push due checks to the Go monitor queue
vendor/bin/sail artisan app:schedule-checks
```

## Architecture

### Hexagonal (Ports & Adapters) for the Monitoring Module

```
app/Domain/Monitoring/        ← Pure business logic, no framework deps
    Contracts/                ← Interfaces (ports): SiteRepositoryInterface, AlertServiceInterface, etc.
    UseCases/                 ← ProcessMonitoringResult, CreateSiteUseCase
    Data/                     ← readonly DTOs: MonitoringResultData, CreateSiteData

app/Infrastructure/Monitoring/ ← Framework implementations (adapters)
    Repositories/             ← EloquentSiteRepository, EloquentResultRepository
    Notifications/            ← NotificationService (implements AlertServiceInterface)
    Cache/                    ← CacheService (implements CachePortInterface)

app/Http/Controllers/Api/     ← Entry points: validation, transactions, calls Use Cases
app/Console/Commands/         ← CLI entry points (ScheduleChecks, ConsumeMonitorResults, etc.)
```

All interface bindings are registered as **singletons** in `MonitoringServiceProvider`.

### Go ↔ Laravel Data Flow (Critical)

1. **Laravel → Go**: `app:schedule-checks` runs every minute, queries `SiteCheckConfiguration::dueForCheck()`, and pushes JSON payloads to Redis key `monitoring:results` (lpush). The Go monitor pops these tasks and performs checks.
2. **Go → Laravel**: Go pushes results back to Redis (`monitoring:results`). `app:consume-monitor-results` is a long-running blocking consumer (brpop) that reads results and calls `ProcessMonitoringResult` use case.
3. Failed payloads are moved to `monitoring:results:failed` after `max_attempts` retries.
4. **Keep in sync**: Go `CheckTask`/`CheckResult` structs (in the monitor repo) must stay aligned with Laravel DTOs in `app/Domain/Monitoring/Data/`.

### API Authentication & Middleware Stack

All API routes require the `frontend.key` middleware (`X-Frontend-Key` header validated against `config('app.frontend_key')`). Authenticated endpoints additionally require `auth:sanctum`. Registration/login additionally pass through Cloudflare Turnstile verification (`turnstile` middleware, configurable via `TURNSTILE_ACTIVE`).

### Encrypted PII & Blind Indexes

`email` and `google_id` fields on `User` are stored encrypted. Do not query them directly with `where()`. Use the blind index columns (`email_bindex`, `google_id_bindex`) for lookups.

### Scheduled Commands

Defined in `routes/console.php`:
- `app:schedule-checks` — every minute (pushes check tasks to Redis)
- `app:archive-check-results` — daily at 01:00
- `app:check-ssl-expirations` — daily at 02:00
- `telescope:prune` — daily

### Realtime / Broadcasting

Support chat (`SupportTicket`/`SupportTicketMessage`) uses `ShouldBroadcastNow` on `MessageSent` event for synchronous delivery. Broadcast auth uses `auth:sanctum` middleware on `/broadcasting/auth`. Channel authorization is in `routes/channels.php`.

### Admin Panel

Filament v5 admin at `/admin`. Access is restricted via `canAccessPanel()` on `User` (role-based via Spatie permissions). `FILAMENT_ADMIN_EMAIL` env controls the initial admin email.

### Octane Constraints

Singletons persist across requests. Never:
- Use `env()` outside `config/` files — use `config()` helper instead.
- Append to static properties (memory leak).
- Inject `Request`, `Application`, or the config repository into singleton constructors — use resolver closures.

## Key Env Variables

| Variable | Purpose |
|---|---|
| `FRONTEND_KEY` | Required header for all API requests (`X-Frontend-Key`) |
| `BROADCAST_CONNECTION` | Set to `pusher` for realtime; `null` disables broadcasting |
| `MONITOR_RESULTS_QUEUE` | Redis key for Go→Laravel result ingestion (default: `monitoring:results`) |
| `TURNSTILE_ACTIVE` | Set `false` to disable Cloudflare Turnstile in local dev |
| `TELEGRAM_BOT_TOKEN` / `TELEGRAM_WEBHOOK_SECRET` | Telegram bot integration |
| `TELESCOPE_ENABLED` / `TELESCOPE_RECORD_ALL` | Laravel Telescope (disabled in testing) |

## Testing

Tests use Pest 4. Feature tests live in `tests/Feature/`, unit tests in `tests/Unit/`. The test database is `testing` (PostgreSQL, created automatically by Sail). `DB_URL` is intentionally blank in `phpunit.xml` to force per-env DB config. Queue runs synchronously (`sync`) in tests.

Create tests with:
```bash
vendor/bin/sail artisan make:test --pest FeatureName
vendor/bin/sail artisan make:test --pest --unit UnitName
```
