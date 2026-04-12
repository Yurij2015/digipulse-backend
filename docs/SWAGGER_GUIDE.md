# Swagger/OpenAPI Documentation

This guide explains how to use the interactive Swagger UI to test and explore the VetCard Settings API.

## 🎯 What is Swagger UI?

Swagger UI is an interactive API documentation tool that allows you to:
- **Test API endpoints** directly in your browser
- **Authenticate** with your Bearer token
- **View request/response schemas** in real-time
- **Validate data** before sending requests
- **Explore all endpoints** with visual documentation

## 🌐 Accessing Swagger UI

### Development Environment
```
http://localhost/api/documentation
```

### Production Environment
```
https://your-tenant.vet.digispace.pro/api/documentation
```

### For Specific Tenants
Replace `your-tenant` with your actual tenant subdomain:
```
https://{tenant-subdomain}.vet.digispace.pro/api/documentation
```

## 🚀 Getting Started

### Step 1: Generate Documentation

Before accessing Swagger UI, ensure the OpenAPI specification is generated:

```bash
php artisan l5-swagger:generate
```

This command reads the OpenAPI annotations in your controllers and generates the documentation.

### Step 2: Access Swagger UI

Open your browser and navigate to the Swagger UI URL (development or production).

### Step 3: Authenticate

1. **Locate the "Authorize" button** (usually in the top-right corner)
2. **Click the "Authorize" button**
3. **Enter your authentication token:**
   ```
   Bearer YOUR_ACCESS_TOKEN_HERE
   ```
   - Replace `YOUR_ACCESS_TOKEN_HERE` with your actual token
   - The `Bearer` prefix is required
4. **Click "Authorize"**
5. **Click "Close"**

You're now authenticated and can test protected endpoints!

## 📋 Using Swagger UI for VetCard API

### Finding VetCard Endpoints

1. Scroll down the Swagger UI page
2. Look for the **"VetCard"** section (endpoints are grouped by tags)
3. You'll see two endpoints:
   - `GET /api/vet-card-settings`
   - `POST /api/vet-card-settings`

### Testing GET Endpoint

**Purpose:** Retrieve current VetCard settings

1. **Click on `GET /api/vet-card-settings`** to expand it
2. **Click the "Try it out" button** (top-right of the endpoint section)
3. **Click "Execute"** button
4. **View the response:**
   - **Response Code:** Should be `200` (success)
   - **Response Body:** JSON with current settings or defaults
   - **Response Headers:** Content-Type, etc.
   - **Request URL:** The actual URL that was called

**Example Response:**
```json
{
  "data": {
    "id": 1,
    "color": "#14b8a6",
    "clinic_name": "VetSpace Clinic",
    "tagline": "Your trusted partner in pet care",
    "enable_appointment_button": true,
    "sections": [...]
  }
}
```

### Testing POST Endpoint

**Purpose:** Create or update VetCard settings

1. **Click on `POST /api/vet-card-settings`** to expand it
2. **Click the "Try it out" button**
3. **Edit the Request Body:**
   - The request body is pre-filled with example data
   - Modify the JSON to test different values
   - Example:
     ```json
     {
       "color": "#ff5733",
       "clinic_name": "My Test Clinic",
       "tagline": "Testing the API",
       "enable_appointment_button": true,
       "sections": [
         {
           "id": "services",
           "name": "Services",
           "visible": true,
           "order": 0
         }
       ]
     }
     ```
4. **Click "Execute"**
5. **View the response:**
   - **Response Code:** Should be `200` (success) or `422` (validation error)
   - **Response Body:** Success message and saved data, or validation errors

**Success Response (200):**
```json
{
  "message": "VetCard settings saved successfully",
  "data": {
    "id": 1,
    "color": "#ff5733",
    "clinic_name": "My Test Clinic",
    ...
  }
}
```

**Validation Error Response (422):**
```json
{
  "message": "Validation failed",
  "errors": {
    "color": ["The color field must match the format #RRGGBB."]
  }
}
```

## 🎨 Features of Swagger UI

### 1. Request/Response Schemas

Click on the "Schema" tab (next to "Example Value") to see:
- All available fields
- Data types (string, boolean, integer, etc.)
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

### 5. Request URL

See the exact URL that was called:
- Useful for debugging
- Copy for use in your application

## 🔧 Advanced Usage

### Testing Validation

Test the API's validation rules by sending invalid data:

1. **Invalid Color Format:**
   ```json
   {
     "color": "red"
   }
   ```
   Expected: `422` error with validation message

2. **Missing Required Fields in Sections:**
   ```json
   {
     "sections": [
       {
         "id": "services"
       }
     ]
   }
   ```
   Expected: `422` error for missing `name`, `visible`, `order`

3. **Invalid Data Types:**
   ```json
   {
     "enable_appointment_button": "yes"
   }
   ```
   Expected: `422` error (should be boolean)

### Testing Partial Updates

Test that partial updates work correctly:

```json
{
  "color": "#123456"
}
```

This should update only the color, leaving other fields unchanged.

### Testing Default Values

1. **Clear existing settings** (using database tools or DELETE endpoint if available)
2. **Call GET endpoint**
3. **Verify default values are returned:**
   - `color`: `#14b8a6`
   - `enable_appointment_button`: `true`
   - `sections`: Array with 5 default sections

## 📝 Regenerating Documentation

When you update OpenAPI annotations in the controller, regenerate the documentation:

```bash
# Generate fresh documentation
php artisan l5-swagger:generate
```

The changes will be immediately available in Swagger UI (refresh the page).

## 🐛 Troubleshooting

### "Authorize" Button Not Working

**Problem:** Token not accepted  
**Solution:**
- Ensure you include `Bearer` prefix
- Check token is valid and not expired
- Verify you have access to the tenant

### Endpoint Returns 401 Unauthorized

**Problem:** Authentication failing  
**Solution:**
1. Click "Authorize" button again
2. Get a fresh token
3. Re-authenticate in Swagger UI

### Changes Not Showing

**Problem:** Documentation not updated  
**Solution:**
```bash
# Regenerate documentation
php artisan l5-swagger:generate

# Clear caches
php artisan cache:clear
php artisan config:clear
```

### Swagger UI Not Loading

**Problem:** 404 or blank page  
**Solution:**
1. Check `config/l5-swagger.php` configuration
2. Ensure route is registered
3. Clear route cache: `php artisan route:clear`
4. Regenerate: `php artisan l5-swagger:generate`

### CORS Errors

**Problem:** Cross-origin requests blocked  
**Solution:**
- Check CORS configuration in `config/cors.php`
- Ensure your domain is allowed
- Check headers in network tab

## 🎓 Best Practices

### For Development

1. **Always regenerate** after controller changes:
   ```bash
   php artisan l5-swagger:generate
   ```

2. **Test each endpoint** after making changes

3. **Document edge cases** with OpenAPI annotations

4. **Use Swagger for demos** to stakeholders

### For Production

1. **Generate before deploying:**
   ```bash
   php artisan l5-swagger:generate
   ```

2. **Include in deployment scripts**

3. **Consider authentication** for Swagger UI in production

4. **Cache the generated docs** for performance

## 📚 OpenAPI Annotations

The VetCard API uses OpenAPI annotations in the controller:

**Location:** `app/Http/Controllers/Api/VetCardSettingController.php`

### GET Endpoint Annotation
```php
/**
 * @OA\Get(
 *   path="/api/vet-card-settings",
 *   summary="Get VetCard settings",
 *   tags={"VetCard"},
 *   security={{"frontendKey":{}}, {"bearerAuth":{}}},
 *   @OA\Response(response=200, description="Success")
 * )
 */
```

### POST Endpoint Annotation
```php
/**
 * @OA\Post(
 *   path="/api/vet-card-settings",
 *   summary="Create or update VetCard settings",
 *   tags={"VetCard"},
 *   security={{"frontendKey":{}}, {"bearerAuth":{}}},
 *   @OA\RequestBody(required=true, ...),
 *   @OA\Response(response=200, description="Success")
 * )
 */
```

## 🔗 Additional Resources

### Laravel Documentation
- [L5-Swagger Package](https://github.com/DarkaOnLine/L5-Swagger)
- [OpenAPI Specification](https://swagger.io/specification/)

### Related Documentation
- [VetCard API Reference](./VETCARD_API.md) - Complete API documentation
- [Implementation Guide](./VETCARD_IMPLEMENTATION_SUMMARY.md) - Setup instructions
- [Deployment Checklist](./VETCARD_DEPLOYMENT_CHECKLIST.md) - Deployment guide

## ✅ Quick Reference

| Action | Command |
|--------|---------|
| **Generate Docs** | `php artisan l5-swagger:generate` |
| **Access Dev UI** | `http://localhost/api/documentation` |
| **Access Prod UI** | `https://your-tenant.vet.digispace.pro/api/documentation` |
| **Authenticate** | Click "Authorize" → `Bearer YOUR_TOKEN` |
| **Clear Cache** | `php artisan cache:clear` |

---

**Last Updated:** January 24, 2026  
**Version:** 1.0.0
