<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Currency;
use App\Models\Transfer_Method;
use App\Models\Transfer_Fee;
use App\Models\Promotion;
use App\Services\ExchangeRateService;
use App\Services\TransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransferSearchController extends Controller
{
    public function __construct(
        private ExchangeRateService $exchangeRateService,
        private TransferService $transferService
    ) {}

    public function index(Request $request)
    {
        $countries = Country::orderBy('name')->get();
        $currencies = Currency::orderBy('code')->get();
        $methods = Transfer_Method::all();
        
        // Get user's country for default "from" country
        $userCountryId = $this->transferService->getSenderCountryId(Auth::id());
        $userCountry = Country::find($userCountryId);

        return view('transfers.search', compact('countries', 'currencies', 'methods', 'userCountry', 'userCountryId'));
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'country_from_id' => ['required', 'integer', 'exists:countries,id'],
            'country_to_id' => ['required', 'integer', 'exists:countries,id'],
            'currency_from' => ['required', 'string', 'size:3', 'exists:currencies,code'],
            'currency_to' => ['required', 'string', 'size:3', 'exists:currencies,code'],
            'speed' => ['nullable', 'string', 'in:instant,same_day,express,standard'],
            'method_id' => ['nullable', 'integer', 'exists:transfer_methods,id'],
            'selected_offers' => ['nullable', 'array'],
            'selected_offers.*' => ['string', 'max:100'],
        ]);

        $amount = (float) $validated['amount'];
        $countryFromId = (int) $validated['country_from_id'];
        $countryToId = (int) $validated['country_to_id'];
        $currencyFrom = $validated['currency_from'];
        $currencyTo = $validated['currency_to'];
        $speed = $validated['speed'] ?? 'standard';
        $methodId = $validated['method_id'] ?? null;
        $speedProfile = $this->transferService->resolveSpeedProfile($speed);
        $selectedOffers = $validated['selected_offers'] ?? [];

        // Get exchange rate
        $exchangeRate = $this->exchangeRateService->getRate($currencyFrom, $currencyTo);
        
        if ($exchangeRate === null || $exchangeRate <= 0) {
            return redirect()->route('app.transfers.search')
                ->with('error', 'Unable to fetch a valid exchange rate for this currency pair. Please try again.')
                ->withInput();
        }

        // Calculate fee
        $baseFee = $this->transferService->calculateFee($amount, $countryFromId, $countryToId, $speed);
        
        // Calculate offers total if offers are selected
        $offersTotal = 0.0;
        if (!empty($selectedOffers)) {
            // Use the same calculation logic as TransferController
            $definitions = [
                'Fee Shield Pass'     => fn() => round(max($baseFee * 0.35, 2), 2),
                'Instant Upgrade'     => fn() => round(max($baseFee * 0.45, 3), 2),
                'Rate Lock'           => fn() => round(max($baseFee * 0.25, 1.5), 2),
                'Cash Pickup Booster' => fn() => round(max($baseFee * 0.3, 2), 2),
                'Mobile Wallet Bonus' => fn() => round(max($baseFee * 0.2, 1), 2),
            ];
            
            foreach ($selectedOffers as $offerName) {
                if (isset($definitions[$offerName])) {
                    $offersTotal += $definitions[$offerName]();
                }
            }
            $offersTotal = round($offersTotal, 2);
        }
        
        // Total fee includes base fee + offers
        $fee = round($baseFee + $offersTotal, 2);
        
        // Get fee details from database
        $feeRule = Transfer_Fee::where('country_from_id', $countryFromId)
            ->where('country_to_id', $countryToId)
            ->where('min_amount', '<=', $amount)
            ->where('max_amount', '>=', $amount)
            ->with(['countryFrom', 'countryTo'])
            ->first();

        // Calculate totals (fee already includes offers if selected)
        $totalAmount = $amount + $fee;
        $recipientAmount = $amount * $exchangeRate;
        
        // Delivery time
        $deliveryMinutes = $speedProfile['minutes'];
        $estimatedDelivery = now()->addMinutes($deliveryMinutes);
        $deliveryTime = "{$speedProfile['label']} ({$speedProfile['eta_text']})";

        // Get available promotions for this destination from database
        $promotions = Promotion::where('active', true)
            ->where('country_to_id', $countryToId)
            ->where(function($query) use ($fee) {
                $query->whereNull('min_amount')
                      ->orWhere('min_amount', '<=', $fee);
            })
            ->where(function($query) {
                $query->whereNull('starts_at')
                      ->orWhere('starts_at', '<=', now());
            })
            ->where(function($query) {
                $query->whereNull('ends_at')
                      ->orWhere('ends_at', '>=', now());
            })
            ->where(function($query) {
                $query->whereNull('usage_limit')
                      ->orWhereRaw('used_count < usage_limit');
            })
            ->with('countryTo')
            ->get();

        // Calculate potential discounts
        $promotionsWithDiscount = $promotions->map(function($promo) use ($fee) {
            $discount = 0;
            if (in_array($promo->discount_type, ['percentage', 'percent'])) {
                $discount = ($fee * $promo->discount_value) / 100;
            } elseif ($promo->discount_type === 'fixed') {
                $discount = $promo->discount_value;
            }
            
            if ($promo->max_discount) {
                $discount = min($discount, $promo->max_discount);
            }
            
            $discount = min($discount, $fee); // Can't discount more than fee
            
            return [
                'promotion' => $promo,
                'discount' => round($discount, 2),
            ];
        });

        // Get available methods from database
        $availableMethods = Transfer_Method::all();
        if ($methodId) {
            $availableMethods = $availableMethods->filter(function($method) use ($methodId) {
                return $method->id == $methodId;
            });
        }

        // Get countries and currencies for the form
        $countries = Country::orderBy('name')->get();
        $currencies = Currency::orderBy('code')->get();
        $methods = Transfer_Method::all();
        $userCountryId = $this->transferService->getSenderCountryId(Auth::id());

        // Get country objects
        $countryFrom = Country::find($countryFromId);
        $countryTo = Country::find($countryToId);

        $transferOptions = $availableMethods->map(function($method) use ($speedProfile, $fee, $amount, $exchangeRate, $currencyFrom, $currencyTo, $deliveryTime) {
            $methodName = strtolower($method->name);
            $methodFactor = match ($methodName) {
                'cash pickup' => 1.05,
                'mobile wallet' => 0.95,
                default => 1.0,
            };

            $optionFee = round($fee * $methodFactor, 2);
            $optionTotal = round($amount + $optionFee, 2);
            $recipient = round($amount * $exchangeRate, 2);

            return [
                'method'            => $method,
                'fee'               => $optionFee,
                'total'             => $optionTotal,
                'recipient_amount'  => $recipient,
                'delivery_text'     => $deliveryTime,
                'speed_label'       => $speedProfile['label'],
                'exchange_rate'     => $exchangeRate,
                'payout_label'      => $method->name,
            ];
        });

        $purchaseOffers = collect([
            [
                'name'        => 'Fee Shield Pass',
                'description' => 'Waive most of the fees on this transfer - best when sending larger amounts.',
                'price'       => round(max($fee * 0.35, 2), 2),
                'savings'     => round(min($fee, $fee * 0.6), 2),
            ],
            [
                'name'        => 'Instant Upgrade',
                'description' => 'Jump the queue and process as an instant transfer.',
                'price'       => round(max($fee * 0.45, 3), 2),
                'savings'     => round(min($fee * 0.4, $fee), 2),
            ],
            [
                'name'        => 'Rate Lock',
                'description' => "Lock today's exchange rate for the next 24 hours.",
                'price'       => round(max($fee * 0.25, 1.5), 2),
                'savings'     => round($amount * max($exchangeRate * 0.005, 0.01), 2),
            ],
            [
                'name'        => 'Cash Pickup Booster',
                'description' => 'Guarantee fast cash availability at partner agents.',
                'price'       => round(max($fee * 0.3, 2), 2),
                'savings'     => round(min($fee * 0.3, $fee), 2),
            ],
            [
                'name'        => 'Mobile Wallet Bonus',
                'description' => 'Add a small cashback to the recipient mobile wallet.',
                'price'       => round(max($fee * 0.2, 1), 2),
                'savings'     => round(min($fee * 0.25, $fee), 2),
            ],
        ]);

        return view('transfers.search', compact(
            'countries',
            'currencies',
            'methods',
            'userCountryId',
            'amount',
            'countryFromId',
            'countryToId',
            'currencyFrom',
            'currencyTo',
            'speed',
            'methodId',
            'exchangeRate',
            'fee',
            'feeRule',
            'totalAmount',
            'recipientAmount',
            'deliveryTime',
            'estimatedDelivery',
            'promotionsWithDiscount',
            'availableMethods',
            'countryFrom',
            'countryTo',
            'transferOptions',
            'purchaseOffers',
            'speedProfile',
            'selectedOffers',
            'offersTotal'
        ));
    }
}
