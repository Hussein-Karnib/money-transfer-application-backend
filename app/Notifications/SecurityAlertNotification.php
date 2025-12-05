<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SecurityAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $alertType, // 'new_bank_account', 'new_device', 'failed_login', etc.
        public array $alertData = []
    ) {
        $this->queue = 'notifications';
    }

    public function via($notifiable): array
    {
        $prefs = $notifiable->getOrCreateNotificationPreference();
        
        if ($prefs->isMuted() || !$prefs->isNotificationTypeEnabled('security_alert')) {
            return [];
        }

        // Security alerts always include email and in-app for critical alerts
        return array_merge($prefs->getEnabledChannels(), ['email', 'in_app']);
    }

    public function toMail($notifiable): MailMessage
    {
        $alertMessages = [
            'new_bank_account' => 'A new bank account has been added to your profile',
            'new_device' => 'Your account was accessed from a new device',
            'failed_login' => 'Failed login attempt detected',
            'password_changed' => 'Your password was changed',
            'email_changed' => 'Your email address was changed',
        ];

        $message = $alertMessages[$this->alertType] ?? 'Security alert';

        return (new MailMessage)
            ->subject('Security Alert - ' . config('app.name'))
            ->greeting("Hello {$notifiable->name},")
            ->line($message)
            ->line("If this wasn't you, please secure your account immediately.")
            ->when(isset($this->alertData['ip']), fn($mail) => $mail->line("IP Address: {$this->alertData['ip']}"))
            ->when(isset($this->alertData['device']), fn($mail) => $mail->line("Device: {$this->alertData['device']}"))
            ->action('Review Account Security', url('/app/security'))
            ->line('Thank you for using our service!');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'security_alert',
            'title' => 'Security Alert',
            'message' => match ($this->alertType) {
                'new_bank_account' => "New bank account added: {$this->alertData['account_name']}",
                'new_device' => "New device login detected",
                'failed_login' => "Failed login attempt detected",
                'password_changed' => "Your password was changed",
                'email_changed' => "Your email was changed",
                default => 'Security alert',
            },
            'alert_type' => $this->alertType,
            'data' => $this->alertData,
        ];
    }
}
