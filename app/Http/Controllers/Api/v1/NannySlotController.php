<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\NannySlot;
use App\Models\User;
use App\Enums\UserRole;

class NannySlotController extends Controller
{
    public function index(Request $request)
    {
        if ($request->user()->role->value !== 'nanny') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $slots = $request->user()->nannySlots()->orderBy('start_time', 'asc')->get();
        return response()->json($slots);
    }

    public function store(Request $request)
    {
        if ($request->user()->role->value !== 'nanny') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
        ]);

        $slot = NannySlot::create([
            'nanny_id' => $request->user()->id,
            'start_time' => $request->input('start_time'),
            'end_time' => $request->input('end_time'),
            'status' => 'available',
        ]);

        return response()->json($slot, 201);
    }

    public function destroy(Request $request, $id)
    {
        if ($request->user()->role->value !== 'nanny') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $slot = NannySlot::where('nanny_id', $request->user()->id)->find($id);

        if (!$slot) {
            return response()->json(['message' => 'Slot not found.'], 404);
        }

        $slot->delete();
        return response()->json(['status' => 'success', 'message' => 'Slot deleted successfully.']);
    }

    public function nannySlots(Request $request, $id)
    {
        $nanny = User::where('role', UserRole::NANNY)->find($id);
        if (!$nanny) {
            return response()->json(['message' => 'Nanny not found.'], 404);
        }

        $slots = NannySlot::where('nanny_id', $id)
            ->where('status', 'available')
            ->where('start_time', '>', now())
            ->orderBy('start_time', 'asc')
            ->get();

        return response()->json($slots);
    }
}
