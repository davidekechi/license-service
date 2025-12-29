# Monitoring & Observability

## Health Checks - DESIGNED & IMPLEMENTED


```bash
GET /health
```

Response:
```json
{
  "status": "healthy",
  "service": "License Service",
  "version": "v1.0.0",
  "timestamp": "2025-12-27T10:00:00Z",
  "checks": {
    "application": {
      "status": "healthy",
      "environment": "production",
      "debug": false
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

## Metrics - DESIGNED & IMPLEMENTED

### System Metrics
```bash
GET /metrics
```

Response:
```json
{
  "status": "success",
  "data": {
    "brands": {
      "total": 4,
      "active": 4,
      "inactive": 0
    },
    "products": {
      "total": 9,
      "active": 9,
      "inactive": 0
    },
    "licenses": {
      "license_keys": {
        "total": 150,
        "with_licenses": 150
      },
      "licenses": {
        "total": 200,
        "valid": 180,
        "suspended": 5,
        "cancelled": 10,
        "expired": 5
      }
    },
    "activations": {
      "total": 500,
      "active": 450,
      "deactivated": 50,
      "by_type": {
        "site": 400,
        "device": 40,
        "server": 10
      }
    }
  }
}
```

## Structured Logging - DESIGNED

All significant events to be logged with structured data:

### License Provisioning
```json
{
  "event": "license_provisioned",
  "license_key": "RANK-ABCD-EFGH-IJKL",
  "brand_id": "01JGXXX...",
  "customer_email": "customer@example.com",
  "products_count": 2,
  "timestamp": "2025-12-27T10:00:00Z"
}
```

### License Activation
```json
{
  "event": "license_activated",
  "license_key": "RANK-ABCD-EFGH-IJKL",
  "instance_identifier": "https://example.com",
  "instance_type": "site",
  "timestamp": "2025-12-27T10:00:00Z"
}
```

### Authentication Attempts
```json
{
  "event": "authentication_attempt",
  "success": false,
  "brand_id": null,
  "ip": "192.168.1.1",
  "timestamp": "2025-12-27T10:00:00Z"
}
```

### Error Tracking (Sentry) - DESIGNED

Configure Sentry for production error tracking:

1. Set `SENTRY_LARAVEL_DSN` in `.env`
2. Set `SENTRY_TRACES_SAMPLE_RATE` for performance monitoring
3. Errors are automatically captured and reported

## Recommended Monitoring Setup

### Application Monitoring
- Health checks every 30 seconds
- Alert if `/health` returns 503
- Track response times for all endpoints

### Database Monitoring
- Monitor connection pool usage
- Track slow queries (>1s)
- Monitor table sizes

### License Metrics
- Track license provisioning rate
- Monitor activation success rate
- Alert on high error rates

### Security
- Monitor authentication failures
- Track rate limit violations
- Alert on suspicious patterns