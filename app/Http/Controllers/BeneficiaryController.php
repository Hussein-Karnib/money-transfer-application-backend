<?php

namespace App\Http\Controllers;

use App\Models\Beneficiary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/*
 Example payout_details for bank transfer:
 {
   "account_number": "123456789",
   "bank_name": "Bank Name",
    "swift_code": "SWIFT123"
 }
 
 Example payout_details for cash pickup:
 {
   "pickup_location": "Main Branch",
   "phone": "+1234567890"
 }
 */
class BeneficiaryController extends Controller
{
   
    public function index(): JsonResponse
    {
        $beneficiaries = Beneficiary::where('user_id', Auth::id())
            ->with(['country', 'method'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $beneficiaries,
        ]);
    }

    /*
      Store a new beneficiary
      Request Body:
      {
     "full_name": "John Doe",
     "country_id": 1,
     "transfer_method_id": 1,
     "payout_details": {
        "account_number": "123456789"
       }
      }
     */
    public function store(Request $request): JsonResponse
    {
       
        $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'transfer_method_id' => ['required', 'integer', 'exists:transfer_methods,id'],
            'payout_details' => ['nullable', 'array'],
        ]);

        $beneficiary = Beneficiary::create([
            'user_id' => Auth::id(),
            'full_name' => $request->full_name,
            'country_id' => $request->country_id,
            'transfer_method_id' => $request->transfer_method_id,
            'payout_details' => $request->payout_details ?? [],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Beneficiary added successfully',
            'data' => $beneficiary->load(['country', 'method']),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $beneficiary = Beneficiary::where('id', $id)
            ->where('user_id', Auth::id())
            ->with(['country', 'method'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $beneficiary,
        ]);
    }

    
    public function update(Request $request, int $id): JsonResponse
    {
        $beneficiary = Beneficiary::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $request->validate([
            'full_name' => ['sometimes', 'string', 'max:255'],
            'country_id' => ['sometimes', 'integer', 'exists:countries,id'],
            'transfer_method_id' => ['sometimes', 'integer', 'exists:transfer_methods,id'],
            'payout_details' => ['sometimes', 'array'],
        ]);

        $beneficiary->update($request->only(['full_name', 'country_id', 'transfer_method_id', 'payout_details']));

        return response()->json([
            'success' => true,
            'message' => 'Beneficiary updated successfully',
            'data' => $beneficiary->fresh()->load(['country', 'method']),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $beneficiary = Beneficiary::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $beneficiary->delete();

        return response()->json([
            'success' => true,
            'message' => 'Beneficiary deleted successfully',
        ]);
    }
}

