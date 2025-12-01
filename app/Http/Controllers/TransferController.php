<?php

namespace App\Http\Controllers;

use App\Models\Transfer;
use App\Models\Beneficiary;
use App\Models\Transfer_Fee;
use App\Models\Transfer_Event;
use App\Models\User;
use App\Services\ExchangeRateService;
use App\Services\TransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/*
 Transfer Statuses:
 - queued: Transfer created but payment not yet made
 - paid: Payment authorized but not captured
 - in_progress: Payment captured, transfer being processed
 - available_for_pickup: Ready for beneficiary to collect (if agent-based)
 - completed: Transfer successfully completed
 - failed: Transfer failed
 - refunded: Transfer was refunded
 - disputed: Transfer is under dispute
 */
class TransferController extends Controller
{
    public function __construct(
        private ExchangeRateService $exchangeRateService,
        private TransferService $transferService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Transfer::where('sender_id', Auth::id())
            ->with(['beneficiary.country', 'beneficiary.method', 'events', 'payment']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('initiated_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('initiated_at', '<=', $request->to_date);
        }

        $transfers = $query->orderBy('initiated_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $transfers,
        ]);
    }

    // Get transfer summary before creating
    public function summary(Request $request): JsonResponse
    {
        // Validate the input data
        $request->validate([
            'beneficiary_id' => ['required', 'integer', 'exists:beneficiaries,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'currency_from' => ['required', 'string', 'size:3', 'exists:currencies,code'],
            'currency_to' => ['required', 'string', 'size:3', 'exists:currencies,code'],
        ]);

       
        $beneficiary = Beneficiary::where('id', $request->beneficiary_id)
            ->where('user_id', Auth::id())
            ->with(['country', 'method'])
            ->firstOrFail();

      
        $exchangeRate = $this->exchangeRateService->getRate(
            $request->currency_from,
            $request->currency_to
        );

        if ($exchangeRate === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch exchange rate',
            ], 400);
        }

        // Get sender's country (default to 1 if not found in verification)
        $senderCountryId = 1;

      
        $fee = $this->transferService->calculateFee(
            (float) $request->amount,
            $senderCountryId,
            $beneficiary->country_id
        );

       
        $amount = (float) $request->amount;
        $totalAmount = $amount + $fee;
        $recipientAmount = $amount * $exchangeRate;

        return response()->json([
            'success' => true,
            'data' => [
                'beneficiary' => [
                    'id' => $beneficiary->id,
                    'full_name' => $beneficiary->full_name,
                    'country' => $beneficiary->country,
                    'method' => $beneficiary->method,
                ],
                'amount' => [
                    'send' => $amount,
                    'currency_from' => $request->currency_from,
                    'receive' => round($recipientAmount, 2),
                    'currency_to' => $request->currency_to,
                ],
                'exchange_rate' => $exchangeRate,
                'fee' => round($fee, 2),
                'total_amount' => round($totalAmount, 2),
                'breakdown' => [
                    'transfer_amount' => $amount,
                    'fee' => round($fee, 2),
                    'total_to_pay' => round($totalAmount, 2),
                    'recipient_receives' => round($recipientAmount, 2),
                ],
            ],
        ]);
    }

    /*
     Request Body:
     {
       "beneficiary_id": 1,
       "amount": 1000,
       "currency_from": "USD",
       "currency_to": "EUR"
     }
     */
    public function store(Request $request): JsonResponse
    {
        
        $request->validate([
            'beneficiary_id' => ['required', 'integer', 'exists:beneficiaries,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'currency_from' => ['required', 'string', 'size:3', 'exists:currencies,code'],
            'currency_to' => ['required', 'string', 'size:3', 'exists:currencies,code'],
        ]);

      
        $transfer = $this->transferService->createTransfer([
            'sender_id' => Auth::id(),
            'beneficiary_id' => $request->beneficiary_id,
            'amount' => $request->amount,
            'currency_from' => $request->currency_from,
            'currency_to' => $request->currency_to,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transfer initiated successfully',
            'data' => $transfer->load(['beneficiary.country', 'beneficiary.method', 'events']),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $transfer = Transfer::where('id', $id)
            ->where('sender_id', Auth::id())
            ->with(['beneficiary.country', 'beneficiary.method', 'events', 'payment'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $transfer,
        ]);
    }

  
     //Returns the current status and all events (status changes) for a transfer

    public function track(int $id): JsonResponse
    {
        $transfer = Transfer::where('id', $id)
            ->where('sender_id', Auth::id())
            ->with(['events' => function ($query) {
                $query->orderBy('created_at', 'desc');
            }])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'transfer' => $transfer,
                'current_status' => $transfer->status,
                'events' => $transfer->events,
            ],
        ]);
    }

  
    public function cancel(int $id): JsonResponse
    {
       
        $transfer = $this->transferService->cancelTransfer($id, Auth::id());

        return response()->json([
            'success' => true,
            'message' => 'Transfer cancelled successfully',
            'data' => $transfer->load(['beneficiary.country', 'beneficiary.method', 'events']),
        ]);
    }

  
    public function refund(int $id): JsonResponse
    {
       
        $transfer = $this->transferService->processRefund($id, Auth::id());

        return response()->json([
            'success' => true,
            'message' => 'Transfer refunded successfully',
            'data' => $transfer->load(['beneficiary.country', 'beneficiary.method', 'events']),
        ]);
    }
}

