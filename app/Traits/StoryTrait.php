<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Http\Request;
use Ramsey\Collection\Collection;

trait StoryTrait
{
    protected function setAdditionalFeedParameters(Request $request, $feeds)
    {
        $authUser = $request->user();
        $updatedItems = $feeds->getCollection();
        // Add bookmark status to each story
        $updatedItems->each(function ($story) use ($authUser) {
            $story->user_has_bookmarked = $story->isBookmarkedByUser($authUser->{'id'});
            $story->story_likes_by_user = $story->getStoryLikesByUser($authUser->{'id'});
            $story->user_view_info = $story->viewInfo($authUser->{'id'});
        });
        $feeds->setCollection($updatedItems);
        return $feeds;
    }
}
