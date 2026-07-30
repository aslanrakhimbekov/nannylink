<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class DocumentStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public Document $document;

    /**
     * Create a new notification instance.
     */
    public function __construct(Document $document)
    {
        $this->document = $document;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail']; // standard dummy channel
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): array
    {
        return [];
    }
}
