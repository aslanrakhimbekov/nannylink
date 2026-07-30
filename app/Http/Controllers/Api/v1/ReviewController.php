<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Review;
use App\Models\Booking;
use App\Models\User;
use App\Enums\UserRole;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        if ($request->user()->role->value !== 'parent') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:5|max:1000',
            'compliments' => 'nullable|array',
            'compliments.*' => 'string|max:50',
        ]);

        $booking = Booking::where('parent_id', $request->user()->id)
            ->whereIn('status', ['confirmed', 'completed'])
            ->find($request->input('booking_id'));

        if (!$booking) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'booking_id' => ['Бронирование не найдено или ещё не завершено.']
                ]
            ], 422);
        }

        if (Review::where('booking_id', $booking->id)->exists()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'booking_id' => ['Вы уже оставили отзыв для этого бронирования.']
                ]
            ], 422);
        }

        $review = Review::create([
            'booking_id' => $booking->id,
            'parent_id' => $request->user()->id,
            'nanny_id' => $booking->nanny_id,
            'rating' => $request->input('rating'),
            'comment' => $request->input('comment'),
            'compliments' => $request->input('compliments', []),
        ]);

        return response()->json($review, 201);
    }

    public function nannyReviews(Request $request, $id)
    {
        $nanny = User::where('role', UserRole::NANNY)->find($id);
        if (!$nanny) {
            return response()->json(['message' => 'Nanny not found.'], 404);
        }

        $reviews = Review::where('nanny_id', $id)
            ->with(['parent.profile'])
            ->orderBy('created_at', 'desc')
            ->get();

        $C = 5;
        $m = 4.5;
        $n = $reviews->count();
        $sumRatings = $reviews->sum('rating');
        $bayesianAverage = $n > 0 ? round(($C * $m + $sumRatings) / ($C + $n), 2) : $m;

        $anonymizedReviews = $reviews->map(function ($review) {
            $firstName = $review->parent?->profile?->first_name ?? '';
            $lastName = $review->parent?->profile?->last_name ?? '';
            $initial = mb_substr($firstName, 0, 1) . '. ' . mb_substr($lastName, 0, 1) . '.';

            return [
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'compliments' => $review->compliments ?? [],
                'author' => $initial,
                'created_at' => $review->created_at,
            ];
        });

        return response()->json([
            'average_rating' => $bayesianAverage,
            'total_reviews' => $n,
            'reviews' => $anonymizedReviews,
        ]);
    }
}
