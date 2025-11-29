<?php

namespace App\Http\Controllers;

use App\Models\User_BankAccount;
use Illuminate\Http\Request;


class UserBankAccountController extends Controller
{
    public function index(Request $request)
    {
        $accounts = $request->user()
            ->bankAccounts()
            ->with('currency')
            ->get();

        return response()->json($accounts);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'bank_name'      => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:100'],
            'currency_code'  => ['required', 'string', 'exists:currencies,code'],
            'is_default'     => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();

        $isDefault = $data['is_default'] ?? false;

        if ($isDefault) {
            $user->bankAccounts()->update(['is_default' => false]);
        }

        $account = User_BankAccount::create([
            'user_id'        => $user->id,
            'bank_name'      => $data['bank_name'],
            'account_number' => $data['account_number'],
            'currency_code'  => $data['currency_code'],
            'is_default'     => $isDefault,
            'verified'       => false,
        ]);

        return response()->json($account, 201);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $account = $user->bankAccounts()->findOrFail($id);
        $account->delete();

        return response()->json([
            'message' => 'Bank account removed.',
        ]);
    }
}
