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
            'speed' => ['nullable', 'string', 'in:standard,express'],
            'method_id' => ['nullable', 'integer', 'exists:transfer_methods,id'],
        ]);

        $amount = (float) $validated['amount'];
        $countryFromId = (int) $validated['country_from_id'];
        $countryToId = (int) $validated['country_to_id'];
        $currencyFrom = $validated['currency_from'];
        $currencyTo = $validated['currency_to'];
        $speed = $validated['speed'] ?? 'standard';
        $methodId = $validated['method_id'] ?? null;

        // Get exchange rate
        $exchangeRate = $this->exchangeRateService->getRate($currencyFrom, $currencyTo);
        
        if ($exchangeRate === null) {
            return redirect()->route('app.transfers.search')
                ->with('error', 'Unable to fetch exchange rate for this currency pair. Please try again.');
        }

        // Calculate fee
        $fee = $this->transferService->calculateFee($amount, $countryFromId, $countryToId);
        
        // Get fee details from database
        $feeRule = Transfer_Fee::where('country_from_id', $countryFromId)
            ->where('country_to_id', $countryToId)
            ->where('min_amount', '<=', $amount)
            ->where('max_amount', '>=', $amount)
            ->with(['countryFrom', 'countryTo'])
            ->first();

        // Calculate totals
        $totalAmount = $amount + $fee;
        $recipientAmount = $amount * $exchangeRate;
        
        // Delivery time
        $deliveryMinutes = $speed === 'express' ? 60 : 1440;
        $estimatedDelivery = now()->addMinutes($deliveryMinutes);
        $deliveryTime = $speed === 'express' ? '1 hour' : '24 hours';

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
            if ($promo->discount_type === 'percentage') {
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
            'countryTo'
        ));
    }
}

