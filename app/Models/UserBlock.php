<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserBlock extends Model
{
    use HasFactory;

    protected $table = 'user_blocks';
    protected $fillable = [
        'initiator_id',
        'offender_id',
        'reason'
    ];
}
