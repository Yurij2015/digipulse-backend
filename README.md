# DigiPulse

A monitoring system for website availability, SSL certificates, and domain status.

Currently running in production, this is a fully independent project without external investment. It doesn't aim to be the "best on the market" or manage thousands of sites; it simply does its job well for the scale it was designed for.

---

## Architecture Overview

The project is divided into 4 independent components that communicate with each other:

| Component | Technology | Purpose | Status |
|---|---|---|---|
| **Backend** | Laravel 13 / PHP 8.5 | API Core, auth, admin panel, notifications | ✅ Production |
| **Monitor** | Go 1.23 | Background worker that performs actual pings, checks certificates, and measures latency | ✅ Production |
| **Frontend** | Nuxt 4 / Vue 3 | User interface | ✅ Production |
| **Infrastructure** | Terraform / Docker | Server configuration, deployment, secrets | ✅ Production |

All components are deployed on Hetzner Cloud and do not use third-party SaaS except for SMTP and Cloudflare Turnstile.

---

## Current Features

✅ Website availability monitoring with 1-minute intervals
✅ SSL certificate expiration tracking
✅ Domain registration expiration tracking
✅ Historical uptime statistics and charts
✅ Telegram and email notifications for downtime
✅ Team permissions and shared monitoring access
✅ Google OAuth authentication
✅ Admin panel powered by Filament v5
✅ Automated deployment with GitHub Actions
✅ Support ticket system

## In Development

- 🚧 Discord / Slack notifications
- 🚧 PagerDuty integration
- 🚧 Page content change detection
- 🚧 False-positive alert protection

## What's NOT included and not planned

❌ Unlimited free plans
❌ Marketing landing pages with loud promises
❌ AI problem analysis (though someone might suggest it eventually)
❌ Server, disk, or CPU monitoring - websites only
❌ Open registrations (currently)

---

## Technology Stack

| Layer | Technologies |
|---|---|
| Server | FrankenPHP / Octane 2 |
| Database | PostgreSQL 18 |
| Cache & Queues | Redis 7 |
| Frontend | Nuxt 4 / Vue 3 / Tailwind 4 |
| Monitor | Pure Go, no frameworks |
| Deployment | GitHub Actions, Docker, Terraform |

All dependencies are kept up-to-date and updated regularly within stable versions.

---

## Local Development

The entire stack can be launched via Laravel Sail with a single command:

```bash
git clone git@github.com:Yurij2015/digipulse-backend.git
cd digipulse-backend
composer install
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
```

Frontend and monitor services are launched separately from their respective repositories.

---

## About the Project

Developed in 2025. Created because most existing monitoring solutions are either too expensive, too slow, or spam with false-positive notifications.

It doesn't strive to be the largest. It strives to be the most reliable for those who use it.
