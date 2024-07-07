<?php

namespace App\Jobs;

use App\Events\UserOnlineStatusChanged;
use App\Models\UserOnline;
use App\Traits\UserTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EvaluateOnlineUsersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, UserTrait;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
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

        $onlineUsersQuery = UserOnline::with([])
            ->where('status', '=', 'online');

        $ids = (clone $onlineUsersQuery)->pluck('user_id');

        foreach ($ids as $userId) {
            // get online users based on user's filters
            $query = $this->getUsersOnlineQuery(userId: $userId);
            $personalizedOnlineIds = $query->pluck('user_onlines.user_id');
            event(new UserOnlineStatusChanged(ids:  $personalizedOnlineIds, userId: $userId));
        }


    }
}
