<?php

namespace Vanguard\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPassword extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public string $token)
    {
    }

    public function build(): self
    {
        $subject = sprintf('[%s] %s', setting('app_name'), __('Password Reset Request'));

        return $this->subject($subject)->markdown('mail.reset-password');
    }
}
