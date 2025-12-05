<?php

namespace App\Notifications\Agent;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkingHoursChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $affectedDate,
        public ?string $oldHours = null,
        public ?string $newHours = null,
        public bool $isClosed = false
    ) {
        $this->queue = 'notifications';
    }

    public function via($notifiable): array
    {
        $prefs = $notifiable->getOrCreateNotificationPreference();
        
        if ($prefs->isMuted() || !$prefs->isNotificationTypeEnabled('admin_message')) {
            return [];
        }

        return $prefs->getEnabledChannels();
    }

    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("Working Hours Updated")
            ->greeting("Hello {$notifiable->user->name},")
            ->line("Your working hours have been updated for {$this->affectedDate}");

        if ($this->isClosed) {
            $message->line("**Status:** Store Closed");
        } else {
            if ($this->oldHours) {
                $message->line("**Previous Hours:** {$this->oldHours}");
            }
            if ($this->newHours) {
                $message->line("**New Hours:** {$this->newHours}");
            }
        }

        return $message
            ->action('View Schedule', url('/agent/schedule'))
            ->line('Your customers will be notified of any schedule changes.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'admin_message',
            'title' => 'Working Hours Updated',
            'message' => "Your hours have been updated for {$this->affectedDate}",
            'affected_date' => $this->affectedDate,
            'old_hours' => $this->oldHours,
            'new_hours' => $this->newHours,
            'is_closed' => $this->isClosed,
        ];
    }
}
