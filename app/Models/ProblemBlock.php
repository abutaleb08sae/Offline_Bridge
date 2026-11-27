<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProblemBlock extends Model
{
    protected $fillable = [
        'user_id',
        'problem_id',
        'violation_type',
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

    public function problem(): BelongsTo
    {
        return $this->belongsTo(JudgeProblem::class, 'problem_id');
    }

    public static function activePairCount(): int
    {
        return static::query()
            ->where('is_blocked', true)
            ->distinct()
            ->get(['user_id', 'problem_id'])
            ->count();
    }

    public function unblockPair(): int
    {
        return static::query()
            ->where('user_id', $this->user_id)
            ->where('problem_id', $this->problem_id)
            ->where('is_blocked', true)
            ->update(['is_blocked' => false]);
    }
}
