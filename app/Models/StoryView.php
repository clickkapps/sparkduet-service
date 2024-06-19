<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoryView extends Model
{
    use HasFactory;
    protected $table = 'story_views';

    protected $fillable = [
        'story_id',
        'user_id',
        'seen_at',
        'watched_created_at',
        'watched_updated_at',
        'watched_count'
    ];
}
