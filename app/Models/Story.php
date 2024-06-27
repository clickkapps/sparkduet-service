<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Story extends Model
{
    use HasFactory, Searchable;
    protected $table = 'stories';

    protected $fillable = [
        'user_id',
        'description',
        'comments_disabled_at',
        'media_path',
        'media_type',
        'asset_id',
        'purpose',
        'aspect_ratio',
        'disciplinary_action',
        'disciplinary_action_taken_at',
        'disciplinary_action_taken_by'
    ];

    // Ensure full_name is included in array and JSON representations
    protected $appends = ['bookmarks_count', 'likes_count', 'views_count'];

    public function likes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StoryLike::class);
    }

    public function likedUsers(){
        return $this->belongsToMany(User::class, 'story_likes', 'story_id', 'user_id');
    }

    public function views(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StoryView::class);
    }

    public function bookmarks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StoryBookmark::class);
    }

    public function userBookmarks()
    {
        return $this->belongsToMany(User::class, 'story_bookmarks', 'story_id', 'user_id');
    }

    // Define an accessor to get the count of bookmarks
    public function getBookmarksCountAttribute(): int
    {
        return $this->bookmarks()->count();
    }

    // Define an accessor to get the count of bookmarks
    public function getViewsCountAttribute(): int
    {
//        return $this->likes()->count();
        return $this->views()->whereNotNull('watched_count')->sum('watched_count');
    }

    // Define an accessor to get the count of bookmarks
    public function getLikesCountAttribute(): int
    {
//        return $this->likes()->count();
        return $this->likes()->whereNotNull('count')->sum('count');
    }


    // Define an accessor to get the count of bookmarks
    public function getStoryLikesByUser($userId)
    {
        return $this->likes()->where('user_id',$userId)->first()?->{'count'};
    }

    // Define a method to check if a given user has bookmarked the story
    public function isBookmarkedByUser($userId): bool
    {
        return $this->bookmarks()->where('user_id', $userId)->exists();
    }


    // Define a method to check if a given user has bookmarked the story
    public function viewInfo($userId)
    {
        return $this->views()->where('user_id', $userId)->first();
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
