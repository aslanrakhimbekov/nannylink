## 2026-07-10T14:33:48Z
You are teamwork_preview_worker. Your working directory is /Users/aslanaslan/.gemini/antigravity/scratch/nannylink/.agents/worker_database_m2_1.
Your mission is to implement Milestone 2 (Database & Geolocation) of NannyLink MVP.

MANDATORY INTEGRITY WARNING:
DO NOT CHEAT. All implementations must be genuine. DO NOT hardcode test results, create dummy/facade implementations, or circumvent the intended task. A Forensic Auditor will independently verify your work. Integrity violations WILL be detected and your work WILL be rejected.

Tasks:
1. Initialize the Laravel 11 project in `/Users/aslanaslan/.gemini/antigravity/scratch/nannylink`. Since the directory is not empty (contains .agents/ etc.), use the temporary directory technique:
   - Create a temp project: `composer create-project laravel/laravel temp_laravel "11.*" --prefer-dist`
   - Move all files (including hidden ones like `.env.example`, `.gitattributes`, `.gitignore` etc.) from `temp_laravel` to the root workspace.
   - Clean up `temp_laravel`.
   - Install required packages: `composer require laravel/sanctum filament/filament:"^3.0" smalot/pdf-parser`
2. Configure `.env` and `.env.example` with:
   - DB_CONNECTION=pgsql
   - DB_HOST=127.0.0.1
   - DB_PORT=5432
   - DB_DATABASE=nannylink
   - DB_USERNAME=postgres
   - DB_PASSWORD=postgres
   Make sure to create the database `nannylink` if it does not exist. (e.g. run a script or psql to CREATE DATABASE if needed before running migrations).
3. Database migrations (using PostGIS):
   - First migration: `enable_postgis_extension` to run `CREATE EXTENSION IF NOT EXISTS postgis;`.
   - Table `users`:
     - id: primary key (bigint)
     - phone: string, unique, with database-level CHECK constraint to match `^\+77[0-9]{9}$` (e.g. `phone ~ '^\+77[0-9]{9}$'`)
     - telegram_id: string or bigint, nullable
     - role: string or enum ('parent', 'nanny', 'admin', 'moderator')
     - status: string or enum ('active', 'blocked'), default 'active'
     - language: string or enum ('ru', 'kk'), default 'ru'
     - timestamps
   - Table `profiles`:
     - id: primary key (bigint)
     - user_id: foreign key referencing users.id, cascade on delete
     - first_name: string
     - last_name: string
     - avatar_url: string, nullable
     - video_url: string, nullable
     - bio: text, nullable
     - hourly_rate: integer, nullable
     - experience_years: integer, nullable
     - balance_coins: integer, default 0
     - is_verified: boolean, default false
     - location: PostGIS geometry/geography point, SRID 4326, nullable. GIST spatial index on location.
     - timestamps
   - Table `documents`:
     - id: primary key (bigint)
     - profile_id: foreign key referencing profiles.id, cascade on delete
     - type: string or enum ('criminal_record', 'medical_clearance')
     - file_path: string
     - status: string or enum ('pending', 'approved', 'rejected'), default 'pending'
     - rejection_reason: text, nullable
     - verified_at: timestamp, nullable
     - verified_by_user_id: foreign key referencing users.id, nullable, set null on delete
     - timestamps
   - Table `orders`:
     - id: primary key (bigint)
     - parent_id: foreign key referencing users.id, cascade on delete
     - title: string
     - description: text
     - address_string: string
     - location: PostGIS geometry/geography point, SRID 4326. GIST spatial index on location.
     - child_age: integer
     - date_start: datetime
     - date_end: datetime
     - payment_type: string or enum ('hourly', 'fixed')
     - budget: integer
     - status: string or enum ('open', 'matched', 'completed', 'cancelled'), default 'open'
     - timestamps
   - Table `responses`:
     - id: primary key (bigint)
     - order_id: foreign key referencing orders.id, cascade on delete
     - nanny_id: foreign key referencing users.id, cascade on delete
     - coin_cost: integer, default 500
     - status: string or enum ('pending', 'accepted', 'rejected'), default 'pending'
     - Unique constraint on (order_id, nanny_id)
     - timestamps
   - Table `coin_transactions`:
     - id: primary key (bigint)
     - uuid: uuid, unique
     - user_id: foreign key referencing users.id, cascade on delete
     - order_id: foreign key referencing orders.id, nullable, cascade on delete
     - type: string or enum ('deposit', 'spend', 'refund')
     - amount: integer
     - timestamps
4. Implement Eloquent Models:
   - `User`, `Profile`, `Document`, `Order`, `Response`, `CoinTransaction`.
   - Setup proper relationships between them.
   - Use spatial casting or helper library for PostGIS location points, or custom accessors/mutators (e.g. converting spatial data from/to lat/long objects, using Raw DB expressions like `DB::raw("ST_SetSRID(ST_MakePoint(?, ?), 4326)")`).
5. Implement Geolocation Query Scopes:
   - On `Order` and `Profile` models (or a shared Trait/helper), implement a query scope `scopeNearby($query, $latitude, $longitude, $radiusKm)` that uses PostGIS spatial functions:
     - `ST_DWithin` casting points to `geography` for exact kilometer calculation (or using `ST_Distance`).
     - Ordering by distance from the target point.
     - Make sure the GIST spatial index is utilized.
6. Write Backend Tests:
   - Create Unit/Feature tests in `tests/Feature` or `tests/Unit` to verify:
     - All migrations run successfully.
     - CHECK constraint on `users.phone` prevents invalid numbers but permits valid ones (e.g. `+77771234567` is valid, `+7777123456` or `87771234567` is invalid).
     - Spatial indices are created.
     - `scopeNearby` correctly filters profiles/orders within radius and sorts them by distance.
     - Relationship definitions (e.g. Profile belongsTo User, User hasMany CoinTransactions, etc.).
7. Verify all tests pass by running phpunit.
8. Document all changes and write `handoff.md` inside `/Users/aslanaslan/.gemini/antigravity/scratch/nannylink/.agents/worker_database_m2_1/`.
9. Update your `progress.md` regularly (heartbeat timestamp) so we know you are alive.
10. Send a message to your parent when done.
