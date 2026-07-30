## 2026-07-10T14:37:05Z

You are teamwork_preview_worker. Your working directory is /Users/aslanaslan/.gemini/antigravity/scratch/nannylink/.agents/teamwork_preview_worker_write_tests_batch2.
Your task is to implement the second batch of the E2E test files for NannyLink MVP:
1. Write `/Users/aslanaslan/.gemini/antigravity/scratch/nannylink/tests/E2E/ResponsesE2ETest.php` containing 20 tests (5 Tier 1 and 5 Tier 2 tests for F7 Nanny Respond to Order, F8 Concurrency Protection). Write realistic Laravel tests:
   - Make requests to `POST /api/v1/orders/{id}/respond`.
   - Mock profiles balance and verify that responding debits 500 coins and records a spend ledger transaction.
   - For F8 concurrency tests, write simulated concurrent coin debit requests, row locking assertions, and unique response constraint checks.
2. Write `/Users/aslanaslan/.gemini/antigravity/scratch/nannylink/tests/E2E/AdminE2ETest.php` containing 10 tests (5 Tier 1 and 5 Tier 2 tests for F9 Filament Admin Portal). Mock livewire component interactions or HTTP requests to Filament routes `/admin/...`. Verify listing users/profiles, document verification flow, and eGov PDF parsing behavior (mocking/simulating pdf-parser and QR link extraction).
3. Write `/Users/aslanaslan/.gemini/antigravity/scratch/nannylink/tests/E2E/SystemE2ETest.php` containing 10 tests (5 Tier 1 and 5 Tier 2 tests for F10 Background Queue & Storage). Mock S3 bucket storage using Laravel's Storage fake, verify temporary URL generation, test queue notification triggering (using Queue fake if appropriate), and the scheduler task that prunes rejected documents after 30 days (using this->travelTo() or Travel class).

Make sure the files are fully implemented and valid PHP code. Do not use stubs or empty test cases.

MANDATORY INTEGRITY WARNING:
DO NOT CHEAT. All implementations must be genuine. DO NOT hardcode test results, create dummy/facade implementations, or circumvent the intended task. A Forensic Auditor will independently verify your work. Integrity violations WILL be detected and your work WILL be rejected.
