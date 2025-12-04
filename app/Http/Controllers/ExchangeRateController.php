<?php

namespace App\Http\Controllers;

use App\Models\Exchange_Rate;
use App\Services\ExchangeRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExchangeRateController extends Controller
{
    private ExchangeRateService $exchangeRateService;

    public function __construct(ExchangeRateService $exchangeRateService)
    {
        $this->exchangeRateService = $exchangeRateService;
    }

    public function index(Request $request): JsonResponse
    {
        $base = strtoupper($request->query('base', 'USD'));

        // Check if we have fresh data (e.g. last 12 hours)
        $hasFresh = Exchange_Rate::where('currency_from', $base)
            ->where('last_updated', '>=', now()->subHours(12))
            ->exists();

        if (!$hasFresh) {
            // Only hit the API when needed
            $this->exchangeRateService->updateRatesForBase($base);
        }

        $rates = Exchange_Rate::where('currency_from', $base)
            ->orderBy('currency_to')
            ->get(['currency_from', 'currency_to', 'rate', 'last_updated']);

        return response()->json([
            'success'       => true,
            'base_currency' => $base,
            'data'          => $rates,
        ]);
    }

    public function convert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'from'   => 'required|string|size:3',
            'to'     => 'required|string|size:3',
        ]);

        $amount = (float) $validated['amount'];
        $from   = strtoupper($validated['from']);
        $to     = strtoupper($validated['to']);

        $result = $this->exchangeRateService->convert($amount, $from, $to);

        if ($result === null) {
            return response()->json([
                'success' => false,
                'message' => "Unable to get exchange rate for {$from} → {$to}.",
            ], 502);
        }

        $rate = $this->exchangeRateService->getRate($from, $to);

        return response()->json([
            'success' => true,
            'data'    => [
                'amount' => $amount,
                'from'   => $from,
                'to'     => $to,
                'rate'   => $rate,
                'result' => $result,
            ],
        ]);
    }

    public function show(string $from, string $to): JsonResponse
    {
        $from = strtoupper($from);
        $to   = strtoupper($to);

        $rate = $this->exchangeRateService->getRate($from, $to);

        if ($rate === null) {
            return response()->json([
                'success' => false,
                'message' => "Exchange rate not available for {$from} → {$to}.",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'from' => $from,
                'to'   => $to,
                'rate' => $rate,
            ],
        ]);
    }
}
