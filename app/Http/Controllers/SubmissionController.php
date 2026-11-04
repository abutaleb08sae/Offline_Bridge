<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Submission;
use App\Models\ActivityLog;

class SubmissionController extends Controller
{
    /**
     * Store a student submission for a lab task.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'lab_task_id' => 'required|exists:lab_tasks,id',
            'student_name' => 'required|string|max:100',
            'student_id' => 'required|string|max:50',
            'submission_file' => 'required|file|max:51200',
        ]);

        $file = $request->file('submission_file');
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $sanitizedStudentId = Str::slug($validated['student_id'], '_');
        $sanitizedName = Str::slug($baseName, '_');
        $filename = time() . '_' . $sanitizedStudentId . '_' . ($sanitizedName ?: 'solution') . ($extension ? '.' . $extension : '');

        // Store file in storage/app/public/submissions/
        $filePath = $file->storeAs('submissions', $filename, 'public');

        $submission = Submission::create([
            'lab_task_id' => $validated['lab_task_id'],
            'student_name' => $validated['student_name'],
            'student_id' => $validated['student_id'],
            'client_ip' => $request->ip(),
            'file_path' => $filePath,
        ]);

        ActivityLog::create([
            'client_ip' => $request->ip(),
            'action' => 'Solution Submitted',
            'details' => "Student {$validated['student_name']} ({$validated['student_id']}) submitted for task #{$validated['lab_task_id']}",
        ]);

        return back()->with('success', 'Lab solution submitted successfully.');
    }
}

