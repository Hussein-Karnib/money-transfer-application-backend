<?php

namespace App\Http\Controllers;

use App\Models\Transfer;
use App\Models\Beneficiary;
use App\Models\Transfer_Method;
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
            $user = Auth::user();
            if ($user && in_array($user->status, ['pending', 'inactive'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is pending approval. You cannot make transfers yet.',
                ], 403);
            }

            $validated = $request->validate([
                'beneficiary_id'         => ['required', 'integer', 'exists:beneficiaries,id'],
                'amount'                 => ['required', 'numeric', 'min:1'],
                'currency_from'          => ['required', 'string', 'size:3', 'exists:currencies,code'],
                'currency_to'            => ['required', 'string', 'size:3', 'exists:currencies,code'],
                'speed'                  => ['nullable', 'string', 'in:instant,same_day,express,standard'],
                'promo_code'             => ['nullable', 'string', 'max:50'],
                'destination_country_id' => ['nullable', 'integer', 'exists:countries,id'],
                'transfer_method_id'     => ['nullable', 'integer', 'exists:transfer_methods,id'],
                'selected_offers'        => ['nullable', 'array'],
                'selected_offers.*'      => ['string', 'max:100'],
            ]);

            $userId = Auth::id();

            $beneficiary = Beneficiary::where('id', $validated['beneficiary_id'])
                ->where('user_id', $userId)
                ->with(['country', 'method'])
                ->firstOrFail();

            $speed = (string) ($validated['speed'] ?? 'standard');
            $speedProfile = $this->transferService->resolveSpeedProfile($speed);
            $transferMethodId = $validated['transfer_method_id'] ?? $beneficiary->transfer_method_id;
            $selectedOffers = $validated['selected_offers'] ?? [];

            $exchangeRate = $this->exchangeRateService->getRate(
                $validated['currency_from'],
                $validated['currency_to']
            );

            if ($exchangeRate === null || $exchangeRate <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to fetch exchange rate',
                ], 400);
            }

            $amount              = (float) $validated['amount'];
            $senderCountryId     = $this->transferService->getSenderCountryId($userId);
            $destinationCountryId = $validated['destination_country_id'] ?? $beneficiary->country_id;

            // 1) Fee (in sender currency)
            $baseFee = (float) $this->transferService->calculateFee(
                $amount,
                $senderCountryId,
                $destinationCountryId,
                $speed
            );
            $offersTotal = $this->calculateOffersTotal($baseFee, $amount, $exchangeRate, $selectedOffers);

            // 2) Promotion (applied on FEE, not on whole amount)
            $promotion      = null;
            $discountAmount = 0.0;

            if (!empty($validated['promo_code'])) {
                try {
                    // validate against FEE, not amount
                    [$promotion, $discountAmount] = $this->promotionService->validateAndCalculate(
                        $validated['promo_code'],
                        $baseFee,
                        $destinationCountryId
                    );

                    // never discount more than fee
                    $discountAmount = min($discountAmount, $baseFee);
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => $e->getMessage(),
                    ], 422);
                }
            }

            $fee           = round($baseFee + $offersTotal, 2);
            $discountAmount = round($discountAmount, 2);

            $totalAmount     = max(0, $amount + $fee - $discountAmount);
            $recipientAmount = $amount * $exchangeRate;

            $deliveryMinutes = $speedProfile['minutes'];
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
                    'offers_total'            => round($offersTotal, 2),
                    'discount_amount'         => $discountAmount,
                    'total_amount'            => round($totalAmount, 2),
                    'speed'                   => $speed,
                    'estimated_delivery_time' => $estimatedDeliveryAt->toIso8601String(),
                    'promotion'               => $promotion,
                    'breakdown'               => [
                        'transfer_amount'    => $amount,
                        'fee'                => $fee,
                        'offers_total'       => round($offersTotal, 2),
                        'discount_amount'    => $discountAmount,
                        'total_to_pay'       => round($totalAmount, 2),
                        'recipient_receives' => round($recipientAmount, 2),
                    ],
                    'selected_offers'         => $selectedOffers,
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
        $user = Auth::user();
        if ($user && in_array($user->status, ['pending', 'inactive'])) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is pending approval. You cannot make transfers yet.',
                ], 403);
            }
            return back()->withErrors(['amount' => 'Your account is pending approval. You cannot make transfers yet.'])->withInput();
        }

        $data = $request->validate([
            'beneficiary_id'         => ['required', 'integer', 'exists:beneficiaries,id'],
            'amount'                 => ['required', 'numeric', 'min:1'],
            'currency_from'          => ['required', 'string', 'size:3', 'exists:currencies,code'],
            'currency_to'            => ['required', 'string', 'size:3', 'exists:currencies,code'],
            'speed'                  => ['nullable', 'string', 'in:instant,same_day,express,standard'],
            'promo_code'             => ['nullable', 'string', 'max:50'],
            'destination_country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'transfer_method_id'     => ['nullable', 'integer', 'exists:transfer_methods,id'],
            'selected_offers'        => ['nullable', 'array'],
            'selected_offers.*'      => ['string', 'max:100'],
        ]);

        $userId = Auth::id();
        $speed  = (string) ($data['speed'] ?? 'standard');
        $speedProfile = $this->transferService->resolveSpeedProfile($speed);

        $beneficiary = Beneficiary::where('id', $data['beneficiary_id'])
            ->where('user_id', $userId)
            ->firstOrFail();

        $transferMethodId = $data['transfer_method_id'] ?? $beneficiary->transfer_method_id;

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

        $baseFee = (float) $this->transferService->calculateFee(
            $amount,
            $senderCountryId,
            $destinationCountryId,
            $speed
        );
        $selectedOffers = $data['selected_offers'] ?? [];
        
        // Calculate offers with individual prices
        $offersWithPrices = $this->calculateOffersWithPrices($baseFee, $amount, $exchangeRate, $selectedOffers);
        $offersTotal = array_sum(array_column($offersWithPrices, 'price'));

        // 3) Promotion / discount (again on FEE, server-side)
        $promotion      = null;
        $promotionId    = null;
        $discountAmount = 0.0;

        if (!empty($data['promo_code'])) {
            try {
                [$promotion, $discountAmount] = $this->promotionService->validateAndCalculate(
                    $data['promo_code'],
                    $baseFee,
                    $destinationCountryId,
                    $data['speed'] ?? 'standard'
                );

                $discountAmount = min($discountAmount, $baseFee);
                $promotionId    = $promotion->id;

                // Increase usage only when we actually create a transfer
                $promotion->increment('used_count');
            } catch (\Exception $e) {
                return back()->withErrors(['promo_code' => $e->getMessage()])->withInput();
            }
        }

        $finalFee       = round($baseFee + $offersTotal, 2);
        $discountAmount = round($discountAmount, 2);

        // 4) Totals
        $totalAmount     = max(0, $amount + $finalFee - $discountAmount);
        $recipientAmount = $amount * $exchangeRate;

        // 5) Delivery estimate
        $deliveryMinutes     = $speedProfile['minutes'];
        $estimatedDeliveryAt = now()->addMinutes($deliveryMinutes);

        try {
            // 6) Create transfer via domain service
            $transfer = $this->transferService->createTransfer([
                'sender_id'             => $userId,
                'beneficiary_id'        => $beneficiary->id,
                'transfer_method_id'    => $transferMethodId,
                'amount'                => $amount,
                'currency_from'         => $data['currency_from'],
                'currency_to'           => $data['currency_to'],
                'exchange_rate'         => $exchangeRate,
                'fee'                   => $finalFee,
                'total_amount'          => round($totalAmount, 2),
                'promotion_id'          => $promotionId,
                'discount_amount'       => $discountAmount,
                'speed'                 => $speed,
                'estimated_delivery_at' => $estimatedDeliveryAt,
                'offers'                => $offersWithPrices,
                'offers_total'          => round($offersTotal, 2),
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
                    'sender_name'   => $user->name,
                    'sender_email'  => $user->email,
                ]
            );

            $transfer->events()->create([
                'status'     => 'queued',
                'note'       => "Transfer created. Fee: {$finalFee}; Offers: " . (empty($selectedOffers) ? 'none' : implode(', ', $selectedOffers)) . "; Total to pay: " . round($totalAmount, 2),
                'actor_type' => 'user',
                'actor_id'   => $userId,
            ]);

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
            ->with(['beneficiary.country', 'beneficiary.method', 'events', 'payment', 'promotion', 'transferMethod'])
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

    public function cancel(int $id)
    {
        try {
            $transfer = $this->transferService->cancelTransfer($id, Auth::id());

            AuditLogController::logSystemAction(
                Auth::id(),
                'cancel_transfer',
                'transfers',
                $id,
                ['status' => 'cancelled']
            );

            return redirect()->route('app.transfers.index')
                ->with('success', 'Transfer cancelled successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
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

    private function calculateOffersTotal(float $baseFee, float $amount, float $exchangeRate, array $selectedOffers): float
    {
        if (empty($selectedOffers)) {
            return 0.0;
        }

        $offersWithPrices = $this->calculateOffersWithPrices($baseFee, $amount, $exchangeRate, $selectedOffers);
        return array_sum(array_column($offersWithPrices, 'price'));
    }

    private function calculateOffersWithPrices(float $baseFee, float $amount, float $exchangeRate, array $selectedOffers): array
    {
        if (empty($selectedOffers)) {
            return [];
        }

        $definitions = [
            'Fee Shield Pass'     => fn() => round(max($baseFee * 0.35, 2), 2),
            'Instant Upgrade'     => fn() => round(max($baseFee * 0.45, 3), 2),
            'Rate Lock'           => fn() => round(max($baseFee * 0.25, 1.5), 2),
            'Cash Pickup Booster' => fn() => round(max($baseFee * 0.3, 2), 2),
            'Mobile Wallet Bonus' => fn() => round(max($baseFee * 0.2, 1), 2),
        ];

        $offers = [];
        foreach ($selectedOffers as $offerName) {
            if (isset($definitions[$offerName])) {
                $price = $definitions[$offerName]();
                $offers[] = [
                    'name' => $offerName,
                    'price' => $price,
                ];
            }
        }

        return $offers;
    }
}
