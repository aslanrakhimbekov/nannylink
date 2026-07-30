## 2026-07-10T14:39:28Z
You are teamwork_preview_worker. Your working directory is /Users/aslanaslan/.gemini/antigravity/scratch/nannylink/.agents/teamwork_preview_worker_write_tests_batch3.
Your task is to implement the third batch of the E2E test files for NannyLink MVP and configure the test runner:
1. Write `/Users/aslanaslan/.gemini/antigravity/scratch/nannylink/tests/E2E/CrossFeatureE2ETest.php` containing 10 tests (Tier 3: Cross-Feature Integration Tests). Test cases:
   - `test_user_blocking_revokes_tokens_and_cancels_active_orders`
   - `test_language_middleware_switches_translation_on_api_responses`
   - `test_language_preference_saved_on_verification_influences_notifications`
   - `test_nanny_verification_enables_nearby_search_results`
   - `test_nanny_coin_spent_ledger_triggers_low_balance_notification`
   - `test_order_cancellation_refunds_coins_to_responded_nannies`
   - `test_document_deletion_during_verification_updates_profile_status`
   - `test_profile_update_rechecks_location_bounds_and_updates_nearby_search`
   - `test_telegram_auth_links_with_existing_otp_phone_user`
   - `test_s3_storage_cleanup_upon_admin_user_deletion`
2. Write `/Users/aslanaslan/.gemini/antigravity/scratch/nannylink/tests/E2E/RealWorldScenariosE2ETest.php` containing 5 tests (Tier 4: Real-World Scenarios). Test cases:
   - `test_scenario_1_happy_path_hiring_journey`
   - `test_scenario_2_profile_verification_and_egov_parsing_failures`
   - `test_scenario_3_insufficient_balance_and_filament_topup`
   - `test_scenario_4_geolocation_distance_and_sorting_boundary_walk`
   - `test_scenario_5_high_concurrency_response_peak`
3. Write or update `/Users/aslanaslan/.gemini/antigravity/scratch/nannylink/phpunit.xml`. If a phpunit.xml file already exists, edit it to add a `<testsuite name="E2E">` block referencing `<directory suffix="Test.php">./tests/E2E</directory>`. If it does not exist, create it with standard Laravel phpunit configuration settings including the `<testsuite name="E2E">` block.

Make sure the files are fully implemented and valid PHP code. Do not use stubs or empty test cases.

MANDATORY INTEGRITY WARNING:
DO NOT CHEAT. All implementations must be genuine. DO NOT hardcode test results, create dummy/facade implementations, or circumvent the intended task. A Forensic Auditor will independently verify your work. Integrity violations WILL be detected and your work WILL be rejected.
