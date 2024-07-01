<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Laravel\Scout\Searchable;

class UserInfo extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'user_id',
        'bio',
        'dob',
        'age',
        'gender',
        'city',
        'country',
        'region',
        'loc',
        'timezone',
        'profile_pic_path',
        'introductory_video_path',
        'requested_basic_info_update',
        'requested_preference_info_update',
        "age_at",
        'race',
        'preferred_gender',
        'preferred_min_age',
        'preferred_max_age',
        'preferred_races',
        'preferred_nationalities',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }



}
