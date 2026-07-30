<?php

namespace Tests\E2E;

use App\Models\User;
use App\Models\Profile;
use App\Models\NannySlot;
use App\Models\Booking;
use App\Models\CoinTransaction;
use App\Enums\CoinTransactionType;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Carbon;

class BookingsE2ETest extends E2ETestCase
{
    private function createNanny(bool $verified = true, int $coins = 1000): User
    {
        $nanny = User::factory()->create([
            'role' => 'nanny',
            'status' => 'active',
        ]);
        Profile::factory()->create([
            'user_id' => $nanny->id,
            'is_verified' => $verified,
            'balance_coins' => $coins,
            'hourly_rate' => 2000,
        ]);
        return $nanny;
    }

    private function createParent(): User
    {
        $parent = User::factory()->create([
            'role' => 'parent',
            'status' => 'active',
        ]);
        Profile::factory()->create([
            'user_id' => $parent->id,
        ]);
        return $parent;
    }

    public function test_nanny_can_manage_availability_slots(): void
    {
        $nanny = $this->createNanny();
        Sanctum::actingAs($nanny);

        // 1. Create slot
        $response = $this->postJson('/api/v1/nanny/slots', [
            'start_time' => now()->addHours(2)->toIso8601String(),
            'end_time' => now()->addHours(6)->toIso8601String(),
        ]);
        $response->assertStatus(201);
        $slotId = $response->json('id');

        // 2. Index slots
        $response = $this->getJson('/api/v1/nanny/slots');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json());

        // 3. Delete slot
        $response = $this->deleteJson("/api/v1/nanny/slots/{$slotId}");
        $response->assertStatus(200);

        // Verify count is 0
        $this->assertDatabaseMissing('nanny_slots', ['id' => $slotId]);
    }

    public function test_parent_cannot_book_slot_less_than_2_hours(): void
    {
        $nanny = $this->createNanny();
        $parent = $this->createParent();
        Sanctum::actingAs($parent);

        $response = $this->postJson('/api/v1/bookings', [
            'nanny_id' => $nanny->id,
            'start_time' => now()->addHours(2)->toIso8601String(),
            'end_time' => now()->addHours(2)->addMinutes(30)->toIso8601String(), // 30 minutes (invalid)
            'address_string' => 'Almaty, Abaya 10',
            'latitude' => 43.238949,
            'longitude' => 76.889709,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['end_time']);
    }

    public function test_booking_fails_on_overlapping_buffer_window(): void
    {
        $nanny = $this->createNanny();
        $parent = $this->createParent();

        // Create an existing booking for nanny
        Booking::create([
            'parent_id' => $parent->id,
            'nanny_id' => $nanny->id,
            'start_time' => now()->addHours(2),
            'end_time' => now()->addHours(4),
            'status' => 'confirmed',
            'address_string' => 'Test',
            'latitude' => 43.238949,
            'longitude' => 76.889709,
        ]);

        Sanctum::actingAs($parent);

        // Try booking overlapping the buffer window (e.g. starting 15 mins after previous booking ends)
        $response = $this->postJson('/api/v1/bookings', [
            'nanny_id' => $nanny->id,
            'start_time' => now()->addHours(4)->addMinutes(15)->toIso8601String(), // Collides with 30-min buffer of previous booking (ends at 4)
            'end_time' => now()->addHours(6)->addMinutes(15)->toIso8601String(),
            'address_string' => 'Almaty, Abaya 10',
            'latitude' => 43.238949,
            'longitude' => 76.889709,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['start_time']);
    }

    public function test_booking_confirmation_deducts_500_coins_from_nanny(): void
    {
        $nanny = $this->createNanny(true, 1000); // 1000 coins
        $parent = $this->createParent();

        // Simulate 3 existing confirmed bookings so nanny is no longer fee exempt
        for ($i = 0; $i < 3; $i++) {
            Booking::create([
                'parent_id' => $parent->id,
                'nanny_id' => $nanny->id,
                'start_time' => now()->subDays($i + 1),
                'end_time' => now()->subDays($i + 1)->addHours(3),
                'status' => 'confirmed',
                'address_string' => 'Old Test',
                'latitude' => 43.238949,
                'longitude' => 76.889709,
            ]);
        }

        $booking = Booking::create([
            'parent_id' => $parent->id,
            'nanny_id' => $nanny->id,
            'start_time' => now()->addHours(2),
            'end_time' => now()->addHours(5),
            'status' => 'pending',
            'address_string' => 'Test',
            'latitude' => 43.238949,
            'longitude' => 76.889709,
        ]);

        Sanctum::actingAs($nanny);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/confirm");
        $response->assertStatus(200);

        // Check coins deducted (500 coins for 4th booking)
        $this->assertEquals(500, $nanny->profile->fresh()->balance_coins);

        // Check spend transaction log recorded
        $this->assertDatabaseHas('coin_transactions', [
            'user_id' => $nanny->id,
            'booking_id' => $booking->id,
            'type' => 'spend',
            'amount' => 500,
        ]);
    }

    public function test_booking_confirmation_fails_if_insufficient_coins(): void
    {
        $nanny = $this->createNanny(true, 400); // Only 400 coins (needs 500)
        $parent = $this->createParent();

        // Simulate 3 existing confirmed bookings so nanny is no longer fee exempt
        for ($i = 0; $i < 3; $i++) {
            Booking::create([
                'parent_id' => $parent->id,
                'nanny_id' => $nanny->id,
                'start_time' => now()->subDays($i + 1),
                'end_time' => now()->subDays($i + 1)->addHours(3),
                'status' => 'confirmed',
                'address_string' => 'Old Test',
                'latitude' => 43.238949,
                'longitude' => 76.889709,
            ]);
        }

        $booking = Booking::create([
            'parent_id' => $parent->id,
            'nanny_id' => $nanny->id,
            'start_time' => now()->addHours(2),
            'end_time' => now()->addHours(5),
            'status' => 'pending',
            'address_string' => 'Test',
            'latitude' => 43.238949,
            'longitude' => 76.889709,
        ]);

        Sanctum::actingAs($nanny);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/confirm");
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['balance_coins']);
    }

    public function test_parent_booking_cancellation_refunds_coins_to_nanny_if_confirmed(): void
    {
        $nanny = $this->createNanny(true, 500);
        $parent = $this->createParent();

        $booking = Booking::create([
            'parent_id' => $parent->id,
            'nanny_id' => $nanny->id,
            'start_time' => now()->addHours(2),
            'end_time' => now()->addHours(5),
            'status' => 'confirmed',
            'address_string' => 'Test',
            'latitude' => 43.238949,
            'longitude' => 76.889709,
        ]);

        Sanctum::actingAs($parent);

        // Fails without comment when confirmed
        $response = $this->postJson("/api/v1/bookings/{$booking->id}/cancel");
        $response->assertStatus(422);

        // Succeeds with comment
        $response = $this->postJson("/api/v1/bookings/{$booking->id}/cancel", [
            'cancellation_comment' => 'This is a valid cancellation comment because plans changed.',
        ]);
        $response->assertStatus(200);

        $this->assertEquals('This is a valid cancellation comment because plans changed.', $booking->fresh()->cancellation_comment);

        // Nanny coins refunded back to 1000
        $this->assertEquals(1000, $nanny->profile->fresh()->balance_coins);

        // Refund transaction log recorded
        $this->assertDatabaseHas('coin_transactions', [
            'user_id' => $nanny->id,
            'booking_id' => $booking->id,
            'type' => 'refund',
            'amount' => 500,
        ]);
    }

    public function test_nanny_can_deposit_coins(): void
    {
        $nanny = $this->createNanny(true, 1000);

        Sanctum::actingAs($nanny);

        $response = $this->postJson('/api/v1/nanny/balance/deposit', [
            'amount' => 2000,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');

        // Check profile updated
        $this->assertEquals(3000, $nanny->profile->fresh()->balance_coins);

        // Check transaction recorded
        $this->assertDatabaseHas('coin_transactions', [
            'user_id' => $nanny->id,
            'type' => 'deposit',
            'amount' => 2000,
        ]);
    }

    public function test_confirmed_booking_blocks_and_cancelling_restores_slots(): void
    {
        $nanny = $this->createNanny(true, 1000);
        $parent = $this->createParent();

        // Create nanny slot
        $slot = NannySlot::create([
            'nanny_id' => $nanny->id,
            'start_time' => now()->addHours(2)->toIso8601String(),
            'end_time' => now()->addHours(6)->toIso8601String(),
            'status' => 'available',
        ]);

        $booking = Booking::create([
            'parent_id' => $parent->id,
            'nanny_id' => $nanny->id,
            'start_time' => now()->addHours(3)->toIso8601String(),
            'end_time' => now()->addHours(5)->toIso8601String(),
            'status' => 'pending',
            'address_string' => 'Abaya 100',
            'latitude' => 43.238949,
            'longitude' => 76.889709,
        ]);

        Sanctum::actingAs($nanny);

        // Confirm booking
        $response = $this->postJson("/api/v1/bookings/{$booking->id}/confirm");
        $response->assertStatus(200);

        // The slot should be marked as booked
        $this->assertEquals('booked', $slot->fresh()->status);

        // Public nanny slots list should be empty
        Sanctum::actingAs($parent);
        $response = $this->getJson("/api/v1/nannies/{$nanny->id}/slots");
        $response->assertStatus(200);
        $this->assertCount(0, $response->json());

        // Cancel booking
        $response = $this->postJson("/api/v1/bookings/{$booking->id}/cancel", [
            'cancellation_comment' => 'Need to cancel this booking because of emergency.',
        ]);
        $response->assertStatus(200);

        // The slot should be restored to available
        $this->assertEquals('available', $slot->fresh()->status);

        // Public nanny slots list should have it again
        $response = $this->getJson("/api/v1/nannies/{$nanny->id}/slots");
        $response->assertStatus(200);
        $this->assertCount(1, $response->json());
    }

    public function test_reviews_system_and_bayesian_rating_formula(): void
    {
        $nanny = $this->createNanny(true, 1000);
        $parent = $this->createParent();

        $booking = Booking::create([
            'parent_id' => $parent->id,
            'nanny_id' => $nanny->id,
            'start_time' => now()->addHours(2),
            'end_time' => now()->addHours(5),
            'status' => 'confirmed',
            'address_string' => 'Test Address',
            'latitude' => 43.238949,
            'longitude' => 76.889709,
        ]);

        Sanctum::actingAs($parent);

        // Post a review
        $response = $this->postJson('/api/v1/reviews', [
            'booking_id' => $booking->id,
            'rating' => 5,
            'comment' => 'Amazing nanny!',
        ]);
        $response->assertStatus(201);

        // Duplicate review should fail
        $response = $this->postJson('/api/v1/reviews', [
            'booking_id' => $booking->id,
            'rating' => 4,
            'comment' => 'Another comment',
        ]);
        $response->assertStatus(422);

        // Check nanny reviews list and Bayesian average rating
        // Formula: R = (C * m + sum(ri)) / (C + n)
        // C = 5, m = 4.5, sum(ri) = 5, n = 1
        // R = (5 * 4.5 + 5) / (5 + 1) = (22.5 + 5) / 6 = 27.5 / 6 = 4.58
        $response = $this->getJson("/api/v1/nannies/{$nanny->id}/reviews");
        $response->assertStatus(200);
        $response->assertJsonPath('average_rating', 4.58);
        $response->assertJsonPath('total_reviews', 1);
        $response->assertJsonStructure([
            'average_rating',
            'total_reviews',
            'reviews' => [
                '*' => [
                    'id',
                    'rating',
                    'comment',
                    'author',
                    'created_at',
                ]
            ]
        ]);

        // Review author must be anonymized (first letter of first and last name)
        // Parent factory first name and last name.
        $firstName = $parent->profile->first_name;
        $lastName = $parent->profile->last_name;
        $expectedAuthor = mb_substr($firstName, 0, 1) . '. ' . mb_substr($lastName, 0, 1) . '.';
        $response->assertJsonPath('reviews.0.author', $expectedAuthor);
    }

    public function test_escrow_payment_and_completion_flow(): void
    {
        $nanny = $this->createNanny(true, 1000);
        $parent = $this->createParent();

        $booking = Booking::create([
            'parent_id' => $parent->id,
            'nanny_id' => $nanny->id,
            'start_time' => now()->addHours(2),
            'end_time' => now()->addHours(5),
            'status' => 'confirmed',
            'total_price' => 7500,
            'address_string' => 'Test Address',
            'latitude' => 43.238949,
            'longitude' => 76.889709,
        ]);

        // Parent pays and holds funds in Escrow
        Sanctum::actingAs($parent);
        $response = $this->postJson("/api/v1/bookings/{$booking->id}/pay", [
            'payment_method' => 'kaspi_qr_mock',
        ]);
        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('escrows', [
            'booking_id' => $booking->id,
            'amount' => 7500,
            'status' => 'held',
            'payment_method' => 'kaspi_qr_mock',
        ]);

        // Nanny completes booking and releases funds
        Sanctum::actingAs($nanny);
        $response = $this->postJson("/api/v1/bookings/{$booking->id}/complete");
        $response->assertStatus(200);

        $this->assertEquals('completed', $booking->fresh()->status);
        $this->assertDatabaseHas('escrows', [
            'booking_id' => $booking->id,
            'status' => 'released',
        ]);
    }

    public function test_in_app_chat_and_praise_compliments(): void
    {
        $nanny = $this->createNanny(true, 1000);
        $parent = $this->createParent();

        $booking = Booking::create([
            'parent_id' => $parent->id,
            'nanny_id' => $nanny->id,
            'start_time' => now()->addHours(2),
            'end_time' => now()->addHours(5),
            'status' => 'completed',
            'total_price' => 7500,
            'address_string' => 'Test Address',
            'latitude' => 43.238949,
            'longitude' => 76.889709,
        ]);

        // Parent sends chat message
        Sanctum::actingAs($parent);
        $response = $this->postJson("/api/v1/bookings/{$booking->id}/messages", [
            'content' => 'Здравствуйте! Вы уже подъехали к дому?',
        ]);
        $response->assertStatus(201);
        $response->assertJsonPath('content', 'Здравствуйте! Вы уже подъехали к дому?');

        // Nanny reads messages and replies
        Sanctum::actingAs($nanny);
        $response = $this->getJson("/api/v1/bookings/{$booking->id}/messages");
        $response->assertStatus(200);
        $this->assertCount(1, $response->json());

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/messages", [
            'content' => 'Да, подхожу к подъезду.',
        ]);
        $response->assertStatus(201);

        // Parent posts review with praise compliments
        Sanctum::actingAs($parent);
        $response = $this->postJson('/api/v1/reviews', [
            'booking_id' => $booking->id,
            'rating' => 5,
            'comment' => 'Отличная няня, очень рекомендую!',
            'compliments' => ['punctual', 'finds_common_ground'],
        ]);
        $response->assertStatus(201);

        // Verify profile compliments summary accessor
        $nannyProfile = $nanny->profile->fresh();
        $this->assertEquals(['punctual' => 1, 'finds_common_ground' => 1], $nannyProfile->compliments_summary);
    }

    public function test_new_nanny_first_3_bookings_fee_exempt_and_discounted(): void
    {
        $newNanny = $this->createNanny(true, 0); // 0 coins balance
        $newNanny->profile->update(['hourly_rate' => 2000]);

        $parent = $this->createParent();
        Sanctum::actingAs($parent);

        // Store booking (2 hours: rate 2000 - 500 = 1500 * 2 = 3000 KZT)
        $bookingResp = $this->postJson('/api/v1/bookings', [
            'nanny_id' => $newNanny->id,
            'start_time' => now()->addHours(2)->toDateTimeString(),
            'end_time' => now()->addHours(4)->toDateTimeString(),
            'address_string' => 'Test Promo Address',
            'latitude' => 43.238949,
            'longitude' => 76.889709,
        ]);
        $bookingResp->assertStatus(201);
        $bookingResp->assertJsonPath('total_price', 3000);

        $bookingId = $bookingResp->json('id');

        // Nanny confirms booking with 0 balance (Fee exempt: 0 coins)
        Sanctum::actingAs($newNanny);
        $confirmResp = $this->postJson("/api/v1/bookings/{$bookingId}/confirm");
        $confirmResp->assertStatus(200);
        $this->assertEquals(0, $newNanny->profile->fresh()->balance_coins);
    }
}
