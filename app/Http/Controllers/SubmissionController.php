<?php

namespace App\Http\Controllers;

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

        return view('labs.submit', compact('labTasks', 'clientIp'));
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

        $disk = Storage::disk('submissions')->exists($submission->file_path)
            ? 'submissions'
            : 'public';

        if (!Storage::disk($disk)->exists($submission->file_path)) {
            abort(404, 'The submitted file does not exist on Master storage.');
        }

        ActivityLogger::log(
            'Submission Downloaded',
            "Instructor downloaded submission for {$submission->student_name} ({$submission->student_id})",
            'info',
            request()
        );

        return Storage::disk($disk)->download($submission->file_path, basename($submission->file_path), [
            'Content-Type' => 'application/octet-stream',
        ]);
    }
}
