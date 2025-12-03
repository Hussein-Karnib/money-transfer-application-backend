<?php

namespace App\Http\Controllers;

use App\Models\Beneficiary;
use App\Models\Country;
use App\Models\Transfer_Method;
use App\Models\UserBankAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuditLogController;

/*
 Example payout_details for bank transfer:
 {
   "type": "iban",
   "iban": "LB62099900000001001101234567",
   "country_iso2": "LB",
   "bank_account_id": 3
 }

 Example payout_details for card transfer:
 {
   "type": "card",
   "brand": "mastercard",
   "last4": "2345",
   "masked": "5123 **** **** 2345",
   "bank_account_id": 3
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
            'data'    => $beneficiaries,
        ]);
    }

    /*
      Store a new beneficiary

      Supported body examples:

      1) Using explicit account / IBAN:
      {
        "full_name": "John Doe",
        "country_id": 1,
        "transfer_method_id": 3,   // e.g. "Bank Transfer"
        "payout_details": {
          "account_number": "LB62099900000001001101234567"
        }
      }

      2) Using an already-created bank account:
      {
        "full_name": "John Doe",
        "country_id": 1,
        "transfer_method_id": 7,   // e.g. "Card-to-Card Transfer"
        "bank_account_id": 3
      }
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'full_name'           => ['required', 'string', 'max:255'],
            'country_id'          => ['required', 'integer', 'exists:countries,id'],
            'transfer_method_id'  => ['required', 'integer', 'exists:transfer_methods,id'],

            'bank_account_id'     => ['nullable', 'integer', 'exists:user_bank_accounts,id'],
            'payout_details'      => ['nullable', 'array'],
            'payout_details.account_number' => ['nullable', 'string', 'max:100'],
        ]);

        $userId   = Auth::id();
        $country  = Country::findOrFail($request->country_id);
        $method   = Transfer_Method::findOrFail($request->transfer_method_id);

        // ----------------------------------------------------
        // 1) Decide where the "account_number" comes from
        // ----------------------------------------------------
        $accountNumber = null;
        $bankAccountId = null;

        if ($request->filled('bank_account_id')) {
            // Use existing user bank account as source of truth
            $bankAccount = UserBankAccount::where('id', $request->bank_account_id)
                ->where('user_id', $userId)
                ->firstOrFail();

            $accountNumber = $bankAccount->account_number;
            $bankAccountId = $bankAccount->id;
        } elseif (!empty($request->payout_details['account_number'])) {
            $accountNumber = $request->payout_details['account_number'];
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Either bank_account_id or payout_details.account_number is required.',
            ], 422);
        }

        // Normalize method name
        $methodName = strtolower($method->name);

        // ----------------------------------------------------
        // 2) Build payout_details based on method type
        // ----------------------------------------------------
        $payoutDetails = [];

        // a) Card-based methods (Card-to-Card, etc.)
        if (str_contains($methodName, 'card')) {

            if (!$this->isValidCardNumber($accountNumber)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid card number. Must be a valid Visa/Mastercard/Amex/Discover card.',
                ], 422);
            }

            $brand  = $this->detectCardBrand($accountNumber) ?? 'unknown';
            $digits = preg_replace('/\D/', '', $accountNumber);
            $last4  = substr($digits, -4);

            $payoutDetails = [
                'type'            => 'card',
                'brand'           => $brand,
                'last4'           => $last4,
                'masked'          => substr($digits, 0, 4) . ' **** **** ' . $last4,
                'bank_account_id' => $bankAccountId,
            ];
        }

        // b) Bank transfer / IBAN-based methods
        elseif (str_contains($methodName, 'bank')) {

            if (!$this->isValidIBAN($accountNumber, $country->iso2)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid IBAN for country ' . $country->iso2 . '.',
                ], 422);
            }

            $iban = strtoupper(str_replace(' ', '', $accountNumber));

            $payoutDetails = [
                'type'            => 'iban',
                'iban'            => $iban,
                'country_iso2'    => $country->iso2,
                'bank_account_id' => $bankAccountId,
            ];
        }

        // c) Other payout types (cash pickup, wallet, etc.)
        else {
            // Just store what was given (if any)
            $payoutDetails = $request->payout_details ?? [];
            $payoutDetails['bank_account_id'] = $bankAccountId;
        }

        $beneficiary = Beneficiary::create([
            'user_id'            => $userId,
            'full_name'          => $request->full_name,
            'country_id'         => $country->id,
            'transfer_method_id' => $method->id,
            'payout_details'     => $payoutDetails,
        ]);

        AuditLogController::logSystemAction(
            Auth::id(),
            'create_beneficiary',
            'beneficiaries',
            $beneficiary->id,
            ['full_name' => $beneficiary->full_name]
        );

        return response()->json([
            'success' => true,
            'message' => 'Beneficiary added successfully',
            'data'    => $beneficiary->load(['country', 'method']),
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
            'data'    => $beneficiary,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $beneficiary = Beneficiary::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $request->validate([
            'full_name'           => ['sometimes', 'string', 'max:255'],
            'country_id'          => ['sometimes', 'integer', 'exists:countries,id'],
            'transfer_method_id'  => ['sometimes', 'integer', 'exists:transfer_methods,id'],
            'payout_details'      => ['sometimes', 'array'],
        ]);

        $beneficiary->update($request->only(['full_name', 'country_id', 'transfer_method_id', 'payout_details']));

        AuditLogController::logSystemAction(
            Auth::id(),
            'update_beneficiary',
            'beneficiaries',
            $beneficiary->id,
            ['changes' => $request->only(['full_name', 'country_id', 'transfer_method_id', 'payout_details'])]
        );

        return response()->json([
            'success' => true,
            'message' => 'Beneficiary updated successfully',
            'data'    => $beneficiary->fresh()->load(['country', 'method']),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $beneficiary = Beneficiary::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $beneficiary->delete();

        AuditLogController::logSystemAction(
            Auth::id(),
            'delete_beneficiary',
            'beneficiaries',
            $id,
            []
        );

        return response()->json([
            'success' => true,
            'message' => 'Beneficiary deleted successfully',
        ]);
    }

    // =========================================================
    //  IBAN + CARD HELPERS
    // =========================================================

    private function isValidIBAN(string $iban, ?string $countryIso2 = null): bool
    {
        $iban = strtoupper(str_replace(' ', '', $iban));

        // 1) Basic char rules
        if (!preg_match('/^[A-Z0-9]+$/', $iban)) {
            return false;
        }

        // 2) Basic length rules
        if (strlen($iban) < 15 || strlen($iban) > 34) {
            return false;
        }

        // 3) Country-specific length rules (if we know the country)
        $countryIso2 = strtoupper($countryIso2 ?? '');
        $lengthMap = [
            'LB' => 28,
            'DE' => 22,
            'FR' => 27,
            'GB' => 22,
            'ES' => 24,
            'IT' => 27,
            'NL' => 18,
            'BE' => 16,
            'CH' => 21,
            'TR' => 26,
            'SA' => 24,
            'QA' => 29,
            'AE' => 23,
        ];

        if ($countryIso2 && isset($lengthMap[$countryIso2])) {
            if (strlen($iban) !== $lengthMap[$countryIso2]) {
                return false;
            }
        }

        // 4) IBAN checksum mod 97 == 1
        return $this->passesIbanChecksum($iban);
    }

    private function passesIbanChecksum(string $iban): bool
    {
        // Move first 4 chars to end
        $rearranged = substr($iban, 4) . substr($iban, 0, 4);

        // Replace letters with numbers: A=10, B=11, ..., Z=35
        $numericString = '';
        for ($i = 0; $i < strlen($rearranged); $i++) {
            $c = $rearranged[$i];
            if (ctype_alpha($c)) {
                $numericString .= (ord($c) - 55); // A=10, B=11, ...
            } else {
                $numericString .= $c;
            }
        }

        // Compute mod 97 iteratively (string may be very long)
        $remainder = 0;
        $len = strlen($numericString);
        $block = '';

        for ($i = 0; $i < $len; $i++) {
            $block .= $numericString[$i];
            // To avoid overflow, do mod when block grows
            if (strlen($block) > 8) {
                $remainder = intval($block) % 97;
                $block = (string)$remainder;
            }
        }

        if ($block !== '') {
            $remainder = intval($block) % 97;
        }

        return $remainder === 1;
    }

    private function isValidCardNumber(string $number): bool
    {
        $number = preg_replace('/\D/', '', $number);

        // Basic PAN length check (14–19 digits allowed generally, we restrict to 15–16 for common brands)
        if (strlen($number) < 14 || strlen($number) > 19) {
            return false;
        }

        // Basic known patterns
        $cardRegex =
            '/^(4[0-9]{12}(?:[0-9]{3})?)$|' .            // Visa
            '^(5[1-5][0-9]{14})$|' .                    // Mastercard (classic)
            '^(3[47][0-9]{13})$|' .                     // American Express
            '^(6(?:011|5[0-9]{2})[0-9]{12})$/';         // Discover

        if (!preg_match($cardRegex, $number)) {
            return false;
        }

        // Luhn checksum
        return $this->luhnCheck($number);
    }

    private function luhnCheck(string $number): bool
    {
        $sum = 0;
        $alt = false;

        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $n = intval($number[$i]);

            if ($alt) {
                $n *= 2;
                if ($n > 9) {
                    $n -= 9;
                }
            }

            $sum += $n;
            $alt = !$alt;
        }

        return ($sum % 10 === 0);
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
