<?php

namespace App\Console\Commands;

use App\Events\NotificationsUpdatedEvent;
use App\Models\ProfileView;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EvaluateProfileViewsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'evaluate:profile:views';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        // Fetch the count of unread profile views for each profile_id
        $unreadProfileViews = ProfileView::with([])->select('profile_id', DB::raw('count(*) as unread_count'))
            ->whereNull('profile_owner_read_at')
//            ->whereNull('profile_owner_notified_at') // uncomment this if you don't want to include people added to previous notification
            ->groupBy('profile_id')
            ->get();

        // Output the result for demonstration purposes
        foreach ($unreadProfileViews as $view) {
//            echo "Profile ID " . $view->profile_id . " has " . $view->unread_count . " unread profile views.\n";
                \App\Jobs\UserProfileViewsEvaluatedJob::dispatch($view->profile_id, $view->unread_count);
        }

        return Command::SUCCESS;
    }
}
