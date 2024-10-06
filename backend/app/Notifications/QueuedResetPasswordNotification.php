<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class QueuedResetPasswordNotification extends ResetPassword implements ShouldQueue
{
    use Queueable;

    public $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    protected function resetUrl($notifiable)
    {
        return config('app.frontend_url') . '/reset-password?token=' . $this->token . '&email=' . $notifiable->getEmailForPasswordReset();
    }
}
