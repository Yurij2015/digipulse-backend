# DigiPulse API Documentation

Welcome to the DigiPulse monitoring system documentation. This directory contains detailed references for our API and internal components.

## 📁 Documentation Sections

### 🌐 [Sites API](./SITES_API.md)

Manage your monitored sites, including creation, updates, and configuration of check types.

### 📈 [History API](./HISTORY_API.md)

Access historical monitoring data, aggregated statistics, and incident logs.

### 🛠️ [Monitoring Checkers](./checkers.md)

Technical details of how various check types (HTTP, Ping, SSL, etc.) are implemented in the Go worker.

### 📖 [Swagger UI Guide](./SWAGGER_GUIDE.md)

How to use the interactive Swagger UI for testing and exploring the API endpoints.

---

## 🏗️ Technical Architecture

- **[Scheduling & Dispatching](./scheduling.md)** - How checks are queued.
- **[Development Tools](./monitoring_tools.md)** - Useful tools for monitoring during development.
- **[Testing Strategy](./testing.md)** - Guide to running tests in DigiPulse.

---

## 🚀 Getting Started

To explore the API interactively, start the development environment and visit:
[http://localhost/api/documentation](http://localhost/api/documentation)

**Note:** Ensure you have generated the latest documentation using:

```bash
vendor/bin/sail artisan l5-swagger:generate
```

---
**Last Updated:** April 12, 2026
