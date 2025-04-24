<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendEmailVerificationToken extends Mailable
{
    use Queueable, SerializesModels;

    public $registerUrl;

    /**
     * Create a new message instance.
     *
     * @param string $token
     *   The verification token to use in the registration URL.
     * @param bool $isAppRequest
     *   Whether this is an app request or not. If true, the API URL will be
     *   replaced with the app URL.
     */
    public function __construct($token, $isAppRequest = false)
    {
        if ($isAppRequest) {
            $this->registerUrl = url('/api/app/signup/email/' . $token);
        } else {
            $this->registerUrl = url('/api/signup/email/' . $token);
        }
    }

    public function build()
    {
        return $this->subject('Complete Your Registration')
            ->view('emails.verify_token')
            ->with([
                'registerUrl' => $this->registerUrl,
            ]);
    }
}
