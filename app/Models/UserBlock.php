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

    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiator_id');
    }

    public function offender()
    {
        return $this->belongsTo(User::class, 'offender_id');
    }
}
