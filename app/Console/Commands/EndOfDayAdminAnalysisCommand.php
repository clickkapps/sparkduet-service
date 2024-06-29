<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class EndOfDayAdminAnalysisCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:name';

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
        // get total subscriptions today
        // user feedbacks


//        $message = "Story Reported: Story ID - $storyId\n";
//        $message .= "Reason: ". $reason . "\n";
//        $message .= "Reporter ID: $reporterId \n";
//        $message .= "------------------------------- \n";


        return Command::SUCCESS;
    }
}
