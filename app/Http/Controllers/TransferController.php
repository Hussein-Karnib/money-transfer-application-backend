<?php

namespace App\Http\Controllers;

use App\Models\Transfer;
use App\Models\Beneficiary;
use App\Services\ExchangeRateService;
use App\Services\TransferService;
use App\Services\PromotionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Http\Controllers\AuditLogController;

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
        private TransferService     $transferService,
        private PromotionService    $promotionService,
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
            'data'    => $transfers,
        ]);
    }

    // ---------------------------------------------------------
    // GET /api/transfers/summary  (preview before creating)
    // ---------------------------------------------------------
    public function summary(Request $request): JsonResponse
    {
        $request->validate([
            'beneficiary_id'        => ['required', 'integer', 'exists:beneficiaries,id'],
            'amount'                => ['required', 'numeric', 'min:1'],
            'currency_from'         => ['required', 'string', 'size:3', 'exists:currencies,code'],
            'currency_to'           => ['required', 'string', 'size:3', 'exists:currencies,code'],
            'speed'                 => ['nullable', 'string', 'in:standard,express'],
            'promo_code'            => ['nullable', 'string', 'max:50'],
            'destination_country_id'=> ['nullable', 'integer', 'exists:countries,id'],
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

        // Determine sender and destination countries (using domain service)
        // Make sure getSenderCountryId is public in TransferService
        $senderCountryId      = $this->transferService->getSenderCountryId(Auth::id());
        $destinationCountryId = $request->destination_country_id ?? $beneficiary->country_id;

        $amount = (float) $request->amount;

        $fee = $this->transferService->calculateFee(
            $amount,
            $senderCountryId,
            $destinationCountryId
        );

        // Optional promotion / discount
        $promotion = null;
        $discount  = 0.0;

        if ($request->filled('promo_code')) {
            try {
                [$promotion, $discount] = $this->promotionService->validateAndCalculate(
                    $request->promo_code,
                    $amount,
                    $destinationCountryId
                );
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 400);
            }
        }

        $totalAmount     = max(0, $amount + $fee - $discount);
        $recipientAmount = $amount * $exchangeRate;

        // Estimate delivery time based on speed
        $speed           = $request->speed ?? 'standard';
        $deliveryMinutes = $speed === 'express' ? 60 : 1440; // 1h vs 24h
        $estimatedDeliveryAt = now()->addMinutes($deliveryMinutes);

        return response()->json([
            'success' => true,
            'data'    => [
                'beneficiary' => [
                    'id'        => $beneficiary->id,
                    'full_name' => $beneficiary->full_name,
                    'country'   => $beneficiary->country,
                    'method'    => $beneficiary->method,
                ],
                'amount' => [
                    'send'          => $amount,
                    'currency_from' => $request->currency_from,
                    'receive'       => round($recipientAmount, 2),
                    'currency_to'   => $request->currency_to,
                ],
                'receiver_amount' => [
                    'amount'   => round($recipientAmount, 2),
                    'currency' => $request->currency_to,
                ],
                'exchange_rate'          => $exchangeRate,
                'fee'                    => round($fee, 2),
                'discount'               => round($discount, 2),
                'total_amount'           => round($totalAmount, 2),
                'speed'                  => $speed,
                'estimated_delivery_time'=> $estimatedDeliveryAt->toIso8601String(),
                'promotion'              => $promotion,
                'breakdown'              => [
                    'transfer_amount'    => $amount,
                    'fee'                => round($fee, 2),
                    'discount'           => round($discount, 2),
                    'total_to_pay'       => round($totalAmount, 2),
                    'recipient_receives' => round($recipientAmount, 2),
                ],
            ],
        ]);
    }

    /*
      POST /api/transfers

      Body:
      {
        "beneficiary_id": 1,
        "amount": 1000,
        "currency_from": "USD",
        "currency_to": "EUR",
        "speed": "standard", // or "express"
        "promo_code": "SUMMER10", // optional
        "destination_country_id": 2 // optional, falls back to beneficiary country
      }
    */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'beneficiary_id'         => ['required', 'integer', 'exists:beneficiaries,id'],
            'amount'                 => ['required', 'numeric', 'min:1'],
            'currency_from'          => ['required', 'string', 'size:3', 'exists:currencies,code'],
            'currency_to'            => ['required', 'string', 'size:3', 'exists:currencies,code'],
            'speed'                  => ['nullable', 'string', 'in:standard,express'],
            'promo_code'             => ['nullable', 'string', 'max:50'],
            'destination_country_id' => ['nullable', 'integer', 'exists:countries,id'],
        ]);

        $userId = Auth::id();

        $beneficiary = Beneficiary::where('id', $data['beneficiary_id'])
            ->where('user_id', $userId)
            ->firstOrFail();

        $amount = (float) $data['amount'];

        // 1) Exchange rate (for preview logic & receiver amount)
        $exchangeRate = $this->exchangeRateService->getRate(
            $data['currency_from'],
            $data['currency_to']
        );

        if ($exchangeRate === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch exchange rate',
            ], 400);
        }

        // 2) Fee
        $senderCountryId      = $this->transferService->getSenderCountryId($userId);
        $destinationCountryId = $data['destination_country_id'] ?? $beneficiary->country_id;

        $fee = $this->transferService->calculateFee(
            $amount,
            $senderCountryId,
            $destinationCountryId
        );

        // 3) Promotion / discount
        $promotion   = null;
        $discount    = 0.0;
        $promotionId = null;

        if (!empty($data['promo_code'])) {
            try {
                [$promotion, $discount] = $this->promotionService->validateAndCalculate(
                    $data['promo_code'],
                    $amount,
                    $destinationCountryId
                );
                $promotionId = $promotion->id;
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 400);
            }
        }

        // 4) Totals
        $totalAmount     = max(0, $amount + $fee - $discount); // what sender pays in source currency
        $recipientAmount = $amount * $exchangeRate;            // what receiver gets in dest currency

        // 5) Delivery estimate
        $speed               = $data['speed'] ?? 'standard';
        $deliveryMinutes     = $speed === 'express' ? 60 : 1440;
        $estimatedDeliveryAt = now()->addMinutes($deliveryMinutes);

        // 6) Actually create the transfer (domain service still validates everything)
        $transfer = $this->transferService->createTransfer([
            'sender_id'            => $userId,
            'beneficiary_id'       => $beneficiary->id,
            'amount'               => $amount,
            'currency_from'        => $data['currency_from'],
            'currency_to'          => $data['currency_to'],
            // these extra fields are ignored by current createTransfer, but fine if you later use them
            'exchange_rate'        => $exchangeRate,
            'fee'                  => $fee,
            'total_amount'         => $totalAmount,
            'promotion_id'         => $promotionId,
            'discount_amount'      => $discount,
            'speed'                => $speed,
            'estimated_delivery_at'=> $estimatedDeliveryAt,
        ]);

        AuditLogController::logSystemAction(
            Auth::id(),
            'create_transfer',
            'transfers',
            $transfer->id,
            ['amount' => $amount, 'currency_from' => $request->currency_from, 'currency_to' => $request->currency_to]
        );

        // Load relations for JSON
        $transfer->load(['beneficiary.country', 'beneficiary.method', 'events']);

        // Use the *saved* values to compute receiver_amount (authoritative)
        $receiverAmount = round($transfer->amount * $transfer->exchange_rate, 2);

        // Keep same shape as before (data = transfer fields) and just append receiver_amount
        $payload                       = $transfer->toArray();
        $payload['receiver_amount']    = [
            'amount'   => $receiverAmount,
            'currency' => $transfer->currency_to,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Transfer initiated successfully',
            'data'    => $payload,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $transfer = Transfer::where('id', $id)
            ->where('sender_id', Auth::id())
            ->with(['beneficiary.country', 'beneficiary.method', 'events', 'payment', 'promotion'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data'    => $transfer,
        ]);
    }

    // Returns the current status and all events (status changes) for a transfer
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
            'data'    => [
                'transfer'       => $transfer,
                'current_status' => $transfer->business_status,
                'events'         => $transfer->events,
            ],
        ]);
    }

    public function cancel(int $id): JsonResponse
    {
        $transfer = $this->transferService->cancelTransfer($id, Auth::id());

        AuditLogController::logSystemAction(
            Auth::id(),
            'cancel_transfer',
            'transfers',
            $id,
            ['status' => 'cancelled']
        );

        return response()->json([
            'success' => true,
            'message' => 'Transfer cancelled successfully',
            'data'    => $transfer->load(['beneficiary.country', 'beneficiary.method', 'events']),
        ]);
    }

    public function refund(int $id): JsonResponse
    {
        $transfer = $this->transferService->processRefund($id, Auth::id());

        AuditLogController::logSystemAction(
            Auth::id(),
            'refund_transfer',
            'transfers',
            $id,
            ['status' => 'refunded']
        );

        return response()->json([
            'success' => true,
            'message' => 'Transfer refunded successfully',
            'data'    => $transfer->load(['beneficiary.country', 'beneficiary.method', 'events']),
        ]);
    }
}
