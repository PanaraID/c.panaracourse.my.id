<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Chat extends Model
{
    protected $guarded = ['id'];

    protected $attributes = [
        'is_active' => true,
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($chat) {
            if (empty($chat->slug)) {
                $chat->slug = Str::slug($chat->title);
                
                // Ensure unique slug
                $counter = 1;
                $originalSlug = $chat->slug;
                while (static::where('slug', $chat->slug)->exists()) {
                    $chat->slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_users')->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function latestMessages(): HasMany
    {
        return $this->hasMany(Message::class)->orderByDesc('created_at');
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }
}
