<?php
namespace App\Http\Controllers;
use App\Models\UserBankAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserBankAccountController extends Controller
{
 
    public function index(): JsonResponse
    {
        $accounts = UserBankAccount::where('user_id', Auth::id())
            ->with('currency')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $accounts,
        ]);
    }

    
    public function store(Request $request): JsonResponse
    {
        // Validate the input data
        $request->validate([
            'bank_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:255'],
            'currency_code' => ['required', 'string', 'size:3', 'exists:currencies,code'],
        ]);

        $account = UserBankAccount::create([
            'user_id' => Auth::id(),
            'bank_name' => $request->bank_name,
            'account_number' => $request->account_number,
            'currency_code' => $request->currency_code,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bank account added successfully',
            'data' => $account->load('currency'),
        ], 201);
    }

 
    public function show(int $id): JsonResponse
    {
        $account = UserBankAccount::where('id', $id)
            ->where('user_id', Auth::id())
            ->with('currency')
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $account,
        ]);
    }

   
    public function update(Request $request, int $id): JsonResponse
    {
        $account = UserBankAccount::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $request->validate([
            'bank_name' => ['sometimes', 'string', 'max:255'],
            'account_number' => ['sometimes', 'string', 'max:255'],
            'currency_code' => ['sometimes', 'string', 'size:3', 'exists:currencies,code'],
        ]);

        $account->update($request->only(['bank_name', 'account_number', 'currency_code']));

        return response()->json([
            'success' => true,
            'message' => 'Bank account updated successfully',
            'data' => $account->fresh()->load('currency'),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $account = UserBankAccount::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $account->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bank account deleted successfully',
        ]);
    }

    
    public function verify(Request $request, int $id): JsonResponse
    {
       
        $account = UserBankAccount::findOrFail($id);

        $request->validate([
            'status' => ['required', 'in:verified,rejected'],
        ]);

        $account->update([
            'status' => $request->status,
            'verified_at' => $request->status === 'verified' ? now() : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bank account verification updated',
            'data' => $account->fresh()->load('currency'),
        ]);
    }
}

