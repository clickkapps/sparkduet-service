<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class ChatMessage extends Model
{
    use HasFactory;

    protected $table = 'chat_messages';

    protected $fillable = [
        'chat_connection_id',
        'client_id',
        'parent_id',
        'deleted_at',
        'delivered_at',
        'seen_at',
        'attachment_path',
        'attachment_type',
        'text',
        'sent_by_id',
        'sent_to_id'
    ];

    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo {
        return $this->belongsTo(ChatMessage::class,'parent_id', 'id');
    }

    /**
     * Encrypt the chat message before saving to the database.
     *
     * @param string $value
     * @return void
     */
    public function setTextAttribute(string $value): void
    {
        $this->attributes['text'] = Crypt::encryptString($value);
    }

    /**
     * Decrypt the chat message when retrieving from the database.
     *
     * @param string $value
     * @return string
     */
    public function getTextAttribute(string $value): string
    {
        return Crypt::decryptString($value);
    }
}
