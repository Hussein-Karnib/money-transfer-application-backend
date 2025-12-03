<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\UserBankAccount;
use App\Http\Controllers\AuditLogController;

class UserBankAccountController extends Controller
{
    /**
     * Generate the next account number for the CURRENT user.
     * Example: 000001, 000002, 000003, ...
     */
    private function generateAccountNumber(): string
    {
        // Get the last account for this user (by id)
        $last = UserBankAccount::where('user_id', Auth::id())
            ->orderByDesc('id')
            ->first();

        // If no accounts yet → start from 1, else increment
        $nextNumber = $last ? ((int) $last->account_number + 1) : 1;

        // Return as 6-digit padded string (change 6 → 10 if you want longer)
        return str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }

    public function index(): JsonResponse
    {
        $accounts = UserBankAccount::where('user_id', Auth::id())
            ->with('currency')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $accounts,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        // Validate the input data
        $data = $request->validate([
            'bank_name'     => ['required', 'string', 'max:255'],
            // account_number is NOT provided by client anymore
            'currency_code' => ['required', 'string', 'size:3', 'exists:currencies,code'],
        ]);

        $account = UserBankAccount::create([
            'user_id'        => Auth::id(),
            'bank_name'      => $data['bank_name'],
            'account_number' => $this->generateAccountNumber(), // 👈 auto-generated
            'currency_code'  => $data['currency_code'],
            'status'         => 'pending',
        ]);

        AuditLogController::logSystemAction(
            Auth::id(),
            'create_bank_account',
            'user_bank_accounts',
            $account->id,
            ['bank_name' => $account->bank_name, 'currency' => $account->currency_code]
        );

        return response()->json([
            'success' => true,
            'message' => 'Bank account added successfully',
            'data'    => $account->load('currency'),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $account = UserBankAccount::where('account_number', $id)
            ->where('user_id', Auth::id())
            ->with('currency')
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data'    => $account,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $account = UserBankAccount::where('account_number', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // We DO NOT allow changing account_number here (that’s dangerous)
        $data = $request->validate([
            'bank_name'     => ['sometimes', 'string', 'max:255'],
            'currency_code' => ['sometimes', 'string', 'size:3', 'exists:currencies,code'],
        ]);

        $account->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Bank account updated successfully',
            'data'    => $account->fresh()->load('currency'),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $account = UserBankAccount::where('account_number', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $account->delete();

        AuditLogController::logSystemAction(
            Auth::id(),
            'delete_bank_account',
            'user_bank_accounts',
            $id,
            []
        );

        return response()->json([
            'success' => true,
            'message' => 'Bank account deleted successfully',
        ]);
    }

    public function verify(Request $request, int $id): JsonResponse
    {
        // This one is probably for admin/agent, so we don't limit by Auth::id()
        $account = UserBankAccount::findOrFail($id);

        $data = $request->validate([
            'status' => ['required', 'in:verified,rejected'],
        ]);

        $account->update([
            'status'      => $data['status'],
            'verified_at' => $data['status'] === 'verified' ? now() : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bank account verification updated',
            'data'    => $account->fresh()->load('currency'),
        ]);
    }
}
