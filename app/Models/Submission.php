<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Submission extends Model
{
    use HasFactory;

    protected $fillable = [
        'lab_task_id',
        'student_name',
        'student_id',
        'client_ip',
        'file_path',
    ];

    /**
     * The lab task this submission belongs to.
     */
    public function labTask(): BelongsTo
    {
        return $this->belongsTo(LabTask::class);
    }
}

