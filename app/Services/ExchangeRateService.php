<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\Exchange_Rate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    private ?string $apiKey;
    private string $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.exchange_rate.api_key') ?: env('EXCHANGE_RATE_API_KEY') ?: null;
        $this->apiUrl = config('services.exchange_rate.api_url', env('EXCHANGE_RATE_API_URL', 'https://v6.exchangerate-api.com/v6'));
    }

    public function fetchRate(string $from, string $to): ?float
    {
        try {
            if (empty($this->apiKey)) {
                Log::error("Exchange rate API key is not configured.");
                return null;
            }

            $cacheKey = "exchange_rate_{$from}_{$to}";
            if (Cache::has($cacheKey)) {
                return (float) Cache::get($cacheKey);
            }

            // Primary: pair endpoint
            $response = Http::timeout(10)->get(
                "{$this->apiUrl}/{$this->apiKey}/pair/{$from}/{$to}"
            );

            if ($response->successful() && isset($response['conversion_rate'])) {
                $rate = (float) $response['conversion_rate'];
                $this->saveRate($from, $to, $rate);
                Cache::put($cacheKey, $rate, now()->addHour());
                return $rate;
            }

            // Fallback: latest endpoint
            $response = Http::timeout(10)->get(
                "{$this->apiUrl}/{$this->apiKey}/latest/{$from}"
            );

            if ($response->successful() && isset($response['conversion_rates'][$to])) {
                $rate = (float) $response['conversion_rates'][$to];
                $this->saveRate($from, $to, $rate);
                Cache::put($cacheKey, $rate, now()->addHour());
                return $rate;
            }

            Log::warning("Failed to fetch exchange rate {$from} → {$to}");
            return null;

        } catch (\Exception $e) {
            Log::error("Exchange rate API error: " . $e->getMessage());
            return null;
        }
    }

    public function getRate(string $from, string $to, bool $forceRefresh = false): ?float
    {
        if ($from === $to) {
            return 1.0;
        }

        if (!$forceRefresh) {
            $exchangeRate = Exchange_Rate::where('currency_from', $from)
                ->where('currency_to', $to)
                ->where('last_updated', '>=', now()->subDay())
                ->first();

            if ($exchangeRate) {
                return (float) $exchangeRate->rate;
            }
        }

        return $this->fetchRate($from, $to);
    }

    public function saveRate(string $from, string $to, float $rate): Exchange_Rate
    {
        return Exchange_Rate::updateOrCreate(
            [
                'currency_from' => $from,
                'currency_to'   => $to,
            ],
            [
                'rate'         => $rate,
                'last_updated' => now(),
            ]
        );
    }

    public function convert(float $amount, string $from, string $to): ?float
    {
        $rate = $this->getRate($from, $to);

        if ($rate === null) {
            return null;
        }

        return $amount * $rate;
    }

    /**
     * 🔥 NEW IMPLEMENTATION: only ONE HTTP call per base currency
     */
    public function updateRatesForBase(string $baseCurrency): array
    {
        $baseCurrency = strtoupper($baseCurrency);

        if (empty($this->apiKey)) {
            Log::error("Exchange rate API key is not configured.");
            return [];
        }

        try {
            // Single call to latest endpoint
            $response = Http::timeout(10)->get(
                "{$this->apiUrl}/{$this->apiKey}/latest/{$baseCurrency}"
            );

            if (!$response->successful() || !isset($response['conversion_rates'])) {
                Log::warning('Failed to fetch latest rates for base', [
                    'base'   => $baseCurrency,
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return [];
            }

            $apiRates = $response['conversion_rates'];

            // Only care about currencies that exist in DB
            $currencies = Currency::where('code', '!=', $baseCurrency)->pluck('code');
            $updated = [];

            foreach ($currencies as $code) {
                $code = strtoupper($code);

                if (!isset($apiRates[$code])) {
                    // API doesn't support this currency, skip it
                    continue;
                }

                $rate = (float) $apiRates[$code];
                $this->saveRate($baseCurrency, $code, $rate);
                $updated[$code] = $rate;
            }

            return $updated;

        } catch (\Exception $e) {
            Log::error('Exchange rate API error in updateRatesForBase', [
                'base'    => $baseCurrency,
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
