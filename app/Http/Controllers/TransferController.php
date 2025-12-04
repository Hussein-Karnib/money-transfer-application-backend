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

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

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
    // GET /transfers/summary  (preview before creating) - Web only
    // ---------------------------------------------------------
    public function summary(Request $request)
    {
        // Only allow for API requests, web should use direct form submission
        if ($request->wantsJson() || $request->is('api/*')) {
            $validated = $request->validate([
                'beneficiary_id'         => ['required', 'integer', 'exists:beneficiaries,id'],
                'amount'                 => ['required', 'numeric', 'min:1'],
                'currency_from'          => ['required', 'string', 'size:3', 'exists:currencies,code'],
                'currency_to'            => ['required', 'string', 'size:3', 'exists:currencies,code'],
                'speed'                  => ['nullable', 'string', 'in:standard,express'],
                'promo_code'             => ['nullable', 'string', 'max:50'],
                'destination_country_id' => ['nullable', 'integer', 'exists:countries,id'],
            ]);

            $userId = Auth::id();

            $beneficiary = Beneficiary::where('id', $validated['beneficiary_id'])
                ->where('user_id', $userId)
                ->with(['country', 'method'])
                ->firstOrFail();

            $exchangeRate = $this->exchangeRateService->getRate(
                $validated['currency_from'],
                $validated['currency_to']
            );

            if ($exchangeRate === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to fetch exchange rate',
                ], 400);
            }

            $amount              = (float) $validated['amount'];
            $senderCountryId     = $this->transferService->getSenderCountryId($userId);
            $destinationCountryId = $validated['destination_country_id'] ?? $beneficiary->country_id;

            // 1) Fee (in sender currency)
            $fee = (float) $this->transferService->calculateFee(
                $amount,
                $senderCountryId,
                $destinationCountryId
            );

            // 2) Promotion (applied on FEE, not on whole amount)
            $promotion      = null;
            $discountAmount = 0.0;

            if (!empty($validated['promo_code'])) {
                try {
                    // validate against FEE, not amount
                    [$promotion, $discountAmount] = $this->promotionService->validateAndCalculate(
                        $validated['promo_code'],
                        $fee,
                        $destinationCountryId
                    );

                    // never discount more than fee
                    $discountAmount = min($discountAmount, $fee);
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => $e->getMessage(),
                    ], 422);
                }
            }

            $fee           = round($fee, 2);
            $discountAmount = round($discountAmount, 2);

            $totalAmount     = max(0, $amount + $fee - $discountAmount);
            $recipientAmount = $amount * $exchangeRate;

            $speed           = $validated['speed'] ?? 'standard';
            $deliveryMinutes = $speed === 'express' ? 60 : 1440;
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
                        'currency_from' => $validated['currency_from'],
                        'receive'       => round($recipientAmount, 2),
                        'currency_to'   => $validated['currency_to'],
                    ],
                    'receiver_amount' => [
                        'amount'   => round($recipientAmount, 2),
                        'currency' => $validated['currency_to'],
                    ],
                    'exchange_rate'           => $exchangeRate,
                    'fee'                     => $fee,
                    'discount_amount'         => $discountAmount,
                    'total_amount'            => round($totalAmount, 2),
                    'speed'                   => $speed,
                    'estimated_delivery_time' => $estimatedDeliveryAt->toIso8601String(),
                    'promotion'               => $promotion,
                    'breakdown'               => [
                        'transfer_amount'    => $amount,
                        'fee'                => $fee,
                        'discount_amount'    => $discountAmount,
                        'total_to_pay'       => round($totalAmount, 2),
                        'recipient_receives' => round($recipientAmount, 2),
                    ],
                ],
            ]);
        }

        // For web requests, redirect to create form
        return redirect()->route('app.transfers.create');
    }

    /*
      POST /api/transfers

      Body:
      {
        "beneficiary_id": 1,
        "amount": 1000,
        "currency_from": "USD",
        "currency_to": "EUR",
        "speed": "standard",
        "promo_code": "SUMMER10",     // optional
        "destination_country_id": 2   // optional, defaults to beneficiary country
      }
    */
    public function store(Request $request)
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

        // 1) Exchange rate
        $exchangeRate = $this->exchangeRateService->getRate(
            $data['currency_from'],
            $data['currency_to']
        );

        if ($exchangeRate === null) {
            return back()->withErrors(['amount' => 'Unable to fetch exchange rate. Please try again.'])->withInput();
        }

        // 2) Fee
        $senderCountryId      = $this->transferService->getSenderCountryId($userId);
        $destinationCountryId = $data['destination_country_id'] ?? $beneficiary->country_id;

        $fee = (float) $this->transferService->calculateFee(
            $amount,
            $senderCountryId,
            $destinationCountryId
        );

        // 3) Promotion / discount (again on FEE, server-side)
        $promotion      = null;
        $promotionId    = null;
        $discountAmount = 0.0;

        if (!empty($data['promo_code'])) {
            try {
                [$promotion, $discountAmount] = $this->promotionService->validateAndCalculate(
                    $data['promo_code'],
                    $fee,
                    $destinationCountryId
                );

                $discountAmount = min($discountAmount, $fee);
                $promotionId    = $promotion->id;

                // Increase usage only when we actually create a transfer
                $promotion->increment('used_count');
            } catch (\Exception $e) {
                return back()->withErrors(['promo_code' => $e->getMessage()])->withInput();
            }
        }

        $fee           = round($fee, 2);
        $discountAmount = round($discountAmount, 2);

        // 4) Totals
        $totalAmount     = max(0, $amount + $fee - $discountAmount);
        $recipientAmount = $amount * $exchangeRate;

        // 5) Delivery estimate
        $speed               = $data['speed'] ?? 'standard';
        $deliveryMinutes     = $speed === 'express' ? 60 : 1440;
        $estimatedDeliveryAt = now()->addMinutes($deliveryMinutes);

        try {
            // 6) Create transfer via domain service
            $transfer = $this->transferService->createTransfer([
                'sender_id'             => $userId,
                'beneficiary_id'        => $beneficiary->id,
                'amount'                => $amount,
                'currency_from'         => $data['currency_from'],
                'currency_to'           => $data['currency_to'],
                'exchange_rate'         => $exchangeRate,
                'fee'                   => $fee,
                'total_amount'          => round($totalAmount, 2),
                'promotion_id'          => $promotionId,
                'discount_amount'       => $discountAmount,
                'speed'                 => $speed,
                'estimated_delivery_at' => $estimatedDeliveryAt,
            ]);

            AuditLogController::logSystemAction(
                $userId,
                'create_transfer',
                'transfers',
                $transfer->id,
                [
                    'amount'        => $amount,
                    'currency_from' => $data['currency_from'],
                    'currency_to'   => $data['currency_to'],
                ]
            );

            // Redirect to transfer details page
            return redirect()->route('transfers.show', $transfer->id)
                ->with('success', 'Transfer created successfully! Reference: ' . $transfer->reference);

        } catch (\Exception $e) {
            // For web requests, always redirect back with error
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }
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
