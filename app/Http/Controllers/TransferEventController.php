<?php

namespace App\Http\Controllers;

use App\Models\Transfer_Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TransferEventController extends Controller
{
    
    public function index(int $transferId): JsonResponse
    {
       
        $transfer = \App\Models\Transfer::where('id', $transferId)
            ->where('sender_id', Auth::id())
            ->firstOrFail();

        $events = Transfer_Event::where('transfer_id', $transferId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $events,
        ]);
    }
}

