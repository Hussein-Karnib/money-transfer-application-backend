<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\Exchange_Rate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    private string $apiKey;
    private string $apiUrl;

    public function __construct()
    {
        // Using exchangerate-api.io as default (free tier available)
        // Can be configured via .env: EXCHANGE_RATE_API_KEY and EXCHANGE_RATE_API_URL
        $this->apiKey = config('services.exchange_rate.api_key', env('EXCHANGE_RATE_API_KEY', ''));
        $this->apiUrl = config('services.exchange_rate.api_url', env('EXCHANGE_RATE_API_URL', 'https://api.exchangerate-api.com/v4/latest'));
    }

 
    public function fetchRate(string $from, string $to): ?float
    {
        try {
            // Try to get from cache first (cache for 1 hour)
            $cacheKey = "exchange_rate_{$from}_{$to}";
            $cachedRate = Cache::get($cacheKey);
            
            if ($cachedRate !== null) {
                return (float) $cachedRate;
            }

            // If API key is provided, use exchangerate-api.io
            if (!empty($this->apiKey)) {
                $response = Http::timeout(10)->get("https://v6.exchangerate-api.com/v6/{$this->apiKey}/pair/{$from}/{$to}");
                
                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['conversion_rate'])) {
                        $rate = (float) $data['conversion_rate'];
                        $this->saveRate($from, $to, $rate);
                        Cache::put($cacheKey, $rate, now()->addHour());
                        return $rate;
                    }
                }
            }

            // Fallback to free API (exchangerate-api.com - no key required)
            $response = Http::timeout(10)->get("{$this->apiUrl}/{$from}");
            
            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['rates'][$to])) {
                    $rate = (float) $data['rates'][$to];
                    $this->saveRate($from, $to, $rate);
                    Cache::put($cacheKey, $rate, now()->addHour());
                    return $rate;
                }
            }

            Log::warning("Failed to fetch exchange rate from {$from} to {$to}");
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

        // Check if rate exists in database and is recent (less than 24 hours old)
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
                'currency_to' => $to,
            ],
            [
                'rate' => $rate,
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

  
    public function updateRatesForBase(string $baseCurrency): array
    {
        $currencies = Currency::where('code', '!=', $baseCurrency)->pluck('code');
        $updated = [];

        foreach ($currencies as $currency) {
            $rate = $this->fetchRate($baseCurrency, $currency);
            if ($rate !== null) {
                $updated[$currency] = $rate;
            }
        }

        return $updated;
    }
}

