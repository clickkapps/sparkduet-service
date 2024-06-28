<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDisciplinaryRecord extends Model
{
    use HasFactory;

    protected $table = "user_disciplinary_records";

    protected $fillable = [
      'user_id',
      'disciplinary_action', //["banned", "warned"]
      'disciplinary_action_taken_by',
      'reason',
      'user_read_at',
      'status' // opened / closed if its opened we show it to the user
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
