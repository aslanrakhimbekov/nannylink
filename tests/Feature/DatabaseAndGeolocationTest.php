<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Profile;
use App\Models\Document;
use App\Models\Order;
use App\Models\Response;
use App\Models\CoinTransaction;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\UserLanguage;
use App\Enums\DocumentType;
use App\Enums\DocumentStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderPaymentType;
use App\Enums\ResponseStatus;
use App\Enums\CoinTransactionType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseAndGeolocationTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test validation and CHECK constraint on users.phone.
     */
    public function test_phone_check_constraint_valid()
    {
        $user = User::create([
            'phone' => '+77011234567',
            'role' => UserRole::PARENT,
            'status' => UserStatus::ACTIVE,
            'language' => UserLanguage::RU,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'phone' => '+77011234567',
        ]);
    }

    public function test_phone_check_constraint_invalid_prefix()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        User::create([
            'phone' => '87011234567', // invalid prefix, must start with +77
            'role' => UserRole::PARENT,
        ]);
    }

    public function test_phone_check_constraint_invalid_too_short()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        User::create([
            'phone' => '+770112345', // too short (9 digits needed after +77)
            'role' => UserRole::PARENT,
        ]);
    }

    public function test_phone_check_constraint_invalid_too_long()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        User::create([
            'phone' => '+770112345678', // too long
            'role' => UserRole::PARENT,
        ]);
    }

    public function test_phone_check_constraint_invalid_letters()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        User::create([
            'phone' => '+7701abcdefg', // letters not permitted
            'role' => UserRole::PARENT,
        ]);
    }

    /**
     * Test user cannot have multiple profiles (one-to-one uniqueness).
     */
    public function test_user_cannot_have_multiple_profiles()
    {
        $user = User::create([
            'phone' => '+77019999999',
            'role' => UserRole::PARENT,
        ]);

        Profile::create([
            'user_id' => $user->id,
            'first_name' => 'First',
            'last_name' => 'Profile',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        // This must fail because user_id has unique constraint
        Profile::create([
            'user_id' => $user->id,
            'first_name' => 'Second',
            'last_name' => 'Profile',
        ]);
    }

    /**
     * Test database-level CHECK constraints for profiles, orders, and responses.
     */
    public function test_profile_balance_cannot_be_negative()
    {
        $user = User::create([
            'phone' => '+77018888888',
            'role' => UserRole::NANNY,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Profile::create([
            'user_id' => $user->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'balance_coins' => -100, // Negative coins violates chk_balance_coins
        ]);
    }

    public function test_order_child_age_cannot_be_negative()
    {
        $user = User::create(['phone' => '+77017777777', 'role' => UserRole::PARENT]);
        $this->expectException(\Illuminate\Database\QueryException::class);
        Order::create([
            'parent_id' => $user->id,
            'title' => 'Need Nanny',
            'description' => 'Help with child',
            'address_string' => 'Test address',
            'location' => DB::raw("ST_SetSRID(ST_MakePoint(76.889709, 43.238940), 4326)::geography"),
            'child_age' => -1, // Invalid age violates chk_child_age
            'date_start' => now(),
            'date_end' => now()->addHours(4),
            'payment_type' => OrderPaymentType::HOURLY,
            'budget' => 1500,
        ]);
    }

    public function test_order_budget_cannot_be_negative()
    {
        $user = User::create(['phone' => '+77016666666', 'role' => UserRole::PARENT]);
        $this->expectException(\Illuminate\Database\QueryException::class);
        Order::create([
            'parent_id' => $user->id,
            'title' => 'Need Nanny',
            'description' => 'Help with child',
            'address_string' => 'Test address',
            'location' => DB::raw("ST_SetSRID(ST_MakePoint(76.889709, 43.238940), 4326)::geography"),
            'child_age' => 4,
            'date_start' => now(),
            'date_end' => now()->addHours(4),
            'payment_type' => OrderPaymentType::HOURLY,
            'budget' => -500, // Invalid budget violates chk_budget
        ]);
    }

    public function test_order_date_end_cannot_be_before_date_start()
    {
        $user = User::create(['phone' => '+77015555555', 'role' => UserRole::PARENT]);
        $this->expectException(\Illuminate\Database\QueryException::class);
        Order::create([
            'parent_id' => $user->id,
            'title' => 'Need Nanny',
            'description' => 'Help with child',
            'address_string' => 'Test address',
            'location' => DB::raw("ST_SetSRID(ST_MakePoint(76.889709, 43.238940), 4326)::geography"),
            'child_age' => 4,
            'date_start' => now(),
            'date_end' => now()->subHours(2), // End before start violates chk_date_order
            'payment_type' => OrderPaymentType::HOURLY,
            'budget' => 1500,
        ]);
    }

    /**
     * Test spatial coordinate parsing and mutators in Profile and Order models.
     */
    public function test_spatial_coordinates_parsing_and_mutators()
    {
        $user = User::create([
            'phone' => '+77011234567',
            'role' => UserRole::NANNY,
        ]);

        // Test coordinates mutators (setters)
        $profile = new Profile();
        $profile->user_id = $user->id;
        $profile->first_name = 'Jane';
        $profile->last_name = 'Doe';
        $profile->latitude = 43.233519;
        $profile->longitude = 76.909930;
        $profile->save();

        $retrieved = Profile::find($profile->id);
        $this->assertEqualsWithDelta(43.233519, $retrieved->latitude, 0.0001);
        $this->assertEqualsWithDelta(76.909930, $retrieved->longitude, 0.0001);

        $order = new Order();
        $order->parent_id = $user->id;
        $order->title = 'Need Nanny';
        $order->description = 'Help with child';
        $order->address_string = 'Test address';
        $order->child_age = 4;
        $order->date_start = now();
        $order->date_end = now()->addHours(4);
        $order->payment_type = OrderPaymentType::HOURLY;
        $order->budget = 1500;
        $order->latitude = 43.238940;
        $order->longitude = 76.889709;
        $order->save();

        $retrievedOrder = Order::find($order->id);
        $this->assertEqualsWithDelta(43.238940, $retrievedOrder->latitude, 0.0001);
        $this->assertEqualsWithDelta(76.889709, $retrievedOrder->longitude, 0.0001);
    }

    /**
     * Test scopeNearby queries on Profile and Order models.
     */
    public function test_nearby_geosearch_scope()
    {
        // Setup users
        $parent = User::create(['phone' => '+77771111111', 'role' => UserRole::PARENT]);
        $nanny1 = User::create(['phone' => '+77772222222', 'role' => UserRole::NANNY]);
        $nanny2 = User::create(['phone' => '+77773333333', 'role' => UserRole::NANNY]);

        // Reference center (Almaty central area)
        $centerLat = 43.238940;
        $centerLng = 76.889709;

        // Profile 1 is close (~2km away)
        $profileClose = new Profile();
        $profileClose->user_id = $nanny1->id;
        $profileClose->first_name = 'Jane';
        $profileClose->last_name = 'Close';
        $profileClose->latitude = 43.233519;
        $profileClose->longitude = 76.909930;
        $profileClose->save();

        // Profile 2 is far (~7.5km away)
        $profileFar = new Profile();
        $profileFar->user_id = $nanny2->id;
        $profileFar->first_name = 'Jane';
        $profileFar->last_name = 'Far';
        $profileFar->latitude = 43.298574;
        $profileFar->longitude = 76.920251;
        $profileFar->save();

        // Verify Profile scopeNearby
        // 1. Search radius 5km: only close profile should be returned
        $nearbyProfiles5km = Profile::nearby($centerLat, $centerLng, 5)->get();
        $this->assertCount(1, $nearbyProfiles5km);
        $this->assertEquals($profileClose->id, $nearbyProfiles5km[0]->id);

        // 2. Search radius 10km: both returned, sorted by distance (closest first)
        $nearbyProfiles10km = Profile::nearby($centerLat, $centerLng, 10)->get();
        $this->assertCount(2, $nearbyProfiles10km);
        $this->assertEquals($profileClose->id, $nearbyProfiles10km[0]->id);
        $this->assertEquals($profileFar->id, $nearbyProfiles10km[1]->id);

        // Create Orders
        // Order 1 is close (~2km away)
        $orderClose = new Order();
        $orderClose->parent_id = $parent->id;
        $orderClose->title = 'Job Close';
        $orderClose->description = 'Help';
        $orderClose->address_string = 'Close address';
        $orderClose->child_age = 4;
        $orderClose->date_start = now();
        $orderClose->date_end = now()->addHours(4);
        $orderClose->payment_type = OrderPaymentType::HOURLY;
        $orderClose->budget = 1500;
        $orderClose->latitude = 43.233519;
        $orderClose->longitude = 76.909930;
        $orderClose->save();

        // Order 2 is far (~7.5km away)
        $orderFar = new Order();
        $orderFar->parent_id = $parent->id;
        $orderFar->title = 'Job Far';
        $orderFar->description = 'Help far';
        $orderFar->address_string = 'Far address';
        $orderFar->child_age = 4;
        $orderFar->date_start = now();
        $orderFar->date_end = now()->addHours(4);
        $orderFar->payment_type = OrderPaymentType::HOURLY;
        $orderFar->budget = 1500;
        $orderFar->latitude = 43.298574;
        $orderFar->longitude = 76.920251;
        $orderFar->save();

        // Verify Order scopeNearby
        // 1. Search radius 5km: only close order returned
        $nearbyOrders5km = Order::nearby($centerLat, $centerLng, 5)->get();
        $this->assertCount(1, $nearbyOrders5km);
        $this->assertEquals($orderClose->id, $nearbyOrders5km[0]->id);

        // 2. Search radius 10km: both returned, sorted by distance (closest first)
        $nearbyOrders10km = Order::nearby($centerLat, $centerLng, 10)->get();
        $this->assertCount(2, $nearbyOrders10km);
        $this->assertEquals($orderClose->id, $nearbyOrders10km[0]->id);
        $this->assertEquals($orderFar->id, $nearbyOrders10km[1]->id);
    }

    /**
     * Test safe SQL injection handling in geolocation scope inputs.
     */
    public function test_nearby_geosearch_sql_injection_safety()
    {
        $latitudeInjection = "43.238940; SELECT pg_sleep(5);";
        $longitudeInjection = "76.889709 OR 1=1";

        // Query execution should be safe and return 0 results since inputs are securely cast/bound
        $results = Order::nearby($latitudeInjection, $longitudeInjection, 5)->get();
        $this->assertCount(0, $results);
    }

    /**
     * Test CoinTransaction auto-generated UUID concern.
     */
    public function test_coin_transaction_auto_generates_uuid()
    {
        $user = User::create(['phone' => '+77014444444', 'role' => UserRole::PARENT]);
        $transaction = CoinTransaction::create([
            'user_id' => $user->id,
            'type' => CoinTransactionType::DEPOSIT,
            'amount' => 1000,
        ]);

        $this->assertNotNull($transaction->uuid);
        $this->assertTrue(Str::isUuid($transaction->uuid));
    }

    /**
     * Test model relationship definitions.
     */
    public function test_relationships_definition()
    {
        $user = User::create([
            'phone' => '+77777777777',
            'role' => UserRole::PARENT,
        ]);

        $profile = Profile::create([
            'user_id' => $user->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);

        $document = Document::create([
            'profile_id' => $profile->id,
            'type' => DocumentType::CRIMINAL_RECORD,
            'file_path' => '/tmp/doc.pdf',
        ]);

        $order = new Order();
        $order->parent_id = $user->id;
        $order->title = 'Need Nanny';
        $order->description = 'Help with child';
        $order->address_string = 'Test address';
        $order->child_age = 4;
        $order->date_start = now();
        $order->date_end = now()->addHours(4);
        $order->payment_type = OrderPaymentType::HOURLY;
        $order->budget = 1500;
        $order->latitude = 43.238940;
        $order->longitude = 76.889709;
        $order->save();

        $response = Response::create([
            'order_id' => $order->id,
            'nanny_id' => $user->id,
        ]);

        $transaction = CoinTransaction::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'type' => CoinTransactionType::SPEND,
            'amount' => 500,
        ]);

        // Assert relationships
        $this->assertEquals($profile->id, $user->profile->id);
        $this->assertEquals($user->id, $profile->user->id);
        $this->assertEquals($profile->id, $document->profile->id);
        $this->assertCount(1, $profile->documents);
        $this->assertEquals($user->id, $order->parent->id);
        $this->assertCount(1, $user->orders);
        $this->assertEquals($order->id, $response->order->id);
        $this->assertEquals($user->id, $response->nanny->id);
        $this->assertCount(1, $order->responses);
        $this->assertEquals($user->id, $transaction->user->id);
        $this->assertEquals($order->id, $transaction->order->id);
        $this->assertCount(1, $user->coinTransactions);
    }
}
