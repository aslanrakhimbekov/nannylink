# Handoff Report — Database & Geolocation Review (Milestone 2)

## 1. Observation
- The worker implemented 7 database migration files in `database/migrations/`:
  - `2024_01_01_000000_enable_postgis_extension.php`
  - `2024_01_01_000001_create_users_table.php`
  - `2024_01_01_000002_create_profiles_table.php`
  - `2024_01_01_000003_create_documents_table.php`
  - `2024_01_01_000004_create_orders_table.php`
  - `2024_01_01_000005_create_responses_table.php`
  - `2024_01_01_000006_create_coin_transactions_table.php`
- The worker implemented 6 Eloquent models in `app/Models/`:
  - `User.php`
  - `Profile.php`
  - `Document.php`
  - `Order.php`
  - `Response.php`
  - `CoinTransaction.php`
- The worker implemented 1 feature test suite file in `tests/Feature/DatabaseAndGeolocationTest.php`.
- In `app/Models/Profile.php` (lines 39-46) and `app/Models/Order.php` (lines 49-56), the geolocation scope is implemented as:
  ```php
  public function scopeNearby($query, $latitude, $longitude, $radiusKm)
  {
      $point = "ST_SetSRID(ST_MakePoint($longitude, $latitude), 4326)::geography";
      $radiusMeters = $radiusKm * 1000;

      return $query->whereRaw("ST_DWithin(location, $point, ?)", [$radiusMeters])
                   ->orderByRaw("ST_Distance(location, $point)");
  }
  ```
- In `database/migrations/2024_01_01_000002_create_profiles_table.php` (line 14), `user_id` foreign key is declared as:
  ```php
  $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
  ```
- In `database/migrations/2024_01_01_000002_create_profiles_table.php` (line 22), the `balance_coins` column is defined as:
  ```php
  $table->integer('balance_coins')->default(0);
  ```
- In `app/Models/Profile.php` (lines 48-102) and `app/Models/Order.php` (lines 58-112), accessors decode latitude and longitude coordinates directly from the PostGIS EWKB binary hex string (`location` column) without DB queries. However, no corresponding mutators (setters) are defined.

---

## 2. Logic Chain
- **SQL Injection Risk**: Direct interpolation of `$longitude` and `$latitude` variables into the raw SQL string `$point` within `scopeNearby()` causes an SQL injection risk if inputs are not pre-validated or cast. Since Eloquent scopes do not guarantee pre-validation, they must use parameters bindings (`?`) or explicit type casting to float.
- **Uniqueness Violations**: The `User` model defines a 1:1 relationship with `Profile` (`$this->hasOne(Profile::class)`). In the `profiles` table schema, there is no `unique` constraint on the `user_id` column. This allows multiple profiles to be created for a single user in the database, causing structural inconsistency.
- **Overdraft/Negative Coin Risk**: The project specifications require `balance_coins >= 500` to respond to an order. However, the database does not enforce `balance_coins >= 0` via a CHECK constraint, leaving the ledger vulnerable to negative balances if concurrency issues or application-level bugs occur.
- **Developer Usability (ActiveRecord Leaks)**: Since there are no mutators for `latitude` and `longitude` on the `Profile` and `Order` models, developers must write raw DB query expressions (e.g. `DB::raw("ST_SetSRID(ST_MakePoint(lng, lat), 4326)::geography")`) to insert or update spatial records. This leaks internal database structures.
- **Test Suite Completeness**: The test suite covers basic functionality but does not verify foreign key constraint cascades, unique constraints (e.g. duplicate response insertions), or potential database exceptions for negative balances.

---

## 3. Caveats
- Static code analysis was performed because command execution is disabled in this environment. Database migrations, scope query execution, and PHPUnit outputs could not be verified dynamically.
- The PostgreSQL/PostGIS environment configuration is assumed to match standard Laravel 11 local defaults.

---

## 4. Conclusion
The database migration schemas, Eloquent models, geolocation scopes, and tests are implemented and functionally correct for standard happy-path operations. However, there are significant security (SQL Injection), data integrity (missing unique constraint on `profiles.user_id`), and validation (missing CHECK constraint on `balance_coins` and range bounds) concerns. 

**Verdict: REQUEST_CHANGES**

Detailed reviews and challenge findings are outlined below.

---

## Quality Review Report

### Review Summary
- **Verdict**: REQUEST_CHANGES

### Findings

#### [Critical] Finding 1: SQL Injection Vulnerability in Geolocation Scopes
- **What**: The `$longitude` and `$latitude` coordinates are directly interpolated into the `$point` SQL string.
- **Where**: `app/Models/Profile.php` (lines 39-46) and `app/Models/Order.php` (lines 49-56).
- **Why**: Direct interpolation in `whereRaw` and `orderByRaw` query fragments allows SQL injection if unvalidated strings are passed to the scope.
- **Suggestion**: Use parameter bindings or explicit float casting inside the scope:
  ```php
  public function scopeNearby($query, $latitude, $longitude, $radiusKm)
  {
      $radiusMeters = (float) $radiusKm * 1000;
      $latitude = (float) $latitude;
      $longitude = (float) $longitude;

      return $query->whereRaw("ST_DWithin(location, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)", [$longitude, $latitude, $radiusMeters])
                   ->orderByRaw("ST_Distance(location, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography)", [$longitude, $latitude]);
  }
  ```

#### [Major] Finding 2: Missing Unique Constraint on `profiles.user_id`
- **What**: The foreign key `user_id` on the `profiles` table is not unique.
- **Where**: `database/migrations/2024_01_01_000002_create_profiles_table.php` (line 14).
- **Why**: Allows duplicate profiles for the same user, violating the 1:1 `hasOne` relation contract.
- **Suggestion**: Change to:
  ```php
  $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
  ```

#### [Major] Finding 3: Missing Database CHECK Constraint on `profiles.balance_coins`
- **What**: No check constraint is defined on `balance_coins` to prevent it from going negative.
- **Where**: `database/migrations/2024_01_01_000002_create_profiles_table.php` (line 22).
- **Why**: The logic requires `balance_coins >= 500` to spend coins. Database-level constraints are necessary to prevent overdrafts.
- **Suggestion**: Add a CHECK constraint in the migration:
  ```php
  DB::statement('ALTER TABLE profiles ADD CONSTRAINT chk_balance_coins CHECK (balance_coins >= 0);');
  ```

#### [Medium] Finding 4: Lack of Latitude/Longitude Setters on Models
- **What**: The models lack mutators to set coordinates, requiring developers to write raw spatial DB queries.
- **Where**: `app/Models/Profile.php` and `app/Models/Order.php`.
- **Why**: Causes high friction and leaks details of spatial columns to business logic.
- **Suggestion**: Add mutators for setting latitude and longitude which automatically update the `location` field before saving.

#### [Minor] Finding 5: Missing Column Range Constraints on Orders Table
- **What**: Numeric columns like `child_age` and `budget` do not have positive constraints, and `date_end` is not constrained to be after `date_start`.
- **Where**: `database/migrations/2024_01_01_000004_create_orders_table.php`.
- **Why**: Allows negative budgets, invalid ages, and negative durations to enter the database.
- **Suggestion**: Add DB CHECK constraints:
  - `CHECK (child_age >= 0)`
  - `CHECK (budget > 0)`
  - `CHECK (date_end >= date_start)`

### Verified Claims
- **Claim: Phone format CHECK constraint matches requirements** &rarr; Verified via static review of `chk_phone_format` constraint regex `^\+77[0-9]{9}$` in `2024_01_01_000001_create_users_table.php` &rarr; PASS
- **Claim: Coordinate hex decoding logic works correctly** &rarr; Verified via trace of `parseWkb` NDR/XDR parsing and SRID offset computation &rarr; PASS
- **Claim: Relationships match PROJECT.md specifications** &rarr; Verified via inspection of relation definitions in all models &rarr; PASS

### Coverage Gaps
- **Test suite bounds validation** &mdash; Risk level: Medium &mdash; Recommendation: Investigate and add verification of unique constraints and invalid budgets/coin balances.

### Unverified Items
- **Actual database behavior on a PostGIS-enabled PostgreSQL instance** &mdash; Reason: Running shell commands is blocked in this headless environment.

---

## Challenge Report (Adversarial Review)

### Challenge Summary
- **Overall risk assessment**: HIGH (due to SQL Injection vulnerability in search endpoints and potential concurrency coin overdrafts).

### Challenges

#### [Critical] Challenge 1: SQL Injection via scopeNearby coordinates
- **Assumption challenged**: Assumed input coordinates passed to `scopeNearby` are always pre-sanitized or cast to float.
- **Attack scenario**: A user hits the `/api/v1/orders/nearby?latitude=43.23&longitude=76.88);--` endpoint. If the controller directly forwards the string from query parameters to `Order::nearby($lat, $lng, $rad)`, the SQL query will be prematurely closed, enabling SQL injection.
- **Blast radius**: Full read/write database compromise.
- **Mitigation**: Bind coordinates using SQL placeholders `?` or cast inputs explicitly to float in the model scope.

#### [High] Challenge 2: Duplicate profiles and race condition coin overdrafts
- **Assumption challenged**: Assumed application logic alone ensures a user has only one profile and that balance deduction never causes overdrafts.
- **Attack scenario**: High concurrency or race conditions during coin deductions (e.g. rapid multiple clicks to respond to orders) can result in a balance dropping below zero. Without a DB unique constraint on `profiles.user_id` and a CHECK constraint on `balance_coins`, duplicate entries and negative balances can occur.
- **Blast radius**: Loss of database structure integrity and ledger correctness.
- **Mitigation**: Add a unique index on `profiles.user_id` and `CHECK (balance_coins >= 0)`.

### Stress Test Results
- **Scenario: Duplicate profile creation** &rarr; Expected: DB rejects &rarr; Predicted: DB accepts &rarr; FAIL
- **Scenario: Coin balance overdraft below 0** &rarr; Expected: DB rejects &rarr; Predicted: DB accepts &rarr; FAIL

---

## 5. Verification Method
1. Install dependencies:
   ```bash
   composer install
   ```
2. Configure database credentials in `.env` and run migrations on local PostgreSQL (with PostGIS enabled):
   ```bash
   php artisan migrate:fresh
   ```
3. Run the test suite:
   ```bash
   php artisan test --filter DatabaseAndGeolocationTest
   ```
4. Verify constraint validation:
   - Try creating two profiles with the same `user_id`. (Expected: `QueryException` or validation error; currently: PASSES).
   - Try saving a profile with `balance_coins = -100`. (Expected: `QueryException`; currently: PASSES).
   - Verify SQL injection resistance by passing a SQL payload string (e.g. `76.88); --`) to `Profile::nearby(43.23, $payload, 5)`. (Expected: Exception or safe evaluation; currently: SQL syntax/injection execution).
