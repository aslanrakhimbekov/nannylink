# Scope: Milestone 2 - Database & Geolocation

## Architecture
- Framework: Laravel 11 (PHP 8.3+)
- Database: PostgreSQL 16 + PostGIS extension
- Models & Schema:
  - `users`: ID, phone (format checking `^\+77[0-9]{9}$` on database level via CHECK constraint), telegram_id, role (parent/nanny/admin/moderator), status (active/blocked), language (ru/kk).
  - `profiles`: ID, user_id, first_name, last_name, avatar_url, video_url, bio, hourly_rate, experience_years, balance_coins, is_verified, location (PostGIS Point, SRID 4326).
  - `documents`: ID, profile_id, type (criminal_record/medical_clearance), file_path, status (pending/approved/rejected), rejection_reason, verified_at, verified_by_user_id.
  - `orders`: ID, parent_id, title, description, address_string, location (PostGIS Point, SRID 4326), child_age, date_start, date_end, payment_type (hourly/fixed), budget, status (open/matched/completed/cancelled).
  - `responses`: ID, order_id, nanny_id, coin_cost, status (pending/accepted/rejected). Unique constraint on (order_id, nanny_id).
  - `coin_transactions`: ID, uuid, user_id, order_id, type (deposit/spend/refund), amount.
- Geolocation logic: Use PostGIS spatial query (`ST_Distance` and `ST_DWithin` with geography casts and SRID 4326) to find orders/nannies within a given radius in kilometers.

## Milestones
| # | Name | Scope | Dependencies | Status |
|---|------|-------|-------------|--------|
| 1 | Laravel 11 Setup | Install/configure Laravel 11, configure .env, PostgreSQL & PostGIS | None | PLANNED |
| 2 | PostGIS Setup & Migrations | Create migrations for PostGIS extension, create tables (users, profiles, documents, orders, responses, coin_transactions) with proper check constraints (phone `^\+77[0-9]{9}$`), foreign keys, and spatial indices. | M1 | PLANNED |
| 3 | Models & Relationships | Implement Eloquent models with spatial attributes, relationships, and validation. | M2 | PLANNED |
| 4 | Geolocation Helper / Scopes | Implement geolocation helper classes/scopes using PostGIS spatial queries. | M3 | PLANNED |
| 5 | Verification & Testing | Verify migrations run, spatial indices exist, and model/geo tests pass. | M4 | PLANNED |

## Interface Contracts
- Location coordinates use SRID 4326 (longitude, latitude).
- Phone validation format on database constraint: `^\+77[0-9]{9}$`.
- Profiles balance_coins defaults to 0.
- Responses have a unique constraint on (order_id, nanny_id).
