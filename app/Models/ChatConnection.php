<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatConnection extends Model
{
    use HasFactory;

    protected $table = 'chat_connections';

    protected $fillable = [
        'chat_message_id',
        'matched_at',
        'read_first_impression_note_at',
        'created_by',
        'deleted_at'
    ];

    public function participants(): \Illuminate\Database\Eloquent\Relations\BelongsToMany {
        return $this->belongsToMany(User::class,'chat_participants', 'chat_connection_id', 'user_id' );
    }

    public function lastMessage(): \Illuminate\Database\Eloquent\Relations\BelongsTo {
        return $this->belongsTo(ChatMessage::class,'chat_message_id', 'id');
    }
}
