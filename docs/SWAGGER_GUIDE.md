# Swagger/OpenAPI Documentation

This guide explains how to use the interactive Swagger UI to test and explore the DigiPulse Monitoring API.

## 🎯 What is Swagger UI?

Swagger UI is an interactive API documentation tool that allows you to:
- **Test API endpoints** directly in your browser
- **Authenticate** with your Bearer token and Frontend Key
- **View request/response schemas** in real-time
- **Validate data** before sending requests
- **Explore all endpoints** with visual documentation

## 🌐 Accessing Swagger UI

### Development Environment (Laravel Sail)
```
http://localhost/api/documentation
```

## 🚀 Getting Started

### Step 1: Generate Documentation

Before accessing Swagger UI, ensure the OpenAPI specification is generated:

```bash
vendor/bin/sail artisan l5-swagger:generate
```

This command reads the OpenAPI annotations/attributes in your controllers and generates the documentation.

### Step 2: Access Swagger UI

Open your browser and navigate to the Swagger UI URL.

### Step 3: Authenticate

1. **Locate the "Authorize" button** (top-right corner)
2. **Setup Frontend Key:**
   - Find `frontendKey` entry
   - Enter your `X-Frontend-Key` value
   - Click "Authorize"
3. **Setup Bearer Token:**
   - Find `bearerAuth` entry
   - Enter your token: `Bearer YOUR_ACCESS_TOKEN_HERE`
   - Click "Authorize"
4. **Click "Close"**

You're now authenticated and can test protected endpoints!

## 📋 Using Swagger UI for DigiPulse API

### Finding Monitoring Endpoints

1. Scroll down the Swagger UI page
2. Look for the **"Sites"** section (endpoints are grouped by tags)
3. You'll see endpoints like:
   - `GET /api/sites`
   - `POST /api/sites`
   - `GET /api/sites/{site}/history`

### Testing GET Sites Endpoint

**Purpose:** Retrieve list of monitored sites

1. **Click on `GET /api/sites`** to expand it
2. **Click the "Try it out" button**
3. **Click "Execute"**
4. **View the response:**
   - **Response Code:** 200 (success)
   - **Response Body:** JSON array of your sites

**Example Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "My Website",
      "url": "https://example.com",
      "update_interval": 300,
      "is_active": true,
      "configurations": [...]
    }
  ]
}
```

## 🎨 Features of Swagger UI

### 1. Request/Response Schemas

Click on the "Schema" tab (next to "Example Value") to see:
- All available fields
- Data types (`string`, `boolean`, `integer`, etc.)
- Required vs. optional fields
- Validation rules
- Descriptions

### 2. Example Values

Click on the "Example Value" to see:
- Pre-populated sample data
- Valid request structure
- Click to copy and paste into your code

### 3. Response Codes

Each endpoint shows all possible response codes:
- **200:** Success
- **401:** Unauthorized (authentication required)
- **422:** Validation Error (invalid data)
- **404:** Not Found

### 4. cURL Command

Swagger UI generates a cURL command for each request:
- Scroll down after executing
- Find the "Curl" section
- Copy the command to use in terminal

## 🔧 Advanced Usage

### Testing Validation

Test the API's validation rules by sending invalid data:

1. **Invalid URL Format:**
   ```json
   {
     "url": "not-a-url"
   }
   ```
   Expected: `422` error with validation message

### Testing Partial Updates

Test that partial updates work correctly:

```json
{
  "is_active": false
}
```

This should update only the status, leaving other fields unchanged.

## 📝 Regenerating Documentation

When you update OpenAPI annotations/attributes in the controller, regenerate the documentation:

```bash
vendor/bin/sail artisan l5-swagger:generate
```

The changes will be immediately available in Swagger UI (refresh the page).

## 🐛 Troubleshooting

### "Authorize" Button Not Working

**Problem:** Token not accepted  
**Solution:**
- Ensure you include `Bearer ` prefix (with space)
- Check token is valid and not expired
- Verify `X-Frontend-Key` is correct

### Endpoint Returns 401 Unauthorized

**Problem:** Authentication failing  
**Solution:**
1. Click "Authorize" button again
2. Get a fresh token via Login API
3. Re-authenticate in Swagger UI

### Changes Not Showing

**Problem:** Documentation not updated  
**Solution:**
```bash
vendor/bin/sail artisan l5-swagger:generate
vendor/bin/sail artisan cache:clear
vendor/bin/sail artisan config:clear
```

## 🎓 Best Practices

### For Development

1. **Always regenerate** after controller changes:
   ```bash
   vendor/bin/sail artisan l5-swagger:generate
   ```

2. **Test each endpoint** after making changes

3. **Document edge cases** with OpenAPI attributes

## 📚 OpenAPI Attributes

DigiPulse uses PHP 8 attributes in the controller:

**Location:** `app/Http/Controllers/Api/SiteController.php`

### Implementation Example
```php
#[OA\Get(
    path: '/api/sites',
    summary: 'List sites',
    tags: ['Sites'],
    security: [['frontendKey' => []], ['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Success')
    ]
)]
```

## 🔗 Additional Resources

### Laravel Documentation
- [L5-Swagger Package](https://github.com/DarkaOnLine/L5-Swagger)
- [OpenAPI Specification](https://swagger.io/specification/)

### Related Documentation
- [Sites API Reference](./SITES_API.md) - Complete API documentation
- [Checkers Documentation](./checkers.md) - Site check details

## ✅ Quick Reference

| Action | Command |
|--------|---------|
| **Generate Docs** | `vendor/bin/sail artisan l5-swagger:generate` |
| **Access Dev UI** | `http://localhost/api/documentation` |
| **Authenticate** | Click "Authorize" → `Bearer YOUR_TOKEN` |
| **Clear Cache** | `vendor/bin/sail artisan cache:clear` |

---

**Last Updated:** April 12, 2026
