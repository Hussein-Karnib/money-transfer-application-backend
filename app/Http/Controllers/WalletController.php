<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\UserBankAccount;
use App\Models\WalletTransaction;
use App\Http\Controllers\AuditLogController;

class WalletController extends Controller
{
    /**
     * Cash In - Transfer money from bank account to user wallet
     */
    public function cashIn(Request $request)
    {
        $request->validate([
            'bank_account_id' => ['required', 'exists:user_bank_accounts,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'size:3', 'exists:currencies,code'],
        ]);

        $user = Auth::user();
        $bankAccount = UserBankAccount::where('id', $request->bank_account_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Check if bank account is verified
        if ($bankAccount->status !== 'verified') {
            return redirect()->back()
                ->with('error', 'Bank account must be verified before you can cash in.');
        }

        // Check currency match
        if ($bankAccount->currency_code !== $request->currency) {
            return redirect()->back()
                ->with('error', 'Currency mismatch. Please select the correct currency for your bank account.');
        }

        try {
            DB::transaction(function () use ($user, $bankAccount, $request) {
                $amount = $request->amount;
                $currency = $request->currency;

                // Update user balance
                if ($user->balance_currency === $currency) {
                    // Same currency - add to balance
                    $user->balance = ($user->balance ?? 0) + $amount;
                } else {
                    // Different currency - set new balance (assuming user wants to switch currency)
                    $user->balance = $amount;
                    $user->balance_currency = $currency;
                }
                $user->save();

                // Create wallet transaction record
                WalletTransaction::create([
                    'user_id' => $user->id,
                    'bank_account_id' => $bankAccount->id,
                    'type' => 'cash_in',
                    'amount' => $amount,
                    'currency_code' => $currency,
                    'status' => 'completed',
                    'description' => "Cash in from {$bankAccount->bank_name}",
                ]);

                // Log audit
                AuditLogController::logSystemAction(
                    $user->id,
                    'cash_in',
                    'wallet_transactions',
                    null,
                    [
                        'amount' => $amount,
                        'currency' => $currency,
                        'bank_account' => $bankAccount->bank_name,
                    ]
                );
            });

            return redirect()->route('dashboard')
                ->with('success', "Successfully cashed in {$request->amount} {$request->currency} to your wallet.");
        } catch (\Exception $e) {
            \Log::error('Cash in error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'bank_account_id' => $request->bank_account_id,
                'amount' => $request->amount,
            ]);

            return redirect()->back()
                ->with('error', 'An error occurred during cash in. Please try again.');
        }
    }

    /**
     * Cash Out - Transfer money from user wallet to bank account
     */
    public function cashOut(Request $request)
    {
        $request->validate([
            'bank_account_id' => ['required', 'exists:user_bank_accounts,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'size:3', 'exists:currencies,code'],
        ]);

        $user = Auth::user();
        $bankAccount = UserBankAccount::where('id', $request->bank_account_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Check if bank account is verified
        if ($bankAccount->status !== 'verified') {
            return redirect()->back()
                ->with('error', 'Bank account must be verified before you can cash out.');
        }

        // Check currency match
        if ($bankAccount->currency_code !== $request->currency) {
            return redirect()->back()
                ->with('error', 'Currency mismatch. Please select the correct currency for your bank account.');
        }

        // Check if user has sufficient balance
        $currentBalance = $user->balance ?? 0;
        if ($user->balance_currency !== $request->currency) {
            return redirect()->back()
                ->with('error', 'Currency mismatch. Your wallet balance is in ' . ($user->balance_currency ?? 'USD') . '.');
        }

        if ($currentBalance < $request->amount) {
            return redirect()->back()
                ->with('error', 'Insufficient balance. Available: ' . number_format($currentBalance, 2) . ' ' . $request->currency);
        }

        try {
            $amount = $request->amount;
            $currency = $request->currency;
            $transaction = null;

            DB::transaction(function () use ($user, $bankAccount, $amount, $currency, &$transaction) {
                // Refresh user to get latest balance
                $user->refresh();
                
                // Deduct balance immediately
                $user->decrement('balance', $amount);
                
                // Ensure balance doesn't go negative (safety check)
                if ($user->balance < 0) {
                    $user->balance = 0;
                    $user->save();
                }

                // Create wallet transaction record with completed status
                $transaction = WalletTransaction::create([
                    'user_id' => $user->id,
                    'bank_account_id' => $bankAccount->id,
                    'type' => 'cash_out',
                    'amount' => $amount,
                    'currency_code' => $currency,
                    'status' => 'completed', // Immediately completed
                    'description' => "Cash out to {$bankAccount->bank_name}",
                ]);

                // Log audit
                AuditLogController::logSystemAction(
                    $user->id,
                    'cash_out',
                    'wallet_transactions',
                    $transaction->id,
                    [
                        'amount' => $amount,
                        'currency' => $currency,
                        'bank_account' => $bankAccount->bank_name,
                        'status' => 'completed',
                    ]
                );
            });

            // Send notification to user AFTER transaction commits
            // This ensures the notification is saved properly
            if ($transaction) {
                $user->refresh();
                try {
                    $user->notify(new \App\Notifications\CashOutSuccessful($transaction, $bankAccount));
                    \Log::info('Cash-out notification sent', [
                        'user_id' => $user->id,
                        'transaction_id' => $transaction->id,
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Failed to send cash-out notification: ' . $e->getMessage(), [
                        'user_id' => $user->id,
                        'transaction_id' => $transaction->id,
                    ]);
                }
            }

                // Log audit
                AuditLogController::logSystemAction(
                    $user->id,
                    'cash_out',
                    'wallet_transactions',
                    $transaction->id,
                    [
                        'amount' => $amount,
                        'currency' => $currency,
                        'bank_account' => $bankAccount->bank_name,
                        'status' => 'completed',
                    ]
                );
            });

            return redirect()->route('dashboard')
                ->with('success', "Cash-out successful! {$request->amount} {$request->currency} has been sent to your bank account. Your balance has been updated.");
        } catch (\Exception $e) {
            \Log::error('Cash out error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'bank_account_id' => $request->bank_account_id,
                'amount' => $request->amount,
            ]);

            return redirect()->back()
                ->with('error', 'An error occurred during cash out. Please try again.');
        }
    }

}

