<?php

namespace App\Http\Controllers;

use App\Models\JudgeProblem;
use App\Services\ProblemStatementSanitizer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class JudgeProblemMediaController extends Controller
{
    public function uploadImages(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'problem_id' => ['required', 'integer', 'exists:judge_problems,id'],
            'images' => ['required', 'array', 'min:1', 'max:8'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        $problem = JudgeProblem::findOrFail($validated['problem_id']);
        $existingImages = $this->storedImages($problem);
        if (count($existingImages) + count($validated['images']) > 12) {
            throw ValidationException::withMessages([
                'images' => 'A problem may have no more than 12 attached diagrams.',
            ]);
        }

        $newPaths = [];
        try {
            foreach ($validated['images'] as $image) {
                $newPaths[] = $image->store('problem-images/'.$problem->id, 'public');
            }

            DB::transaction(function () use ($problem, $newPaths): void {
                $lockedProblem = JudgeProblem::whereKey($problem->id)->lockForUpdate()->firstOrFail();
                $lockedProblem->update([
                    'images' => array_values(array_merge($this->storedImages($lockedProblem), $newPaths)),
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($newPaths);
            throw $exception;
        }

        return back()->with('success', 'Problem diagrams uploaded successfully.');
    }

    public function uploadPdf(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'problem_id' => ['required', 'integer', 'exists:judge_problems,id'],
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);
        $problem = JudgeProblem::findOrFail($validated['problem_id']);
        $oldPath = $problem->pdf_path;
        $newPath = $validated['pdf']->storeAs(
            'problem-pdfs/'.$problem->id,
            Str::uuid().'.pdf',
            'public'
        );

        try {
            $problem->update(['pdf_path' => $newPath]);
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($newPath);
            throw $exception;
        }

        $this->deleteStoredFile($oldPath, 'problem-pdfs/');

        return back()->with('success', 'Custom problem PDF uploaded successfully.');
    }

    public function generatePdf(Request $request, ProblemStatementSanitizer $sanitizer): RedirectResponse
    {
        $validated = $request->validate([
            'problem_id' => ['required', 'integer', 'exists:judge_problems,id'],
        ]);
        $problemId = (int) $validated['problem_id'];
        $problem = JudgeProblem::with('testCases')->findOrFail($problemId);
        $diagramImages = [];
        foreach ($this->storedImages($problem) as $path) {
            if (! Storage::disk('public')->exists($path)) {
                continue;
            }
            $mime = Storage::disk('public')->mimeType($path);
            if (! is_string($mime) || ! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                continue;
            }
            $diagramImages[] = 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($path));
        }

        $pdfContents = Pdf::loadView('judge.pdf.uva_problem', [
            'problem' => $problem,
            'descriptionHtml' => $sanitizer->sanitize($problem->description),
            'diagramImages' => $diagramImages,
            'sampleCases' => $problem->testCases->take(2),
        ])->setPaper('a4')->output();

        $newPath = 'problem-pdfs/'.$problem->id.'/'.Str::uuid().'.pdf';
        Storage::disk('public')->put($newPath, $pdfContents);
        $oldPath = $problem->pdf_path;

        try {
            $problem->update(['pdf_path' => $newPath]);
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($newPath);
            throw $exception;
        }

        $this->deleteStoredFile($oldPath, 'problem-pdfs/');

        return back()->with('success', 'UVa-style problem PDF generated successfully.');
    }

    public function showPdf(int $problemId): BinaryFileResponse
    {
        $problem = JudgeProblem::findOrFail($problemId);
        abort_unless($this->isStoredPath($problem->pdf_path, 'problem-pdfs/'), 404);
        abort_unless(Storage::disk('public')->exists($problem->pdf_path), 404);

        return response()->file(Storage::disk('public')->path($problem->pdf_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.basename($problem->pdf_path).'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function removePdf(int $problemId): RedirectResponse
    {
        $problem = JudgeProblem::findOrFail($problemId);
        $oldPath = $problem->pdf_path;
        $problem->update(['pdf_path' => null]);
        $this->deleteStoredFile($oldPath, 'problem-pdfs/');

        return back()->with('success', 'Problem PDF removed successfully.');
    }

    private function storedImages(JudgeProblem $problem): array
    {
        return array_values(array_filter(
            is_array($problem->images) ? $problem->images : [],
            fn (mixed $path): bool => $this->isStoredPath($path, 'problem-images/')
        ));
    }

    private function isStoredPath(mixed $path, string $prefix): bool
    {
        return is_string($path)
            && str_starts_with($path, $prefix)
            && ! str_contains($path, '..')
            && ! str_contains($path, '\\');
    }

    private function deleteStoredFile(?string $path, string $prefix): void
    {
        if ($this->isStoredPath($path, $prefix) && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
