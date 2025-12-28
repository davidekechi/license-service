# Code Quality

## Static Analysis

### PHPStan Level 6
The project maintains PHPStan level 6 compliance for strict type checking.
```bash
composer analyse
```

The project also uses php-cs-fixer to ensure that all code are PSR compliant.
```bash
# Check non-compliant codes
composer lint

# Fix non-compliant codes
composer fix
```

**Configuration:** `phpstan.neon`

## Testing

### Test Coverage
- **Total Tests:** 220+
- **Unit Tests:** 60+
- **Feature Tests:** 140+
- **Integration Tests:** 20+

### Running Tests
Setup a testing environment file

```bash
# Copy the .env.example file to .env.testing
cp .env.example .env.testing

# Change DB name to "license_service_test" and APP_ENV to testing
APP_ENV=testing
DB_DATABASE=license_service_test
```

Create a testing database and run migrations

```bash
# Create a new database inside the postgresql container
docker exec -it license-service-postgres psql -U postgres -c "CREATE DATABASE license_service_test;"

# Run migrations and seed test data
php artisan migrate:fresh --seed --env=testing
```

Run tests

```bash
# All tests
php artisan test

# Specific test suite
php artisan test --filter=ProvisionLicense
```

## Code Standards

### Architecture Principles
1. **Repository Pattern:** All database access through repositories
2. **Service Layer:** Business logic in services
3. **DTOs:** Data transfer objects for all operations
4. **Events:** Async processing for audit logs
5. **Middleware:** Authentication and validation

### Naming Conventions
- **Controllers:** `{Action}{Resource}Controller` (e.g., `LicenseProvisioningController`)
- **Services:** `{Action}{Resource}Service` (e.g., `ActivateLicenseService`)
- **DTOs:** `{Action}{Resource}DTO` (e.g., `ProvisionLicenseDTO`)
- **Events:** `{Resource}{PastTense}` (e.g., `LicenseActivated`)
- **Tests:** `{feature}_test_description` (Pest PHP)

### Type Safety
- All methods have return type hints
- All parameters have type hints
- Properties have declared types
- No mixed types without justification

## Continuous Integration

Tests run automatically on:
- Pull requests to `develop`
- Commits to `develop` and `trunk`

CI checks:
- ✅ PHPStan analysis
- ✅ All tests passing
- ✅ No security vulnerabilities