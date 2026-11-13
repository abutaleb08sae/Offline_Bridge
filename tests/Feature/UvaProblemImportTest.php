<?php

namespace Tests\Feature;

use App\Models\JudgeProblem;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UvaProblemImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_uva_problem_page_import_saves_official_pdf_and_generates_six_judge_cases(): void
    {
        config(['services.gemini.api_key' => 'AQ.test-key']);
        Storage::fake('public');
        $pdfContents = Pdf::loadHTML(
            '<html><body><h1>100 - The 3n + 1 problem</h1><p>Given two integers i and j, determine the maximum cycle length for every integer between i and j.</p></body></html>'
        )->output();
        $cases = [
            ['input' => "1 10\n", 'expected_output' => "1 10 20\n"],
            ['input' => "100 200\n", 'expected_output' => "100 200 125\n"],
            ['input' => "201 210\n", 'expected_output' => "201 210 89\n"],
            ['input' => "900 1000\n", 'expected_output' => "900 1000 174\n"],
            ['input' => "1 1\n", 'expected_output' => "1 1 1\n"],
            ['input' => "0 0\n", 'expected_output' => "0 0 0\n"],
        ];
        Http::fake([
            'https://onlinejudge.org/index.php*' => Http::response(
                '<html><body><h1>100 - The 3n + 1 problem</h1><div id="problem-statement"><p>Given two integers i and j, determine the maximum cycle length for every integer between i and j.</p><h3>Sample Input</h3><pre>1 10</pre><h3>Sample Output</h3><pre>1 10 20</pre><p>Input: Two integers i and j.</p><p>Output: The integers and maximum cycle length.</p></div><a href="/external/1/100.pdf">PDF</a></body></html>',
                200,
                ['Content-Type' => 'text/html']
            ),
            'https://onlinejudge.org/external/1/100.pdf' => Http::response($pdfContents, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Length' => (string) strlen($pdfContents),
            ]),
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode([
                            'time_limit' => 3,
                            'memory_limit' => 64,
                            'test_cases' => $cases,
                        ], JSON_THROW_ON_ERROR),
                    ]]],
                ]],
            ], 200),
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('judge.admin.problems.import'), [
                'url' => 'https://onlinejudge.org/index.php?option=com_onlinejudge&problem=100',
            ])
            ->assertRedirect(route('judge.problems.edit', 1));

        $problem = JudgeProblem::with('testCases')->firstOrFail();
        $this->assertSame('100 - The 3n + 1 problem', $problem->title);
        $this->assertSame(3, $problem->time_limit);
        $this->assertSame(64, $problem->memory_limit);
        $this->assertCount(7, $problem->testCases);
        $this->assertSame('1 10', $problem->testCases[0]->input);
        $this->assertSame('1 10 20', $problem->testCases[0]->expected_output);
        $this->assertStringStartsWith('problem-pdfs/uva_100_', $problem->pdf_path);
        Storage::disk('public')->assertExists($problem->pdf_path);
        $this->assertSame($pdfContents, Storage::disk('public')->get($problem->pdf_path));
        $this->get(route('judge.show', $problem->id))
            ->assertOk()
            ->assertDontSee('UVa PDF View')
            ->assertDontSee('HTML View')
            ->assertDontSee('data-problem-html')
            ->assertSee(asset('storage/'.$problem->pdf_path), false);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://onlinejudge.org/external/1/100.pdf');
        Http::assertSent(function (Request $request): bool {
            if (! str_contains($request->url(), 'generativelanguage.googleapis.com')) {
                return false;
            }

            return str_contains($request['contents'][0]['parts'][0]['text'], 'The 3n + 1 problem')
                && ($request->header('x-goog-api-key')[0] ?? '') === 'AQ.test-key';
        });
    }

    public function test_direct_uva_pdf_link_is_downloaded_and_saved(): void
    {
        config(['services.gemini.api_key' => 'AQ.test-key']);
        Storage::fake('public');
        $pdfContents = Pdf::loadHTML(
            '<html><body><h1>100 - The 3n + 1 problem</h1><p>Given integers i and j, find their maximum cycle length using the defined process.</p></body></html>'
        )->output();
        $cases = array_fill(0, 6, ['input' => "1 10\n", 'expected_output' => "1 10 20\n"]);
        Http::fake([
            'https://onlinejudge.org/external/1/100.pdf' => Http::response($pdfContents, 200, ['Content-Type' => 'application/pdf']),
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode([
                            'time_limit' => 2,
                            'memory_limit' => 64,
                            'test_cases' => $cases,
                        ], JSON_THROW_ON_ERROR),
                    ]]],
                ]],
            ], 200),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->post(route('judge.admin.problems.import'), [
                'url' => 'https://onlinejudge.org/external/1/100.pdf',
            ])
            ->assertRedirect(route('judge.problems.edit', 1));

        $problem = JudgeProblem::firstOrFail();
        Storage::disk('public')->assertExists($problem->pdf_path);
        $this->assertSame(6, $problem->testCases()->count());
        $pdfPath = $problem->pdf_path;
        $this->actingAs($admin)
            ->delete(route('judge.admin.problems.destroy', $problem->id))
            ->assertRedirect(route('judge.index'));
        Storage::disk('public')->assertMissing($pdfPath);
    }
}
