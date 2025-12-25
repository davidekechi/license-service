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
```bash
# Clone repository
git clone https://github.com/davidekechi/license-service.git
cd license-service

# Copy environment file
cp .env.example .env

# Build and start up docker composer
docker compose up -d --build

# Bash into application container
docker exec -it license-service-app bash

# Install dependencies
composer install --no-scripts

# Generate application key
php artisan key:generate

# Configure database in .env
# Then run migrations
php artisan migrate

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

## Development

Currently on **Day 1, Hour 1** - Database schema complete.

## License

Proprietary