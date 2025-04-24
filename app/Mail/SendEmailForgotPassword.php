<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendEmailForgotPassword extends Mailable
{
    use Queueable, SerializesModels;

    public $resetUrl;

    /**
     * Create a new message instance.
     *
     * @param string $token
     *   The token used to create the reset password URL.
     * @param bool $isAppRequest
     *   Indicates whether the request is from the app or not. If true, the API URL
     *   will be used for resetting the password. Otherwise, the app URL will be used.
     */

    public function __construct($token, $isAppRequest = false)
    {
        if ($isAppRequest) {
            $this->resetUrl = url('/api/app/reset-password?email_token=' . $token);
        } else {
            $this->resetUrl = url('/api/reset-password?email_token=' . $token);
        }
    }

    public function build()
    {
        return $this->subject('Reset Password')
            ->view('emails.reset_password')
            ->with([
                'resetUrl' => $this->resetUrl,
            ]);
    }
}
