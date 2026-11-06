<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'description',
        'ip_address',
        'client_ip',
        'details',
        'status',
        'user_agent',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include recent logs ordered descending.
     */
    public function scopeRecent($query, $limit = 50)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Human-friendly time display.
     */
    public function getFormattedTimeAttribute(): string
    {
        return $this->created_at ? $this->created_at->diffForHumans() : 'Just now';
    }

    /**
     * Alias for 'time' for backward compatibility.
     */
    public function getTimeAttribute(): string
    {
        return $this->getFormattedTimeAttribute();
    }

    /**
     * Combined action & details message for feed display.
     */
    public function getMessageAttribute(): string
    {
        if ($this->details) {
            return "{$this->action} - {$this->details}";
        }

        return $this->action;
    }

    /**
     * Categorize action for UI filtering.
     */
    public function getCategoryAttribute(): string
    {
        $action = strtolower((string) $this->action);
        if (str_contains($action, 'task') || str_contains($action, 'file') || str_contains($action, 'solution')) {
            return 'Files';
        }
        if (str_contains($action, 'user') || str_contains($action, 'client') || str_contains($action, 'student')) {
            return 'Users';
        }
        if (str_contains($action, 'app') || str_contains($action, 'packet tracer')) {
            return 'Applications';
        }

        return 'System';
    }

    /**
     * Icon type for styling (user, app, file, backup, system).
     */
    public function getIconAttribute(): string
    {
        $action = strtolower((string) $this->action);
        if (str_contains($action, 'user') || str_contains($action, 'client') || str_contains($action, 'connected')) {
            return 'user';
        }
        if (str_contains($action, 'app') || str_contains($action, 'packet tracer')) {
            return 'app';
        }
        if (str_contains($action, 'backup')) {
            return 'backup';
        }
        if (str_contains($action, 'file') || str_contains($action, 'task') || str_contains($action, 'download') || str_contains($action, 'upload') || str_contains($action, 'solution')) {
            return 'file';
        }

        return 'system';
    }
}
