# DigiPulse Backend (Laravel API)

The core API for the DigiPulse monitoring platform, built with Laravel 13 and powered by FrankenPHP Octane for high performance.

## Technology Stack

- **PHP 8.5**
- **Framework**: Laravel 13
- **Server**: FrankenPHP (Octane)
- **Admin Panel**: Filament v5
- **Database**: PostgreSQL 18
- **Cache/Queue**: Redis (Alpine)
- **Frontend**: Nuxt 4 / Node.js 22
- **Docker Image**: [`ghcr.io/yurij2015/digipulse-backend`](https://ghcr.io/yurij2015/digipulse-backend)

## Key Features

- **Real-time Monitoring**: Integrates with the Go-based monitor service via Redis.
- **Telegram Notifications**: Immediate alerts for website downtime.
- **Bento Grid API**: Optimized data structures for the Nuxt 4 frontend.
- **Auth**: Google OAuth + Email verification.

## Deployment (CI/CD)

Deployments are automated via **GitHub Actions**.

### Workflow:
1.  **Build**: Composer dependencies are installed, and artifacts are packed.
2.  **Docker**: If `docker/` files changed, a new image is built and pushed to GHCR.
3.  **Environment**: A production `.env` file is generated on the server from GitHub Secrets.
4.  **Deploy**: Artifacts are uploaded via SCP and symlinked to `/home/yurii/digi-pulse-backend/current`.
5.  **Database**: Migrations are run automatically via the `artisan migrate` hook.

### Required GitHub Secrets:

| Secret | Description |
|---|---|
| `SSH_KEY` | Private SSH key for the Hetzner server. |
| `APP_KEY` | Laravel Application Key. |
| `APP_URL` | Production URL (e.g., `https://api.pulse.digispace.pro`). |
| `FRONTEND_KEY` | Shared key for frontend request authorization (`X-Frontend-Key`). |
| `DB_HOST` | Database host (usually `digipulse-db`). |
| `DB_PASSWORD` | PostgreSQL password. |
| `REDIS_HOST` | Redis host (usually `digipulse-redis`). |
| `TELEGRAM_BOT_TOKEN` | Token from BotFather for downtime alerts. |
| `INTERNAL_MONITOR_KEY` | Shared secret for the Go Monitor service. |
| `GOOGLE_CLIENT_ID` | Google OAuth Client ID. |
| `GOOGLE_CLIENT_SECRET` | Google OAuth Client Secret. |
| `TURNSTILE_SITE_KEY` | Cloudflare Turnstile Site Key. |
| `TURNSTILE_SECRET_KEY` | Cloudflare Turnstile Secret Key. |
| `MAIL_HOST` | SMTP Host for email notifications. |
| `MAIL_PASSWORD` | SMTP Password. |

## Local Development

1.  Clone the repository.
2.  Install dependencies: `composer install`.
3.  Start Laravel Sail: `./vendor/bin/sail up -d`.
4.  Run migrations: `./vendor/bin/sail artisan migrate`.

## Documentation

- [Filament Admin](app/Filament/Resources)
- [API Endpoints](routes/api.php)
- [Deployment Workflow](.github/workflows/deploy.yml)
