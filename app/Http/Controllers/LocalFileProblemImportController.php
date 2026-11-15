<?php

namespace App\Http\Controllers;

use App\Models\JudgeProblem;
use App\Services\ActivityLogger;
use App\Services\Judge\LocalFileProblemImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class LocalFileProblemImportController extends Controller
{
    public function store(Request $request, LocalFileProblemImporter $importer): RedirectResponse
    {
        $validated = $request->validate([
            'problem_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'mimetypes:application/pdf,image/jpeg,image/png,image/webp', 'max:20480'],
            'contest_start_time' => ['nullable', 'date'],
            'contest_end_time' => ['nullable', 'date'],
        ]);
        if (! empty($validated['contest_start_time']) && ! empty($validated['contest_end_time'])
            && Carbon::parse($validated['contest_end_time'])->lte(Carbon::parse($validated['contest_start_time']))) {
            return back()->withErrors([
                'contest_end_time' => 'The contest end time must be after its start time.',
            ])->withInput();
        }

        $generated = $importer->generate($validated['problem_file']);
        $pdfContents = $generated['pdf_contents'];
        $generatedByGemini = $generated['generated_by_gemini'];
        unset($generated['pdf_contents'], $generated['generated_by_gemini']);

        $pdfPath = null;
        try {
            $problem = DB::transaction(function () use ($generated, $validated, $pdfContents, &$pdfPath): JudgeProblem {
                $problem = JudgeProblem::create([
                    'title' => $generated['title'],
                    'description' => $generated['description'],
                    'time_limit' => $generated['time_limit'],
                    'memory_limit' => $generated['memory_limit'],
                    'start_time' => $validated['contest_start_time'] ?? null,
                    'end_time' => $validated['contest_end_time'] ?? null,
                ]);
                $problem->testCases()->createMany($generated['test_cases']);

                $pdfPath = 'problem-pdfs/local_'.Str::uuid().'.pdf';
                if (! Storage::disk('public')->put($pdfPath, $pdfContents)) {
                    throw new RuntimeException('Unable to store the uploaded problem PDF.');
                }
                $problem->update(['pdf_path' => $pdfPath]);

                return $problem;
            });
        } catch (Throwable $exception) {
            if ($pdfPath !== null) {
                Storage::disk('public')->delete($pdfPath);
            }
            throw $exception;
        }

        ActivityLogger::log(
            'Local File Judge Problem Imported',
            "Problem: {$problem->title} ({$problem->testCases()->count()} test cases)",
            'success',
            $request
        );

        $message = $generatedByGemini
            ? 'Local file contest problem generated successfully.'
            : 'The problem PDF was saved, but Gemini is temporarily unavailable. Review the problem and add judge test cases before submissions.';

        return redirect()->route('judge.show', $problem->id)->with('success', $message);
    }
}
