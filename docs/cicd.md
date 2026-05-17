# CI/CD Pipeline

DigiPulse backend uses GitHub Actions for automated testing and deployment.

## Workflows

### `deploy.yml` — Deploy DigiPulse Backend

Triggered on every push to `main`. Runs the following jobs in order:

1. **create-deployment-artifacts** — installs Composer deps, creates a `.tar.gz` artifact, exports the deployment matrix.
2. **run-tests** — runs Pest tests against PostgreSQL and Redis services.
3. **build-docker-image** — builds and pushes the Docker image to GHCR (only if files under `docker/` changed).
4. **prepare-release-on-servers** — uploads and extracts the artifact on each server.
5. **create-env-file** — writes the `.env` from GitHub Variables/Secrets to each server.
6. **run-before-hooks** — runs optional pre-deploy commands (`DEPLOY_BEFORE_HOOKS`).
7. **activate-release** — symlinks the new release to `current`.
8. **run-after-hooks** — runs post-deploy commands (migrations, cache clear, container restart).
9. **clean-up** — keeps the last 5 releases and artifacts on each server.

### `health-check.yml`

Periodic health check that pings the API and notifies via Telegram on failure.

### `db-backup.yml` / `db-restore.yml`

Manual database backup and restore workflows.

---

## Deployment Matrix

Deployment targets are configured via a single GitHub Actions Variable: **`DEPLOYMENT_MATRIX`**.

### Format

```json
[
  {
    "name": "prod-1",
    "ip": "1.2.3.4",
    "port": "22",
    "username": "deploy",
    "path": "/var/www/digipulse",
    "enabled": true
  },
  {
    "name": "prod-2",
    "ip": "5.6.7.8",
    "port": "22",
    "username": "deploy",
    "path": "/var/www/digipulse",
    "enabled": false
  }
]
```

### Fields

| Field | Required | Description |
|---|---|---|
| `name` | yes | Display name used in job titles |
| `ip` | yes | Server IP address |
| `port` | yes | SSH port |
| `username` | yes | SSH user |
| `path` | yes | Base deployment path on the server |
| `enabled` | no | Set to `false` to skip this server. Omitting the field is treated as `true`. |

### Enabling / Disabling a Server

To temporarily disable a deployment target, set `"enabled": false` in the `DEPLOYMENT_MATRIX` variable via GitHub → Settings → Variables. No code changes needed.

If all servers are disabled the workflow fails immediately with an error.

### Multi-server Deploys

GitHub Actions matrix strategy automatically parallelizes all deployment jobs for each server in the array. Adding a second server requires only updating the `DEPLOYMENT_MATRIX` variable — the workflow itself does not change.

---

## GitHub Variables & Secrets

### Variables (non-sensitive)

| Variable | Description |
|---|---|
| `DEPLOYMENT_MATRIX` | JSON array of deployment targets (see above) |
| `APP_URL` | Production app URL |
| `APP_KEY_NEW` | Laravel app key |
| `DB_HOST` | Database host |
| `DB_DATABASE` | Database name |
| `DB_USERNAME` | Database user |
| `DB_PASSWORD` | Database password |
| `REDIS_HOST` | Redis host |
| `SESSION_DOMAIN` | Session cookie domain |
| `SANCTUM_STATEFUL_DOMAINS` | Sanctum stateful domains |
| `GOOGLE_CLIENT_ID` | Google OAuth client ID |
| `TELEGRAM_BOT_TOKEN` | Telegram bot token |
| `TELEGRAM_BOT_USERNAME` | Telegram bot username |
| `TURNSTILE_ACTIVE` | Enable/disable Cloudflare Turnstile |
| `TURNSTILE_SITE_KEY` | Turnstile site key |
| `BROADCAST_CONNECTION` | Broadcasting driver (`pusher` / `null`) |
| `PUSHER_APP_ID` / `PUSHER_APP_KEY` / `PUSHER_APP_CLUSTER` | Pusher config |
| `FILAMENT_ADMIN_EMAIL` | Initial Filament admin email |
| `FRONTEND_URL` | Frontend app URL |
| `MCP_SERVER_URL` | MCP server public URL (used to generate token connection links; falls back to `APP_URL` if empty) |
| `SENTRY_TRACES_SAMPLE_RATE` | Sentry tracing sample rate |
| `DEPLOY_BEFORE_HOOKS` | Optional shell commands run before release activation |
| `DEPLOY_AFTER_HOOKS` | Optional shell commands run after release activation |

### Secrets (sensitive)

| Secret | Description |
|---|---|
| `SSH_KEY` | Private SSH key for server access |
| `FRONTEND_KEY` | `X-Frontend-Key` header value |
| `RESEND_API_KEY` | Resend email API key |
| `GOOGLE_CLIENT_SECRET` | Google OAuth client secret |
| `TELEGRAM_WEBHOOK_SECRET` | Telegram webhook secret |
| `PUSHER_APP_SECRET` | Pusher app secret |
| `SENTRY_LARAVEL_DSN` | Sentry DSN |

---

## Server Directory Layout

Each server path (e.g. `/var/www/digipulse`) has the following structure:

```
/var/www/digipulse/
├── releases/          # Up to 5 recent releases
│   └── <git-sha>/
├── artifacts/         # Up to 5 recent .tar.gz archives
├── storage/           # Shared storage (symlinked into each release)
│   ├── app/
│   ├── framework/
│   └── logs/
├── current -> releases/<git-sha>   # Symlink to active release
└── .env               # Symlinked into active release
```
