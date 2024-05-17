<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

class UserInfo extends Model
{
    use HasFactory;

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
        'requested_profile_update',
        "age_at"
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }



}
