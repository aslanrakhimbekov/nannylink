<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class LowBalanceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $balance;

    /**
     * Create a new notification instance.
     */
    public function __construct(int $balance)
    {
        $this->balance = $balance;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): array
    {
        return [];
    }
}
