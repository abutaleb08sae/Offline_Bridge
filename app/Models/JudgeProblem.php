<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JudgeProblem extends Model
{
    protected $fillable = [
        'title',
        'description',
        'time_limit',
        'memory_limit',
    ];

    public function testCases(): HasMany
    {
        return $this->hasMany(JudgeTestCase::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(JudgeSubmission::class);
    }
}
