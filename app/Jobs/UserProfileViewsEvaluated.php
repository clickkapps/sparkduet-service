<?php

namespace App\Jobs;

use App\Events\NotificationsUpdatedEvent;
use App\Models\ProfileView;
use App\Models\User;
use App\Traits\NotificationTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UserProfileViewsEvaluated implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, NotificationTrait;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(public $userId, public $message)
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->createUserNotification(userId: $this->userId, title: "Profile views", message: $this->message, type: "profile_views");
        ProfileView::with([])->where([
            'profile_id' =>  $this->userId
        ])->update([
            'profile_owner_notified_at' => now()
        ]);
        $user = User::with([])->find($this->userId);
        $count = $this->countUnseenNotifications($user);
        event(new NotificationsUpdatedEvent(userId: $this->userId, count: $count)); // update notification with websocket
    }
}
