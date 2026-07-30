# NannyLink E2E Testing Infrastructure Strategy

This document outlines the End-to-End (E2E) opaque-box testing framework, architecture, and feature coverage requirements for the NannyLink MVP application.

## 1. Feature Inventory

The MVP version of NannyLink consists of the following 10 core features (N = 10):

| ID | Feature Name | Description | Key Constraints & Acceptance Criteria |
|---|---|---|---|
| **F1** | Phone OTP Generation | Generates a 4-digit code. In local testing, saves to Redis (TTL 5 mins) and logs to Laravel log (mocking SmartSender/Mobizon). | Must generate exactly 4-digit code. Code must match what is written in Redis and log. |
| **F2** | OTP Rate Limiting | Restricts OTP requests to a maximum of 1 request per 60 seconds per phone number or IP address. | Returns HTTP 429 on rapid requests. Must include standard retry-after headers. |
| **F3** | OTP Verification & Auth | Validates the 4-digit OTP from Redis, creates the User if it does not exist, and returns a Laravel Sanctum API token. | Phone must format-match `^\+77[0-9]{9}$`. Invalid/expired code returns HTTP 422. |
| **F4** | Telegram OAuth Login | Endpoint `/api/v1/auth/telegram-callback` verifies Telegram widget login signatures, creates/finds user, and issues token. | Verifies signature authenticity using the Bot Token hash algorithm. Returns token on success. |
| **F5** | Parent Order Creation | Parent-only endpoint `/api/v1/orders` verifies inputs (bounds checks) and inserts order with PostGIS location Point (SRID 4326). | Restricts access to Parents. Rejects coordinate coordinates outside acceptable bounds. |
| **F6** | Nearby Order Geosearch | Nanny-only endpoint `/api/v1/orders/nearby` filters and lists open orders within a radius (default 5km), sorted by proximity. | Restricts access to Nannies. Uses PostGIS distance sorting. |
| **F7** | Nanny Respond to Order | Nanny-only endpoint `/api/v1/orders/{id}/respond` checks balance, debits 500 coins, creates transaction ledger, and returns parent contacts. | Restricts access to Nannies. Requires >= 500 coins. Ledger record is written with type `spend`. |
| **F8** | Concurrency Protection | Nanny response execution handles concurrent requests without race conditions or double coin deductions. | Implements database row-locking (`FOR UPDATE` / `lockForUpdate`) on profiles inside DB transaction. |
| **F9** | Filament Admin Portal | TALL stack admin dashboard for user, profile, and document verification flow. | Allows moderators to review uploaded documents and verify/reject nanny profiles. |
| **F10**| Background Queue & Storage | Horizon/Redis queues for SMS/notifications, S3/MinIO uploads with 10-min presigned URLs, and scheduler-based document pruning. | Documents of rejected users auto-deleted after 30 days. All documents pruned on account deletion. |

---

## 2. Test Architecture

The E2E test suite acts as an opaque-box verification framework, interacting with the system strictly through exposed API routes, Filament endpoints, database side-effects, and caching layers.

### Test Runner & Base Configuration
- **Runner**: PHPUnit (v10+ or v11+) configured with a custom `<testsuite name="E2E">` block inside `phpunit.xml`.
- **Base Class**: `Tests\E2E\E2ETestCase` (extending `Illuminate\Foundation\Testing\TestCase`).
- **Database Isolation**: The runner executes tests on a separate PostgreSQL test database (e.g. `nannylink_test`), running migrations and PostGIS setup before tests, and enclosing each test inside a database transaction to roll back changes.

### Dependency Mocking & Services
- **Cache & Redis**: A separate Redis database index (e.g., database `15`) is used to isolate OTP cache from local development caches.
- **S3 / MinIO Storage**: Storage is mocked using Laravel's native `Storage::fake('s3')` disk, allowing verification of file presence without hitting remote services.
- **External APIs (SmartSender/Mobizon)**: Verified by inspecting Redis state or intercepting events/logs rather than issuing outbound HTTP calls.
- **Time/Scheduler**: Laravel's `this->travelTo()` is used to fast-forward time for testing document pruning (e.g. traveling 31 days to trigger pruning).

---

## 3. Real-World Application Scenarios (Tier 4)

The test suite covers the following 5 real-world integration journeys (max(5, N/2) = 5):

- **Scenario 1: Complete Happy-Path Nanny Hiring Journey**
  1. Parent requests OTP, retrieves it from logs, verifies, and logs in.
  2. Parent creates a profile and posts a nanny job order at specific coordinates in Almaty.
  3. Nanny requests OTP, verifies, registers, and populates profile details (with coordinates 2km away).
  4. Nanny queries nearby orders, finds the parent's job post, and responds (debiting 500 coins).
  5. The response is successfully recorded, coins ledger is logged, and parent contact details are returned to the nanny.
- **Scenario 2: Profile Verification and Document Parsing Failures**
  1. Nanny registers and uploads a PDF certificate.
  2. The parsing library fails to find a valid `results.egov.kz` QR link (invalid template).
  3. Filament UI displays the error/warning to the moderator.
  4. Moderator marks the document as rejected.
  5. Horizon queue triggers a notification to the nanny. Profile remains unverified and nanny cannot respond to orders.
- **Scenario 3: Insufficient Balance and Ledger Verification**
  1. Nanny with an initial balance of 300 coins attempts to respond to an open order.
  2. The system rejects the response with HTTP 422 (insufficient balance) and makes no changes to the DB.
  3. An admin (via Filament dashboard) tops up the nanny's balance by 1000 coins.
  4. Nanny attempts the response again. The response succeeds; balance is debited by 500 (now 800), and a transaction ledger entry of type `spend` is created.
- **Scenario 4: Geolocation Distance & Sorting Boundary Walk**
  1. Parent posts an order at point $P_1$ (Almaty, Lat/Lng).
  2. Nanny A is positioned 3km away.
  3. Nanny B is positioned 7km away.
  4. Nanny A searches with `radius_km=5`. Parent order is returned.
  5. Nanny B searches with `radius_km=5`. Parent order is NOT returned.
  6. Nanny B searches with `radius_km=10`. Parent order is returned, with Nanny A ranked higher in distance sorting.
- **Scenario 5: High Concurrency Response Peak**
  1. A parent posts an open job order.
  2. 10 different nannies (each with 500+ coins) attempt to respond to the same order simultaneously.
  3. The system executes requests in parallel.
  4. Only unique nanny responses are created; each responding nanny's balance is debited exactly once, preventing double deductions or invalid double-responses from the same nanny.

---

## 4. Coverage Thresholds

To guarantee system stability, the E2E suite enforces the following verification matrix:

- **Tier 1 (Happy Paths)**: **50 test cases** (5 test cases per feature across all 10 features). Ensures that standard operation flows successfully.
- **Tier 2 (Sad Paths & Validation Boundaries)**: **50 test cases** (5 test cases per feature covering invalid phone formats, rate limit violations, expired OTP, invalid PDF uploads, budget bounds, search out-of-range).
- **Tier 3 (Cross-Feature Integration)**: **10 test cases** verifying integrations between modules (e.g. user deletion triggering document deletion from S3, language header swapping translation content).
- **Tier 4 (Real-World Application Scenarios)**: **5 test cases** covering the full multi-step journeys detailed in Section 3.
