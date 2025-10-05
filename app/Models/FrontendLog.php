<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FrontendLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'log_id',
        'session_id',
        'user_id',
        'level',
        'message',
        'data',
        'context',
        'stack_trace',
        'url',
        'user_agent',
        'ip_address',
        'type',
        'log_timestamp',
    ];

    protected $casts = [
        'data' => 'array',
        'context' => 'array',
        'log_timestamp' => 'datetime',
    ];

    /**
     * Get the user that owns the log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for filtering by level
     */
    public function scopeLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Scope for filtering by type
     */
    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for filtering by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('log_timestamp', [$startDate, $endDate]);
    }

    /**
     * Scope for filtering by user
     */
    public function scopeUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for filtering by session
     */
    public function scopeSession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope for errors only
     */
    public function scopeErrors($query)
    {
        return $query->where('level', 'error');
    }

    /**
     * Scope for warnings and errors
     */
    public function scopeWarningsAndErrors($query)
    {
        return $query->whereIn('level', ['warn', 'error']);
    }

    /**
     * Get formatted data for display
     */
    public function getFormattedDataAttribute()
    {
        return $this->data ? json_encode($this->data, JSON_PRETTY_PRINT) : null;
    }

    /**
     * Get formatted context for display
     */
    public function getFormattedContextAttribute()
    {
        return $this->context ? json_encode($this->context, JSON_PRETTY_PRINT) : null;
    }

    /**
     * Get log level color for UI
     */
    public function getLevelColorAttribute()
    {
        return match($this->level) {
            'debug' => '#8B949E',
            'info' => '#2196F3',
            'warn' => '#FF9800',
            'error' => '#F44336',
            default => '#2196F3'
        };
    }

    /**
     * Get log level badge class
     */
    public function getLevelBadgeClassAttribute()
    {
        return match($this->level) {
            'debug' => 'badge-secondary',
            'info' => 'badge-primary',
            'warn' => 'badge-warning',
            'error' => 'badge-danger',
            default => 'badge-primary'
        };
    }

    /**
     * Get short stack trace for preview
     */
    public function getShortStackTraceAttribute()
    {
        if (!$this->stack_trace) return null;
        
        $lines = explode("\n", $this->stack_trace);
        return implode("\n", array_slice($lines, 0, 3));
    }

    /**
     * Check if log is an error
     */
    public function isError(): bool
    {
        return $this->level === 'error';
    }

    /**
     * Check if log is a warning
     */
    public function isWarning(): bool
    {
        return $this->level === 'warn';
    }

    /**
     * Get browser name from user agent
     */
    public function getBrowserAttribute()
    {
        $userAgent = $this->user_agent;
        
        if (strpos($userAgent, 'Chrome') !== false) return 'Chrome';
        if (strpos($userAgent, 'Firefox') !== false) return 'Firefox';
        if (strpos($userAgent, 'Safari') !== false) return 'Safari';
        if (strpos($userAgent, 'Edge') !== false) return 'Edge';
        if (strpos($userAgent, 'Opera') !== false) return 'Opera';
        
        return 'Unknown';
    }

    /**
     * Get platform from user agent
     */
    public function getPlatformAttribute()
    {
        $userAgent = $this->user_agent;
        
        if (strpos($userAgent, 'Windows') !== false) return 'Windows';
        if (strpos($userAgent, 'Mac') !== false) return 'macOS';
        if (strpos($userAgent, 'Linux') !== false) return 'Linux';
        if (strpos($userAgent, 'Android') !== false) return 'Android';
        if (strpos($userAgent, 'iOS') !== false) return 'iOS';
        
        return 'Unknown';
    }
}