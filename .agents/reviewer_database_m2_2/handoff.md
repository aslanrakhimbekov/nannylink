# Handoff Report: Milestone 2.2 Database & Geolocation Review

## 1. Observation

A detailed static analysis was conducted on all database migrations, Eloquent models, geolocation scopes, and the test suite written by the worker. The following specific observations were recorded:

1. **SQL Injection Vulnerabilities in Geolocation Scopes**:
   - In `app/Models/Profile.php` (lines 41-45):
     ```php
     $point = "ST_SetSRID(ST_MakePoint($longitude, $latitude), 4326)::geography";
     $radiusMeters = $radiusKm * 1000;

     return $query->whereRaw("ST_DWithin(location, $point, ?)", [$radiusMeters])
                  ->orderByRaw("ST_Distance(location, $point)");
     ```
   - In `app/Models/Order.php` (lines 51-55):
     ```php
     $point = "ST_SetSRID(ST_MakePoint($longitude, $latitude), 4326)::geography";
     $radiusMeters = $radiusKm * 1000;

     return $query->whereRaw("ST_DWithin(location, $point, ?)", [$radiusMeters])
                  ->orderByRaw("ST_Distance(location, $point)");
     ```

2. **Missing Unique Constraint on One-to-One Relationship (Profiles)**:
   - In `database/migrations/2024_01_01_000002_create_profiles_table.php` (line 14):
     ```php
     $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
     ```
     No unique index or constraint is added to enforce the one-to-one mapping relationship between `User` and `Profile`.

3. **Lack of Indexing on Foreign Keys**:
   - In `database/migrations/2024_01_01_000002_create_profiles_table.php`: `user_id` has no index.
   - In `database/migrations/2024_01_01_000003_create_documents_table.php`: `profile_id` (line 13) and `verified_by_user_id` (line 19) have no indexes.
   - In `database/migrations/2024_01_01_000004_create_orders_table.php`: `parent_id` (line 14) has no index.
   - In `database/migrations/2024_01_01_000006_create_coin_transactions_table.php`: `user_id` (line 14) and `order_id` (line 15) have no indexes.

4. **Lack of Database-Level CHECK Constraints**:
   - In `database/migrations/2024_01_01_000002_create_profiles_table.php` (line 22): `balance_coins` lacks a `CHECK (balance_coins >= 0)` constraint.
   - In `database/migrations/2024_01_01_000004_create_orders_table.php`: `child_age` (line 18) and `budget` (line 22) lack non-negativity check constraints, and there is no constraint enforcing `date_end >= date_start`.
   - In `database/migrations/2024_01_01_000005_create_responses_table.php` (line 15): `coin_cost` lacks a `CHECK (coin_cost >= 0)` constraint.

5. **Missing PHP Type Safety via Model Casts & Enums**:
   - In `app/Models/User.php`: `role`, `status`, and `language` have no enum casts.
   - In `app/Models/Order.php`: `status` and `payment_type` have no enum casts.
   - In `app/Models/Document.php`: `type` and `status` have no enum casts.
   - In `app/Models/Response.php`: `status` has no enum casts.
   - In `app/Models/CoinTransaction.php`: `type` has no enum casts, and it does not use the `HasUuids` trait for automated UUID generation.

6. **Phone Format Check Constraint**:
   - In `database/migrations/2024_01_01_000001_create_users_table.php` (line 22):
     ```php
     DB::statement("ALTER TABLE users ADD CONSTRAINT chk_phone_format CHECK (phone ~ '^\\+77[0-9]{9}$');");
     ```
     This matches the requirement and is correctly implemented for PostgreSQL.

---

## 2. Logic Chain

1. **Vulnerability Analysis**:
   - **SQL Injection**: Since Eloquent scope parameters like `$latitude` and `$longitude` are often populated directly from HTTP query arguments (e.g. `latitude=43.238&longitude=76.889`), interpolating them as strings directly into raw query templates (`whereRaw` and `orderByRaw`) allows attackers to inject malicious SQL commands. Using query bindings (`?`) is the industry standard to prevent SQL injection.
2. **Database Integrity Analysis**:
   - **One-to-One Model Violations**: The relationship between `User` and `Profile` is structurally 1:1. If the database schema does not declare `user_id` as `unique()`, multiple profiles can be created for the same user, causing query behavior exceptions or silent data degradation.
   - **Negative Values & Logical Chronology**: Application-level checks can fail or be bypassed. If `balance_coins` drops below 0 due to concurrency issues, it violates the ledger invariant. Similarly, a job order ending before it starts (`date_end < date_start`) represents corrupted domain state. Database-level CHECK constraints are crucial for defense-in-depth.
3. **Database Performance Analysis**:
   - **Foreign Key Indexing**: Joins on foreign keys and cascading deletions trigger full table scans in PostgreSQL if no index exists on the foreign key columns. Under peak load, this will cause high database CPU usage and transaction timeouts.
4. **PHP Type Safety Analysis**:
   - **Model Casts**: Casting database string/enum columns to PHP backed Enums prevents runtime assignment of invalid enum values in application code.

---

## 3. Caveats

- **No Active Execution**: Command execution is disabled/blocked in this environment due to permission timeouts. As a result, tests could not be run locally. The analysis is based purely on static code verification.
- **Postgres Specifics**: The migrations use PostgreSQL-specific dialects (PostGIS geographic types, `~` POSIX regex operator, and `USING gist` indexes). Running these migrations on SQLite or MySQL test databases will fail. The application config assumes PostgreSQL 16.

---

## 4. Conclusion

### Review Summary

**Verdict**: **REQUEST_CHANGES**

The worker's implementation correctly establishes the primary database schemas, custom spatial GIST indexes, and basic tests. However, critical security issues and major database integrity/performance issues must be resolved before approval.

---

## Findings

### [Critical] Finding 1: SQL Injection Vulnerability in Geolocation Scopes
- **What**: Direct string interpolation of latitude/longitude into raw queries.
- **Where**: `App\Models\Profile::scopeNearby` (lines 41-45) and `App\Models\Order::scopeNearby` (lines 51-55).
- **Why**: Allows arbitrary SQL injection from query parameters.
- **Suggestion**: Replace double-quoted string variables with query bindings `?`:
  ```php
  public function scopeNearby($query, $latitude, $longitude, $radiusKm)
  {
      $radiusMeters = $radiusKm * 1000;
      return $query->whereRaw(
          "ST_DWithin(location, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)",
          [$longitude, $latitude, $radiusMeters]
      )->orderByRaw(
          "ST_Distance(location, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography)",
          [$longitude, $latitude]
      );
  }
  ```

### [Major] Finding 2: Missing Unique Constraint on Profile `user_id`
- **What**: One-to-one mapping constraint is not enforced at DB level.
- **Where**: `database/migrations/2024_01_01_000002_create_profiles_table.php` (line 14).
- **Why**: Allows duplicate profiles for the same user.
- **Suggestion**: Add `->unique()` to `user_id` foreign key:
  ```php
  $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
  ```

### [Major] Finding 3: Lack of Indexing on Foreign Keys
- **What**: Missing indexes on critical foreign keys.
- **Where**: All migrations except `responses` (which has a composite unique index).
- **Why**: Causes full table scans on joins and cascades.
- **Suggestion**: Append `->index()` to the foreign key declarations:
  - `documents.profile_id`
  - `documents.verified_by_user_id`
  - `orders.parent_id`
  - `coin_transactions.user_id`
  - `coin_transactions.order_id`

### [Medium] Finding 4: Missing DB-level CHECK Constraints
- **What**: Lack of non-negativity and date integrity checks.
- **Where**: `profiles.balance_coins`, `orders.child_age`, `orders.budget`, `orders.date_end`/`date_start`, and `responses.coin_cost`.
- **Why**: Allows database corruption or invalid coin balances under race conditions.
- **Suggestion**: Add DB CHECK constraints using `DB::statement`:
  - `balance_coins >= 0`
  - `child_age >= 0`
  - `budget >= 0`
  - `date_end >= date_start`
  - `coin_cost >= 0`

### [Minor] Finding 5: Missing Enum Casts & Auto UUIDs
- **What**: Model enums are not cast to PHP backed Enums, and `CoinTransaction` doesn't automate UUIDs.
- **Where**: All models.
- **Why**: Reduces PHP type safety and increases error rate when manually creating coin transactions.
- **Suggestion**: Define backed Enums, reference them in the models' `casts()` methods, and use the `HasUuids` trait in `CoinTransaction`.

---

## Verified Claims

- Phone format CHECK constraint matches requirements → verified via static review → **PASS**
- Spatial coordinates parsing logic uses binary unpack safely → verified via static review → **PASS**
- Geolocation scopes use PostGIS functions correctly → verified via static review → **PASS** (aside from SQLi)
- Relationships defined in models match requirements → verified via static review → **PASS**

---

## Coverage Gaps

- **Test Suite SQL Injection Testing** — risk level: **Medium** — recommendation: Add a test verifying that passing non-numeric or SQLi payloads to latitude/longitude in scopes throws a formatting/query error rather than executing.
- **Conformant 1:1 profile integrity test** — risk level: **Low** — recommendation: Add a test asserting that creating a second profile for the same user fails at database level.

---

## Unverified Items

- **Actual test suite execution** — reason not verified: Command execution blocked by local headless workspace configuration.

---

# Adversarial Challenge Report

## Challenge Summary

**Overall risk assessment**: **HIGH** (primarily due to SQL Injection vulnerability in raw queries and data integrity vulnerabilities).

## Challenges

### [High] Challenge 1: Geolocation SQL Injection
- **Assumption challenged**: Assumes that input parameters to `scopeNearby` are always sanitized by controllers.
- **Attack scenario**: A user queries nearby orders using a latitude/longitude payload containing SQL delimiters and payloads (e.g. `longitude=76.889; DELETE FROM users; --`).
- **Blast radius**: Full database compromise (arbitrary read, update, or deletion of database contents).
- **Mitigation**: Bind coordinates using PDO query bindings `?`.

### [Medium] Challenge 2: Duplicate Profiles (1:1 Bypass)
- **Assumption challenged**: Assumes that the application layer handles profile creation constraints perfectly.
- **Attack scenario**: Simultaneous profile creation requests (concurrency race condition) could succeed in creating multiple profile rows for a single user ID.
- **Blast radius**: Inconsistent state where `User::profile()` returns only the first record, but subsequent writes or reads target duplicate records, leading to incorrect balance queries or document associations.
- **Mitigation**: Add a unique index constraint to `profiles.user_id`.

### [Medium] Challenge 3: Negative Coins Ledger
- **Assumption challenged**: Assumes that balance debits will never exceed current coin count.
- **Attack scenario**: Race conditions on simultaneous responses by the same nanny could exceed the coin balance.
- **Blast radius**: Nanny coin balance goes negative, breaking ledger integrity.
- **Mitigation**: Enforce `CHECK (balance_coins >= 0)` at database level.

---

## 5. Verification Method

To verify these findings and the corrected implementations once updated:
1. Run the database migration suite in a PostgreSQL environment with PostGIS enabled:
   ```bash
   php artisan migrate:fresh
   ```
2. Run the test suite:
   ```bash
   php artisan test tests/Feature/DatabaseAndGeolocationTest.php
   ```
3. Verify that the unique constraint on `profiles.user_id` exists:
   ```sql
   \d profiles
   -- Check for UNIQUE constraint on user_id
   ```
4. Verify that the CHECK constraints exist:
   ```sql
   \d users
   \d profiles
   \d orders
   -- Check for check constraints chk_phone_format, chk_balance_coins_non_negative, etc.
   ```
