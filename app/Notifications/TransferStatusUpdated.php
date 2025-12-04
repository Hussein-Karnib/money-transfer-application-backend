<?php

namespace App\Notifications;

use App\Models\Transfer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransferStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Transfer $transfer,
        public string $oldStatus,
        public string $newStatus
    ) {}

    public function via($notifiable): array
    {
        // You can toggle channels here:
        return ['mail', 'database']; // later: 'sms', 'broadcast', 'expo'
    }

    public function toMail($notifiable): MailMessage
    {
        $t = $this->transfer;

        return (new MailMessage)
            ->subject("Your transfer #{$t->reference} is now {$this->newStatus}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your transfer to {$t->beneficiary->full_name} changed status.")
            ->line("Old status: {$this->oldStatus}")
            ->line("New status: {$this->newStatus}")
            ->line("Amount: {$t->amount} {$t->currency_from}")
            ->action('View transfer', url("/app/transfers/{$t->id}")) // frontend URL
            ->line('Thank you for using our service!');
    }

    public function toArray($notifiable): array
    {
        $t = $this->transfer;

        return [
            'transfer_id'   => $t->id,
            'reference'     => $t->reference,
            'old_status'    => $this->oldStatus,
            'new_status'    => $this->newStatus,
            'amount'        => $t->amount,
            'currency_from' => $t->currency_from,
            'currency_to'   => $t->currency_to,
            'beneficiary'   => $t->beneficiary?->full_name,
        ];
    }
    public function toSms($notifiable): string
{
    $t = $this->transfer;

    return "Transfer {$t->reference} is now {$this->newStatus}. Amount: {$t->amount} {$t->currency_from}.";
}

}
