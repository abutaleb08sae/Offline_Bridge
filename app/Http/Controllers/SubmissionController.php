<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

    /**
     * Batch download all student submissions for a specific lab task as a single ZIP archive.
     */
    public function batchDownload($taskId)
    {
        $task = LabTask::with('submissions')->findOrFail($taskId);
        $submissions = $task->submissions;

        if ($submissions->isEmpty()) {
            return back()->with('error', 'No submissions found for this lab task.');
        }

        $tempDir = storage_path('app/temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $slug = Str::slug($task->title ?? 'Lab_Task_' . $task->id);
        $zipName = $slug . '_submissions_' . now()->format('Ymd_His') . '.zip';
        $zipPath = $tempDir . DIRECTORY_SEPARATOR . $zipName;

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Could not create zip archive.');
        }

        $addedCount = 0;
        foreach ($submissions as $index => $submission) {
            $filePath = null;
            if (Storage::disk('submissions')->exists($submission->file_path)) {
                $filePath = Storage::disk('submissions')->path($submission->file_path);
            } elseif (Storage::disk('public')->exists($submission->file_path)) {
                $filePath = Storage::disk('public')->path($submission->file_path);
            } elseif (file_exists(storage_path('app/' . $submission->file_path))) {
                $filePath = storage_path('app/' . $submission->file_path);
            }

            if ($filePath && file_exists($filePath) && !is_dir($filePath)) {
                $studentId = $submission->student_id ?? ($submission->user->name ?? 'Student_' . ($index + 1));
                $cleanStudentId = preg_replace('/[^A-Za-z0-9_\-]/', '_', $studentId);
                $zipEntryName = $cleanStudentId . '_' . basename($filePath);
                $zip->addFile($filePath, $zipEntryName);
                $addedCount++;
            }
        }

        $zip->close();

        if ($addedCount === 0) {
            if (file_exists($zipPath)) {
                @unlink($zipPath);
            }
            return back()->with('error', 'Submission records exist, but physical files were missing from storage.');
        }

        ActivityLogger::log(
            'Batch Submissions Downloaded',
            "Instructor batch downloaded {$addedCount} submissions for task '{$task->title}' as ZIP",
            'info',
            request()
        );

        return response()->download($zipPath, $zipName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }
}
