<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfileView extends Model
{
    use HasFactory;

    protected $table = 'profile_views';

    protected $fillable = [
      'viewer_id',
      'profile_id',
      'profile_owner_notified_at',
      'profile_owner_read_at',
    ];

    public function viewer(): \Illuminate\Database\Eloquent\Relations\BelongsTo {
        return $this->belongsTo(User::class,'viewer_id', 'id');
    }

    public function profile(): \Illuminate\Database\Eloquent\Relations\BelongsTo {
        return $this->belongsTo(User::class,'profile_id', 'id');
    }
}
