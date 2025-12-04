<?php

namespace App\Notifications\Channels;

use App\Services\SmsService;
use Illuminate\Notifications\Notification;

class SmsChannel
{
    public function __construct(
        private SmsService $sms
    ) {}

    public function send($notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toSms')) {
            return;
        }

        $message = $notification->toSms($notifiable);

        if (!$message || empty($notifiable->phone_number)) {
            return;
        }

        $this->sms->send($notifiable->phone_number, $message);
    }
}
