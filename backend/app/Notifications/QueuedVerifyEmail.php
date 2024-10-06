<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\URL;

class QueuedVerifyEmail extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    protected function verificationUrl($notifiable)
    {
        $url = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );

        $parsedUrl = parse_url($url);
        $queryParams = [];
        parse_str($parsedUrl['query'] ?? '', $queryParams);

        $key = $notifiable->getKey();
        $hash = sha1($notifiable->getEmailForVerification());

        return config('app.frontend_url') . '/verify-email?' . http_build_query($queryParams) . '&key=' . $key . '&hash=' . $hash;
    }
}
