<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoryCountry extends Model
{
    use HasFactory;
    protected $table = 'story_countries';

    protected $fillable = [
        'story_id',
        'country_id'
    ];
}
