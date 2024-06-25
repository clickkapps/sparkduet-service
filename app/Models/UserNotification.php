<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    use HasFactory;
    protected $fillable = [
       'user_id',
       'message',
       'title',
       'read_at',
       'seen_at',
       'type'
    ];
}
