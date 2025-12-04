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
    $data = $request->validate([
        'full_name'           => ['required', 'string', 'max:255'],
        'country_id'          => ['required', 'integer', 'exists:countries,id'],
        'transfer_method_id'  => ['required', 'integer', 'exists:transfer_methods,id'],

        'bank_account_id'     => ['nullable', 'integer', 'exists:user_bank_accounts,id'],

        // generic payout_details object (optional)
        'payout_details'                => ['nullable', 'array'],
        'payout_details.account_number' => ['nullable', 'string', 'max:100'],

        // optional shortcuts – front-end is NOT forced to send these
        'card_number'                   => ['nullable', 'string', 'max:30'],
        'iban'                          => ['nullable', 'string', 'max:50'],
    ]);

    $userId  = Auth::id();
    $country = Country::findOrFail($data['country_id']);
    $method  = Transfer_Method::findOrFail($data['transfer_method_id']);

    $methodName = strtolower($method->name);

    // --------------------------------------------------
    // BASE DETAILS FROM SENDER BANK ACCOUNT (USER)
    // --------------------------------------------------
    $baseDetails = [];
    $bankAccount = null;

    if (!empty($data['bank_account_id'])) {
        $bankAccount = UserBankAccount::where('id', $data['bank_account_id'])
            ->where('user_id', $userId)
            ->firstOrFail();

        // IMPORTANT:
        // This is the SENDER account, not the receiver.
        // We do NOT copy its account_number to the receiver.
        $baseDetails = [
            'bank_account_id' => $bankAccount->id,
            'bank_name'       => $bankAccount->bank_name,
            'currency_code'   => $bankAccount->currency_code,
        ];
    }

    // --------------------------------------------------
    // BUILD PAYOUT DETAILS PER METHOD TYPE
    // --------------------------------------------------
    $payoutDetails = [];

    // =============== CARD METHODS ======================
    if (str_contains($methodName, 'card')) {
        // receiver card number:
        // 1) if frontend sends card_number (for testing)
        // 2) otherwise AUTO-GENERATE
        $accountNumber = null;

        if (!empty($data['card_number'])) {
            $accountNumber = $data['card_number'];
        } else {
            // AUTO generate for receiver, no user input needed
            $accountNumber = $this->generateCardNumber();
        }

        $digits = preg_replace('/\D/', '', $accountNumber);

        if (!$this->isValidCardNumber($digits)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid card number generated or provided.',
            ], 422);
        }

        $brand = $this->detectCardBrand($digits) ?? 'unknown';
        $last4 = substr($digits, -4);

        $payoutDetails = array_merge($baseDetails, [
            'type'        => 'card',
            // full PAN stored only for our project logic
            'card_number' => $digits,
            'brand'       => $brand,
            'last4'       => $last4,
            'masked'      => substr($digits, 0, 4) . ' **** **** ' . $last4,
        ]);
    }

    // =============== BANK / IBAN METHODS ==============
    elseif (str_contains($methodName, 'bank')) {

        $ibanCountries = ['LB','DE','FR','GB','ES','IT','NL','BE','CH','TR','SA','QA','AE'];

        // Receiver account (IBAN or local)
        $accountNumber = null;

        // 1) explicit IBAN in "iban" field
        if (!empty($data['iban'])) {
            $accountNumber = $data['iban'];
        }
        // 2) explicit account_number inside payout_details (for manual/testing)
        elseif (!empty($data['payout_details']['account_number'])) {
            $accountNumber = $data['payout_details']['account_number'];
        }
        // 3) if still empty → AUTO-GENERATE IBAN-like
        else {
            $accountNumber = $this->generateIbanForCountry($country->iso2);
        }

        $accountNumber = strtoupper(str_replace(' ', '', $accountNumber));

        if (in_array($country->iso2, $ibanCountries)) {
            if (!$this->isValidIBAN($accountNumber, $country->iso2)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid IBAN for country ' . $country->iso2 . '.',
                ], 422);
            }

            $payoutDetails = array_merge($baseDetails, [
                'type'         => 'iban',
                'iban'         => $accountNumber,
                'country_iso2' => $country->iso2,
            ]);
        } else {
            // Non-IBAN country
            $payoutDetails = array_merge($baseDetails, [
                'type'           => 'local_account',
                'account_number' => $accountNumber,
                'country_iso2'   => $country->iso2,
            ]);
        }
    }

    // =============== OTHER METHODS ====================
    // Cash pickup, mobile wallet, ATM, etc.
    else {
        // Here we don't force an account_number at all.
        // We just merge whatever extra was sent plus the sender bank info.
        $extra = $data['payout_details'] ?? [];

        $payoutDetails = array_merge($baseDetails, $extra);
        // Example final structure:
        // {
        //   "bank_account_id": 4,
        //   "bank_name": "beirut_bank",
        //   "currency_code": "USD",
        //   "wallet_number": "...",
        //   "pickup_location": "..."
        // }
    }

    // --------------------------------------------------
    // CREATE BENEFICIARY
    // --------------------------------------------------
    $beneficiary = Beneficiary::create([
        'user_id'            => $userId,
        'full_name'          => $data['full_name'],
        'country_id'         => $data['country_id'],
        'transfer_method_id' => $data['transfer_method_id'],
        'payout_details'     => $payoutDetails,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Beneficiary added successfully',
        'data'    => $beneficiary->load(['country', 'method']),
    ], 201);
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

        // Basic PAN length check (14–19 digits allowed generally, we restrict to 14–19)
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

    // =========================================================
    //  AUTO GENERATORS (CARD + IBAN)
    // =========================================================

    /**
     * Generate a random, Luhn-valid 16-digit Visa style card number.
     * This is for DEMO purposes (you don't want real cards in dev).
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

    /**
     * Generate an IBAN-like string for a given country that passes the checksum.
     * Not a real bank account, but structurally valid for demos.
     */
    private function generateIbanForCountry(string $countryIso2): string
    {
        $countryIso2 = strtoupper($countryIso2 ?: 'XX');

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

        // Default to 24 if country not in map
        $totalLength = $lengthMap[$countryIso2] ?? 24;

        // BBAN length = total - 4 (country + 2 check digits)
        $bbanLength = $totalLength - 4;

        $bban = '';
        for ($i = 0; $i < $bbanLength; $i++) {
            $bban .= random_int(0, 9);
        }

        // Temporary IBAN with '00' check digits
        $tempIban = $countryIso2 . '00' . $bban;

        // Rearrange as per IBAN rules
        $rearranged = substr($tempIban, 4) . substr($tempIban, 0, 4);

        // Convert to numeric string
        $numericString = '';
        for ($i = 0; $i < strlen($rearranged); $i++) {
            $c = $rearranged[$i];
            if (ctype_alpha($c)) {
                $numericString .= (ord($c) - 55); // A=10...
            } else {
                $numericString .= $c;
            }
        }

        // Compute mod 97
        $remainder = 0;
        $block = '';
        $len = strlen($numericString);

        for ($i = 0; $i < $len; $i++) {
            $block .= $numericString[$i];
            if (strlen($block) > 8) {
                $remainder = intval($block) % 97;
                $block = (string)$remainder;
            }
        }

        if ($block !== '') {
            $remainder = intval($block) % 97;
        }

        // Calculate check digits
        $checkDigits = 98 - ($remainder % 97);
        $checkDigitsStr = str_pad((string)$checkDigits, 2, '0', STR_PAD_LEFT);

        return $countryIso2 . $checkDigitsStr . $bban;
    }
}
