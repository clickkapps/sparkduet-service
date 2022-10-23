<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserInfo extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'description',
        'dob',
        'age',
        'gender',
        'city',
        'country',
        'profile_pic'
    ];

}
