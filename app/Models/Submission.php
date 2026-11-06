<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Submission extends Model
{
    use HasFactory;

    protected $fillable = [
        'lab_task_id',
        'user_id',
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
        return $this->belongsTo(LabTask::class)->withDefault([
            'title' => 'Archived Task',
            'file_name' => 'N/A',
        ]);
    }

    /**
     * Get the base filename.
     */
    public function getFileNameAttribute(): string
    {
        return basename($this->file_path);
    }

    /**
     * Get human-readable formatted file size.
     */
    public function getFormattedFileSizeAttribute(): string
    {
        try {
            if (Storage::disk('public')->exists($this->file_path)) {
                $bytes = Storage::disk('public')->size($this->file_path);
                if ($bytes >= 1048576) {
                    return number_format($bytes / 1048576, 2) . ' MB';
                } elseif ($bytes >= 1024) {
                    return number_format($bytes / 1024, 2) . ' KB';
                }
                return $bytes . ' B';
            }
        } catch (\Throwable $e) {
            // Ignore if file system temporarily inaccessible
        }

        return 'N/A';
    }

    /**
     * Get file extension in uppercase.
     */
    public function getFileExtensionAttribute(): string
    {
        return strtoupper(pathinfo($this->file_path, PATHINFO_EXTENSION) ?: 'FILE');
    }
}
