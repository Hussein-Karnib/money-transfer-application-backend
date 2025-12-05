<?php

namespace App\Jobs;

use App\Mail\NotificationDigestMail;
use App\Models\NotificationLog;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendNotificationDigestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $frequency = 'daily' // daily, weekly
    ) {
        $this->queue = 'notifications';
        $this->delay = 0;
    }

    public function handle(): void
    {
        $prefs = NotificationPreference::where('email_frequency', $this->frequency)->get();

        foreach ($prefs as $preference) {
            $notifiable = $preference->notifiable;
            
            if (!$notifiable || !method_exists($notifiable, 'email')) {
                continue;
            }

            $this->sendDigest($notifiable, $preference);
        }
    }

    private function sendDigest($notifiable, NotificationPreference $preference): void
    {
        // Get time range
        $since = match ($this->frequency) {
            'daily' => now()->subDay(),
            'weekly' => now()->subWeek(),
            default => now()->subDay(),
        };

        // Fetch unread notifications
        $notifications = NotificationLog::where('notifiable_type', get_class($notifiable))
            ->where('notifiable_id', $notifiable->id)
            ->whereNull('read_at')
            ->where('sent_at', '>=', $since)
            ->orderByDesc('sent_at')
            ->get();

        if ($notifications->isEmpty()) {
            return; // Don't send empty digest
        }

        try {
            Mail::to($notifiable->email)->send(
                new NotificationDigestMail($notifiable, $notifications, $this->frequency)
            );

            // Mark as part of digest
            $notifications->each->markAsRead();
        } catch (\Exception $e) {
            \Log::error('Failed to send notification digest', [
                'notifiable' => get_class($notifiable),
                'notifiable_id' => $notifiable->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
