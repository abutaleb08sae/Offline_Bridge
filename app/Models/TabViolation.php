<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TabViolation extends Model
{
    protected $fillable = [
        'user_id',
        'violation_type',
        'page_url',
        'details',
        'is_blocked',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'is_blocked' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Count distinct students currently blocked by tab policy violations.
     */
    public static function activeUserCount(): int
    {
        return static::query()
            ->where('is_blocked', true)
            ->distinct()
            ->count('user_id');
    }

    /**
     * Unblock all active tab violation records for this user.
     */
    public function unblockUser(): int
    {
        return static::query()
            ->where('user_id', $this->user_id)
            ->where('is_blocked', true)
            ->update(['is_blocked' => false]);
    }
}
