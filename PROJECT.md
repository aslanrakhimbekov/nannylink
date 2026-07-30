# Project: NannyLink MVP

## Architecture
- **Framework**: Laravel 11 (PHP 8.3+)
- **Database**: PostgreSQL 16 + PostGIS extension
- **Cache & Queue**: Redis (Sanctum/OTP caching, Horizon background queues)
- **Object Storage**: AWS S3 driver (fallback to MinIO locally)
- **Admin Portal**: Filament PHP v3 (TALL stack)
- **Frontend / TMA**: Tailwind CSS, Telegram WebApp SDK integration for TMA mobile-first experience

## Code Layout
- `app/Models/`: Eloquent models (`User`, `Profile`, `Document`, `Order`, `Response`, `CoinTransaction`)
- `app/Http/Controllers/Api/v1/`: Rest API controllers (Auth, Orders, Responses)
- `app/Filament/`: Filament Admin Panel resources and custom page templates
- `database/migrations/`: Database migration files, containing spatial index definitions and constraints
- `routes/api.php`: Sanctum guarded endpoints
- `lang/`: i18n localization translation directories (`ru`, `kk`)
- `tests/Feature/` & `tests/Unit/`: Backend unit/feature tests
- `tests/E2E/`: End-to-End opaque-box test suite

## Milestones
| # | Name | Scope | Dependencies | Status |
|---|------|-------|-------------|--------|
| 1 | E2E Test Suite | Set up E2E test runner, create Tier 1-4 tests (Dual Track) | None | IN_PROGRESS (5a06fb21-cddb-4ba8-be05-4122d7a17720) |
| 2 | Database & Geo | Setup PostGIS, migrate schemas, configure spatial indices | None | IN_PROGRESS (29137dbc-1505-455d-808c-f276b22a957c) |
| 3 | Authentication | OTP auth (Redis cache, rate limits) + Telegram OAuth widget callback | M2 | PLANNED |
| 4 | Orders & Coins | Order creation, nearby geo-search API, responses with transaction protection | M3 | PLANNED |
| 5 | Admin & PDF Parser | Filament dashboard, profile verification flow, eGov PDF parsing | M4 | PLANNED |
| 6 | TMA UX & Queues | Tailwinds styling, TMA SDK integration, S3 storage, queues, account deletion | M5 | PLANNED |
| 7 | Adversarial Hardening | Tier 5 white-box coverage hardening (Challenger adversarial test loop) | M1, M6 | PLANNED |

## Interface Contracts
### Auth Module
- `POST /api/v1/auth/request-otp`
  - Request Body: `{ "phone": "+77XXXXXXXXX" }`
  - Phone format check: `^\+77[0-9]{9}$`
  - Output: `200 OK` with code logged/cached.
  - Limits: 1 request/60 seconds per IP/phone (Laravel RateLimiter).
- `POST /api/v1/auth/verify-otp`
  - Request Body: `{ "phone": "+77XXXXXXXXX", "code": "1234" }`
  - Output: `200 OK` with token and user details.
- `POST /api/v1/auth/telegram-callback`
  - Request Body: Telegram widget OAuth callback parameters (validated signature).
  - Output: `200 OK` with Sanctum token and user details.

### Orders Module
- `POST /api/v1/orders` (Parent only)
  - Request Body: `{ "title": "...", "description": "...", "address_string": "...", "latitude": 51.16, "longitude": 71.42, "child_age": 5, "date_start": "...", "date_end": "...", "payment_type": "hourly", "budget": 1500 }`
  - Output: `201 Created` with created order object.
- `GET /api/v1/orders/nearby` (Nanny only)
  - Request Query: `?latitude=51.16&longitude=71.42&radius_km=5`
  - Output: `200 OK` with list of nearby orders, sorted by distance (PostGIS geography distance).

### Coins & Responses Module
- `POST /api/v1/orders/{id}/respond` (Nanny only)
  - Output: `200 OK` with parent's contact info.
  - Logic: Implements double-spend protection for coin debit using row locking (`FOR UPDATE` / Eloquent `lockForUpdate`) on profiles inside a DB transaction. Deducts 500 coins from balance (requires balance >= 500), creates a `coin_transactions` ledger entry, and inserts response.
