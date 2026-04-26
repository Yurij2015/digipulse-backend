<?php

namespace App\Notifications\Contracts;

interface TelegramNotification
{
    /**
     * Get the telegram representation of the notification.
     */
    public function toTelegram(mixed $notifiable): mixed;
}
