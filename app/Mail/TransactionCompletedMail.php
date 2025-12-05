<?php

namespace App\Mail;

use App\Models\Transfer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TransactionCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Transfer $transfer)
    {
    }

    public function build(): self
    {
        $transfer = $this->transfer->loadMissing(['sender', 'beneficiary', 'currencyFrom', 'currencyTo']);

        return $this
            ->subject('Your transfer is completed')
            ->view('emails.transaction_completed', [
                'transfer' => $transfer,
                'sender' => $transfer->sender,
                'beneficiary' => $transfer->beneficiary,
            ]);
    }
}
