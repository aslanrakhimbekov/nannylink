# Original User Request

## Initial Request — 2026-07-10T19:30:32+05:00

# Teamwork Project Prompt

Implement the MVP version of the "NannyLink" marketplace. The application connects parents with verified nannies, featuring geolocation searching, a coin-based response mechanism, secure handling of credentials, and mockable external interfaces.

Working directory: /Users/aslanaslan/.gemini/antigravity/scratch/nannylink
Integrity mode: benchmark

## Requirements

### R1. Backend Architecture, Database Schema, and Geolocation
- Framework: Laravel 11 (PHP 8.3+).
- Database: PostgreSQL 16 + PostGIS extension.
- Schema:
  - `users`: ID, phone (format checking `^\+77[0-9]{9}$` on database level), telegram_id, role (parent/nanny/admin/moderator), status (active/blocked), language (ru/kk).
  - `profiles`: ID, user_id, first_name, last_name, avatar_url, video_url, bio, hourly_rate, experience_years, balance_coins, is_verified, location (PostGIS Point, SRID 4326).
  - `documents`: ID, profile_id, type (criminal_record/medical_clearance), file_path, status (pending/approved/rejected), rejection_reason, verified_at, verified_by_user_id.
  - `orders`: ID, parent_id, title, description, address_string, location (PostGIS Point, SRID 4326), child_age, date_start, date_end, payment_type (hourly/fixed), budget, status (open/matched/completed/cancelled).
  - `responses`: ID, order_id, nanny_id, coin_cost, status (pending/accepted/rejected). Unique constraint on (order_id, nanny_id).
  - `coin_transactions`: ID, uuid, user_id, order_id, type (deposit/spend/refund), amount.
- Geolocation logic: Use PostGIS spatial query (`ST_Distance` and `ST_DWithin` with geography casts and SRID 4326) to find orders/nannies within a given radius in kilometers.

### R2. API Endpoints
- **Auth Module**:
  - `POST /api/v1/auth/request-otp`: Generates a 4-digit code. For local testing, code is stored in Redis (TTL 5 mins) and logged to Laravel log instead of hitting the real SmartSender.kz/Mobizon API. Hard rate limit of 1 request/60 seconds per IP or phone number using Laravel's rate limiter.
  - `POST /api/v1/auth/verify-otp`: Validates code from Redis and issues a Sanctum token. Creates user if not exists.
  - `POST /api/v1/auth/telegram-callback`: Handles Telegram OAuth widget callback data, validates signature, log-in/creates user and issues Sanctum token.
- **Orders Module**:
  - `POST /api/v1/orders` (Parent only): Validates inputs (including coordinate bounds checking) and creates order using PostGIS `ST_SetSRID(ST_MakePoint(lng, lat), 4326)` for location.
  - `GET /api/v1/orders/nearby` (Nanny only): Filters and returns orders in radius (radius_km, default 5) sorted by distance.
- **Coins & Responses Module**:
  - `POST /api/v1/orders/{id}/respond` (Nanny only): Implements double-spend protection for coin debit using row locking (`FOR UPDATE` / Eloquent `lockForUpdate`) on both orders and profiles inside a DB transaction. Checks balance (requires >= 500 coins), debits 500 coins, writes a ledger entry to `coin_transactions`, and inserts response with status pending. Returns parent contact info on success.

### R3. Admin Panel (Filament PHP v3)
- TALL stack admin dashboard.
- Admin features: user/profile management, document verification.
- Verification flow: Parses uploaded PDF certificates (eGov template) using a PDF parsing library (e.g., `smalot/pdf-parser`) to extract the official verification QR link (`results.egov.kz`) and shows it in Filament UI to the moderator for validation.

### R4. Mobile-First TMA UX & Localization
- Design System: Mobile-First/PWA styled using Tailwind CSS (Primary: paste/amber yellow #F59E0B, Secondary: emerald-green #059669 for verification/badges).
- Telegram Mini App (TMA) Integration: Adapts color themes dynamically via Telegram WebApp SDK CSS variables and synchronizes with Telegram's native `MainButton` and `BackButton` on key pages.
- UX States: Skeleton loader pulse animations during fetching, graceful offline/error notifications, and optimistic UI for user actions (e.g. marking favorite / basic actions).
- Localization: Laravel i18n middleware detecting locale from Accept-Language header, session, or user preferences, supporting `ru` and `kk` locales with translations directory lang files.

### R5. Background Queues & Storage
- Horizon/Redis queues configured for async tasks (sending mock notifications and delayed SMS fallback).
- Files (PDF documents, video/avatar uploads) stored using Laravel S3 driver, configured locally to fallback to local/minio driver. Documents of rejected users automatically pruned after 30 days via Laravel scheduler tasks, and deleted entirely when user requests account deletion.

## Acceptance Criteria

### Core Functionality
- [ ] Database schema is correctly migrated with spatial index on location fields.
- [ ] OTP auth works with rate limiting (fails when requested more than once per 60s per phone/IP).
- [ ] Nearby geo-search works, returning locations inside the radius ordered by distance.
- [ ] Nanny response transaction executes successfully under concurrent mock requests without double coin deduction or race conditions.
- [ ] S3 uploads are retrievable using temporary presigned URLs (valid for max 10 mins).

### Admin Dashboard & Parser
- [ ] Filament panel allows listing and changing status of nanny profiles (unverified vs verified).
- [ ] Uploading a PDF document parses and displays the verification URL from the eGov document on the Filament page.

### UI & UX
- [ ] UI displays in Mobile-First dimensions with responsive Amber/Emerald styling.
- [ ] If window.Telegram.WebApp is active, theme colors bind to Telegram styles and native Telegram buttons are displayed.
- [ ] Language switching alters frontend strings between RU and KK correctly.
