## 2026-07-10T14:35:04Z
You are teamwork_preview_worker. Your working directory is /Users/aslanaslan/.gemini/antigravity/scratch/nannylink/.agents/teamwork_preview_worker_write_tests_batch1.
Your task is to:
1. Create the directory `/Users/aslanaslan/.gemini/antigravity/scratch/nannylink/tests/E2E` if it does not exist.
2. Write `/Users/aslanaslan/.gemini/antigravity/scratch/nannylink/tests/E2E/E2ETestCase.php` with the following content:
```php
<?php

namespace Tests\E2E;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redis;

abstract class E2ETestCase extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('s3');

        Redis::select(15);
        Redis::flushdb();
    }
}
```

3. Write `/Users/aslanaslan/.gemini/antigravity/scratch/nannylink/tests/E2E/AuthE2ETest.php` containing 40 tests (5 Tier 1 and 5 Tier 2 tests for each of the 4 Auth features: F1 Phone OTP Generation, F2 OTP Rate Limiting, F3 OTP Verification & Auth, F4 Telegram OAuth Login). Write realistic Laravel test cases making requests to the routes:
   - `POST /api/v1/auth/request-otp`
   - `POST /api/v1/auth/verify-otp`
   - `POST /api/v1/auth/telegram-callback`
   Verify response codes, Redis data, Laravel log entries (using Log fake if appropriate), database user/profile records, and token generation.

4. Write `/Users/aslanaslan/.gemini/antigravity/scratch/nannylink/tests/E2E/OrdersE2ETest.php` containing 20 tests (5 Tier 1 and 5 Tier 2 tests for each of the 2 Orders features: F5 Parent Order Creation, F6 Nearby Order Geosearch). Use routes:
   - `POST /api/v1/orders`
   - `GET /api/v1/orders/nearby`
   Verify coordinate checks, role restrictions, PostGIS coordinates insertion, distance sorting, and search radii.

Make sure the files are fully implemented and valid PHP code. Do not use stubs or empty test cases.

MANDATORY INTEGRITY WARNING:
DO NOT CHEAT. All implementations must be genuine. DO NOT hardcode test results, create dummy/facade implementations, or circumvent the intended task. A Forensic Auditor will independently verify your work. Integrity violations WILL be detected and your work WILL be rejected.
