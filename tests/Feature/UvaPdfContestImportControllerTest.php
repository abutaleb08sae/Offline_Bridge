<?php

namespace Tests\Feature;

use App\Models\JudgeProblem;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UvaPdfContestImportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_page_has_separate_uva_pdf_link_import_card_and_keeps_original_import_card(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('judge.admin.problems.create'))
            ->assertOk()
            ->assertSee('Auto-Generate an Original Problem from a Link (LeetCode / Codeforces / Online Judge)')
            ->assertSee('Auto-Generate a PDF Contest Problem from a Link (UVa Online Judge / PDF)')
            ->assertSee(route('judge.admin.problems.importPdfUrl'))
            ->assertSee('Paste UVa or PDF URL e.g., https://onlinejudge.org/external/1/100.pdf')
            ->assertSee('Import & Generate PDF Problem');
    }

    public function test_uva_import_downloads_pdf_and_saves_schedule_and_sample_plus_hidden_cases(): void
    {
        config(['services.gemini.api_key' => 'AQ.test-key']);
        Storage::fake('public');
        $pdfContents = Pdf::loadHTML(
            '<html><body><h1>100 - The 3n + 1 problem</h1><p>Given two integers i and j, determine the maximum cycle length for every integer between i and j.</p></body></html>'
        )->output();
        $hiddenCases = [
            ['input' => "100 200\n", 'expected_output' => "100 200 125\n"],
            ['input' => "201 210\n", 'expected_output' => "201 210 89\n"],
            ['input' => "900 1000\n", 'expected_output' => "900 1000 174\n"],
            ['input' => "1 1\n", 'expected_output' => "1 1 1\n"],
            ['input' => "10 1\n", 'expected_output' => "10 1 20\n"],
            ['input' => "100 101\n", 'expected_output' => "100 101 119\n"],
        ];
        Http::fake([
            'https://onlinejudge.org/index.php*' => Http::response(
                '<html><body><h1>100 - The 3n + 1 problem</h1><div id="problem-statement"><p>Given two integers i and j, determine the maximum cycle length for every integer between i and j.</p><h3>Sample Input</h3><pre>1 10</pre><h3>Sample Output</h3><pre>1 10 20</pre></div><a href="/external/1/100.pdf">PDF</a></body></html>',
                200,
                ['Content-Type' => 'text/html']
            ),
            'https://onlinejudge.org/external/1/100.pdf' => Http::response($pdfContents, 200, ['Content-Type' => 'application/pdf']),
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode([
                            'title' => 'The 3n + 1 problem',
                            'time_limit' => '3.000 seconds',
                            'memory_limit' => '32 MB',
                            'test_cases' => $hiddenCases,
                        ], JSON_THROW_ON_ERROR),
                    ]]],
                ]],
            ], 200),
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('judge.admin.problems.importPdfUrl'), [
                'pdf_url' => 'https://onlinejudge.org/index.php?option=com_onlinejudge&problem=100',
                'contest_start_time' => '2026-11-15T10:00',
                'contest_end_time' => '2026-11-15T12:00',
            ])
            ->assertRedirect(route('judge.problems.edit', 1));

        $problem = JudgeProblem::with('testCases')->firstOrFail();
        $this->assertSame('The 3n + 1 problem', $problem->title);
        $this->assertSame(3, $problem->time_limit);
        $this->assertSame(32, $problem->memory_limit);
        $this->assertSame('2026-11-15 10:00:00', $problem->start_time->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-15 12:00:00', $problem->end_time->format('Y-m-d H:i:s'));
        $this->assertCount(7, $problem->testCases);
        $this->assertSame('1 10', $problem->testCases[0]->input);
        $this->assertSame('1 10 20', $problem->testCases[0]->expected_output);
        $this->assertStringStartsWith('problem-pdfs/uva_', $problem->pdf_path);
        $this->assertSame($pdfContents, Storage::disk('public')->get($problem->pdf_path));
        $this->get(route('judge.show', $problem->id))
            ->assertOk()
            ->assertSee(asset('storage/'.$problem->pdf_path), false)
            ->assertSee('Submit Code');
    }

    public function test_uva_html_without_downloadable_pdf_generates_local_pdf_and_rejects_bad_schedule(): void
    {
        config(['services.gemini.api_key' => 'AQ.test-key']);
        Storage::fake('public');
        Http::fake([
            'https://onlinejudge.org/index.php*' => Http::response(
                '<html><body><h1>100 - The 3n + 1 problem</h1><div id="problem-statement"><p>Given integers i and j, find their maximum cycle length by applying the process to every integer in range.</p></div></body></html>',
                200,
                ['Content-Type' => 'text/html']
            ),
            'https://onlinejudge.org/external/1/100.pdf' => Http::response('not found', 404),
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode([
                            'title' => 'The 3n + 1 problem',
                            'time_limit' => 2,
                            'memory_limit' => 64,
                            'test_cases' => array_fill(0, 6, [
                                'input' => "1 10\n",
                                'expected_output' => "1 10 20\n",
                            ]),
                        ], JSON_THROW_ON_ERROR),
                    ]]],
                ]],
            ], 200),
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('judge.admin.problems.create'))
            ->post(route('judge.admin.problems.importPdfUrl'), [
                'pdf_url' => 'https://onlinejudge.org/index.php?problem=100',
                'contest_start_time' => '2026-11-15T12:00',
                'contest_end_time' => '2026-11-15T10:00',
            ])
            ->assertRedirect(route('judge.admin.problems.create'))
            ->assertSessionHasErrors('contest_end_time');
        $this->assertDatabaseCount('judge_problems', 0);

        $this->actingAs($admin)
            ->post(route('judge.admin.problems.importPdfUrl'), [
                'pdf_url' => 'https://onlinejudge.org/index.php?problem=100',
            ])
            ->assertRedirect(route('judge.problems.edit', 1));

        $problem = JudgeProblem::firstOrFail();
        Storage::disk('public')->assertExists($problem->pdf_path);
        $this->assertStringStartsWith('%PDF-', Storage::disk('public')->get($problem->pdf_path));
    }

    public function test_missing_gemini_limits_and_title_use_uvaproblem_defaults(): void
    {
        $validCases = array_fill(0, 6, [
            'input' => "1 2\n",
            'expected_output' => "3\n",
        ]);
        $problem = $this->importPdfProblem(['test_cases' => $validCases]);

        $this->assertSame('UVa Problem #100', $problem->title);
        $this->assertSame(3, $problem->time_limit);
        $this->assertSame(256, $problem->memory_limit);
    }

    public function test_out_of_range_gemini_limits_and_empty_title_use_defaults(): void
    {
        $validCases = array_fill(0, 6, [
            'input' => "1 2\n",
            'expected_output' => "3\n",
        ]);
        $problem = $this->importPdfProblem([
            'title' => '',
            'time_limit' => '61 seconds',
            'memory_limit' => '2049 MB',
            'test_cases' => $validCases,
        ]);

        $this->assertSame('UVa Problem #100', $problem->title);
        $this->assertSame(3, $problem->time_limit);
        $this->assertSame(256, $problem->memory_limit);
    }

    private function importPdfProblem(array $generated): JudgeProblem
    {
        config(['services.gemini.api_key' => 'AQ.test-key']);
        Storage::fake('public');
        $pdfContents = Pdf::loadHTML(
            '<html><body><p>Given a sequence, calculate the required result for each supplied input.</p></body></html>'
        )->output();
        Http::fake([
            'https://onlinejudge.org/external/1/100.pdf' => Http::response($pdfContents, 200, ['Content-Type' => 'application/pdf']),
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode($generated, JSON_THROW_ON_ERROR),
                    ]]],
                ]],
            ], 200),
        ]);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->post(route('judge.admin.problems.importPdfUrl'), [
                'pdf_url' => 'https://onlinejudge.org/external/1/100.pdf',
            ])
            ->assertRedirect(route('judge.problems.edit', 1));

        return JudgeProblem::firstOrFail();
    }

    public function test_gemini_model_404s_trigger_ipv4_safe_model_discovery_and_retry(): void
    {
        config([
            'services.gemini.api_key' => 'AQ.test-key',
            'services.gemini.model' => 'gemini-configured-invalid',
        ]);
        Storage::fake('public');
        $pdfContents = Pdf::loadHTML(
            '<html><body><h1>100 - The 3n + 1 problem</h1><p>Given two integers i and j, determine the maximum cycle length for every integer between i and j.</p></body></html>'
        )->output();
        $generated = [
            'title' => 'The 3n + 1 problem',
            'time_limit' => 2,
            'memory_limit' => 64,
            'test_cases' => array_fill(0, 6, [
                'input' => "1 10\n",
                'expected_output' => "1 10 20\n",
            ]),
        ];
        Http::fake(function (Request $request) use ($pdfContents, $generated) {
            if ($request->url() === 'https://onlinejudge.org/external/1/100.pdf') {
                return Http::response($pdfContents, 200, ['Content-Type' => 'application/pdf']);
            }
            if (str_ends_with($request->url(), '/v1beta/models')) {
                return Http::response([
                    'models' => [
                        [
                            'name' => 'models/gemini-2.5-pro',
                            'supportedGenerationMethods' => ['generateContent'],
                        ],
                        [
                            'name' => 'models/gemini-2.5-flash-dynamic',
                            'supportedGenerationMethods' => ['generateContent'],
                        ],
                        [
                            'name' => 'models/gemini-2.5-flash-not-supported',
                            'supportedGenerationMethods' => ['embedContent'],
                        ],
                    ],
                ]);
            }
            if (str_contains($request->url(), '/models/gemini-2.5-flash-dynamic:generateContent')) {
                return Http::response([
                    'candidates' => [[
                        'content' => ['parts' => [[
                            'text' => json_encode($generated, JSON_THROW_ON_ERROR),
                        ]]],
                    ]],
                ]);
            }
            if (str_contains($request->url(), 'generativelanguage.googleapis.com')) {
                return Http::response(['error' => ['message' => 'model not found']], 404);
            }

            return Http::response('not found', 404);
        });

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('judge.admin.problems.importPdfUrl'), [
                'pdf_url' => 'https://onlinejudge.org/external/1/100.pdf',
            ])
            ->assertRedirect(route('judge.problems.edit', 1));

        Http::assertSent(fn (Request $request): bool => str_contains(
            $request->url(),
            '/models/gemini-configured-invalid:generateContent'
        ));
        Http::assertSent(function (Request $request): bool {
            return str_ends_with($request->url(), '/v1beta/models')
                && ($request->header('x-goog-api-key')[0] ?? '') === 'AQ.test-key';
        });
        Http::assertSent(fn (Request $request): bool => str_contains(
            $request->url(),
            '/models/gemini-2.5-flash-dynamic:generateContent'
        ));
        $this->assertDatabaseCount('judge_problems', 1);
    }

    public function test_model_503_retries_next_candidate_instead_of_aborting(): void
    {
        config([
            'services.gemini.api_key' => 'AQ.test-key',
            'services.gemini.model' => 'gemini-configured-busy',
        ]);
        Storage::fake('public');
        $pdfContents = Pdf::loadHTML(
            '<html><body><h1>100 - The 3n + 1 problem</h1><p>Given two integers i and j, determine the maximum cycle length for every integer between i and j.</p></body></html>'
        )->output();
        $generated = [
            'title' => 'The 3n + 1 problem',
            'time_limit' => 3,
            'memory_limit' => 256,
            'test_cases' => array_fill(0, 6, [
                'input' => "1 10\n",
                'expected_output' => "1 10 20\n",
            ]),
        ];
        Http::fake(function (Request $request) use ($pdfContents, $generated) {
            if ($request->url() === 'https://onlinejudge.org/external/1/100.pdf') {
                return Http::response($pdfContents, 200, ['Content-Type' => 'application/pdf']);
            }
            if (str_contains($request->url(), '/models/gemini-configured-busy:generateContent')) {
                return Http::response(['error' => ['message' => 'High demand']], 503);
            }
            if (str_contains($request->url(), '/models/gemini-2.5-flash:generateContent')) {
                return Http::response([
                    'candidates' => [[
                        'content' => ['parts' => [[
                            'text' => json_encode($generated, JSON_THROW_ON_ERROR),
                        ]]],
                    ]],
                ]);
            }

            return Http::response(['error' => ['message' => 'unexpected request']], 404);
        });

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('judge.admin.problems.importPdfUrl'), [
                'pdf_url' => 'https://onlinejudge.org/external/1/100.pdf',
            ])
            ->assertRedirect(route('judge.problems.edit', 1));

        Http::assertSent(fn (Request $request): bool => str_contains(
            $request->url(),
            '/models/gemini-configured-busy:generateContent'
        ));
        Http::assertSent(fn (Request $request): bool => str_contains(
            $request->url(),
            '/models/gemini-2.5-flash:generateContent'
        ));
        $this->assertDatabaseCount('judge_problems', 1);
    }

    public function test_all_temporary_model_failures_import_with_parsed_samples_and_log_warning(): void
    {
        config([
            'services.gemini.api_key' => 'AQ.test-key',
            'services.gemini.model' => 'gemini-configured-busy',
        ]);
        Storage::fake('public');
        Log::spy();
        $pdfContents = Pdf::loadHTML(
            '<html><body><h1>100 - The 3n + 1 problem</h1><p>Given two integers i and j, determine the maximum cycle length for every integer between i and j.</p></body></html>'
        )->output();
        Http::fake(function (Request $request) use ($pdfContents) {
            if ($request->url() === 'https://onlinejudge.org/index.php?problem=100') {
                return Http::response(
                    '<html><body><h1>100 - The 3n + 1 problem</h1><div id="problem-statement"><p>Given two integers i and j, determine the maximum cycle length for every integer between i and j.</p><h3>Sample Input</h3><pre>1 10</pre><h3>Sample Output</h3><pre>1 10 20</pre></div><a href="/external/1/100.pdf">PDF</a></body></html>',
                    200,
                    ['Content-Type' => 'text/html']
                );
            }
            if ($request->url() === 'https://onlinejudge.org/external/1/100.pdf') {
                return Http::response($pdfContents, 200, ['Content-Type' => 'application/pdf']);
            }
            if (str_contains($request->url(), 'generativelanguage.googleapis.com')) {
                return Http::response(['error' => ['message' => 'This model is currently experiencing high demand.']], 503);
            }

            return Http::response('unexpected request', 404);
        });

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('judge.admin.problems.importPdfUrl'), [
                'pdf_url' => 'https://onlinejudge.org/index.php?problem=100',
            ])
            ->assertRedirect(route('judge.problems.edit', 1));

        $problem = JudgeProblem::with('testCases')->firstOrFail();
        $this->assertSame('100 - The 3n + 1 problem', $problem->title);
        $this->assertSame(3, $problem->time_limit);
        $this->assertSame(256, $problem->memory_limit);
        $this->assertCount(1, $problem->testCases);
        $this->assertSame('1 10', $problem->testCases[0]->input);
        $this->assertSame('1 10 20', $problem->testCases[0]->expected_output);
        Log::shouldHaveReceived('warning')->withArgs(fn (string $message): bool => str_contains($message, 'without Gemini judge cases'))->once();
    }

    public function test_all_temporary_model_failures_without_samples_import_placeholder_case(): void
    {
        config([
            'services.gemini.api_key' => 'AQ.test-key',
            'services.gemini.model' => 'gemini-configured-busy',
        ]);
        Storage::fake('public');
        $pdfContents = Pdf::loadHTML(
            '<html><body><h1>100 - The 3n + 1 problem</h1><p>Given two integers i and j, determine the maximum cycle length for every integer between i and j.</p></body></html>'
        )->output();
        Http::fake(function (Request $request) use ($pdfContents) {
            if ($request->url() === 'https://onlinejudge.org/external/1/100.pdf') {
                return Http::response($pdfContents, 200, ['Content-Type' => 'application/pdf']);
            }
            if (str_contains($request->url(), 'generativelanguage.googleapis.com')) {
                return Http::response(['error' => ['message' => 'The service is overloaded.']], 503);
            }

            return Http::response('unexpected request', 404);
        });

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('judge.admin.problems.importPdfUrl'), [
                'pdf_url' => 'https://onlinejudge.org/external/1/100.pdf',
            ])
            ->assertRedirect(route('judge.problems.edit', 1));

        $problem = JudgeProblem::with('testCases')->firstOrFail();
        $this->assertSame('100 - The 3n + 1 problem', $problem->title);
        $this->assertCount(1, $problem->testCases);
        $this->assertSame('', $problem->testCases[0]->input);
        $this->assertSame('', $problem->testCases[0]->expected_output);
    }
}
