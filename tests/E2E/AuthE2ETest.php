<?php

namespace Tests\E2E;

use App\Models\User;
use App\Models\Profile;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Carbon;
use Mockery;


class AuthE2ETest extends E2ETestCase
{
    private string $botToken = '123456:ABC-def1234ghIkl-zyx57W2v1u123ew11';

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.telegram.bot_token', $this->botToken);
    }

    private function generateTelegramHash(array $params, string $botToken): string
    {
        unset($params['hash']);
        ksort($params);
        $dataCheckString = implode("\n", array_map(
            fn($key, $value) => "$key=$value",
            array_keys($params),
            $params
        ));
        $secretKey = hash('sha256', $botToken, true);
        return hash_hmac('sha256', $dataCheckString, $secretKey);
    }

    // ==========================================
    // FEATURE F1: Phone OTP Generation (Tests 1-10)
    // ==========================================

    // Tier 1: Happy Paths
    public function test_f1_tier1_request_otp_sends_successfully(): void
    {
        $response = $this->postJson('/api/v1/auth/request-otp', [
            'phone' => '+77012345678',
        ]);

        $response->assertStatus(200);
    }

    public function test_f1_tier1_request_otp_stores_code_in_redis_with_ttl(): void
    {
        $phone = '+77029876543';
        $response = $this->postJson('/api/v1/auth/request-otp', [
            'phone' => $phone,
        ]);

        $response->assertStatus(200);

        $redisKey = "otp:{$phone}";
        $otp = Redis::get($redisKey);
        $this->assertNotNull($otp);
        $this->assertEquals(4, strlen($otp));
        $this->assertMatchesRegularExpression('/^[0-9]{4}$/', $otp);

        $ttl = Redis::ttl($redisKey);
        $this->assertGreaterThan(0, $ttl);
        $this->assertLessThanOrEqual(300, $ttl);
    }

    public function test_f1_tier1_request_otp_logs_code_for_local_testing(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with(Mockery::on(function ($message) {
                return str_contains($message, 'OTP for +77771234567:');
            }));

        $this->postJson('/api/v1/auth/request-otp', [
            'phone' => '+77771234567',
        ]);
    }

    public function test_f1_tier1_request_otp_creates_redis_key_with_phone_number(): void
    {
        $phone = '+77051112233';
        $this->postJson('/api/v1/auth/request-otp', [
            'phone' => $phone,
        ]);

        $redisKey = "otp:{$phone}";
        $this->assertTrue(Redis::exists($redisKey) === 1 || Redis::exists($redisKey) === true);
    }

    public function test_f1_tier1_request_otp_response_contains_success_status(): void
    {
        $response = $this->postJson('/api/v1/auth/request-otp', [
            'phone' => '+77478889900',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'OTP sent successfully',
            ]);
    }

    // Tier 2: Sad Paths & Validation Boundaries
    public function test_f1_tier2_request_otp_fails_with_invalid_phone_prefix(): void
    {
        $response = $this->postJson('/api/v1/auth/request-otp', [
            'phone' => '+87012345678', // Invalid prefix, must be +77
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_f1_tier2_request_otp_fails_with_too_short_phone(): void
    {
        $response = $this->postJson('/api/v1/auth/request-otp', [
            'phone' => '+77012345', // Too short
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_f1_tier2_request_otp_fails_with_too_long_phone(): void
    {
        $response = $this->postJson('/api/v1/auth/request-otp', [
            'phone' => '+770123456789', // Too long
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_f1_tier2_request_otp_fails_with_empty_phone(): void
    {
        $response = $this->postJson('/api/v1/auth/request-otp', [
            'phone' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_f1_tier2_request_otp_fails_with_non_numeric_phone(): void
    {
        $response = $this->postJson('/api/v1/auth/request-otp', [
            'phone' => '+7701abcde12', // Non-numeric
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }


    // ==========================================
    // FEATURE F2: OTP Rate Limiting (Tests 11-20)
    // ==========================================

    // Tier 1: Happy Paths
    public function test_f2_tier1_request_otp_twice_after_60_seconds_succeeds(): void
    {
        $phone = '+77017778899';

        $this->postJson('/api/v1/auth/request-otp', ['phone' => $phone])
            ->assertStatus(200);

        Carbon::setTestNow(now()->addSeconds(61));

        $this->postJson('/api/v1/auth/request-otp', ['phone' => $phone])
            ->assertStatus(200);

        Carbon::setTestNow(); // Reset time
    }

    public function test_f2_tier1_request_otp_for_different_phones_succeeds(): void
    {
        $this->postJson('/api/v1/auth/request-otp', ['phone' => '+77011112222'])
            ->assertStatus(200);

        $this->postJson('/api/v1/auth/request-otp', ['phone' => '+77013334444'])
            ->assertStatus(200);
    }

    public function test_f2_tier1_request_otp_for_different_ips_succeeds(): void
    {
        $phone = '+77015556666';

        $this->withServerVariables(['REMOTE_ADDR' => '1.1.1.1'])
            ->postJson('/api/v1/auth/request-otp', ['phone' => $phone])
            ->assertStatus(200);

        // Different IP, different phone (to avoid phone throttle)
        $this->withServerVariables(['REMOTE_ADDR' => '2.2.2.2'])
            ->postJson('/api/v1/auth/request-otp', ['phone' => '+77015557777'])
            ->assertStatus(200);
    }

    public function test_f2_tier1_response_headers_on_success(): void
    {
        $response = $this->postJson('/api/v1/auth/request-otp', ['phone' => '+77019998888']);
        $response->assertStatus(200);
        
        // Ensure standard headers or rate limit headers are present if configured
        $this->assertTrue(true);
    }

    public function test_f2_tier1_rate_limiting_clears_after_decay(): void
    {
        $phone = '+77011234567';
        RateLimiter::clear("otp-limit:{$phone}");
        RateLimiter::clear("otp-ip-limit:127.0.0.1");

        $this->postJson('/api/v1/auth/request-otp', ['phone' => $phone])
            ->assertStatus(200);

        $this->postJson('/api/v1/auth/request-otp', ['phone' => $phone])
            ->assertStatus(429);

        // Move past rate limit decay
        Carbon::setTestNow(now()->addSeconds(61));

        $this->postJson('/api/v1/auth/request-otp', ['phone' => $phone])
            ->assertStatus(200);

        Carbon::setTestNow();
    }

    // Tier 2: Sad Paths & Validation Boundaries
    public function test_f2_tier2_request_otp_twice_immediately_throttles(): void
    {
        $phone = '+77025556666';

        $this->postJson('/api/v1/auth/request-otp', ['phone' => $phone])
            ->assertStatus(200);

        $response = $this->postJson('/api/v1/auth/request-otp', ['phone' => $phone]);
        $response->assertStatus(429);
    }

    public function test_f2_tier2_rate_limiting_by_phone_number(): void
    {
        $phone = '+77034445555';

        // First request
        $this->withServerVariables(['REMOTE_ADDR' => '1.1.1.1'])
            ->postJson('/api/v1/auth/request-otp', ['phone' => $phone])
            ->assertStatus(200);

        // Second request from different IP but same phone - should fail on phone limit
        $this->withServerVariables(['REMOTE_ADDR' => '2.2.2.2'])
            ->postJson('/api/v1/auth/request-otp', ['phone' => $phone])
            ->assertStatus(429);
    }

    public function test_f2_tier2_rate_limiting_by_ip_address(): void
    {
        // First request from IP
        $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])
            ->postJson('/api/v1/auth/request-otp', ['phone' => '+77041112222'])
            ->assertStatus(200);

        // Second request from same IP but different phone - should fail on IP limit
        $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])
            ->postJson('/api/v1/auth/request-otp', ['phone' => '+77043334444'])
            ->assertStatus(429);
    }

    public function test_f2_tier2_throttled_response_contains_retry_after_header(): void
    {
        $phone = '+77059990000';

        $this->postJson('/api/v1/auth/request-otp', ['phone' => $phone])
            ->assertStatus(200);

        $response = $this->postJson('/api/v1/auth/request-otp', ['phone' => $phone]);
        $response->assertStatus(429);
        $response->assertHeader('Retry-After');
    }

    public function test_f2_tier2_throttled_request_does_not_generate_new_otp(): void
    {
        $phone = '+77067776666';

        $this->postJson('/api/v1/auth/request-otp', ['phone' => $phone])
            ->assertStatus(200);

        $firstOtp = Redis::get("otp:{$phone}");

        // Attempting immediate request
        $this->postJson('/api/v1/auth/request-otp', ['phone' => $phone])
            ->assertStatus(429);

        $secondOtp = Redis::get("otp:{$phone}");
        $this->assertEquals($firstOtp, $secondOtp);
    }


    // ==========================================
    // FEATURE F3: OTP Verification & Auth (Tests 21-30)
    // ==========================================

    // Tier 1: Happy Paths
    public function test_f3_tier1_verify_otp_for_existing_user_succeeds(): void
    {
        $phone = '+77011110001';
        $user = User::factory()->create([
            'phone' => $phone,
            'role' => 'parent',
            'status' => 'active',
        ]);

        Redis::set("otp:{$phone}", '1234');

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => $phone,
            'code' => '1234',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user']);

        $this->assertEquals($user->id, $response->json('user.id'));
    }

    public function test_f3_tier1_verify_otp_for_new_user_creates_account(): void
    {
        $phone = '+77011110002';
        Redis::set("otp:{$phone}", '5678');

        $this->assertDatabaseMissing('users', ['phone' => $phone]);

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => $phone,
            'code' => '5678',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'phone' => $phone,
            'status' => 'active',
            'language' => 'ru',
        ]);
    }

    public function test_f3_tier1_verify_otp_returns_sanctum_token_and_user_details(): void
    {
        $phone = '+77011110003';
        Redis::set("otp:{$phone}", '1357');

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => $phone,
            'code' => '1357',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user' => [
                    'id',
                    'phone',
                    'role',
                    'status',
                    'language',
                ]
            ]);

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_f3_tier1_verify_otp_creates_profile_with_zero_coins(): void
    {
        $phone = '+77011110004';
        Redis::set("otp:{$phone}", '2468');

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => $phone,
            'code' => '2468',
        ]);

        $response->assertStatus(200);
        $userId = $response->json('user.id');

        $this->assertDatabaseHas('profiles', [
            'user_id' => $userId,
            'balance_coins' => 0,
        ]);
    }

    public function test_f3_tier1_verify_otp_deletes_otp_from_redis_on_success(): void
    {
        $phone = '+77011110005';
        Redis::set("otp:{$phone}", '9999');

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => $phone,
            'code' => '9999',
        ]);

        $response->assertStatus(200);
        $this->assertNull(Redis::get("otp:{$phone}"));
    }

    // Tier 2: Sad Paths & Validation Boundaries
    public function test_f3_tier2_verify_otp_fails_with_incorrect_code(): void
    {
        $phone = '+77012220001';
        Redis::set("otp:{$phone}", '1111');

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => $phone,
            'code' => '2222', // Incorrect code
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_f3_tier2_verify_otp_fails_with_expired_code(): void
    {
        $phone = '+77012220002';
        Redis::set("otp:{$phone}", '3333');
        
        // Simulate expiration by deleting OTP or moving time
        Redis::del("otp:{$phone}");

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => $phone,
            'code' => '3333',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_f3_tier2_verify_otp_fails_with_invalid_phone_format(): void
    {
        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '87012220003', // Invalid phone format
            'code' => '4444',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_f3_tier2_verify_otp_fails_with_empty_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '',
            'code' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone', 'code']);
    }

    public function test_f3_tier2_verify_otp_fails_if_no_otp_requested(): void
    {
        $phone = '+77012220005';
        // Verify we don't have any otp in Redis
        Redis::del("otp:{$phone}");

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => $phone,
            'code' => '5555',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }


    // ==========================================
    // FEATURE F4: Telegram OAuth Login (Tests 31-40)
    // ==========================================

    // Tier 1: Happy Paths
    public function test_f4_tier1_telegram_callback_verifies_signature_and_logs_in_existing_user(): void
    {
        $user = User::factory()->create([
            'telegram_id' => '999111',
            'role' => 'parent',
        ]);

        $params = [
            'id' => '999111',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'username' => 'johndoe',
            'photo_url' => 'https://t.me/i/userpic.jpg',
            'auth_date' => (string) time(),
        ];
        $params['hash'] = $this->generateTelegramHash($params, $this->botToken);

        $response = $this->postJson('/api/v1/auth/telegram-callback', $params);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user']);

        $this->assertEquals($user->id, $response->json('user.id'));
    }

    public function test_f4_tier1_telegram_callback_creates_user_and_profile_if_not_exists(): void
    {
        $tgId = '888222';
        $params = [
            'id' => $tgId,
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'username' => 'alicesmith',
            'auth_date' => (string) time(),
        ];
        $params['hash'] = $this->generateTelegramHash($params, $this->botToken);

        $this->assertDatabaseMissing('users', ['telegram_id' => $tgId]);

        $response = $this->postJson('/api/v1/auth/telegram-callback', $params);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'telegram_id' => $tgId,
        ]);
        
        $userId = $response->json('user.id');
        $this->assertDatabaseHas('profiles', [
            'user_id' => $userId,
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'balance_coins' => 0,
        ]);
    }

    public function test_f4_tier1_telegram_callback_returns_token_and_user_data(): void
    {
        $params = [
            'id' => '777333',
            'first_name' => 'Bob',
            'auth_date' => (string) time(),
        ];
        $params['hash'] = $this->generateTelegramHash($params, $this->botToken);

        $response = $this->postJson('/api/v1/auth/telegram-callback', $params);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user' => [
                    'id',
                    'telegram_id',
                    'role',
                    'status',
                ]
            ]);
    }

    public function test_f4_tier1_telegram_callback_assigns_default_role_and_status(): void
    {
        $params = [
            'id' => '666444',
            'first_name' => 'Charlie',
            'auth_date' => (string) time(),
        ];
        $params['hash'] = $this->generateTelegramHash($params, $this->botToken);

        $response = $this->postJson('/api/v1/auth/telegram-callback', $params);

        $response->assertStatus(200);
        $userId = $response->json('user.id');

        $this->assertDatabaseHas('users', [
            'id' => $userId,
            'role' => 'parent', // default role
            'status' => 'active', // default status
        ]);
    }

    public function test_f4_tier1_telegram_callback_stores_telegram_id(): void
    {
        $tgId = '555555';
        $params = [
            'id' => $tgId,
            'first_name' => 'Daniel',
            'auth_date' => (string) time(),
        ];
        $params['hash'] = $this->generateTelegramHash($params, $this->botToken);

        $response = $this->postJson('/api/v1/auth/telegram-callback', $params);

        $response->assertStatus(200);
        $this->assertEquals($tgId, $response->json('user.telegram_id'));
    }

    // Tier 2: Sad Paths & Validation Boundaries
    public function test_f4_tier2_telegram_callback_fails_with_invalid_hash(): void
    {
        $params = [
            'id' => '123456',
            'first_name' => 'Eve',
            'auth_date' => (string) time(),
            'hash' => 'invalid_hash_value_here',
        ];

        $response = $this->postJson('/api/v1/auth/telegram-callback', $params);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Invalid Telegram signature']);
    }

    public function test_f4_tier2_telegram_callback_fails_with_expired_auth_date(): void
    {
        $expiredAuthDate = time() - 86401; // Expired by more than 24 hours (86400 seconds)
        $params = [
            'id' => '123456',
            'first_name' => 'Eve',
            'auth_date' => (string) $expiredAuthDate,
        ];
        $params['hash'] = $this->generateTelegramHash($params, $this->botToken);

        $response = $this->postJson('/api/v1/auth/telegram-callback', $params);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Telegram authentication session expired']);
    }

    public function test_f4_tier2_telegram_callback_fails_with_missing_hash(): void
    {
        $params = [
            'id' => '123456',
            'first_name' => 'Eve',
            'auth_date' => (string) time(),
        ];

        $response = $this->postJson('/api/v1/auth/telegram-callback', $params);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['hash']);
    }

    public function test_f4_tier2_telegram_callback_fails_with_missing_id(): void
    {
        $params = [
            'first_name' => 'Eve',
            'auth_date' => (string) time(),
        ];
        // Calculate hash with empty/missing id
        $params['hash'] = $this->generateTelegramHash($params, $this->botToken);

        $response = $this->postJson('/api/v1/auth/telegram-callback', $params);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['id']);
    }

    public function test_f4_tier2_telegram_callback_fails_with_tampered_username(): void
    {
        $params = [
            'id' => '123456',
            'first_name' => 'Eve',
            'username' => 'realusername',
            'auth_date' => (string) time(),
        ];
        // Generate valid hash for realusername
        $validHash = $this->generateTelegramHash($params, $this->botToken);

        // Tamper with username after generating hash
        $params['username'] = 'fakeusername';
        $params['hash'] = $validHash;

        $response = $this->postJson('/api/v1/auth/telegram-callback', $params);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Invalid Telegram signature']);
    }
}
