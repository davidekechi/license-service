# Postman Collection Setup Guide

This guide will help you set up and use the License Service API Postman collection.

## Files

- **License Service API.postman_collection.json** - The main API collection with all endpoints
- **License Service API.postman_environment.json** - Environment variables for local development

## Import into Postman

1. Open Postman
2. Click **Import** button
3. Import both files:
   - `License Service API.postman_collection.json`
   - `License Service API.postman_environment.json`

## Configure Environment Variables

After importing, you need to set the actual values for your environment:

1. Click on **Environments** in the left sidebar
2. Select **License Service - Local**
3. Update the following variables:

### Required Variables

| Variable | Description | How to Get |
|----------|-------------|------------|
| `baseUrl` | API base URL including version | Default: `http://localhost:8000/api/v1` |
| `brandApiKey` | Brand API key for authentication | Run database seeder and copy the API key from output |
| `productPublicId` | Product public ID (ULID) | Run database seeder and copy a product ID from output |
| `customerEmail` | Customer email for testing | Default: `customer@example.com` (can be any valid email) |
| `licenseKey` | License key for activation/status | Leave empty initially - will be set from provision response |

### Getting Seeder Data

To get the `brandApiKey` and `productPublicId`, run the database seeder:

```bash
docker exec license-service-app php artisan db:seed
```

Look for output like:
```
Brand created: RankMath
  API Key: sk_live_rankmath_abc123def456...
  Product: SEO Pro (01HZABC123DEF456GHI789JKLM)
```

Copy these values into your Postman environment.

## Usage

### 1. Test Health Check
- Select **Health Check > Health Check**
- Click **Send**
- Should return 200 OK with API status

### 2. Provision a License
- Select **License Provisioning > Provision New License**
- Make sure `brandApiKey`, `customerEmail`, and `productPublicId` are set
- Click **Send**
- Copy the `license_key` from the response
- Update the `licenseKey` environment variable with this value

### 3. Activate the License
- Select **License Activation > Activate License for Site**
- The `licenseKey` variable will be used automatically
- Click **Send**

### 4. Check License Status
- Select **License Status > Check License Status**
- Click **Send**
- View all license details, activations, and seat usage

### 5. List Customer Licenses
- Select **Customer Licenses > List Licenses by Customer Email**
- Click **Send**
- View all licenses for the customer email

## Environment Variables Reference

All request bodies and URLs use these variables automatically:

- `{{baseUrl}}` - Base API URL with version (http://localhost:8000/api/v1)
- `{{brandApiKey}}` - Used in Authorization header for brand endpoints
- `{{licenseKey}}` - Used in URL path for activation and status endpoints
- `{{productPublicId}}` - Used in request bodies for provisioning and activation
- `{{customerEmail}}` - Used in request bodies and URL paths

## Tips

1. **Set licenseKey after provisioning**: After running a provision request, copy the `license_key` from the response and update your `licenseKey` environment variable for subsequent requests.

2. **Multiple products**: The collection includes examples for provisioning multiple products on a single license key.

3. **Different instance types**: Try the different activation examples (site, device, server) to see how instance metadata works.

4. **Pagination**: The customer licenses endpoint supports `page` and `per_page` query parameters.

## Troubleshooting

### 401 Unauthorized
- Check that `brandApiKey` is set correctly
- Ensure the API key starts with `sk_live_` or `sk_test_`
- Verify the brand is active in the database

### 404 License Not Found
- Ensure `licenseKey` variable is set
- Check the license key format (XXXX-XXXX-XXXX-XXXX)
- Verify the license exists in the database

### 422 Validation Error
- Check all required fields are present
- Verify `productPublicId` belongs to the authenticated brand
- Ensure `customerEmail` is a valid email format

### Connection Refused
- Ensure Docker containers are running: `docker ps`
- Verify the API is accessible at `http://localhost:8000`
- Check nginx and app containers are up
