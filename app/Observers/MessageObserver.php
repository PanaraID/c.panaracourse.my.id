<?php

namespace App\Observers;

use App\Events\MessageSent;
use App\Models\Notification;

class MessageObserver
{
    public function created($message)
    {
        logger()->info("MessageObserver: Message ID {$message->id} has been created.");

        // Create notifications for other members
        Notification::createForNewMessage($message);

        // // Trigger the MessageSent event (without broadcasting)
        // MessageSent::dispatch($message);
        // event(new \App\Events\MessageSent($message));
    }
}
