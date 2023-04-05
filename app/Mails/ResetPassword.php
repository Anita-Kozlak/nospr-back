<?php

namespace App\Mails;

use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPassword extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $user;

    public function __construct(string $user, string $code)
    {
        $this->user = $user;
        $this->code = $code;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->from('automat@inspektor.nfm.wroclaw.pl', 'Inspektor NFM')
            ->subject('Resetowanie hasła dla konta Inspektor NFM')
            ->view('reset-password')
            ->with([
                'name' => $this->user,
                'code' => $this->code
            ]);
    }
}
