<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmail extends BaseVerifyEmail
{
    /**
     * Get the verification URL for the given notifiable.
     *
     * @param  mixed  $notifiable
     */
    protected function verificationUrl($notifiable): string
    {
        $baseUrl = rtrim(config('app.frontend_url'), '/');
        $url = $baseUrl.'/auth/verify-email?'.http_build_query([
            'id' => $notifiable->getKey(),
            'hash' => sha1($notifiable->email),
        ]);

        $signature = hash_hmac('sha256', $url, config('app.key'));

        return $url.'&signature='.$signature;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject(config('app.name') . ' - Verify Your Email Address')
            ->view('emails.verify-email', ['url' => $verificationUrl]);
    }
}
