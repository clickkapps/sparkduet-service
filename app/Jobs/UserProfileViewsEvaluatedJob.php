<?php

namespace App\Jobs;

use App\Events\NotificationsUpdatedEvent;
use App\Events\UserProfileViewsCounted;
use App\Models\ProfileView;
use App\Models\User;
use App\Traits\NotificationTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class UserProfileViewsEvaluatedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, NotificationTrait;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(public $userId, public $unreadCount)
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
        $user = User::with([])->find($this->userId);
        $message = $this->unreadCount . '+ new people viewed your profile';

        if($user) {

            $this->createUserNotification(userId: $this->userId, title: "Profile views", message: $message, type: "profile_views");
            $count = $this->countUnseenNotifications($user);
            event(new NotificationsUpdatedEvent(userId: $this->userId, count: $count)); // update notification with websocket

            ProfileView::with([])->where(['profile_id' =>  $this->userId])->update(['profile_owner_notified_at' => now()]);
            $unreadProfileViews = DB::table('profile_views')->where([
                'profile_id' => $this->userId,
                'profile_owner_read_at' => null
            ])->count();
            event(new UserProfileViewsCounted(userId: $this->userId, count: $unreadProfileViews));

            $user->notify(new \App\Notifications\UserProfileViewsEvaluated(message: $message));
        }

    }
}
