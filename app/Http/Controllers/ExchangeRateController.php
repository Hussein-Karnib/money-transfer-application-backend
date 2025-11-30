<?php

namespace App\Http\Controllers;

use App\Models\Exchange_Rate;
use App\Services\ExchangeRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExchangeRateController extends Controller
{
    public function __construct(
        private ExchangeRateService $exchangeRateService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Exchange_Rate::query();

        if ($request->has('from')) {
            $query->where('currency_from', $request->from);
        }

        if ($request->has('to')) {
            $query->where('currency_to', $request->to);
        }

        $rates = $query->orderBy('last_updated', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $rates,
        ]);
    }

   
    public function show(string $from, string $to): JsonResponse
    {
        $rate = $this->exchangeRateService->getRate($from, $to);

        if ($rate === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch exchange rate',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'from' => $from,
                'to' => $to,
                'rate' => $rate,
            ],
        ]);
    }

    /*
     Request Body:
     {
       "amount": 100,
       "from": "USD",
       "to": "EUR"
     }
     */
    public function convert(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'from' => ['required', 'string', 'size:3', 'exists:currencies,code'],
            'to' => ['required', 'string', 'size:3', 'exists:currencies,code'],
        ]);

        $amount = (float) $request->amount;
        $from = $request->from;
        $to = $request->to;

        $rate = $this->exchangeRateService->getRate($from, $to);

        if ($rate === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch exchange rate',
            ], 404);
        }

        $convertedAmount = $this->exchangeRateService->convert($amount, $from, $to);

        return response()->json([
            'success' => true,
            'data' => [
                'amount' => $amount,
                'from' => $from,
                'to' => $to,
                'rate' => $rate,
                'converted_amount' => $convertedAmount,
            ],
        ]);
    }
}

