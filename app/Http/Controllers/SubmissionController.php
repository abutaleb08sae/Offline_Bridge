<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Submission;
use App\Models\LabTask;
use App\Services\ActivityLogger;

class SubmissionController extends Controller
{
    /**
     * Show the student-facing submission form (Client Portal).
     */
    public function create()
    {
        $labTasks = LabTask::latest()->get();
        $clientIp = request()->ip() ?? '127.0.0.1';

        return view('submissions.create', compact('labTasks', 'clientIp'));
    }

    /**
     * Store a student submission for a lab task.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'lab_task_id' => 'required|exists:lab_tasks,id',
            'student_name' => 'required|string|max:100',
            'student_id' => 'required|string|max:50',
            'submission_file' => ['required', 'file', 'max:51200', 'extensions:pkt,pkz,zip,pdf'],
        ], [
            'submission_file.extensions' => 'The submission file must be a Cisco Packet Tracer file (.pkt, .pkz), ZIP archive (.zip), or PDF document (.pdf).',
            'submission_file.max' => 'The file size cannot exceed 50MB (51,200 KB).',
        ]);

        $file = $request->file('submission_file');
        $originalName = $file->getClientOriginalName();
        $fileName = time() . '_' . $request->student_id . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $originalName);

        // Store file in storage/app/public/submissions/
        $filePath = $file->storeAs('submissions', $fileName, 'public');

        $submission = Submission::create([
            'lab_task_id' => $validated['lab_task_id'],
            'student_name' => $validated['student_name'],
            'student_id' => $validated['student_id'],
            'client_ip' => $request->ip() ?: '127.0.0.1',
            'file_path' => $filePath,
        ]);

        // Audit Trail Activity Log
        ActivityLogger::log(
            'Solution Submitted',
            "Student {$request->student_name} ({$request->student_id}) submitted for task #{$request->lab_task_id}",
            'success',
            $request
        );

        return redirect()->back()->with('success', 'Solution file submitted successfully!');
    }

    /**
     * Display a listing of all student submissions for Instructor on Master Host.
     */
    public function index()
    {
        $submissions = Submission::with('labTask')->latest()->paginate(20);
        $totalSubmissions = Submission::count();
        $uniqueStudents = Submission::distinct('student_id')->count('student_id');
        $latestSubmission = Submission::latest()->first();

        return view('admin.submissions.index', compact(
            'submissions',
            'totalSubmissions',
            'uniqueStudents',
            'latestSubmission'
        ));
    }

    /**
     * Download the student submission file.
     */
    public function download($id)
    {
        $submission = Submission::findOrFail($id);

        if (!Storage::disk('public')->exists($submission->file_path)) {
            abort(404, 'The submitted file does not exist on Master storage.');
        }

        ActivityLogger::log(
            'Submission Downloaded',
            "Instructor downloaded submission for {$submission->student_name} ({$submission->student_id})",
            'info',
            request()
        );

        return Storage::disk('public')->download($submission->file_path, basename($submission->file_path));
    }
}
