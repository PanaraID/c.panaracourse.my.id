<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageTag extends Model
{
    protected $fillable = [
        'message_id',
        'tagged_user_id',
        'tagged_by_user_id',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    /**
     * Get the message that owns the tag.
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Get the user who was tagged.
     */
    public function taggedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tagged_user_id');
    }

    /**
     * Get the user who created the tag.
     */
    public function taggedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tagged_by_user_id');
    }
}
