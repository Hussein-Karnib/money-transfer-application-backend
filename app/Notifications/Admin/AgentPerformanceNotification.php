<?php

namespace App\Notifications\Admin;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AgentPerformanceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $agentName,
        public int $transfersCompleted,
        public float $totalVolume,
        public string $currency,
        public string $period = 'today',
        public array $topMetrics = []
    ) {
        $this->queue = 'notifications';
    }

    public function via($notifiable): array
    {
        $prefs = $notifiable->getOrCreateNotificationPreference();
        
        if ($prefs->isMuted() || !$prefs->isNotificationTypeEnabled('system_alert')) {
            return [];
        }

        return $prefs->getEnabledChannels();
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Agent Performance Report - {$this->agentName}")
            ->greeting("Hello {$notifiable->user->name},")
            ->line("Agent performance summary for {$this->period}:")
            ->line("**Agent:** {$this->agentName}")
            ->line("**Transfers Completed:** {$this->transfersCompleted}")
            ->line("**Total Volume:** {$this->totalVolume} {$this->currency}")
            ->when(count($this->topMetrics) > 0, function ($mail) {
                $mail->line("**Performance Metrics:**");
                foreach ($this->topMetrics as $key => $value) {
                    $mail->line("- " . str_replace('_', ' ', ucwords($key)) . ": $value");
                }
                return $mail;
            })
            ->action('View Agent Details', url('/admin/agents'))
            ->line('Keep monitoring agent performance.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'agent_performance',
            'title' => "Agent Performance - {$this->agentName}",
            'message' => "{$this->agentName} completed {$this->transfersCompleted} transfers totaling {$this->totalVolume} {$this->currency}",
            'agent_name' => $this->agentName,
            'transfers_completed' => $this->transfersCompleted,
            'total_volume' => $this->totalVolume,
            'currency' => $this->currency,
            'period' => $this->period,
            'top_metrics' => $this->topMetrics,
        ];
    }
}
