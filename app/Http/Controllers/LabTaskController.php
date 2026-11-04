<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\LabTask;
use App\Models\ActivityLog;

class LabTaskController extends Controller
{
    /**
     * Store a newly created lab task and file.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'lab_file' => ['required', 'file', 'max:51200', 'extensions:pkt,pkz,pdf,zip'],
        ]);

        $file = $request->file('lab_file');
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $sanitizedName = Str::slug($baseName, '_');
        $filename = time() . '_' . ($sanitizedName ?: 'lab_task') . ($extension ? '.' . $extension : '');

        // Store file in storage/app/public/labs/
        $filePath = $file->storeAs('labs', $filename, 'public');

        $task = LabTask::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'file_path' => $filePath,
            'file_name' => $originalName,
            'file_size' => $file->getSize(),
        ]);

        ActivityLog::create([
            'client_ip' => $request->ip(),
            'action' => 'Lab Task Uploaded',
            'details' => "Uploaded {$task->title}",
        ]);

        return back()->with('success', "Lab Task '{$task->title}' uploaded successfully.");
    }

    /**
     * Download the lab task file.
     */
    public function download(Request $request, $id)
    {
        $task = LabTask::findOrFail($id);

        ActivityLog::create([
            'client_ip' => $request->ip(),
            'action' => 'Lab Task Downloaded',
            'details' => "Downloaded {$task->file_name}",
        ]);

        return Storage::disk('public')->download($task->file_path, $task->file_name);
    }
}

