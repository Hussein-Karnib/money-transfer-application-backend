<?php

namespace App\Notifications;

use App\Models\Transfer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransferStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Transfer $transfer,
        public string $oldStatus,
        public string $newStatus,
        public ?string $message = null
    ) {
        $this->queue = 'notifications';
    }

    public function via($notifiable): array
    {
        $prefs = $notifiable->getOrCreateNotificationPreference();
        
        if ($prefs->isMuted() || !$prefs->isNotificationTypeEnabled('transfer_status_update')) {
            return [];
        }

        return $prefs->getEnabledChannels();
    }

    public function toMail($notifiable): MailMessage
    {
        $statusMessages = [
            'pending' => 'Your transfer is pending',
            'processing' => 'Your money is being processed',
            'funds_sent' => 'Your money has been sent! Recipient should receive it by ' . $this->transfer->estimated_delivery_at->format('H:i A'),
            'completed' => 'Transfer completed successfully',
            'cancelled' => 'Your transfer has been cancelled',
            'failed' => 'Your transfer failed. Please contact support.',
        ];

        $statusMessage = $this->message ?? ($statusMessages[$this->newStatus] ?? 'Status updated');

        return (new MailMessage)
            ->subject("Transfer {$this->newStatus} - {$this->transfer->reference}")
            ->greeting("Hello {$notifiable->name},")
            ->line($statusMessage)
            ->line("Reference: {$this->transfer->reference}")
            ->line("Amount: {$this->transfer->amount} {$this->transfer->currency_from}")
            ->line("Status: " . ucfirst(str_replace('_', ' ', $this->newStatus)))
            ->action('View Transfer', url("/app/transfers/{$this->transfer->id}"))
            ->line('Thank you for using our service!');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'transfer_status_update',
            'title' => 'Transfer ' . ucfirst(str_replace('_', ' ', $this->newStatus)),
            'message' => $this->message ?? "Your transfer is now {$this->newStatus}",
            'transfer_id' => $this->transfer->id,
            'reference' => $this->transfer->reference,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'amount' => $this->transfer->amount,
            'currency' => $this->transfer->currency_from,
            'estimated_delivery' => $this->transfer->estimated_delivery_at,
        ];
    }
}
