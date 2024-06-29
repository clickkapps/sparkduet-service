<?php

namespace App\Console\Commands;

use App\Models\StoryReport;
use App\Notifications\AdminAnalysisCompleted;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class HourlyAdminAnalysisCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hourly:admin:analysis';

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
        // unattended user reports
        $unattendedUserReportsCount = DB::table('user_reports')->whereNull('admin_read_at')->count();

        // unattended story reports
        $unattendedStoryReportsCount = StoryReport::with([])->whereNull('admin_read_at')->count();


        $message = "Hourly Analysis - \n";
        $message .= "------------------------------- \n";
        $message .= "Unattended story reports: ". $unattendedStoryReportsCount . "\n";
        $message .= "Unattended user reports: ". $unattendedUserReportsCount . "\n";
        $message .= "------------------------------- \n";

        $admin = getAdmin();
        if(!blank($admin)) {
            $admin->notify(new AdminAnalysisCompleted(message: $message));
        }

        return Command::SUCCESS;
    }
}
