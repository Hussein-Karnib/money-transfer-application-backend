<?php

namespace App\Notifications;

use App\Models\WalletTransaction;
use App\Models\UserBankAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CashOutSuccessful extends Notification
{
    use Queueable;

    public function __construct(
        public WalletTransaction $transaction,
        public UserBankAccount $bankAccount
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Cash-Out Successful')
            ->greeting("Hello {$notifiable->name},")
            ->line("Your cash-out request has been processed successfully!")
            ->line("Amount: " . number_format($this->transaction->amount, 2) . " {$this->transaction->currency_code}")
            ->line("Bank Account: {$this->bankAccount->bank_name}")
            ->line("Account Number: {$this->bankAccount->account_number}")
            ->line("The funds have been sent to your bank account and your wallet balance has been updated.")
            ->action('View Dashboard', url('/dashboard'))
            ->line('Thank you for using our service!');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'cash_out_successful',
            'message' => "Cash-out successful! " . number_format($this->transaction->amount, 2) . " {$this->transaction->currency_code} has been sent to your bank account ({$this->bankAccount->bank_name}).",
            'amount' => $this->transaction->amount,
            'currency' => $this->transaction->currency_code,
            'bank_account' => $this->bankAccount->bank_name,
            'transaction_id' => $this->transaction->id,
            'created_at' => $this->transaction->created_at->toDateTimeString(),
        ];
    }
}
