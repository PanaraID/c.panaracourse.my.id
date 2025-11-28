<?php

namespace App\Observers;

use App\Events\MessageSent;
use App\Events\SendBrowserNotificationEvent;
use App\Models\Notification;

class MessageObserver
{
    public function created($message)
    {
        logger()->info("MessageObserver: Message ID {$message->id} has been created.");

        // Create notifications for other members
        Notification::createForNewMessage($message);

        // Dispatch event to send browser notification
        SendBrowserNotificationEvent::dispatch($message);
    }
}
