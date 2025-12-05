<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificationDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public $notifiable,
        public $notifications,
        public string $frequency = 'daily'
    ) {}

    public function envelope(): Envelope
    {
        $frequencyLabel = ucfirst($this->frequency);
        return new Envelope(
            subject: "{$frequencyLabel} Notification Digest - " . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.notification-digest',
            with: [
                'notifiable' => $this->notifiable,
                'notifications' => $this->notifications,
                'frequency' => $this->frequency,
            ],
        );
    }
}
