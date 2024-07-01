<?php

namespace App\Console\Commands;

use App\Models\StoryLike;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EvaluateStoryLikesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'evaluate:story:likes';

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
        $today = Carbon::today();

        $results = DB::table('stories')
            ->join('story_likes', 'stories.id', '=', 'story_likes.story_id')
            ->select('stories.user_id', DB::raw('sum(story_likes.count) as likes_count'))
            ->whereDate('story_likes.created_at', $today)
            ->groupBy('stories.user_id')
            ->get();

        // To see the results
        foreach ($results as $result) {
            $log = "User ID: " . $result->user_id . " - Likes Today: " . $result->likes_count . "\n";
            Log::info($log);
        }


        return Command::SUCCESS;
    }
}
