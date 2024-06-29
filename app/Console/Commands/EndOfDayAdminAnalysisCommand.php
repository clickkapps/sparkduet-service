<?php

namespace App\Console\Commands;

use App\Models\Story;
use App\Notifications\AdminAnalysisCompleted;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EndOfDayAdminAnalysisCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daily:admin:analysis';

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
        // Check for unreviewed posts
        $unreviewedPostsCount = Story::with([])->whereNull('reviewed_at')->count();
        // get total subscriptions today
        $subs = DB::table('daily_subscriptions_records')->whereDate('created_at', '=', today())->first();
        $totalSubs = 0;
        $totalUnsubs = 0;
        if($subs) {
            $totalSubs = $subs->{'sub_counter'};
            $totalUnsubs = $subs->{'unsub_counter'};
        }
        // user feedbacks
        $userFeedbacksCount = DB::table('user_feedbacks')->where('status', '=', 'pending')->count();


        $message = "End Of Day Analysis - \n";
        $message .= "------------------------------- \n";
        $message .= "Unreviewed Posts: ". $unreviewedPostsCount . "\n";
        $message .= "Total Subs: $totalSubs \n";
        $message .= "Total Unsubs: $totalUnsubs \n";
        $message .= "Unattended User Feedbacks: $userFeedbacksCount \n";
        $message .= "------------------------------- \n";

        $admin = getAdmin();
        if(!blank($admin)) {
            $admin->notify(new AdminAnalysisCompleted(message: $message));
        }

        return Command::SUCCESS;
    }
}
