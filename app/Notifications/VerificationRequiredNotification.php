<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerificationRequiredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $user,
        public string $verificationType, // 'id_verification', 'bank_account_verification', etc.
    ) {
        $this->queue = 'notifications';
    }

    public function via($notifiable): array
    {
        $prefs = $notifiable->getOrCreateNotificationPreference();
        
        if ($prefs->isMuted() || !$prefs->isNotificationTypeEnabled('verification_required')) {
            return [];
        }

        return $prefs->getEnabledChannels();
    }

    public function toMail($notifiable): MailMessage
    {
        $verificationMessages = [
            'id_verification' => 'Complete your ID verification to unlock more features',
            'bank_account_verification' => 'Verify your bank account to add more payment methods',
            'phone_verification' => 'Verify your phone number for enhanced security',
            'email_verification' => 'Verify your email address',
        ];

        $message = $verificationMessages[$this->verificationType] ?? 'Complete verification';

        return (new MailMessage)
            ->subject('Verification Required - ' . config('app.name'))
            ->greeting("Hello {$notifiable->name},")
            ->line($message)
            ->line("Completing verification helps us keep your account secure and unlocks additional features.")
            ->action('Complete Verification', url('/app/verify'))
            ->line('Thank you for using our service!');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'verification_required',
            'title' => 'Verification Required',
            'message' => "Complete {$this->verificationType} to add bank accounts",
            'verification_type' => $this->verificationType,
        ];
    }
}
