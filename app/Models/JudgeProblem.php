<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class JudgeProblem extends Model
{
    protected $fillable = [
        'title',
        'description',
        'time_limit',
        'memory_limit',
        'start_time',
        'end_time',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
        ];
    }

    public function testCases(): HasMany
    {
        return $this->hasMany(JudgeTestCase::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(JudgeSubmission::class);
    }

    public function isUpcoming(?Carbon $now = null): bool
    {
        return $this->start_time !== null && ($now ?? now())->lt($this->start_time);
    }

    public function isEnded(?Carbon $now = null): bool
    {
        return $this->end_time !== null && ($now ?? now())->gte($this->end_time);
    }

    public function isLive(?Carbon $now = null): bool
    {
        $now ??= now();

        return ! $this->isUpcoming($now) && ! $this->isEnded($now)
            && ($this->start_time !== null || $this->end_time !== null);
    }

    public function status(?Carbon $now = null): string
    {
        if ($this->isUpcoming($now)) {
            return 'Upcoming';
        }

        if ($this->isEnded($now)) {
            return 'Ended';
        }

        return $this->isLive($now) ? 'Live' : 'Practice';
    }
}
