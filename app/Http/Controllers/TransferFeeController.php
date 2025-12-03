<?php

namespace App\Http\Controllers;

use App\Models\Transfer_Fee;
use App\Models\Country;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class TransferFeeController extends Controller
{
    
    public function index(Request $request): JsonResponse
    {
        $query = Transfer_Fee::with(['countryFrom', 'countryTo']);

        // Filter by sender country
        if ($request->has('country_from_id')) {
            $query->where('country_from_id', $request->country_from_id);
        }

        
        if ($request->has('country_to_id')) {
            $query->where('country_to_id', $request->country_to_id);
        }

        $fees = $query->orderBy('country_from_id')
            ->orderBy('country_to_id')
            ->orderBy('min_amount')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $fees,
        ]);
    }

    /*
      Body: {
        "amount": 1000,
       "country_from_id": 1,
       "country_to_id": 2
     }
     */
   public function calculate(Request $request): JsonResponse
{
    $request->validate([
        'amount'          => ['required', 'numeric', 'min:0'],
        'country_from_id' => ['required', 'integer', 'exists:countries,id'],
        'country_to_id'   => ['required', 'integer', 'exists:countries,id'],
    ]);

    $amount        = (float) $request->amount;
    $countryFromId = (int) $request->country_from_id;
    $countryToId   = (int) $request->country_to_id;

    // 1) Find matching fee rule
    $feeRule = Transfer_Fee::where('country_from_id', $countryFromId)
        ->where('country_to_id', $countryToId)
        ->where('min_amount', '<=', $amount)
        ->where('max_amount', '>=', $amount)
        ->with(['countryFrom', 'countryTo'])
        ->first();

    // 2) Calculate fee
    if ($feeRule) {
        $fixed   = (float) $feeRule->fee_fixed;
        $percent = (float) $feeRule->fee_percent;

        // If rule explicitly says "no fees" (0 + 0), you have 2 options:
        // A) Respect it → fee = 0
        // B) Fall back to default formula
        //
        // I'll show A (respect) by default, and comment B.
        if ($fixed === 0.0 && $percent === 0.0) {
            // A) Respect rule: no fees for this corridor / range
            $fee = 0.0;
        } else {
            // Normal rule-based calculation
            $fee = $fixed + ($amount * $percent / 100.0);
        }

        $appliedRule = $feeRule;
    } else {
        // 3) No rule found → fallback logic (global default)
        // 2% of amount, minimum 5.0
        $fee        = max($amount * 0.02, 5.0);
        $appliedRule = null;
    }

    $fee        = round($fee, 2);
    $total      = $amount + $fee;
    $countryFrom = Country::find($countryFromId);
    $countryTo   = Country::find($countryToId);

    return response()->json([
        'success' => true,
        'data'    => [
            'amount'       => $amount,
            'country_from' => $countryFrom,
            'country_to'   => $countryTo,
            'fee'          => $fee,
            'fee_rule'     => $appliedRule,
            'total_amount' => $total,
        ],
    ]);
}


  
    public function show(int $id): JsonResponse
    {
        $fee = Transfer_Fee::with(['countryFrom', 'countryTo'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $fee,
        ]);
    }

    /*
     Create a new transfer fee rule
      Body: {
       "country_from_id": 1,
       "country_to_id": 2,
       "min_amount": 0,
       "max_amount": 10000,
       "fee_fixed": 5,
       "fee_percent": 2.5
     }
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'country_from_id' => ['required', 'integer', 'exists:countries,id'],
            'country_to_id' => ['required', 'integer', 'exists:countries,id'],
            'min_amount' => ['required', 'numeric', 'min:0'],
            'max_amount' => ['required', 'numeric', 'min:0', 'gt:min_amount'],
            'fee_fixed' => ['nullable', 'numeric', 'min:0'],
            'fee_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        // Check for overlapping ranges (optional validation)
        $overlapping = Transfer_Fee::where('country_from_id', $request->country_from_id)
            ->where('country_to_id', $request->country_to_id)
            ->where(function ($query) use ($request) {
                $query->whereBetween('min_amount', [$request->min_amount, $request->max_amount])
                    ->orWhereBetween('max_amount', [$request->min_amount, $request->max_amount])
                    ->orWhere(function ($q) use ($request) {
                        $q->where('min_amount', '<=', $request->min_amount)
                            ->where('max_amount', '>=', $request->max_amount);
                    });
            })
            ->exists();

        if ($overlapping) {
            return response()->json([
                'success' => false,
                'message' => 'A fee rule already exists for this amount range',
            ], 400);
        }

        $fee = Transfer_Fee::create([
            'country_from_id' => $request->country_from_id,
            'country_to_id' => $request->country_to_id,
            'min_amount' => $request->min_amount,
            'max_amount' => $request->max_amount,
            'fee_fixed' => $request->fee_fixed ?? 0,
            'fee_percent' => $request->fee_percent ?? 0,
            'last_updated' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transfer fee rule created successfully',
            'data' => $fee->load(['countryFrom', 'countryTo']),
        ], 201);
    }


    public function update(Request $request, int $id): JsonResponse
    {
        $fee = Transfer_Fee::findOrFail($id);

        $request->validate([
            'country_from_id' => ['sometimes', 'integer', 'exists:countries,id'],
            'country_to_id' => ['sometimes', 'integer', 'exists:countries,id'],
            'min_amount' => ['sometimes', 'numeric', 'min:0'],
            'max_amount' => ['sometimes', 'numeric', 'min:0'],
            'fee_fixed' => ['nullable', 'numeric', 'min:0'],
            'fee_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        
        if ($request->has('min_amount') && $request->has('max_amount')) {
            if ($request->max_amount <= $request->min_amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'max_amount must be greater than min_amount',
                ], 400);
            }
        }

        $fee->update(array_merge(
            $request->only(['country_from_id', 'country_to_id', 'min_amount', 'max_amount', 'fee_fixed', 'fee_percent']),
            ['last_updated' => now()]
        ));

        return response()->json([
            'success' => true,
            'message' => 'Transfer fee rule updated successfully',
            'data' => $fee->fresh()->load(['countryFrom', 'countryTo']),
        ]);
    }

 
    public function destroy(int $id): JsonResponse
    {
        $fee = Transfer_Fee::findOrFail($id);
        $fee->delete();

        return response()->json([
            'success' => true,
            'message' => 'Transfer fee rule deleted successfully',
        ]);
    }
}

