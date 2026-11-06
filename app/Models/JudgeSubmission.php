<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JudgeSubmission extends Model
{
    protected $fillable = [
        'user_id',
        'judge_problem_id',
        'language',
        'code',
        'verdict',
        'error_details',
    ];

    public function problem(): BelongsTo
    {
        return $this->belongsTo(JudgeProblem::class, 'judge_problem_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
