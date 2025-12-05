<?php

namespace App\Notifications;

use App\Models\Promotion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PromotionAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Promotion $promotion,
        public ?string $customMessage = null
    ) {
        $this->queue = 'notifications';
    }

    public function via($notifiable): array
    {
        $prefs = $notifiable->getOrCreateNotificationPreference();
        
        if ($prefs->isMuted() || !$prefs->isNotificationTypeEnabled('promotion_alert')) {
            return [];
        }

        return $prefs->getEnabledChannels();
    }

    public function toMail($notifiable): MailMessage
    {
        $discountPercent = $this->promotion->discount_percentage ?? ($this->promotion->discount_amount ? 'fixed amount' : '0');

        return (new MailMessage)
            ->subject("Special Offer: {$this->promotion->name}")
            ->greeting("Hello {$notifiable->name},")
            ->line("We have a special promotion for you!")
            ->line("**{$this->promotion->name}**")
            ->line($this->promotion->description)
            ->line("Discount: {$discountPercent}%")
            ->line("Valid until: {$this->promotion->valid_until->format('M d, Y')}")
            ->when($this->customMessage, fn($mail) => $mail->line($this->customMessage))
            ->action('Use Promotion', url('/app/transfers/create'))
            ->line('Terms and conditions apply.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'promotion_alert',
            'title' => "Special Offer: {$this->promotion->name}",
            'message' => "New promotion: {$this->promotion->discount_percentage}% discount on transfers over \${$this->promotion->min_amount}",
            'promotion_id' => $this->promotion->id,
            'discount' => $this->promotion->discount_percentage,
            'valid_until' => $this->promotion->valid_until,
            'custom_message' => $this->customMessage,
        ];
    }
}
