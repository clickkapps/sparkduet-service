<?php

namespace App\Traits;

use App\Events\NotificationsUpdatedEvent;

trait NotificationTrait
{
    public function countNotifications(): void {
        event(new NotificationsUpdatedEvent(24));
    }
}
