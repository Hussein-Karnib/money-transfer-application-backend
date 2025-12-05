<?php

namespace App\Notifications;

use App\Models\Transfer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransferInitiated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Transfer $transfer,
        public string $senderName,
        public string $beneficiaryName
    ) {
        $this->queue = 'notifications';
        $this->delay = 0;
    }

    public function via($notifiable): array
    {
        $prefs = $notifiable->getOrCreateNotificationPreference();
        
        if ($prefs->isMuted() || !$prefs->isNotificationTypeEnabled('transfer_initiated')) {
            return [];
        }

        return $prefs->getEnabledChannels();
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Transfer Initiated - {$this->transfer->reference}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your transfer has been initiated successfully!")
            ->line("Amount: {$this->transfer->amount} {$this->transfer->currency_from}")
            ->line("Recipient: {$this->beneficiaryName}")
            ->line("Reference: {$this->transfer->reference}")
            ->line("Speed: {$this->transfer->speed}")
            ->line("Estimated Delivery: {$this->transfer->estimated_delivery_at->format('M d, Y H:i A')}")
            ->action('Track Transfer', url("/app/transfers/{$this->transfer->id}"))
            ->line('Thank you for using our service!');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'transfer_initiated',
            'title' => 'Transfer Initiated',
            'message' => "Your transfer of {$this->transfer->amount} {$this->transfer->currency_from} to {$this->beneficiaryName} is processing",
            'transfer_id' => $this->transfer->id,
            'reference' => $this->transfer->reference,
            'amount' => $this->transfer->amount,
            'currency' => $this->transfer->currency_from,
            'beneficiary' => $this->beneficiaryName,
            'estimated_delivery' => $this->transfer->estimated_delivery_at,
        ];
    }
}
