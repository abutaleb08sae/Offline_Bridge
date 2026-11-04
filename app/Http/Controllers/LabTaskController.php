<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\LabTask;
use App\Models\ActivityLog;

class LabTaskController extends Controller
{
    /**
     * Display a listing of all lab tasks.
     */
    public function index()
    {
        $labTasks = LabTask::latest()->get();

        return view('pages.files', compact('labTasks'));
    }

    /**
     * Store a newly uploaded lab task file (.pkt, .pkz, .zip, .pdf).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'lab_file' => ['required', 'file', 'max:51200', 'extensions:pkt,pkz,zip,pdf'],
        ], [
            'lab_file.extensions' => 'The uploaded file must be a Cisco Packet Tracer (.pkt, .pkz), ZIP archive (.zip), or PDF document (.pdf).',
            'lab_file.max' => 'The file size cannot exceed 50MB (51,200 KB).',
        ]);

        $file = $request->file('lab_file');
        $originalName = $file->getClientOriginalName();
        $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $originalName);

        // Store file in storage/app/public/labs/
        $filePath = $file->storeAs('labs', $safeName, 'public');

        // Create database record
        $task = LabTask::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'file_path' => $filePath,
            'file_name' => $originalName,
            'file_size' => $file->getSize(),
        ]);

        // Audit Trail Activity Log
        ActivityLog::create([
            'client_ip' => $request->ip() ?: '127.0.0.1',
            'action' => 'Lab Task Uploaded',
            'details' => "Task: {$task->title} ({$task->file_name})",
        ]);

        return redirect()->route('labs.index')->with('success', "Lab Task '{$task->title}' uploaded successfully.");
    }

    /**
     * Download the lab task file.
     */
    public function download(Request $request, $id)
    {
        $task = LabTask::findOrFail($id);

        if (!Storage::disk('public')->exists($task->file_path)) {
            abort(404, 'The requested lab file does not exist on Master storage.');
        }

        ActivityLog::create([
            'client_ip' => $request->ip() ?: '127.0.0.1',
            'action' => 'Lab Task Downloaded',
            'details' => "File: {$task->file_name}",
        ]);

        return Storage::disk('public')->download($task->file_path, $task->file_name);
    }

    /**
     * Delete the lab task from database and storage.
     */
    public function destroy($id)
    {
        $task = LabTask::findOrFail($id);

        // Delete physical file from public disk
        if (Storage::disk('public')->exists($task->file_path)) {
            Storage::disk('public')->delete($task->file_path);
        }

        $title = $task->title;
        $fileName = $task->file_name;
        $task->delete();

        ActivityLog::create([
            'client_ip' => request()->ip() ?: '127.0.0.1',
            'action' => 'Lab Task Deleted',
            'details' => "Deleted Task: {$title} ({$fileName})",
        ]);

        return redirect()->route('labs.index')->with('success', "Lab task '{$title}' deleted successfully.");
    }
}
