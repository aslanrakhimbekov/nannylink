<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\User;
use App\Models\Profile;
use App\Models\CoinTransaction;
use App\Enums\UserRole;
use App\Enums\CoinTransactionType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function nearbyNannies(Request $request)
    {
        if ($request->user()->role->value !== 'parent') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius_km' => 'nullable|numeric|min:0.001',
            'language' => 'nullable|string',
            'skill' => 'nullable|string',
            'max_hourly_rate' => 'nullable|integer|min:0',
            'min_rating' => 'nullable|numeric|min:1|max:5',
        ]);

        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');
        $radiusKm = $request->input('radius_km', 5);

        $query = Profile::nearby($latitude, $longitude, $radiusKm)
            ->where('is_verified', true)
            ->where('is_active', true)
            ->with('user');

        if ($request->has('max_hourly_rate') && $request->input('max_hourly_rate')) {
            $query->where('hourly_rate', '<=', (int) $request->input('max_hourly_rate'));
        }

        $profiles = $query->get();

        // Perform in-memory filtering for json arrays & custom rating attribute if requested
        if ($request->has('language') && $request->input('language')) {
            $lang = $request->input('language');
            $profiles = $profiles->filter(function ($profile) use ($lang) {
                return is_array($profile->languages) && in_array($lang, $profile->languages);
            });
        }

        if ($request->has('skill') && $request->input('skill')) {
            $sk = $request->input('skill');
            $profiles = $profiles->filter(function ($profile) use ($sk) {
                return is_array($profile->skills) && in_array($sk, $profile->skills);
            });
        }

        if ($request->has('min_rating') && $request->input('min_rating')) {
            $minRating = (float) $request->input('min_rating');
            $profiles = $profiles->filter(function ($profile) use ($minRating) {
                return $profile->average_rating >= $minRating;
            });
        }

        return response()->json($profiles->values());
    }

    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role->value === 'parent') {
            $bookings = Booking::where('parent_id', $user->id)
                ->with(['nanny.profile'])
                ->orderBy('created_at', 'desc')
                ->get();
        } else if ($user->role->value === 'nanny') {
            $bookings = Booking::where('nanny_id', $user->id)
                ->with(['parent.profile'])
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return response()->json($bookings);
    }

    public function store(Request $request)
    {
        if ($request->user()->role->value !== 'parent') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'nanny_id' => 'required|exists:users,id',
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
            'address_string' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $nannyId = $request->input('nanny_id');
        $nanny = User::find($nannyId);

        if ($nanny->role->value !== 'nanny' || !$nanny->profile || !$nanny->profile->is_verified) {
            return response()->json(['message' => 'Selected nanny is not available or verified.'], 422);
        }

        $startTime = Carbon::parse($request->input('start_time'));
        $endTime = Carbon::parse($request->input('end_time'));

        // Rule 1: Min duration 2 hours (120 minutes)
        if ($startTime->diffInMinutes($endTime) < 120) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'end_time' => ['Минимальная длительность бронирования составляет 2 часа.']
                ]
            ], 422);
        }

        // Rule 2: 30 minutes buffer overlap check
        $startWithBuffer = (clone $startTime)->subMinutes(30);
        $endWithBuffer = (clone $endTime)->addMinutes(30);

        $overlapExists = Booking::where('nanny_id', $nannyId)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where(function ($query) use ($startWithBuffer, $endWithBuffer) {
                $query->where('start_time', '<', $endWithBuffer)
                      ->where('end_time', '>', $startWithBuffer);
            })
            ->exists();

        if ($overlapExists) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'start_time' => ['Выбранное время пересекается с существующим бронированием или техническим буфером в 30 минут.']
                ]
            ], 422);
        }

        // Calculate total price based on nanny's effective hourly rate (discounted by 500 ₸ for new nannies)
        $effectiveHourlyRate = $nanny->profile->effective_hourly_rate ?? 0;
        $hours = $startTime->diffInMinutes($endTime) / 60.0;
        $totalPrice = (int) ceil($hours * $effectiveHourlyRate);

        $booking = Booking::create([
            'parent_id' => $request->user()->id,
            'nanny_id' => $nannyId,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => 'pending',
            'total_price' => $totalPrice,
            'address_string' => $request->input('address_string'),
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
        ]);

        return response()->json($booking, 201);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        if ($booking->parent_id !== $user->id && $booking->nanny_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // If confirmed, return full profile contact details. Otherwise, mask/hide phone and telegram.
        $isConfirmed = $booking->status === 'confirmed';
        
        $nannyData = User::with('profile')->find($booking->nanny_id);
        $parentData = User::with('profile')->find($booking->parent_id);

        $response = $booking->toArray();

        if ($user->role->value === 'parent') {
            $response['nanny'] = [
                'id' => $nannyData->id,
                'profile' => $nannyData->profile,
                'phone' => $isConfirmed ? $nannyData->phone : null,
                'telegram_username' => $isConfirmed ? $nannyData->telegram_username : null,
            ];
        } else {
            $response['parent'] = [
                'id' => $parentData->id,
                'profile' => $parentData->profile,
                'phone' => $isConfirmed ? $parentData->phone : null,
                'telegram_username' => $isConfirmed ? $parentData->telegram_username : null,
            ];
        }

        return response()->json($response);
    }

    public function confirm(Request $request, $id)
    {
        if ($request->user()->role->value !== 'nanny') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return DB::transaction(function () use ($request, $id) {
            $booking = Booking::where('nanny_id', $request->user()->id)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->find($id);

            if (!$booking) {
                return response()->json(['message' => 'Booking not found or not in pending state.'], 404);
            }

            // Lock nanny's profile row for double-spend protection
            $profile = Profile::where('user_id', $request->user()->id)
                ->lockForUpdate()
                ->first();

            // First 3 bookings are fee exempt (0 coins). Standard fee is 500 coins.
            $isFeeExempt = $profile->is_new_nanny;
            $requiredCoins = $isFeeExempt ? 0 : 500;

            if (!$isFeeExempt && $profile->balance_coins < $requiredCoins) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => [
                        'balance_coins' => ['Недостаточно монет для подтверждения заказа.']
                    ]
                ], 422);
            }

            if ($requiredCoins > 0) {
                // Deduct coins
                $profile->decrement('balance_coins', $requiredCoins);

                // Record transaction log
                CoinTransaction::create([
                    'user_id' => $request->user()->id,
                    'booking_id' => $booking->id,
                    'type' => CoinTransactionType::SPEND,
                    'amount' => $requiredCoins,
                ]);
            }

            // Set slot booked
            $booking->status = 'confirmed';
            $booking->save();

            // Mark overlapping nanny slots as 'booked'
            \App\Models\NannySlot::where('nanny_id', $booking->nanny_id)
                ->where('status', 'available')
                ->where('start_time', '<', $booking->end_time)
                ->where('end_time', '>', $booking->start_time)
                ->update(['status' => 'booked']);
            return response()->json([
                'status' => 'success',
                'message' => 'Booking confirmed successfully.',
                'parent_phone' => $booking->parent->phone,
                'parent_name' => $booking->parent->profile->first_name . ' ' . $booking->parent->profile->last_name,
                'parent_telegram_username' => $booking->parent->telegram_username,
            ]);
        });
    }

    public function reject(Request $request, $id)
    {
        if ($request->user()->role->value !== 'nanny') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $booking = Booking::where('nanny_id', $request->user()->id)
            ->where('status', 'pending')
            ->find($id);

        if (!$booking) {
            return response()->json(['message' => 'Booking not found or not in pending state.'], 404);
        }

        $booking->status = 'rejected';
        $booking->save();

        return response()->json(['status' => 'success', 'message' => 'Booking rejected successfully.']);
    }

    public function cancel(Request $request, $id)
    {
        if ($request->user()->role->value !== 'parent') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return DB::transaction(function () use ($request, $id) {
            $booking = Booking::where('parent_id', $request->user()->id)
                ->whereIn('status', ['pending', 'confirmed'])
                ->lockForUpdate()
                ->find($id);

            if (!$booking) {
                return response()->json(['message' => 'Booking not found or cannot be cancelled.'], 404);
            }

            $wasConfirmed = $booking->status === 'confirmed';

            // Require cancellation comment for confirmed bookings
            if ($wasConfirmed) {
                $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                    'cancellation_comment' => 'required|string|min:10|max:1000',
                ]);
                if ($validator->fails()) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => $validator->errors(),
                    ], 422);
                }
            }

            // If it was already confirmed, we refund the 500 coins to the Nanny
            if ($wasConfirmed) {
                $nannyProfile = Profile::where('user_id', $booking->nanny_id)
                    ->lockForUpdate()
                    ->first();

                $nannyProfile->increment('balance_coins', 500);

                CoinTransaction::create([
                    'user_id' => $booking->nanny_id,
                    'booking_id' => $booking->id,
                    'type' => CoinTransactionType::REFUND,
                    'amount' => 500,
                ]);

                // Restore overlapping nanny slots back to 'available'
                \App\Models\NannySlot::where('nanny_id', $booking->nanny_id)
                    ->where('status', 'booked')
                    ->where('start_time', '<', $booking->end_time)
                    ->where('end_time', '>', $booking->start_time)
                    ->update(['status' => 'available']);
            }

            if ($request->has('cancellation_comment')) {
                $booking->cancellation_comment = $request->input('cancellation_comment');
            }

            $booking->status = 'cancelled';
            $booking->save();

            return response()->json(['status' => 'success', 'message' => 'Booking cancelled successfully.']);
        });
    }

    public function deposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|integer|min:500|max:10000',
        ]);

        if ($request->user()->role->value !== 'nanny') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return DB::transaction(function () use ($request) {
            $profile = Profile::where('user_id', $request->user()->id)
                ->lockForUpdate()
                ->first();

            $amount = (int) $request->input('amount');
            $profile->increment('balance_coins', $amount);

            CoinTransaction::create([
                'user_id' => $request->user()->id,
                'type' => CoinTransactionType::DEPOSIT,
                'amount' => $amount,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Balance topped up successfully.',
                'user' => $request->user()->fresh(['profile.documents', 'coinTransactions']),
            ]);
        });
    }
}
