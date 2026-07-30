<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Escrow;
use Illuminate\Support\Facades\DB;

class EscrowController extends Controller
{
    /**
     * Process mock payment and hold funds in Escrow.
     */
    public function pay(Request $request, $id)
    {
        if ($request->user()->role->value !== 'parent') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $booking = Booking::where('parent_id', $request->user()->id)->find($id);

        if (!$booking) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        $paymentMethod = $request->input('payment_method', 'kaspi_qr_mock');

        $escrow = Escrow::updateOrCreate(
            ['booking_id' => $booking->id],
            [
                'parent_id' => $booking->parent_id,
                'nanny_id' => $booking->nanny_id,
                'amount' => $booking->total_price,
                'status' => 'held',
                'payment_method' => $paymentMethod,
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Payment held in Escrow successfully.',
            'escrow' => $escrow,
        ]);
    }

    /**
     * Complete booking & release Escrow funds to nanny.
     */
    public function complete(Request $request, $id)
    {
        $user = $request->user();
        $booking = Booking::where(function ($q) use ($user) {
            $q->where('parent_id', $user->id)->orWhere('nanny_id', $user->id);
        })->find($id);

        if (!$booking) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        return DB::transaction(function () use ($booking) {
            $escrow = Escrow::where('booking_id', $booking->id)->lockForUpdate()->first();

            if ($escrow && $escrow->status === 'held') {
                $escrow->status = 'released';
                $escrow->save();
            }

            $booking->status = 'completed';
            $booking->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Booking completed and funds released from Escrow.',
                'escrow' => $escrow,
            ]);
        });
    }
}
