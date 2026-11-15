<?php

namespace Tests\Feature;

use App\Models\JudgeProblem;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LocalFileProblemImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_page_has_a_separate_local_file_import_card(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('judge.admin.problems.create'))
            ->assertOk()
            ->assertSee('Auto-Generate an Original Problem from a Link (LeetCode / Codeforces / Online Judge)')
            ->assertSee('Auto-Generate a PDF Contest Problem from a Link (UVa Online Judge / PDF)')
            ->assertSee('Auto-Generate a PDF Contest Problem from Local File (Upload PDF / Image from PC)')
            ->assertSee(route('judge.admin.problems.importLocalFile'))
            ->assertSee('Upload & Generate PDF Problem');
    }

    public function test_pdf_upload_saves_source_pdf_schedule_and_generated_test_cases(): void
    {
        config(['services.gemini.api_key' => 'AQ.test-key']);
        Storage::fake('public');
        $pdfContents = Pdf::loadHTML(
            '<html><body><h1>Sample Challenge</h1><p>Read two integers and print their sum.</p>'
            .'<h2>Sample Input</h2><pre>2 3</pre><h2>Sample Output</h2><pre>5</pre></body></html>'
        )->output();
        $cases = array_fill(0, 6, [
            'input' => '1 2',
            'expected_output' => '3',
        ]);
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode([
                            'title' => 'Sample Challenge',
                            'time_limit' => 1,
                            'memory_limit' => 128,
                            'test_cases' => $cases,
                        ], JSON_THROW_ON_ERROR),
                    ]]],
                ]],
            ]),
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('judge.admin.problems.importLocalFile'), [
                'problem_file' => UploadedFile::fake()->createWithContent('challenge.pdf', $pdfContents),
                'contest_start_time' => '2026-11-15T10:00',
                'contest_end_time' => '2026-11-15T12:00',
            ])
            ->assertRedirect(route('judge.show', 1))
            ->assertSessionHas('success', 'Local file contest problem generated successfully.');

        $problem = JudgeProblem::with('testCases')->firstOrFail();
        $this->assertSame('Sample Challenge', $problem->title);
        $this->assertSame(1, $problem->time_limit);
        $this->assertSame(128, $problem->memory_limit);
        $this->assertSame('2026-11-15 10:00:00', $problem->start_time->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-15 12:00:00', $problem->end_time->format('Y-m-d H:i:s'));
        $this->assertCount(7, $problem->testCases);
        $this->assertSame('2 3', $problem->testCases[0]->input);
        $this->assertSame('5', $problem->testCases[0]->expected_output);
        $this->assertStringStartsWith('problem-pdfs/local_', $problem->pdf_path);
        $this->assertSame($pdfContents, Storage::disk('public')->get($problem->pdf_path));
        $this->get(route('judge.show', $problem->id))
            ->assertOk()
            ->assertSee(asset('storage/'.$problem->pdf_path), false)
            ->assertSee('Submit Code');
        Http::assertSent(fn (Request $request): bool =>
            ($request['contents'][0]['parts'][1]['inlineData']['mimeType'] ?? null) === 'application/pdf'
            && ($request['contents'][0]['parts'][1]['inlineData']['data'] ?? null) === base64_encode($pdfContents)
        );
    }

    public function test_image_upload_is_rendered_to_pdf_and_sent_with_its_image_mime_type(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD is required by DomPDF to render uploaded raster images.');
        }

        config(['services.gemini.api_key' => 'AQ.test-key']);
        Storage::fake('public');
        $imageContents = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC');
        $cases = array_fill(0, 6, [
            'input' => '1',
            'expected_output' => '1',
        ]);
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode([
                            'title' => 'Photo Problem',
                            'test_cases' => $cases,
                        ], JSON_THROW_ON_ERROR),
                    ]]],
                ]],
            ]),
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('judge.admin.problems.importLocalFile'), [
                'problem_file' => UploadedFile::fake()->createWithContent('question.png', $imageContents),
            ])
            ->assertRedirect(route('judge.show', 1));

        $problem = JudgeProblem::with('testCases')->firstOrFail();
        $storedPdf = Storage::disk('public')->get($problem->pdf_path);
        $this->assertStringStartsWith('%PDF-', $storedPdf);
        $this->assertCount(6, $problem->testCases);
        Http::assertSent(fn (Request $request): bool =>
            ($request['contents'][0]['parts'][1]['inlineData']['mimeType'] ?? null) === 'image/png'
            && ($request['contents'][0]['parts'][1]['inlineData']['data'] ?? null) === base64_encode($imageContents)
        );
    }

    public function test_temporary_gemini_overload_still_saves_the_uploaded_pdf_safely(): void
    {
        config(['services.gemini.api_key' => 'AQ.test-key']);
        Storage::fake('public');
        $pdfContents = Pdf::loadHTML('<html><body><h1>Local Problem</h1><p>Find a result.</p></body></html>')->output();
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'error' => ['message' => 'The service is overloaded.'],
            ], 503),
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('judge.admin.problems.importLocalFile'), [
                'problem_file' => UploadedFile::fake()->createWithContent('local.pdf', $pdfContents),
            ])
            ->assertRedirect(route('judge.show', 1))
            ->assertSessionHas('success', 'The problem PDF was saved, but Gemini is temporarily unavailable. Review the problem and add judge test cases before submissions.');

        $problem = JudgeProblem::with('testCases')->firstOrFail();
        $this->assertSame('Local Problem', $problem->title);
        $this->assertSame(3, $problem->time_limit);
        $this->assertSame(256, $problem->memory_limit);
        $this->assertCount(0, $problem->testCases);
        $this->assertSame($pdfContents, Storage::disk('public')->get($problem->pdf_path));
    }

    public function test_image_upload_without_gd_returns_a_clear_conversion_error(): void
    {
        if (extension_loaded('gd')) {
            $this->markTestSkipped('This validation case applies only when GD is unavailable.');
        }

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->from(route('judge.admin.problems.create'))
            ->post(route('judge.admin.problems.importLocalFile'), [
                'problem_file' => UploadedFile::fake()->createWithContent('question.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC')),
            ])
            ->assertRedirect(route('judge.admin.problems.create'))
            ->assertSessionHasErrors([
                'problem_file' => 'The PHP GD extension is required to convert uploaded images to PDF. Enable GD and try again.',
            ]);
    }
}
