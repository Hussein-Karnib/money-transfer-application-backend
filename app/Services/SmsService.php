<?php

namespace App\Services;

class SmsService
{
    public function send(string $phone, string $message): void
    {
        // Pseudo-implementation; later plug real provider
        // Example: Twilio SDK / API call

        // Twilio-like pseudo:
        // $client = new Client(config('services.twilio.sid'), config('services.twilio.token'));
        // $client->messages->create($phone, [
        //     'from' => config('services.twilio.from'),
        //     'body' => $message,
        // ]);
    }
}
