<?php

namespace App\Notifications;

use App\Models\Transfer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BeneficiaryActionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Transfer $transfer,
        public string $action, // 'pickup', 'received', 'confirmed'
        public ?string $actionDetails = null
    ) {
        $this->queue = 'notifications';
    }

    public function via($notifiable): array
    {
        $prefs = $notifiable->getOrCreateNotificationPreference();
        
        if ($prefs->isMuted() || !$prefs->isNotificationTypeEnabled('beneficiary_action')) {
            return [];
        }

        return $prefs->getEnabledChannels();
    }

    public function toMail($notifiable): MailMessage
    {
        $actionMessages = [
            'pickup' => "has picked up the cash",
            'received' => "has received the transfer",
            'confirmed' => "has confirmed receipt",
        ];

        $actionMessage = $actionMessages[$this->action] ?? 'action completed';

        return (new MailMessage)
            ->subject("Transfer Pickup Confirmed - {$this->transfer->reference}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Great news! {$this->transfer->beneficiary->full_name} {$actionMessage}!")
            ->line("Reference: {$this->transfer->reference}")
            ->line("Amount: {$this->transfer->amount} {$this->transfer->currency_from}")
            ->when($this->actionDetails, fn($mail) => $mail->line("Details: {$this->actionDetails}"))
            ->action('View Transfer', url("/app/transfers/{$this->transfer->id}"))
            ->line('Thank you for using our service!');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'beneficiary_action',
            'title' => ucfirst($this->action) . ' Confirmed',
            'message' => "{$this->transfer->beneficiary->full_name} has " . str_replace('_', ' ', $this->action) . " the cash",
            'transfer_id' => $this->transfer->id,
            'reference' => $this->transfer->reference,
            'action' => $this->action,
            'beneficiary' => $this->transfer->beneficiary->full_name,
            'amount' => $this->transfer->amount,
            'currency' => $this->transfer->currency_from,
            'details' => $this->actionDetails,
        ];
    }
}
