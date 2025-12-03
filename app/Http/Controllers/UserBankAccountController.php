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
     * OLD: generateAccountNumber (incremental like 000001...)
     * You can keep it for future non-card accounts if you want,
     * but it's no longer used for card accounts.
     */
    private function generateAccountNumber(): string
    {
        $last = UserBankAccount::where('user_id', Auth::id())
            ->orderByDesc('id')
            ->first();

        $nextNumber = $last ? ((int) $last->account_number + 1) : 1;

        return str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }

    public function index(): JsonResponse
    {
        $accounts = UserBankAccount::where('user_id', Auth::id())
            ->with('currency')
            ->get();

        // Format for frontend
        $data = $accounts->map(function (UserBankAccount $account) {
            return $this->formatAccount($account);
        });

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        // Validate the input data
        $data = $request->validate([
            // bank_name now OPTIONAL – we can auto-generate a nice label
            'bank_name'     => ['nullable', 'string', 'max:255'],
            // account_number is NOT provided by client anymore
            'currency_code' => ['required', 'string', 'size:3', 'exists:currencies,code'],
        ]);

        // -----------------------------------------
        // 1) Generate SENDER CARD (Visa-style)
        // -----------------------------------------
        $cardNumber = $this->generateCardNumber();
        $brand      = $this->detectCardBrand($cardNumber) ?? 'visa';
        $digits     = preg_replace('/\D/', '', $cardNumber);
        $last4      = substr($digits, -4);
        $masked     = substr($digits, 0, 4) . ' **** **** ' . $last4;

        // Auto bank name if not provided
        $bankName = $data['bank_name'] ?? ('My ' . strtoupper($data['currency_code']) . ' Visa');

        $account = UserBankAccount::create([
            'user_id'        => Auth::id(),
            'bank_name'      => $bankName,
            // store FULL card number in account_number (SENDER side)
            'account_number' => $cardNumber,
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

        $account->load('currency');

        return response()->json([
            'success' => true,
            'message' => 'Bank account added successfully',
            'data'    => $this->formatAccount($account, $brand, $last4, $masked),
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
        'data'    => $this->formatAccount($account),
    ]);
}

public function update(Request $request, int $id): JsonResponse
{
    $account = UserBankAccount::where('id', $id)
        ->where('user_id', Auth::id())
        ->firstOrFail();

    // We DO NOT allow changing account_number here (that’s dangerous)
    $data = $request->validate([
        'bank_name'     => ['sometimes', 'string', 'max:255'],
        'currency_code' => ['sometimes', 'string', 'size:3', 'exists:currencies,code'],
    ]);

    $account->update($data);

    $account = $account->fresh()->load('currency');

    return response()->json([
        'success' => true,
        'message' => 'Bank account updated successfully',
        'data'    => $this->formatAccount($account),
    ]);
}

public function destroy(int $id): JsonResponse
{
    $account = UserBankAccount::where('id', $id)
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

        $account = $account->fresh()->load('currency');

        return response()->json([
            'success' => true,
            'message' => 'Bank account verification updated',
            'data'    => $this->formatAccount($account),
        ]);
    }

    // =========================================================
    //  FORMATTER FOR FRONTEND
    // =========================================================
    private function formatAccount(UserBankAccount $account, ?string $brandOverride = null, ?string $last4Override = null, ?string $maskedOverride = null): array
    {
        $digits = preg_replace('/\D/', '', $account->account_number);

        $brand = $brandOverride ?? ($this->detectCardBrand($digits) ?? 'unknown');
        $last4 = $last4Override ?? substr($digits, -4);
        $masked = $maskedOverride ?? (strlen($digits) >= 4
            ? substr($digits, 0, 4) . ' **** **** ' . $last4
            : $digits
        );

        return [
            'id'            => $account->id,
            'user_id'       => $account->user_id,
            'bank_name'     => $account->bank_name,
            'currency_code' => $account->currency_code,
            'status'        => $account->status,
            'card'          => [
                'card_number' => $digits,   // full PAN – visible in dev, hide in prod UI
                'brand'       => $brand,
                'last4'       => $last4,
                'masked'      => $masked,
            ],
            'currency'      => $account->currency ? [
                'code'     => $account->currency->code,
                'name'     => $account->currency->name,
                'decimals' => $account->currency->decimals,
            ] : null,
        ];
    }

    // =========================================================
    //  CARD HELPERS (same logic style as BeneficiaryController)
    // =========================================================

    /**
     * Generate a random, Luhn-valid 16-digit Visa style card number.
     * This is for DEMO purposes (no real cards in dev).
     */
    private function generateCardNumber(): string
    {
        // Start with 15 digits (Visa usually starts with 4)
        $digits = '4';
        for ($i = 0; $i < 14; $i++) {
            $digits .= random_int(0, 9);
        }

        // Compute Luhn check digit
        $sum = 0;
        $alt = true; // start doubling from the rightmost of the 15
        for ($i = strlen($digits) - 1; $i >= 0; $i--) {
            $n = intval($digits[$i]);
            if ($alt) {
                $n *= 2;
                if ($n > 9) {
                    $n -= 9;
                }
            }
            $sum += $n;
            $alt = !$alt;
        }

        $checkDigit = (10 - ($sum % 10)) % 10;

        return $digits . $checkDigit;
    }

    private function detectCardBrand(string $number): ?string
    {
        $number = preg_replace('/\D/', '', $number);

        if (preg_match('/^4[0-9]{12}(?:[0-9]{3})?$/', $number)) {
            return 'visa';
        }

        if (preg_match('/^5[1-5][0-9]{14}$/', $number)) {
            return 'mastercard';
        }

        if (preg_match('/^3[47][0-9]{13}$/', $number)) {
            return 'amex';
        }

        if (preg_match('/^6(?:011|5[0-9]{2})[0-9]{12}$/', $number)) {
            return 'discover';
        }

        return null;
    }
}
