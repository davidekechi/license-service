# License Service

A centralized license management system for managing software licenses across multiple brands.

## Architecture

This project follows a modular monolith architecture with the following modules:

- **Brand** - Manages brands and products
- **License** - Manages license keys, licenses, and activations
- **AuditLog** - Handles audit trail and compliance logging
- **Shared** - Common utilities, traits, events, and helpers

## Requirements

- PHP 8.2+
- PostgreSQL 15
- Composer

## Installation
Fork and clone the repo

```bash
cd license-service

# Copy environment file
cp .env.example .env

# Build and start up docker composer
docker compose up -d --build

# Check that containers are up and running
docker ps

# Bash into application container
docker exec -it license-service-app bash

# Install dependencies
composer install --no-scripts

# Generate application key
php artisan key:generate

# Configure database in .env
# Then run migrations and seed test data
php artisan migrate --seed

# Check system health
http://localhost:8000/health # See /docs/MONITORING.md for more observability information

# Import postman collections in /docs using the POSTMAN_SETUP.md as guide


## Database Schema

### Brands Module
- `brands` - Brand information and API keys
- `products` - Products per brand

### License Module
- `license_keys` - Customer-facing license keys
- `licenses` - Product entitlements per license key
- `license_activations` - Product activations (seat management)

### AuditLog Module
- `audit_logs` - Audit trail for all operations

## Module Communication

- Same module: Direct relationships using `id`
- Cross-module: Relationships using `public_id` (ULID)
- Cross-module access: Via Service layer with interfaces
```

**Code quality and tests in /docs/CODE_QUALITY.md**
