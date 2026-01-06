<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Get all chats created by this user
     */
    public function createdChats(): HasMany
    {
        return $this->hasMany(Chat::class, 'created_by');
    }

    /**
     * Get all chats where this user is a member
     */
    public function chats(): BelongsToMany
    {
        return $this->belongsToMany(Chat::class, 'chat_users')->withTimestamps();
    }

    public function chatUsers(): HasMany
    {
        return $this->hasMany(ChatUser::class, 'user_id', 'id');
    }

    public function hasReadMessage(Message $message): bool
    {
        $chatUser = $this->chatUsers()
            ->where('chat_id', $message->chat_id)
            ->first();

        if (! $chatUser || ! $chatUser->latest_accessed_at) {
            return false;
        }

        return $chatUser->latest_accessed_at >= $message->created_at;
    }

    /**
     * Get all messages sent by this user
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Get all notifications for this user
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class)->orderByDesc('created_at');
    }

    /**
     * Get unread notifications count
     */
    public function unreadNotificationsCount(): int
    {
        return $this->notifications()->unread()->count();
    }

    /**
     * Get all message tags where this user was tagged
     */
    public function messageTags(): HasMany
    {
        return $this->hasMany(MessageTag::class, 'tagged_user_id');
    }

    /**
     * Get unread message tags count
     */
    public function unreadMessageTagsCount(): int
    {
        return $this->messageTags()->where('is_read', false)->count();
    }

    /**
     * Get recent unread message tags
     */
    public function recentUnreadMessageTags(): HasMany
    {
        return $this->messageTags()
            ->where('is_read', false)
            ->with(['message.chat', 'taggedByUser'])
            ->orderByDesc('created_at')
            ->limit(10);
    }
}
