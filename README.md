# License Service

> Centralized license management system for multi-brand WordPress plugin ecosystem

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Architecture](#architecture)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Configuration](#configuration)
- [Database Setup](#database-setup)
- [Running the Application](#running-the-application)
- [Testing](#testing)
- [API Documentation](#api-documentation)
- [Postman Collection](#postman-collection)
- [Health Checks & Monitoring](#health-checks--monitoring)
- [Project Structure](#project-structure)
- [User Stories](#user-stories)
- [Development](#development)
- [Troubleshooting](#troubleshooting)
- [Documentation](#documentation)
- [License](#license)

---

## Overview

The License Service is a **production-ready, centralized license management system** designed for multi-brand WordPress plugin ecosystems. It provides a unified API for license provisioning, activation, validation, and customer management across multiple brands (WP Rocket, RankMath, Imagify, BackWPup).

### Key Capabilities

- 🎫 **License Provisioning:** Brands can create and manage license keys
- ✅ **License Activation:** End-user products can activate licenses with seat management
- 📊 **Status Checking:** Real-time license validation and seat availability
- 👥 **Customer Management:** Cross-brand customer license lookup
- 📝 **Audit Logging:** Complete event trail for compliance
- 🚀 **Production-Ready:** Health checks, metrics, rate limiting, error handling

---

## Features

### Implemented User Stories ✅

| Story | Description | Status |
|-------|-------------|--------|
| **US1** | Brand can provision licenses for customers | ✅ Complete |
| **US3** | End-user products can activate licenses | ✅ Complete |
| **US4** | Users can check license status and entitlements | ✅ Complete |
| **US6** | Brands can list all licenses for a customer email | ✅ Complete |

### Production Features ✅

- ✅ RESTful API with consistent JSON responses
- ✅ Brand authentication with API keys
- ✅ Rate limiting (4 different limiters)
- ✅ CORS configuration
- ✅ Global exception handling
- ✅ Health check endpoints
- ✅ System metrics endpoint
- ✅ Structured logging
- ✅ Event-driven audit logging (async)
- ✅ Comprehensive test suite (330+ tests)
- ✅ PHPStan level 6 compliance
- ✅ Postman collection included

---

## Architecture

### Technology Stack

- **Framework:** Laravel 11
- **Language:** PHP 8.2
- **Database:** PostgreSQL 15
- **Testing:** Pest PHP
- **Static Analysis:** PHPStan Level 6
- **Containerization:** Docker & Docker Compose

### Design Pattern

**Modular Monolith** with strict module boundaries:
```
app/Modules/
├── Brand/          # Brand and product management
├── License/        # License key and activation logic
├── AuditLog/       # Event tracking and compliance
└── Shared/         # Cross-cutting concerns (middleware, exceptions, helpers)
```

**Key Architectural Decisions:**

- Repository pattern for data access
- Service layer for business logic
- Event-driven audit logging
- DTOs for data transfer
- Resource classes for API responses

For detailed architecture explanation, see [Explanation.md](./Explanation.md).

---

## Prerequisites

Ensure you have the following installed:

- **Docker:** >= 20.10
- **Docker Compose:** >= 2.0

**OR** for local development without Docker:

- **PHP:** >= 8.2 with extensions: pdo_pgsql, mbstring, xml, curl, zip
- **Composer:** >= 2.6
- **PostgreSQL:** >= 15

---

## Installation

### Option 1: Docker (Recommended)

#### 1. Clone the Repository
```bash
git clone https://github.com/davidekechi/license-service.git
cd license-service
```

#### 2. Copy Environment File
```bash
cp .env.example .env
```

#### 3. Build and Start Containers
```bash
docker-compose up -d
```

This will start:
- **app:** PHP 8.2 with Laravel
- **postgres:** PostgreSQL 15 database
- **nginx:** Web server (port 8000)

#### 4. Install Dependencies
```bash
docker exec -it license-service-app composer install --no-scripts
```

#### 5. Generate Application Key
```bash
docker exec -it license-service-app php artisan key:generate
```

#### 6. Run Migrations
```bash
docker exec -it license-service-app php artisan migrate
```

#### 7. Seed Database (Optional)
```bash
docker exec -it license-service-app php artisan db:seed
```

This creates 4 brands with products and API keys.

#### 8. Verify Installation
```bash
curl http://localhost:8000/health
```

Expected response:
```json
{
    "status": "healthy",
    "service": "License Service",
    "version": "v1.0.0",
    "timestamp": "2025-12-29T02:33:35+00:00",
    "checks": {
        "application": {
            "status": "healthy",
            "environment": "local",
            "debug": true
        },
        "database": {
            "status": "healthy",
            "connection": "pgsql"
        },
        "cache": {
            "status": "healthy",
            "driver": "redis"
        }
    }
}
```

---

## Configuration

### Environment Variables

Key configuration options in `.env`:
```env
# Application
APP_NAME="License Service"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_VERSION=v1.0.0

# Database
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=license_service
DB_USERNAME=license_user
DB_PASSWORD=secret

# Queue (for async audit logging)
QUEUE_CONNECTION=database

# Cache
CACHE_DRIVER=file

# CORS
CORS_ALLOWED_ORIGINS=*

# Rate Limiting - These values are for tests only and should be changed
RATE_LIMIT_API=120
RATE_LIMIT_BRAND_API=120
RATE_LIMIT_PUBLIC_API=120
RATE_LIMIT_ACTIVATION=120

# Monitoring (Optional)
SENTRY_LARAVEL_DSN=
SENTRY_TRACES_SAMPLE_RATE=0.1
```

---

## Database Setup

### Schema Overview

The service uses 6 main tables:

1. **brands** - Brand entities (WP Rocket, RankMath, etc.)
2. **products** - Products per brand
3. **license_keys** - Customer license keys
4. **licenses** - Product licenses (multiple per key)
5. **license_activations** - Active installations
6. **audit_logs** - Event audit trail

### Migrations

Run migrations:
```bash
# Docker
docker exec -it license-service-app php artisan migrate

# Local
php artisan migrate
```

Reset and re-migrate:
```bash
# Docker
docker exec -it license-service-app php artisan migrate:fresh --seed

# Local
php artisan migrate:fresh --seed
```

### Seeders

The `DatabaseSeeder` creates:

- **4 brands** with unique API keys
- **9 products** across brands
- Sample data for testing

View seeded data:
```bash
# Docker
docker exec -it license-service-app php artisan tinker
>>> App\Modules\Brand\Models\Brand::with('products')->get()

# Local
php artisan tinker
>>> App\Modules\Brand\Models\Brand::with('products')->get()
```

---

## Running the Application

### Docker
```bash
# Start services
docker-compose up -d

# View logs
docker-compose logs -f app

# Stop services
docker-compose down

# Rebuild (after code changes)
docker-compose up -d --build
```

### Accessing the Container
```bash
docker exec -it license-service-app bash
```
---

## Testing

### Running Tests
```bash
# Copy the .env.example file to .env.testing
cp .env.example .env.testing

# Change DB name to "license_service_test" and APP_ENV to testing
APP_ENV=testing
DB_DATABASE=license_service_test
```

```bash
# Create a new test database inside the postgresql container
docker exec -it license-service-postgres psql -U postgres -c "CREATE DATABASE license_service_test;"

# Run migrations and seed test data
php artisan migrate:fresh --env=testing
```

```bash
# Docker - All tests
docker exec -it license-service-app php artisan test

# Docker - Specific test
docker exec -it license-service-app php artisan test --filter=ProvisionLicense

# Local
php artisan test
```

### Test Suites
```bash
# Unit tests only
php artisan test tests/Unit/

# Feature tests only
php artisan test tests/Feature/
```

### Test Coverage

- **Total Tests:** 330+
- **Unit Tests:** 60+ (models, services, DTOs)
- **Feature Tests:** 240+ (API endpoints)
- **Integration Tests:** 30+ (complete workflows)
- **Performance Tests:** 4 (response time benchmarks)

### Static Analysis (PHPStan)
```bash
# Docker
docker exec -it license-service-app composer analyse

# Local
composer analyse
```

**Level:** 6 (strict type checking)

---

## API Documentation

### Base URL
```
http://localhost:8000/api/v1
```

### Authentication

**Brand-Authenticated Endpoints:**
```http
Authorization: Bearer {brand_api_key}
```

Get API keys from database after seeding:
```sql
SELECT name, api_key FROM brands;
```

**Public Endpoints:** No authentication required

### Response Format

All responses follow this structure:

**Success:**
```json
{
  "statusCode": 200,
  "success": true,
  "message": "Success message",
  "data": { ... }
}
```

**Error:**
```json
{
  "statusCode": 400,
  "success": false,
  "message": "Error message",
  "errors": { ... }
}
```

### Core Endpoints

#### 1. Provision License (US1)
```http
POST /api/v1/brands/licenses/provision
Authorization: Bearer {brand_api_key}
Content-Type: application/json

{
  "customer_email": "customer@example.com",
  "products": [
    {
      "product_slug": "{product_public_id}",
      "expires_at": "2026-12-31",
      "max_activations": 5
    }
  ]
}
```

**Response (201):**
```json
{
  "statusCode": 201,
  "success": true,
  "message": "License provisioned successfully",
  "data": {
    "license_key": "RANK-ABCD-EFGH-IJKL",
    "customer_email": "customer@example.com",
    "licenses": [...]
  }
}
```

#### 2. Activate License (US3)
```http
POST /api/v1/licenses/{license_key}/activate
Content-Type: application/json

{
  "instance_identifier": "https://example.com",
  "instance_type": "site",
  "product_slug": "{product_public_id}",
  "instance_meta": {
    "ip": "192.168.1.1",
    "user_agent": "WordPress/6.0"
  }
}
```

**Response (201):**
```json
{
  "statusCode": 201,
  "success": true,
  "message": "License activated successfully",
  "data": {
    "id": "{activation_public_id}",
    "instance_identifier": "https://example.com",
    "instance_type": "site",
    "activated_at": "2025-12-29T10:00:00Z",
    "is_active": true
  }
}
```

#### 3. Check License Status (US4)
```http
GET /api/v1/licenses/{license_key}/status
```

**Response (200):**
```json
{
  "statusCode": 200,
  "success": true,
  "message": "License status retrieved successfully",
  "data": {
    "license_key": "RANK-ABCD-EFGH-IJKL",
    "customer_email": "customer@example.com",
    "licenses": [
      {
        "id": "{license_public_id}",
        "product_id": "{product_public_id}",
        "status": "valid",
        "is_valid": true,
        "is_expired": false,
        "expires_at": "2026-12-31T23:59:59Z",
        "seats": {
          "used": 2,
          "available": 3,
          "total": 5
        },
        "activations": [...]
      }
    ]
  }
}
```

#### 4. List Customer Licenses (US6)
```http
GET /api/v1/brands/customers/{email}/licenses?per_page=20&page=1
Authorization: Bearer {brand_api_key}
```

**Response (200):**
```json
{
  "statusCode": 200,
  "success": true,
  "message": "Customer licenses retrieved successfully",
  "data": {
    "data": [...],
    "meta": {
      "current_page": 1,
      "per_page": 20,
      "total": 50,
      "last_page": 3
    }
  }
}
```

### Error Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden (e.g., seat limit exceeded) |
| 404 | Not Found |
| 422 | Validation Error |
| 429 | Rate Limit Exceeded |
| 500 | Internal Server Error |
| 503 | Service Unavailable |

---

## Postman Collection

A complete Postman collection is included in the `docs/` directory.

### Import Collection

1. Open Postman
2. Click **Import**
3. Select `docs/License-Service-API.postman_collection.json`
4. Select `docs/License-Service.postman_environment.json`

### Configure Environment

1. In Postman, select "License Service - Local" environment
2. Update variables:
   - `base_url`: `http://localhost:8000/api/v1`
   - `brand_api_key`: Get from database after seeding

### Run Collection

The collection includes:
- All user story endpoints (US1, US3, US4, US6)
- Error examples (401, 404, 422)
- Variable templates for easy testing

---

## Health Checks & Monitoring

### Health Check Endpoints

#### Basic Health Check
```bash
curl http://localhost:8000/health
```

Response:
```json
{
    "status": "healthy",
    "service": "License Service",
    "version": "v1.0.0",
    "timestamp": "2025-12-29T02:33:35+00:00",
    "checks": {
        "application": {
            "status": "healthy",
            "environment": "local",
            "debug": true
        },
        "database": {
            "status": "healthy",
            "connection": "pgsql"
        },
        "cache": {
            "status": "healthy",
            "driver": "redis"
        }
    }
}
```

### Metrics Endpoint
```bash
curl http://localhost:8000/metrics
```

Returns system metrics:
- Brand counts (total, active, inactive)
- Product counts
- License statistics (by status)
- Activation statistics (by type)

**Note:** Consider adding authentication for metrics in production.

---

## Project Structure
```
license-service/
├── app/
│   ├── Exceptions/
│   │   └── Handler.php                    # Global exception handler
│   ├── Http/
│   │   └── Controllers/
│   │       ├── HealthController.php       # Health checks
│   │       └── MetricsController.php      # System metrics
│   ├── Modules/
│   │   ├── AuditLog/
│   │   │   ├── Contracts/                 # Repository interfaces
│   │   │   ├── Listeners/                 # Event listeners
│   │   │   ├── Models/
│   │   │   │   └── AuditLog.php
│   │   │   └── Repositories/
│   │   ├── Brand/
│   │   │   ├── Contracts/
│   │   │   ├── Models/
│   │   │   │   ├── Brand.php
│   │   │   │   └── Product.php
│   │   │   ├── Repositories/
│   │   │   └── Services/
│   │   │       └── BrandLookupService.php
│   │   ├── License/
│   │   │   ├── Contracts/
│   │   │   ├── Controllers/
│   │   │   │   ├── CustomerLicenseController.php
│   │   │   │   ├── LicenseActivationController.php
│   │   │   │   ├── LicenseProvisioningController.php
│   │   │   │   └── LicenseStatusController.php
│   │   │   ├── DTOs/
│   │   │   │   ├── ActivateLicenseDTO.php
│   │   │   │   └── ProvisionLicenseDTO.php
│   │   │   ├── Enums/
│   │   │   │   ├── InstanceType.php
│   │   │   │   └── LicenseStatus.php
│   │   │   ├── Models/
│   │   │   │   ├── License.php
│   │   │   │   ├── LicenseActivation.php
│   │   │   │   └── LicenseKey.php
│   │   │   ├── Repositories/
│   │   │   ├── Requests/
│   │   │   │   ├── ActivateLicenseRequest.php
│   │   │   │   └── ProvisionLicenseRequest.php
│   │   │   ├── Resources/
│   │   │   │   ├── LicenseActivationResource.php
│   │   │   │   ├── LicenseKeyResource.php
│   │   │   │   └── LicenseStatusResource.php
│   │   │   ├── Routes/
│   │   │   │   └── api.php
│   │   │   └── Services/
│   │   │       ├── ActivateLicenseService.php
│   │   │       ├── BrandService.php
│   │   │       ├── LicenseKeyGenerator.php
│   │   │       ├── LicenseValidationService.php
│   │   │       ├── ProvisionLicenseService.php
│   │   │       └── SeatManagementService.php
│   │   └── Shared/
│   │       ├── Events/
│   │       │   ├── LicenseActivated.php
│   │       │   └── LicenseProvisioned.php
│   │       ├── Middleware/
│   │       │   ├── AddApiVersion.php
│   │       │   ├── AuthenticateBrand.php
│   │       │   └── ValidateLicenseKey.php
│   │       └── Support/
│   │           ├── Exceptions/
│   │           │   ├── LicenseException.php
│   │           │   ├── LicenseExpiredException.php
│   │           │   ├── LicenseInvalidException.php
│   │           │   ├── LicenseNotFoundException.php
│   │           │   └── SeatLimitExceededException.php
│   │           └── Helpers/
│   │               ├── ApiResponse.php
│   │               └── StructuredLog.php
├── config/                                # Configuration files
├── database/
│   ├── factories/                         # Model factories
│   ├── migrations/                        # Database migrations
│   └── seeders/                           # Database seeders
├── docs/
│   ├── CODE_QUALITY.md                    # Code quality standards
│   └── MONITORING.md                      # Monitoring guide
├── postman/
│   ├── License-Service-API.postman_collection.json
│   └── License-Service.postman_environment.json
├── tests/
│   ├── Feature/                           # Feature tests
│   │   ├── ErrorHandling/
│   │   ├── Integration/
│   │   ├── License/
│   │   ├── Observability/
│   │   └── Performance/
│   └── Unit/                              # Unit tests
├── docker-compose.yml                     # Docker configuration
├── Explanation.md                         # Technical explanation (THIS FILE IS CRITICAL)
├── phpstan.neon                           # PHPStan configuration
└── README.md                              # This file
```

---

## User Stories

### ✅ Implemented

#### US1: Provision License
**As a brand system**, I can create license keys and licenses for a customer email and associate them together to grant access to one or more products.

**Endpoint:** `POST /api/v1/brands/licenses/provision`

**Tests:** 15 (12 feature + 3 integration)

---

#### US3: Activate License
**As an end-user product**, I can activate a license for a specific instance (e.g., site URL, host, machine ID), potentially consuming a seat if applicable.

**Endpoint:** `POST /api/v1/licenses/{key}/activate`

**Tests:** 22 (17 feature + 5 integration)

---

#### US4: Check License Status
**As an end-user product or customer**, I can check the status and entitlements of a license key to know if it's valid, what it provides access to, and if applicable, the number of remaining seats.

**Endpoint:** `GET /api/v1/licenses/{key}/status`

**Tests:** 26 (18 feature + 6 integration + 2 unit)

---

#### US6: List Customer Licenses
**As a brand**, I can list all licenses associated with a given email across the entire ecosystem. End users & external parties should not have access to this list.

**Endpoint:** `GET /api/v1/brands/customers/{email}/licenses`

**Tests:** 20 (16 feature + 4 integration)

---

### 📋 Designed but Not Implemented

#### US2: License Lifecycle Management
**Status:** Designed (see Explanation.md)

Endpoints designed:
- `POST /api/v1/brands/licenses/{key}/suspend`
- `POST /api/v1/brands/licenses/{key}/resume`
- `DELETE /api/v1/brands/licenses/{key}/cancel`
- `POST /api/v1/brands/licenses/{key}/renew`

**Reason:** Time constraint (focused on minimum requirements)

---

#### US5: Deactivate License
**Status:** Designed (see Explanation.md)

Endpoint designed:
- `DELETE /api/v1/licenses/{key}/activations/{activationId}`

**Reason:** Time constraint (focused on activation first)

---

## Development

### Code Standards

- **PSR-12:** PHP coding style
- **PHPStan Level 6:** Strict type checking
- **Repository Pattern:** All database access
- **Service Layer:** Business logic separation
- **DTOs:** Data transfer objects
- **Events:** Async audit logging

### Git Workflow
```bash
# Create feature branch
git checkout -b feature/my-feature

# Make changes and commit
git add .
git commit -m "feat: add new feature"

# Push and create PR
git push origin feature/my-feature
```

**Branch Strategy:**
- `trunk` - Production
- `develop` - Main development
- `feature/*` - Feature branches

### Running Locally for Development
```bash
# Start containers
docker-compose up -d

# Access container
docker exec -it license-service-app bash

# Inside container
php artisan serve

# Watch tests
php artisan test --filter=MyTest

# Run PHPStan
composer analyse

# Run php-cs-fixer
composer lint # Dry run

composer fix
```

---

## Troubleshooting

### Database Connection Failed

**Error:** `SQLSTATE[08006] [7] could not connect to server`

**Solution:**

1. Check PostgreSQL is running:
```bash
   docker-compose ps postgres
```

2. Check database credentials in `.env`:
```env
   DB_HOST=postgres
   DB_PORT=5432
   DB_DATABASE=license_service
   DB_USERNAME=license_user
   DB_PASSWORD=secret
```

3. Restart containers:
```bash
   docker-compose down
   docker-compose up -d
```

### Tests Failing

**Error:** Tests failing with database errors

**Solution:**

1. Refresh test database:
```bash
   docker exec -it license-service-app php artisan migrate:fresh --env=testing
```

2. Clear cache:
```bash
   docker exec -it license-service-app php artisan config:clear
   docker exec -it license-service-app php artisan cache:clear
```

### Rate Limit Exceeded

**Error:** `429 Too Many Requests`

**Solution:**

1. Check rate limit settings in `.env`
2. Wait for rate limit window to reset (1 minute)
3. Use different IP or brand authentication

### Port Already in Use

**Error:** `Bind for 0.0.0.0:8000 failed: port is already allocated`

**Solution:**

1. Change port in `docker-compose.yml`:
```yaml
   ports:
     - "8001:80"  # Change 8000 to 8001
```

2. Or stop conflicting service:
```bash
   # Find process using port 8000
   lsof -i :8000
   
   # Kill process
   kill -9 <PID>
```

---

## Documentation

Comprehensive documentation available:

- **[Explanation.md](./Explanation.md)** - Technical architecture and design decisions (CRITICAL)
- **[CODE_QUALITY.md](./docs/CODE_QUALITY.md)** - Code standards and testing
- **[MONITORING.md](./docs/MONITORING.md)** - Health checks and observability
- **[Postman Collection](./docs/)** - API testing collection

---

## Performance Benchmarks

| Endpoint | Target | Actual |
|----------|--------|--------|
| Provision License | < 500ms | ~200ms ✅ |
| Activate License | < 300ms | ~150ms ✅ |
| Check Status | < 200ms | ~100ms ✅ |
| List Licenses | < 400ms | ~250ms ✅ |

---

## Support

For issues or questions:

1. Check [Troubleshooting](#troubleshooting)
2. Review [Explanation.md](./Explanation.md)
3. Search existing issues on GitHub
4. Create new issue with:
   - Environment details
   - Steps to reproduce
   - Expected vs actual behavior
   - Relevant logs

---

## License

This project is proprietary software developed for group.one assessment.

---

## Acknowledgments

- **Laravel Framework** - Elegant PHP framework
- **Pest PHP** - Elegant testing framework
- **PHPStan** - Static analysis tool
- **Docker** - Containerization platform

---

**Version:** 1.0.0  
**Last Updated:** December 29, 2025  
**Status:** Production-Ready ✅