# License Service - Technical Explanation

## Table of Contents
1. [Problem Statement](#problem-statement)
2. [Multi-Tenancy Strategy](#multi-tenancy-strategy)
3. [Integration Points](#integration-points)
4. [Architecture Overview](#architecture-overview)
5. [Data Model](#data-model)
6. [Trade-offs & Design Decisions](#trade-offs--design-decisions)
7. [API Design](#api-design)
8. [Scaling Strategy](#scaling-strategy)
9. [Extensibility & Evolution](#extensibility--evolution)
10. [User Story Implementation Status](#user-story-implementation-status)
11. [Known Limitations](#known-limitations)

---

## Problem Statement

### Business Context
The ecosystem consists of multiple brands (WP Rocket, RankMath, Imagify, BackWPup) that each sell WordPress plugins and SaaS products. Currently, each brand manages its own licensing system independently, leading to:

- **Duplication:** Each brand reimplements the same licensing logic
- **Inconsistency:** Different validation rules and behaviors across brands
- **Poor Customer Experience:** Customers with products from multiple brands must manage separate license keys
- **Limited Visibility:** No cross-brand customer insights
- **Maintenance Burden:** Bug fixes and features must be implemented multiple times across each brand licensing system

### Solution Requirements
Build a **centralized License Service** that:

1. **Provides brand-integrable APIs** for license provisioning and management
2. **Provides product-facing APIs** for license validation and activation
3. **Supports multi-tenancy** with brand isolation
4. **Enables cross-brand customer tracking** for support and analytics
5. **Scales horizontally** to handle growing license volumes
6. **Is observable and operable** in production

### Key Constraints
- **No breaking changes** to existing brand systems during migration
- **High availability** required (licensing is critical path for product functionality)
- **Performance** must support real-time validation (< 200ms response times)
- **Security** must prevent license key enumeration and abuse
- **Data privacy** must isolate brand data where appropriate

---

## Architecture Overview

### Multi-Tenancy Strategy

#### Tenant Model: Brand as Tenant
```
Tenant = Brand (WP Rocket, RankMath, etc.)
```

**Isolation Level:** Logical (shared database, brand_id filtering)

**Why Not Physical Isolation (separate databases)?**

- **Cross-Brand Queries:** Customer license lookup requires scanning all brands
- **Operational Simplicity:** Single database, single schema, simple migrations
- **Cost:** No need to provision databases per brand
- **Scale:** Brands are countable (4-20), not infinite

#### Data Isolation Strategy
```sql
-- All brand-owned entities have brand_id
license_keys.brand_id = brands.public_id

-- Queries always filter by brand
SELECT * FROM license_keys WHERE brand_id = ?
```

**Enforcement:**

1. **API Level:** Middleware attaches `authenticated_brand` to request
2. **Service Level:** Services filter by brand
3. **Repository Level:** Queries include brand_id in WHERE clause

**Exception: Cross-Brand Visibility**
```
Customer License Lookup (US6): Intentionally shows all brands
```

**Rationale:**

- Customer support needs full license history
- Brands collaborate (same parent company)
- Customer experience (see all purchases in one view)

#### License Key Generation Per Brand
```
WP Rocket:  WPRO-XXXX-XXXX-XXXX
RankMath:   RANK-XXXX-XXXX-XXXX
Imagify:    IMAG-XXXX-XXXX-XXXX
```

**Collision Avoidance:**

- Brand-specific prefix (4 characters from brand slug)
- Random alphanumeric (12 characters)
- **Probability of collision:** ~1 in 10^21 per brand

**Alternative Considered:** Global UUID

**Why brand prefix?**

- **Customer Recognition:** "That's my RankMath key"
- **Support:** Quickly identify brand from key
- **Partitioning:** Keys naturally partition by prefix

---

### Integration Points

#### 1. Brand E-commerce Systems

**Integration Type:** HTTP API (Brand-initiated)

**Flow:**
```
1. Customer purchases product in WP Rocket shop
2. Shop backend calls POST /api/v1/brands/licenses/provision
3. License Service returns license key
4. Shop sends key to customer via email
```

**Authentication:** Brand API key (static, rotatable)

**Error Handling:** Shop must handle failures (retry logic, manual provision)

#### 2. End-User Products (WordPress Plugins)

**Integration Type:** HTTP API (Product-initiated)

**Activation Flow:**
```
1. User enters license key in plugin settings
2. Plugin calls POST /api/v1/licenses/{key}/activate
3. Service validates and creates activation
4. Plugin stores activation locally
```

**Heartbeat Flow:**
```
1. Plugin calls GET /api/v1/licenses/{key}/status (daily)
2. Service updates last_checked_at
3. Plugin validates status is still valid
```

**Offline Handling:**

- Plugins cache license status locally
- Grace period (7 days) before disabling features

#### 3. Future Integrations (Designed but Not Implemented)

**Webhooks (Designed):**
```
POST https://brand-webhook-url.com/license-events
{
  "event": "license.activated",
  "license_key": "RANK-...",
  "timestamp": "..."
}
```

**Analytics Platform (Designed):**
```
Metrics endpoint feeds data to Grafana/Datadog
```

---

### High-Level Architecture
```
┌─────────────────────────────────────────────────────────────────┐
│                         BRAND SYSTEMS                            │
│                    (E-commerce Websites)                         │
├──────────────┬──────────────┬──────────────┬───────────────────┤
│  RankMath.com│ WP-Rocket.me │  Imagify.com │  BackWPup.com     │
│              │              │              │                    │
│  [Payments]  │  [Payments]  │  [Payments]  │   [Payments]      │
└──────┬───────┴──────┬───────┴──────┬───────┴──────┬────────────┘
       │              │              │              │
       │ Brand API    │ Brand API    │ Brand API    │ Brand API
       │ (API Key)    │ (API Key)    │ (API Key)    │ (API Key)
       │              │              │              │
       ▼              ▼              ▼              ▼
┌─────────────────────────────────────────────────────────────────┐
│                                                                  │
│                    LICENSE SERVICE (API)                         │
│                   ┌──────────────────────┐                      │
│                   │   API Gateway        │                      │
│                   │  - Authentication    │                      │
│                   │  - Rate Limiting     │                      │
│                   │  - Logging           │                      │
│                   └──────────┬───────────┘                      │
│                              │                                   │
│         ┌────────────────────┼────────────────────┐             │
│         │                    │                    │             │
│    ┌────▼─────┐      ┌──────▼──────┐     ┌──────▼──────┐      │
│    │  Brand   │      │   Product   │     │    Audit    │      │
│    │   API    │      │     API     │     │   Logging   │      │
│    │ Endpoint │      │  Endpoints  │     │             │      │
│    └────┬─────┘      └──────┬──────┘     └─────────────┘      │
│         │                   │                                   │
│    ┌────▼───────────────────▼─────┐                            │
│    │     APPLICATION LAYER         │                            │
│    │  ┌─────────────────────────┐ │                            │
│    │  │     Use Cases           │ │                            │
│    │  │ - ProvisionLicense      │ │                            │
│    │  │ - ActivateLicense       │ │                            │
│    │  │ - CheckLicenseStatus    │ │                            │
│    │  │ - ListCustomerLicenses  │ │                            │
│    │  └─────────────────────────┘ │                            │
│    └────┬───────────────────┬─────┘                            │
│         │                   │                                   │
│    ┌────▼───────────────────▼─────┐                            │
│    │      DOMAIN LAYER             │                            │
│    │  ┌────────┐  ┌──────────┐   │                            │
│    │  │License │  │Activation│   │                            │
│    │  │Service │  │ Manager  │   │                            │
│    │  └────────┘  └──────────┘   │                            │
│    │  ┌────────┐  ┌──────────┐   │                            │
│    │  │  Seat  │  │   Key    │   │                            │
│    │  │Manager │  │Generator │   │                            │
│    │  └────────┘  └──────────┘   │                            │
│    └────┬───────────────────┬─────┘                            │
│         │                   │                                   │
│    ┌────▼───────────────────▼─────┐                            │
│    │   INFRASTRUCTURE LAYER        │                            │
│    │  ┌─────────────────────────┐ │                            │
│    │  │     Repositories        │ │                            │
│    │  │ - LicenseRepository     │ │                            │
│    │  │ - ActivationRepository  │ │                            │
│    │  │ - BrandRepository       │ │                            │
│    │  └─────────────────────────┘ │                            │
│    └────┬─────────────────────────┘                            │
│         │                                                       │
│    ┌────▼─────────────────────────┐                            │
│    │       DATABASE               │                            │
│    │  ┌─────┐  ┌────────┐        │                            │
│    │  │MySQL│  │  Redis │        │                            │
│    │  │     │  │ (Cache)│        │                            │
│    │  └─────┘  └────────┘        │                            │
│    └──────────────────────────────┘                            │
└─────────────────────────────────────────────────────────────────┘
       ▲              ▲              ▲              ▲
       │              │              │              │
       │ Product API  │ Product API  │ Product API  │ Product API
       │ (License Key)│ (License Key)│ (License Key)│ (License Key)
       │              │              │              │
┌──────┴──────┬───────┴──────┬───────┴──────┬───────┴────────────┐
│   RankMath  │   WP Rocket  │    Imagify   │    BackWPup        │
│    Plugin   │    Plugin    │    Plugin    │     Plugin         │
│             │              │              │                     │
│ example.com │ mysite.com   │ blog.com     │  shop.com          │
└─────────────┴──────────────┴──────────────┴────────────────────┘
           END-USER SITES (WordPress Installations)
```

### Data Model

#### Entity-Relationship Diagram
```
┌────────────────────────────┐         ┌────────────────────────────┐
│        brands              │         │        products            │
│                            │         │                            │
│ id (PK, SERIAL)            │         │ id (PK, SERIAL)            │
│ public_id (ULID, UNIQUE)   │◄────────│ brand_id (FK → brands.id)  │
│ name (VARCHAR)             │  1    * │ public_id (ULID, UNIQUE)   │
│ slug (VARCHAR, UNIQUE)     │         │ name (VARCHAR)             │
│ api_key (VARCHAR, UNIQUE)  │         │ slug (VARCHAR)             │
│ is_active (BOOLEAN)        │         │ max_seats (INTEGER)        │
│ created_at (TIMESTAMP)     │         │ is_active (BOOLEAN)        │
│ updated_at (TIMESTAMP)     │         │ created_at (TIMESTAMP)     │
│ deleted_at (TIMESTAMP)     │         │ updated_at (TIMESTAMP)     │
│                            │         │ deleted_at (TIMESTAMP)     │
└────────────────────────────┘         └─────────────┬──────────────┘
       │                                             │
       │ 1                                           │
       │                                             │ *
       │                                      ┌──────▼─────────────────────┐
       │                                      │      licenses              │
       │                                      │                            │
       │                                   ┌──│ id (PK, SERIAL)            │
       │                                   │  │ public_id (ULID, UNIQUE)   │
┌──────▼─────────────────────┐             │  │ license_key_id (FK)        │
│    license_keys            │             │  │ product_id (ULID FK)       │
│                            │  1        * │  │ status (ENUM)              │
│ id (PK, SERIAL)            │─────────────┘  │ expires_at (TIMESTAMP)     │
│ public_id (ULID, UNIQUE)   │                │ max_activations (INTEGER)  │
│ key (VARCHAR, UNIQUE)      │                │ created_at (TIMESTAMP)     │
│ brand_id (ULID FK)         │                │ updated_at (TIMESTAMP)     │
│ customer_email (VARCHAR)   │                │ deleted_at (TIMESTAMP)     │
│ created_at (TIMESTAMP)     │                │                            │
│ updated_at (TIMESTAMP)     │                └──────────┬─────────────────┘
│ deleted_at (TIMESTAMP)     │                           │
│                            │                           │ 1
└────────────────────────────┘                           │
                                                         │
                                                         │ *
                                          ┌──────────────▼───────────────────────┐
                                          │    license_activations               │
                                          │                                      │
                                          │ id (PK, SERIAL)                      │
                                          │ public_id (ULID, UNIQUE)             │
                                          │ license_id (FK → licenses.id)        │
                                          │ instance_identifier (VARCHAR)        │
                                          │ instance_type (ENUM)                 │
                                          │ instance_meta (JSON)                 │
                                          │ activated_at (TIMESTAMP)             │
                                          │ last_checked_at (TIMESTAMP)          │
                                          │ deactivated_at (TIMESTAMP)           │
                                          │ created_at (TIMESTAMP)               │
                                          │ updated_at (TIMESTAMP)               │
                                          │ deleted_at (TIMESTAMP)               │
                                          │                                      │
                                          └──────────────────────────────────────┘

┌──────────────────────────────────────┐
│         audit_logs                   │  (Polymorphic - References all entities)
│                                      │
│ id (PK, SERIAL)                      │
│ public_id (ULID, UNIQUE)             │
│ auditable_type (VARCHAR)             │
│ auditable_id (ULID)                  │
│ event (VARCHAR)                      │
│ actor_type (VARCHAR)                 │
│ actor_identifier (VARCHAR)           │
│ metadata (JSON)                      │
│ ip_address (VARCHAR)                 │
│ created_at (TIMESTAMP)               │
│                                      │
└──────────────────────────────────────┘
```

**Foreign Key Relationships:**

**Same-Module (INTEGER FK):**
- `products.brand_id` → `brands.id`
- `licenses.license_key_id` → `license_keys.id`
- `license_activations.license_id` → `licenses.id`

**Cross-Module (ULID FK):**
- `license_keys.brand_id` → `brands.public_id`
- `licenses.product_id` → `products.public_id`

**Polymorphic (ULID):**
- `audit_logs.auditable_id` → Any `{table}.public_id`

**Indexes:**
- All foreign keys are indexed
- `brands`: slug, api_key, is_active
- `products`: brand_id, slug, is_active, UNIQUE(brand_id, slug)
- `license_keys`: key, brand_id, customer_email, (brand_id, customer_email)
- `licenses`: license_key_id, product_id, status, expires_at
- `license_activations`: license_id, instance_identifier, deactivated_at, (license_id, deactivated_at)
- `audit_logs`: (auditable_type, auditable_id), event, actor_type, created_at

---

### Observability & Testing

#### Observability
- Basic health check at `/health` (app, database, cache)
- Metrics at `/metrics` (brands, products, licenses, activations)
- Metrics cached for 1 minute
- Returns 503 if unhealthy

#### Monitoring
- Structured logging for all events
- Health check monitoring
- Error tracking

**More designs and implementation information in /docs/MONITORING.md:**

---

## Trade-offs and Key Design Decisions

### 1. Modular Monolith Structure

The service is architected as a **modular monolith** with strict module boundaries:
```
app/Modules/
├── Brand/          # Brand and product management
├── License/        # License key and activation logic
├── AuditLog/       # Event tracking and compliance
└── Shared/         # Cross-cutting concerns
```

**Why Modular Monolith over Tranditional Monolith?**

1. **Concern Seperations:** Clear, well-defined module boundaries
2. **Isolation:** Modules can be tested in isolation with minimal dependencies
3. **Extensibility:** New features and modules can be added, managed, tested and deployed independently
4. **Development:** Teams can work independently with fewer merge conflicts

```
Current (Modular Monolith):
┌─────────────────────────────┐
│  Single Application         │
│  ┌─────────┐  ┌──────────┐ │
│  │ Brand   │  │ License  │ │
│  │ Module  │  │ Module   │ │
│  └─────────┘  └──────────┘ │
└─────────────────────────────┘
```

**Why Modular Monolith over Microservices?**

1. **Simplicity:** Single deployment unit, simpler operations
2. **Performance:** No network latency between modules
3. **Transactions:** ACID guarantees across modules
4. **Development Speed:** Faster iteration, easier refactoring
5. **Future-proof:** Can extract modules to microservices if needed

```
Future (Microservices):
┌─────────────┐    ┌──────────────┐
│   Brand     │    │   License    │
│   Service   │◄───│   Service    │
│             │HTTP│              │
└─────────────┘    └──────────────┘
```

Only changes needed:
1. Replace in-process calls with HTTP calls
2. Deploy modules as separate services
3. Handle distributed transactions (if needed)

**Module Communication Rules:**

- **Within Module:** Direct method calls, Eloquent relationships
- **Cross-Module:** Service interfaces only (no direct model access)
- **Example:** License module accesses Brand data via `BrandLookupService` interface

---

### 2. Synchronous vs Asynchronous Audit Logging

**Decision:** Synchronous (Events without Queue)

**Trade-offs:**

- **Consistency** — Synchronous: ✅ Immediate · Asynchronous: ⚠️ Eventual  
- **Performance** — Synchronous: ❌ Slightly slower requests · Asynchronous: ✅ Faster requests  
- **Reliability** — Synchronous: ⚠️ Fails with request · Asynchronous: ✅ Retryable  
- **Complexity** — Synchronous: ✅ Simple · Asynchronous: ❌ Queue required

**Why Synchronous:**

- Small-scale project with low request volume  
- No production deployment or background worker infrastructure  
- Audit logging overhead is minimal and predictable  
- Immediate consistency is preferred for debugging and visibility  
- Events used for decoupling intent, not deferred execution  
- Avoids queue setup, monitoring, and failure handling 

**Risk Mitigation:**

- Audit writes are lightweight and fast  
- Failures surface immediately during development  
- Can be migrated to queued async processing later if scale increases

---

### 3. Integer IDs vs UUIDs Only

**Decision:** Dual ID Strategy (id + public_id)

**Why Not UUIDs Only?**

- **Join Performance** — UUID Only: ❌ Slower (36 chars) · Integer + ULID: ✅ Faster (4 bytes)  
- **Index Size** — UUID Only: ❌ Larger · Integer + ULID: ✅ Smaller  
- **Readability** — UUID Only: ❌ Hard to read · Integer + ULID: ✅ Easier  
- **Distribution** — UUID Only: ✅ Better · Integer + ULID: ⚠️ Sequential

**Why Dual IDs:**

- Performance: Internal joins use integers
- API exposure: Public IDs use ULIDs (sortable, unguessable)
- Best of both worlds

**Every table has TWO identifiers:**
```sql
-- Example: licenses table
id               SERIAL PRIMARY KEY       -- Same-module relationships
public_id        CHAR(26) NOT NULL UNIQUE -- Cross-module relationships
```

**Rationale:**

- **Same Module:** Use `id` (integer) for foreign keys within module
  - `license_activations.license_id → licenses.id`
  - Faster joins, smaller indexes
  
- **Cross Module:** Use `public_id` (ULID) for foreign keys across modules
  - `licenses.product_id → products.public_id`
  - Module independence, easier extraction to microservices

**Example:**
```sql
-- Same module (License → LicenseActivation)
license_activations.license_id = integer FK to licenses.id

-- Cross module (License → Product in Brand module)
licenses.product_id = ULID FK to products.public_id
```

---

### 4. Seat Count Storage

**Decision:** Calculated on-the-fly (not stored)

**Alternative:** Store `seats_used` column

**Trade-offs:**

- **Consistency** — Calculated: ✅ Always accurate · Stored Column: ❌ Can drift  
- **Performance** — Calculated: ⚠️ Query per check · Stored Column: ✅ Direct read  
- **Complexity** — Calculated: ✅ Simple · Stored Column: ❌ Update logic

**Why Calculated:**

- Accuracy > Performance for seat checks
- Seat checks are infrequent (activation time only)
- Eliminates sync issues

---

## API Design

### API Philosophy

1. **RESTful:** Resource-based URLs, HTTP verbs convey intent
2. **Consistent Responses:** All endpoints return same JSON structure
3. **Explicit Authentication:** Bearer token in header (no hidden auth)
4. **Rate Limited:** Prevent abuse, ensure fair usage
5. **Versioned:** `/api/v1/...` for future compatibility

### Response Format

All responses follow this structure:
```json
{
  "statusCode": 200,
  "success": true,
  "message": "Success message",
  "data": { ... }  // or null for errors
}
```

**Error responses:**
```json
{
  "statusCode": 422,
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

### Endpoint Design Decisions

#### 1. Brand Authentication vs Public Endpoints

**Brand-Authenticated (API Key Required):**
```
POST   /api/v1/brands/licenses/provision
GET    /api/v1/brands/customers/{email}/licenses
```

**Public (No Authentication):**
```
POST   /api/v1/licenses/{key}/activate
GET    /api/v1/licenses/{key}/status
```

**Rationale:**

- **Provision/Lookup:** Sensitive operations, only brands should access
- **Activate/Status:** End-user products need simple, unauthenticated access
- **Security:** License key itself acts as authentication for public endpoints

#### 2. Email in URL Path
```
GET /api/v1/brands/customers/{email}/licenses
```

**Why email in path instead of query param?**

- **RESTful Design:** Email identifies the resource (customer)
- **Explicit Intent:** Clear what resource is being accessed
- **URL Encoding Handled:** Middleware decodes `%40` → `@`

#### 3. Pagination Strategy

**In-Memory Pagination (Current):**
```php
$licenseKeys = $this->repository->getByCustomerEmail($email);
$paginated = $licenseKeys->slice($offset, $perPage);
```

**Rationale:**

- **Customer-Level:** Most customers have < 10 license keys
- **Simplicity:** No complex cursor/offset logic needed
- **Performance:** Acceptable for current scale

**Future Optimization:**

- Database-level pagination when customer counts exceed 1000s

#### 4. License Key in URL
```
POST /api/v1/licenses/{licenseKey}/activate
GET  /api/v1/licenses/{licenseKey}/status
```

**Alternative Considered:** License key in request body

**Why URL?**

- **REST Convention:** Resource identifier belongs in URL
- **Caching:** URL-based caching easier (CDN, browser)
- **Logging:** Request logs automatically capture license key
- **Idempotency:** Same URL = same resource

**More information on API requests in /docs/License Service API.postman_collection.json**

---

## Scaling Strategy

### Current Scale

- **Brands:** 4 (target: 20)
- **Products:** 9 (target: 100)
- **License Keys:** 0 (target: 100k)
- **Activations:** 0 (target: 500k)

### Horizontal Scaling

**Stateless Application:**
```
[Load Balancer]
       │
       ├──► [App Instance 1] ──┐
       ├──► [App Instance 2] ──┼──► [PostgreSQL Primary]
       └──► [App Instance N] ──┘
```

**Key Points:**

- No session state (stateless API)
- Database is bottleneck (address separately)
- Can scale app instances infinitely

### Database Scaling

**Phase 1: Vertical Scaling (Current)**
```
Single PostgreSQL instance
- 4 CPU, 8GB RAM
- Handles ~10k req/sec
```

**Phase 2: Read Replicas**
```
[Primary] ──┬──► [Replica 1] (Read-only)
            └──► [Replica 2] (Read-only)

Write operations → Primary
Read operations → Replicas (status checks, list queries)
```

**Phase 3: Partitioning**
```sql
-- Partition activations by created_at (monthly)
CREATE TABLE license_activations_2025_01 PARTITION OF license_activations
  FOR VALUES FROM ('2025-01-01') TO ('2025-02-01');
```

**Benefits:**

- Faster queries (smaller tables)
- Archive old data easily
- Drop old partitions without VACUUM

### Caching Strategy

**1. Metrics Endpoint (Implemented):**
```php
Cache::remember('system_metrics', 60, fn() => ...);
```

**2. License Status (Future):**
```php
// Cache valid licenses for 5 minutes
Cache::remember("license:{$key}", 300, fn() => ...);
```

**3. Product Data (Future):**
```php
// Products rarely change
Cache::remember("brand:{$id}:products", 3600, fn() => ...);
```

**Cache Invalidation:**

- Event-driven (on license provision, clear cache)
- Time-based (TTL)

### CDN Integration (Future)
```
[CDN] → Cache /api/v1/licenses/{key}/status responses
- 1-minute TTL
- Reduces database load for status checks
```

---

## Extensibility & Evolution

The modular architecture enables the system to evolve with growing business requirements while maintaining clean boundaries and manageable complexity.

### Evolution Path: Brand Self-Service

**Current State:** Brands are seeded manually via database migrations

**Future Evolution:** Self-service brand registration and management

#### Phase 1: Brand Registration
```php
POST /api/v1/brands/register
Body: {
  "name": "New Brand Inc",
  "slug": "new-brand",
  "contact_email": "admin@newbrand.com"
}

Response: {
  "brand_id": "01JKZ...",
  "api_key": "sk_live_new-brand_a7f3...",
  "status": "pending_verification"
}
```

**Implementation:**
- New `BrandRegistrationService` in Brand module
- Email verification workflow
- Admin approval workflow
- Automatic API key generation and rotation

#### Phase 2: Brand Product Management
```php
POST /api/v1/brands/products
Body: {
  "name": "Premium Plugin",
  "slug": "premium-plugin",
  "max_seats": 5,
  "is_active": true
}

Response: {
  "product_id": "01JKZ...",
  "brand_id": "01JKY...",
  "created_at": "..."
}
```

**Implementation:**
- Product CRUD endpoints in Brand module
- Brand-scoped product management
- Product activation/deactivation
- Product analytics and insights

#### Phase 3: Brand Dashboard & Analytics
```php
GET /api/v1/brands/dashboard/metrics
Response: {
  "total_licenses": 15240,
  "active_licenses": 12890,
  "total_activations": 45120,
  "revenue_impact": {...},
  "top_products": [...]
}
```

**Implementation:**
- New `BrandAnalytics` module
- Metrics aggregation service
- Dashboard API endpoints
- Real-time license usage tracking

### Why Modular Architecture Supports This Evolution

**1. Isolated Brand Logic**
```
app/Modules/Brand/
├── Controllers/
│   ├── BrandRegistrationController.php  # New
│   └── ProductManagementController.php  # New
├── Services/
│   ├── BrandRegistrationService.php     # New
│   └── ProductManagementService.php     # New
└── ...
```

All brand-related evolution stays within the Brand module:
- No impact on License module
- No cross-module breaking changes
- Independent testing and deployment

**2. Clean Service Boundaries**

The existing `BrandLookupService` interface enables seamless enhancement:
```php
interface BrandLookupServiceInterface
{
    // Existing methods
    public function findBrandByPublicId(string $publicId): ?Brand;
    public function findProductByPublicId(string $publicId): ?Product;

    // Future additions (backward compatible)
    public function getBrandMetrics(string $brandId): BrandMetrics;
    public function listBrandProducts(string $brandId): Collection;
}
```

License module continues to use the interface without changes.

**3. Database Independence**

Cross-module references use ULIDs:
```sql
-- License module references Brand module via ULID
licenses.product_id → products.public_id

-- Can extract Brand module to separate database later
-- No integer FK constraints to break
```

**4. Future Microservice Extraction**

When brand management becomes complex enough:

```
Before (Current):
┌─────────────────────────────┐
│  License Service (Monolith) │
│  ┌─────────┐  ┌──────────┐ │
│  │ Brand   │  │ License  │ │
│  │ Module  │  │ Module   │ │
│  └─────────┘  └──────────┘ │
└─────────────────────────────┘

After (Microservices):
┌─────────────┐    ┌──────────────┐
│   Brand     │    │   License    │
│   Service   │◄───│   Service    │
│             │HTTP│              │
│ - Register  │    │ - Provision  │
│ - Products  │    │ - Activate   │
└─────────────┘    └──────────────┘
```

**Migration Steps:**
1. Extract Brand module to separate repository
2. Replace in-process `BrandService` calls with HTTP API calls
3. Deploy Brand Service independently
4. No changes needed in License module logic

**5. Testing Independence**

Each evolution can be tested independently:
```php
// Test brand registration without touching license logic
class BrandRegistrationTest extends TestCase
{
    public function test_brand_can_self_register()
    {
        // Test only Brand module
    }
}

// Existing license tests remain unchanged
class ProvisionLicenseTest extends TestCase
{
    public function test_can_provision_license()
    {
        // Still works with mock BrandService
    }
}
```

### Real-World Evolution Example

**Scenario:** Add brand subscription tiers (Free, Pro, Enterprise)

**Without Modular Architecture:**
```
Changes needed across entire codebase:
- Update brands table
- Modify license provisioning logic
- Change activation validation
- Update all API responses
- Risk breaking existing functionality
```

**With Modular Architecture:**
```
Changes isolated to Brand module:
- Add subscription_tier to brands table
- Create SubscriptionService in Brand module
- Update BrandLookupService to include tier
- License module receives tier via service (no changes needed)
```

### Additional Evolution Paths Enabled by Modularity

**1. Multi-Currency Support**
- Add `Payment` module
- No changes to License or Brand modules
- Clean separation of concerns

**2. Advanced Analytics**
- Add `Analytics` module
- Listens to license events
- Provides insights without touching core logic

**3. Customer Self-Service Portal**
- Add `Customer` module
- Reuses existing License APIs
- Independent frontend and backend

**4. Integration Marketplace**
- Add `Integration` module
- Connects brands with third-party tools
- Extensible without core changes

### Key Takeaway

The modular architecture provides a **sustainable growth path** where:
- New features are **additive**, not disruptive
- Modules can **evolve independently**
- **Testing remains isolated** and manageable
- **Deployment can be modular** (monolith now, microservices later)
- **Team scaling** becomes easier (one team per module)

This is why the application is built as a modular monolith rather than a traditional monolith—it's designed for evolution from day one.

---

## User Story Implementation Status

### ✅ Fully Implemented

#### US1: Provision License

**Status:** ✅ Complete

**Implementation:**

- Endpoint: `POST /api/v1/brands/licenses/provision`
- Service: `ProvisionLicenseService`
- Features: Single product, multiple products, add to existing key
- Tests: 15 tests (12 feature + 3 integration)

**Deliverables:**

- Brand can create license keys
- Associate multiple products with single key
- Add products to existing keys
- Automatic license key generation per brand
- Event-driven audit logging

#### US3: Activate License

**Status:** ✅ Complete

**Implementation:**

- Endpoint: `POST /api/v1/licenses/{license_key}/activate`
- Service: `ActivateLicenseService`
- Features: Seat validation, idempotent activation, reactivation
- Tests: 22 tests (17 feature + 5 integration)

**Deliverables:**

- End-user products can activate licenses
- Seat limit enforcement
- Support for site, device, server instance types
- Idempotent activations (return existing if active)
- Reactivate previously deactivated instances

#### US4: Check License Status

**Status:** ✅ Complete

**Implementation:**

- Endpoint: `GET /api/v1/licenses/{key}/status`
- Controller: `LicenseStatusController`
- Features: Multi-product status, seat availability, heartbeat
- Tests: 26 tests (18 feature + 6 integration + 2 unit)

**Deliverables:**

- Products/customers can check license validity
- See all products on license key
- View seat usage (used, available, total)
- Active activations list
- Heartbeat mechanism (updates last_checked_at)

#### US6: List Licenses by Email

**Status:** ✅ Complete

**Implementation:**

- Endpoint: `GET /api/v1/brands/customers/{email}/licenses`
- Controller: `CustomerLicenseController`
- Features: Cross-brand listing, pagination
- Tests: 20 tests (16 feature + 4 integration)

**Deliverables:**

- Brands can list all customer licenses
- Cross-brand visibility (ecosystem-wide)
- Pagination support (customizable per_page)
- Brand authentication required

### 📋 Designed but Not Implemented

#### US2: License Lifecycle Management

**Status:** 📋 Designed, Not Implemented

**Design:**
```php
// Suspend License
POST /api/v1/brands/licenses/{key}/suspend
Response: { status: "suspended", suspended_at: "..." }

// Resume License
POST /api/v1/brands/licenses/{key}/resume
Response: { status: "valid", resumed_at: "..." }

// Cancel License
DELETE /api/v1/brands/licenses/{key}/cancel
Response: { status: "cancelled", cancelled_at: "..." }

// Renew License
POST /api/v1/brands/licenses/{key}/renew
Body: { extends_by_days: 365 }
Response: { expires_at: "2027-12-31" }
```

**Database Schema:**

Already supports lifecycle:
```sql
licenses.status ENUM('valid', 'suspended', 'cancelled', 'expired')
```

**Service Design:**
```php
class LicenseLifecycleService
{
    public function suspend(License $license): License
    {
        $license->update(['status' => LicenseStatus::SUSPENDED]);
        event(new LicenseSuspended($license));
        return $license;
    }
    
    public function resume(License $license): License
    {
        $license->update(['status' => LicenseStatus::VALID]);
        event(new LicenseResumed($license));
        return $license;
    }
    
    public function cancel(License $license): License
    {
        $license->update(['status' => LicenseStatus::CANCELLED]);
        event(new LicenseCancelled($license));
        return $license;
    }
    
    public function renew(License $license, int $days): License
    {
        $newExpiry = $license->expires_at->addDays($days);
        $license->update(['expires_at' => $newExpiry]);
        event(new LicenseRenewed($license));
        return $license;
    }
}
```

**Events:**
```php
LicenseSuspended
LicenseResumed
LicenseCancelled
LicenseRenewed
```

**Why Not Implemented:**

- Time constraint (3-day assessment)
- Focused on minimum required user stories
- Foundation is complete (enums, status checks already work)

**Implementation Effort:** ~2 hours

#### US5: Deactivate License

**Status:** 📋 Designed, Not Implemented

**Design:**
```php
// Deactivate Seat
DELETE /api/v1/licenses/{key}/activations/{activationId}
Response: { message: "Activation removed", seats_freed: 1 }
```

**Service Design:**
```php
class DeactivateLicenseService
{
    public function deactivate(
        LicenseActivation $activation
    ): LicenseActivation {
        $activation->update(['deactivated_at' => now()]);
        event(new LicenseDeactivated($activation));
        return $activation;
    }
}
```

**Database Support:**

Already implemented:
```sql
license_activations.deactivated_at TIMESTAMP NULL
```

**Why Not Implemented:**

- Time constraint
- Focused on activation (US3) first
- Deactivation is natural extension of activation

**Implementation Effort:** ~1.5 hours

---

## Setup commands

### Requirements

- PHP 8.2+
- PostgreSQL 15
- Composer

### Installation
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
```

**More detailed setup information in README.md**

**ALL ENVIRONMENT VARIABLES AVAILABLE IN .env.example**

**Postman json collection in /docs/License Service API.postman_collection.json**

**Postman json envrionment variables in /docs/License Service API.postman_environment.json**

**Use /docs/POSTMAN_SETUP.md as a guide to import the collection and environment variables in postman**

---

## Known Limitations

### 1. No License Key Rotation

**Current:** License keys are permanent

**Limitation:** Compromised keys cannot be rotated

**Future Solution:**
```php
POST /api/v1/brands/licenses/{key}/rotate
Response: { old_key: "RANK-...", new_key: "RANK-..." }
```

**Impact:** Low (keys are customer-specific, not shared)

### 2. No Webhook Support

**Current:** No event notifications to brands

**Limitation:** Brands must poll for changes

**Future Solution:**
```php
// Brand configures webhook URL
POST '/api/v1/brands/webhooks'
Body: { url: "https://...", events: ["license.activated"] }

// Service sends POST on events
POST 'https://brand-webhook-url.com'
Body: { event: "license.activated", data: {...} }
```

**Impact:** Medium (polling works but inefficient)

### 3. No API Versioning Strategy (Yet)

**Current:** All endpoints under `/api/v1/`

**Limitation:** Breaking changes require new version

**Future Solution:**
```
/api/v2/licenses/{key}/activate  # New version
/api/v1/licenses/{key}/activate  # Old version (deprecated)
```

**Impact:** Low (v1 is stable, unlikely to break)

### 4. No Grace Period for Expired Licenses

**Current:** Expired licenses immediately invalid

**Limitation:** No grace period for renewals

**Future Solution:**
```php
// 7-day grace period
$isValid = $license->expires_at->addDays(7) > now();
```

**Impact:** Medium (customer experience)

### 5. No License Transfer Between Customers

**Current:** License tied to email permanently

**Limitation:** Cannot transfer licenses

**Future Solution:**
```php
POST '/api/v1/brands/licenses/{key}/transfer'
Body: { new_email: "newowner@example.com" }
```

**Impact:** Low (rare use case)

---

**Document Version:** 1.0  
**Last Updated:** December 29, 2025  
**Author:** License Service Team
