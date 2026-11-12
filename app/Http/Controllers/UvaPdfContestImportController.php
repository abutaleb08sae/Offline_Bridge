<?php

namespace App\Http\Controllers;

use App\Models\JudgeProblem;
use App\Services\ActivityLogger;
use App\Services\UvaPdfContestImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class UvaPdfContestImportController extends Controller
{
    public function store(Request $request, UvaPdfContestImporter $importer): RedirectResponse
    {
        $validated = $request->validate([
            'pdf_url' => ['required', 'string', 'url', 'max:2048'],
            'contest_start_time' => ['nullable', 'date'],
            'contest_end_time' => ['nullable', 'date'],
        ]);
        if (! empty($validated['contest_start_time']) && ! empty($validated['contest_end_time'])
            && Carbon::parse($validated['contest_end_time'])->lte(Carbon::parse($validated['contest_start_time']))) {
            return back()->withErrors([
                'contest_end_time' => 'The contest end time must be after its start time.',
            ])->withInput();
        }

        $generated = $importer->generate($validated['pdf_url']);
        $pdfContents = $generated['pdf_contents'];
        unset($generated['pdf_contents'], $generated['problem_number'], $generated['samples']);

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

                $pdfPath = 'problem-pdfs/uva_'.Str::uuid().'.pdf';
                if (! Storage::disk('public')->put($pdfPath, $pdfContents)) {
                    throw new RuntimeException('Unable to store the imported contest PDF.');
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
            'UVa PDF Problem Imported',
            "Problem: {$problem->title} ({$problem->testCases()->count()} test cases)",
            'success',
            $request
        );

        return redirect()->route('judge.problems.edit', $problem->id)
            ->with('success', 'UVa/PDF contest problem imported. Review the statement and test cases before publishing.');
    }
}
