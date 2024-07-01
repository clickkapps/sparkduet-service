<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\StoryLikesEvaluated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StoryLikesEvaluatedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(public int $userId, public $likes)
    { }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $user = User::with([])->find(($this->userId));
        $message = $this->likes . '+ new likes on your posts today';
        $user->notify(new StoryLikesEvaluated(message: $message));

    }
}
