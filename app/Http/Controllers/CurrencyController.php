<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Services\ExchangeRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function __construct(
        private ExchangeRateService $exchangeRateService
    ) {}

    
     // Get all currencies
     
    public function index(): JsonResponse
    {
        $currencies = Currency::all();
        
        return response()->json([
            'success' => true,
            'data' => $currencies,
        ]);
    }

    
    public function show(string $code): JsonResponse
    {
        $currency = Currency::findOrFail($code);
        
        return response()->json([
            'success' => true,
            'data' => $currency,
        ]);
    }
}

