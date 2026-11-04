<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'file_path',
        'file_name',
        'file_size',
    ];

    /**
     * Submissions submitted for this lab task.
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }
}

