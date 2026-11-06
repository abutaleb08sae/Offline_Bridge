<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JudgeTestCase extends Model
{
    protected $fillable = [
        'judge_problem_id',
        'input',
        'expected_output',
    ];

    public function problem(): BelongsTo
    {
        return $this->belongsTo(JudgeProblem::class, 'judge_problem_id');
    }
}
