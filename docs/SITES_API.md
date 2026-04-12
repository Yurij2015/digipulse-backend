# Sites API Reference

This document provides a detailed reference for the Sites management API in DigiPulse.

## Base URL

```
http://localhost/api
```

## Authentication

All endpoints require both a **Frontend Key** and a **Bearer Token** (after login):

```
X-Frontend-Key: your-frontend-key
Authorization: Bearer your-jwt-token
```

---

## Endpoints

### List Sites

Fetch all sites monitored by the authenticated user.

**Endpoint:** `GET /api/sites`

**Response Example:**

```json
{
  "data": [
    {
      "id": 1,
      "name": "Production Server",
      "url": "https://api.example.com",
      "update_interval": 300,
      "is_active": true,
      "configurations": [
        {
          "id": 1,
          "check_type": {
            "id": 1,
            "name": "HTTP",
            "slug": "http"
          },
          "is_active": true,
          "last_status": "up",
          "last_checked_at": "2026-04-12T15:00:00Z"
        }
      ],
      "created_at": "2026-04-01T10:00:00Z"
    }
  ]
}
```

---

### Create Site

Add a new site to be monitored.

**Endpoint:** `POST /api/sites`

**Request Body:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `name` | string | Yes | Human-readable name for the site |
| `url` | string | Yes | Full URL including protocol |
| `update_interval` | integer | Yes | Interval in seconds (e.g., 60, 300) |
| `is_active` | boolean | No | Defaults to true |

**Example Request:**
```json
{
  "name": "My Blog",
  "url": "https://myblog.com",
  "update_interval": 600
}
```

---

### Update Site

Modify an existing site's configuration.

**Endpoint:** `PUT /api/sites/{id}`

**Request Body:** (All fields optional)
- `name`
- `url`
- `update_interval`
- `is_active`

---

### Delete Site

Remove a site and all its monitoring configurations/results.

**Endpoint:** `DELETE /api/sites/{id}`

---

## Response Structure

### Site Object

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Unique identifier |
| `name` | string | Site name |
| `url` | string | Monitored URL |
| `update_interval` | integer | Scan frequency in seconds |
| `is_active` | boolean | Monitoring status toggle |
| `configurations` | array | List of active check configurations |

---

## Error Responses

### 401 Unauthorized
Missing or invalid authentication credentials.

### 422 Unprocessable Entity
Validation failed for one or more fields.

---

## Related Documentation

- [History API Reference](./HISTORY_API.md)
- [Swagger UI Guide](./SWAGGER_GUIDE.md)
- [Monitoring Checkers](./checkers.md)
