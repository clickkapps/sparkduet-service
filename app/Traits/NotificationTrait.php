<?php

namespace App\Traits;

use App\Events\NotificationsUpdatedEvent;
use App\Models\UserNotification;

trait NotificationTrait
{
    protected function countUnseenNotifications($user) {

        return $user->userNotifications()->where(['seen_at' => null])->count();

//        event(new NotificationsUpdatedEvent(1,24));
    }

    protected function createUserNotification($userId, $title, $message, $type = "general"): void {

        UserNotification::with([])->create([
           'user_id' => $userId,
           'title' => $title,
            'message' =>  $message,
            'type' => $type,
            'read_at' => null,
            'seen_at' => null
        ]);

    }


}
