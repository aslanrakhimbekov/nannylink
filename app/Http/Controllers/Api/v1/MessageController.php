<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Message;

class MessageController extends Controller
{
    /**
     * Get chat messages for a booking.
     */
    public function index(Request $request, $bookingId)
    {
        $user = $request->user();
        $booking = Booking::where(function ($q) use ($user) {
            $q->where('parent_id', $user->id)->orWhere('nanny_id', $user->id);
        })->find($bookingId);

        if (!$booking) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        $messages = Message::where('booking_id', $bookingId)
            ->with(['sender.profile'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }

    /**
     * Send a message in booking chat.
     */
    public function store(Request $request, $bookingId)
    {
        $user = $request->user();
        $booking = Booking::where(function ($q) use ($user) {
            $q->where('parent_id', $user->id)->orWhere('nanny_id', $user->id);
        })->find($bookingId);

        if (!$booking) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $receiverId = ($user->id === $booking->parent_id) ? $booking->nanny_id : $booking->parent_id;

        $message = Message::create([
            'booking_id' => $booking->id,
            'sender_id' => $user->id,
            'receiver_id' => $receiverId,
            'content' => $request->input('content'),
        ]);

        return response()->json($message->load(['sender.profile']), 201);
    }
}
