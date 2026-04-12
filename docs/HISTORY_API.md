# History API Reference

This document covers the historical monitoring data API for DigiPulse.

## Endpoints

### Get Site History

Retrieve aggregated monitoring statistics and incident history for a specific site.

**Endpoint:** `GET /api/sites/{id}/history`

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `week` | string | Current | ISO week format (e.g., `2026-W15`) |

**Response Example:**

```json
{
  "data": {
    "stats": {
      "avg_response_time": 245,
      "uptime_percentage": 99.8,
      "total_checks": 1440,
      "hourly_chart": [
        { "hour": "00:00", "avg_response_time": 230, "uptime_percentage": 100 },
        { "hour": "01:00", "avg_response_time": 250, "uptime_percentage": 100 }
      ]
    },
    "incidents": [
      {
        "id": 123,
        "status": "down",
        "error_message": "HTTP 500",
        "checked_at": "2026-04-12T10:15:00Z"
      }
    ]
  }
}
```

---

## Data Aggregation

DigiPulse automatically aggregates check results into hourly buckets to ensure fast load times for the dashboard charts. 

- **Response Time:** Averaged across all checks in the hour.
- **Uptime:** Calculated as (Successful Checks / Total Checks) * 100.

## Caching

To optimize performance, history data is cached:
- **Current Week:** 60 seconds (near real-time)
- **Past Weeks:** 24 hours (static archives)

---

## Related Documentation

- [Sites API Reference](./SITES_API.md)
- [Swagger UI Guide](./SWAGGER_GUIDE.md)
