<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\LabTask;
use App\Models\Submission;
use App\Models\ActivityLog;
use App\Services\ActivityLogger;

class LabTaskController extends Controller
{
    /**
     * Display a listing of all lab tasks with submission count.
     */
    public function index()
    {
        $labTasks = LabTask::withCount('submissions')->latest()->get();

        return view('pages.files', compact('labTasks'));
    }

    /**
     * Store a newly uploaded lab task file (.pkt, .pkz, .pka, .zip, .pdf).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'lab_file' => ['required', 'file', 'max:51200', 'extensions:pkt,pkz,pka,zip,pdf'],
        ], [
            'lab_file.extensions' => 'The uploaded file must be a Cisco Packet Tracer (.pkt, .pkz, .pka), ZIP archive (.zip), or PDF document (.pdf).',
            'lab_file.max' => 'The file size cannot exceed 50MB (51,200 KB).',
        ]);

        $file = $request->file('lab_file');
        $originalName = $file->getClientOriginalName();
        $cleanName = preg_replace('/[^a-zA-Z0-9._-]/', '', $originalName) ?: 'lab_task_' . time() . '.pkt';
        $safeName = time() . '_' . $cleanName;

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
        ActivityLogger::log('Lab Task Uploaded', "Task: {$task->title} ({$task->file_name})", 'success', $request);

        return redirect()->route('labs.index')->with('success', "Lab Task '{$task->title}' uploaded successfully.");
    }

    /**
     * Store a student submission for a lab task.
     */
    public function storeSubmission(Request $request)
    {
        $validated = $request->validate([
            'lab_task_id' => 'required|exists:lab_tasks,id',
            'student_name' => 'required|string|max:100',
            'student_id' => 'required|string|max:50',
            'submission_file' => ['required', 'file', 'max:51200', 'extensions:pkt,pkz,pka,zip,pdf,cpp'],
        ], [
            'submission_file.extensions' => 'The submission file must be a Packet Tracer file (.pkt, .pkz, .pka), ZIP archive (.zip), PDF document (.pdf), or C++ source file (.cpp).',
            'submission_file.max' => 'The file size cannot exceed 50MB (51,200 KB).',
        ]);

        $file = $request->file('submission_file');
        $cleanStudentId = preg_replace('/[^a-zA-Z0-9_-]/', '', $validated['student_id']) ?: 'student';
        $cleanName = preg_replace('/[^a-zA-Z0-9._-]/', '', $file->getClientOriginalName()) ?: 'solution';
        $fileName = uniqid('', true) . '_' . $cleanStudentId . '_' . $cleanName;
        $filePath = $file->storeAs('submissions', $fileName, 'submissions');

        Submission::create([
            'lab_task_id' => $validated['lab_task_id'],
            'user_id' => $request->user()?->id,
            'student_name' => $validated['student_name'],
            'student_id' => $validated['student_id'],
            'client_ip' => $request->ip() ?: '127.0.0.1',
            'file_path' => $filePath,
        ]);

        ActivityLogger::log(
            'Solution Submitted',
            "Student {$validated['student_name']} ({$validated['student_id']}) submitted for task #{$validated['lab_task_id']}",
            'success',
            $request
        );

        return redirect()->back()->with('success', 'Solution file submitted successfully!');
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

        ActivityLogger::log('Lab Task Downloaded', "File: {$task->file_name} for {$task->title}", 'info', $request);

        return Storage::disk('public')->download($task->file_path, $task->file_name, [
            'Content-Type' => 'application/octet-stream',
        ]);
    }

    /**
     * Delete the lab task and all associated files from database and storage.
     */
    public function destroy($id)
    {
        $task = LabTask::with('submissions')->findOrFail($id);

        // Remove submission files from both the current and legacy storage locations.
        foreach ($task->submissions as $submission) {
            foreach (['submissions', 'public'] as $disk) {
                if ($submission->file_path && Storage::disk($disk)->exists($submission->file_path)) {
                    Storage::disk($disk)->delete($submission->file_path);
                }
            }
        }

        // Delete physical task file from public disk
        if (Storage::disk('public')->exists($task->file_path)) {
            Storage::disk('public')->delete($task->file_path);
        }

        $title = $task->title;
        $fileName = $task->file_name;
        $task->delete();

        ActivityLogger::log('Lab Task Deleted', "Deleted Task: {$title} ({$fileName})", 'warning', request());

        return redirect()->route('labs.index')->with('success', "Lab task '{$title}' deleted successfully.");
    }
}
