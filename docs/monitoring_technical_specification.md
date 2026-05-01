# Monitoring Module - Technical Specification

## 1. Module Overview
Core monitoring system responsible for availability checks, status tracking, and alert management. This is a core business module implemented following Hexagonal Architecture principles.

## 2. Architecture Layers

| Layer | Responsibility |
|---|---|
| **Domain Layer** | Pure business logic, Use Cases, DTOs, Port interfaces |
| **Infrastructure Layer** | Adapter implementations (Eloquent, Cache, Alerts) |
| **Application Layer** | HTTP Controllers, Console Commands entry points |

## 3. Core Business Rules

### 3.1 Alert Triggering Logic
**Rule ID: MON-001**

✅ **Alert is sent ONLY ONCE when site transitions to down status**

- Condition: `previousStatus !== 'down' AND currentStatus === 'down'`
- Alerts will NOT be sent repeatedly while site remains down
- New alert will be sent only after site recovers and goes down again
- This rule prevents:
  - User spam flooding with duplicate alerts
  - Unnecessary resource usage for repeated notifications
  - Rate limiting issues with notification channels

### 3.2 Result Processing Flow

```
1. Read previous status for check configuration
2. Update current configuration status
3. Persist historical check result
4. Trigger alert if status transition to down occurred
5. Invalidate affected user caches
```

## 4. Ports & Adapters Mapping

| Port Interface | Adapter Implementation |
|---|---|
| `SiteRepositoryInterface` | `EloquentSiteRepository` |
| `ResultRepositoryInterface` | `EloquentResultRepository` |
| `AlertServiceInterface` | `LaravelAlertService` |
| `CachePortInterface` | `LaravelCacheAdapter` |

## 5. Non Functional Requirements

- All business logic must remain framework agnostic
- Use Cases must be testable without booting Laravel
- No state leakage between requests (Octane compatible)
- Maximum processing time per result: < 50ms
