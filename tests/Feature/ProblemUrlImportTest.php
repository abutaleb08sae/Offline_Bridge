<?php

namespace Tests\Feature;

use App\Models\JudgeProblem;
use App\Models\User;
use App\Services\ProblemStatementSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProblemUrlImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_generate_and_review_a_problem_from_page_metadata(): void
    {
        config(['services.gemini.api_key' => "  AQ.test-key \n"]);
        Http::fake([
            'http://93.184.216.34/problem' => Http::response(
                '<html><head><title>Pair Finder</title><meta name="description" content="Find values with a target relationship"></head><body>DO NOT SEND THIS STATEMENT BODY</body></html>',
                200
            ),
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode([
                            'title' => 'River Signal Pairs',
                            'description' => "Problem Statement\nFind two signal values with a specified sum.\n\nInput Format\n...\n\nOutput Format\n...\n\nConstraints\n...",
                            'time_limit' => 3,
                            'memory_limit' => 128,
                            'test_cases' => [
                                ['input' => "4 7\n1 2 5 6\n", 'expected_output' => "1 4\n"],
                                ['input' => "3 0\n-2 0 2\n", 'expected_output' => "1 3\n"],
                                ['input' => "2 8\n3 5\n", 'expected_output' => "1 2\n"],
                                ['input' => "4 20\n1 4 7 9\n", 'expected_output' => "-1\n"],
                                ['input' => "3 5\n1 4 9\n", 'expected_output' => "1 2\n"],
                            ],
                        ], JSON_THROW_ON_ERROR),
                    ]]],
                ]],
            ], 200),
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('judge.admin.problems.create'))
            ->post(route('judge.admin.problems.import'), [
                'url' => 'http://93.184.216.34/problem',
                'start_time' => '2026-11-15T10:00',
                'end_time' => '2026-11-15T12:00',
            ])
            ->assertRedirect(route('judge.problems.edit', 1))
            ->assertSessionHas('success');

        $problem = JudgeProblem::with('testCases')->firstOrFail();
        $this->assertSame('River Signal Pairs', $problem->title);
        $this->assertSame(3, $problem->time_limit);
        $this->assertSame(128, $problem->memory_limit);
        $this->assertSame(5, $problem->testCases->count());
        $this->assertSame('2026-11-15 10:00:00', $problem->start_time->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-15 12:00:00', $problem->end_time->format('Y-m-d H:i:s'));
        $this->get(route('judge.problems.edit', $problem->id))
            ->assertOk()
            ->assertSee('An imported problem draft was created.');

        Http::assertSent(function ($request): bool {
            if ($request->url() !== 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash-lite:generateContent') {
                return false;
            }

            $prompt = $request['contents'][0]['parts'][0]['text'];

            return str_contains($prompt, 'Pair Finder')
                && str_contains($prompt, 'standard input (stdin)')
                && ! str_contains($prompt, 'Find values with a target relationship')
                && ! str_contains($prompt, 'DO NOT SEND THIS STATEMENT BODY')
                && ($request->header('x-goog-api-key')[0] ?? '') === 'AQ.test-key'
                && $request['generationConfig']['responseMimeType'] === 'application/json'
                && ! str_contains($request->url(), '?key=');
        });
    }

    public function test_import_requires_gemini_api_key_and_does_not_create_a_problem(): void
    {
        config(['services.gemini.api_key' => null]);
        Http::fake();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('judge.admin.problems.create'))
            ->post(route('judge.admin.problems.import'), [
                'url' => 'https://example.com/problem',
            ])
            ->assertSessionHasErrors('url');

        Http::assertNothingSent();
        $this->assertDatabaseCount('judge_problems', 0);
    }

    public function test_gemini_connection_errors_include_the_underlying_exception_message(): void
    {
        config([
            'services.gemini.api_key' => null,
            'services.gemini.key' => "  AQ.config-key \n",
        ]);
        Http::fake([
            'http://93.184.216.34/problem' => Http::response('<html><title>Pair Finder</title></html>', 200),
            'https://generativelanguage.googleapis.com/*' => Http::sequence()
                ->pushFailedConnection('cURL error 60: certificate verify failed')
                ->pushFailedConnection('cURL error 60: certificate verify failed')
                ->pushFailedConnection('cURL error 60: certificate verify failed'),
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('judge.admin.problems.create'))
            ->post(route('judge.admin.problems.import'), [
                'url' => 'http://93.184.216.34/problem',
            ])
            ->assertSessionHasErrors([
                'url' => 'Google Gemini could not be reached: cURL error 60: certificate verify failed.',
            ]);

        $modelUrls = Http::recorded()
            ->map(fn ($pair) => $pair[0]->url())
            ->filter(fn (string $url) => str_contains($url, 'generativelanguage.googleapis.com'))
            ->values()
            ->all();
        $this->assertSame([
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash-lite:generateContent',
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent',
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.0-flash:generateContent',
        ], $modelUrls);
        $this->assertDatabaseCount('judge_problems', 0);
    }

    public function test_gemini_connection_failure_on_first_model_fails_over_to_next_model(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        Http::fake([
            'http://93.184.216.34/problem' => Http::response('<html><title>Pair Finder</title></html>', 200),
            'https://generativelanguage.googleapis.com/*' => Http::sequence()
                ->pushFailedConnection('cURL error 28: timed out')
                ->push($this->geminiResponse()),
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('judge.admin.problems.import'), [
                'url' => 'http://93.184.216.34/problem',
            ])
            ->assertRedirect(route('judge.problems.edit', 1));

        $modelUrls = Http::recorded()
            ->map(fn ($pair) => $pair[0]->url())
            ->filter(fn (string $url) => str_contains($url, 'generativelanguage.googleapis.com'))
            ->values()
            ->all();
        $this->assertSame([
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash-lite:generateContent',
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent',
        ], $modelUrls);
    }

    public function test_gemini_prompt_limits_statement_length_but_keeps_full_imported_content_and_samples(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        $longParagraph = str_repeat('Original wording. ', 600);
        $html = '<html><body><main><h1>Long Statement</h1><div class="problem-statement"><p>'
            .$longParagraph
            .'</p><div class="sample-test"><div class="input"><pre>1</pre></div><div class="output"><pre>2</pre></div></div></div></main></body></html>';
        Http::fake([
            'http://93.184.216.34/long-problem' => Http::response($html, 200, ['Content-Type' => 'text/html']),
            'https://generativelanguage.googleapis.com/*' => Http::response($this->additionalCasesResponse()),
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('judge.admin.problems.import'), ['url' => 'http://93.184.216.34/long-problem'])
            ->assertRedirect(route('judge.problems.edit', 1));

        $problem = JudgeProblem::with('testCases')->firstOrFail();
        $this->assertGreaterThan(8000, mb_strlen($problem->description));
        $this->assertSame('1', $problem->testCases[0]->input);
        $this->assertSame('2', $problem->testCases[0]->expected_output);

        Http::assertSent(function ($request): bool {
            if (! str_contains($request->url(), 'generativelanguage.googleapis.com')) {
                return false;
            }

            $prompt = $request['contents'][0]['parts'][0]['text'];
            $metadata = json_decode(substr($prompt, strrpos($prompt, "\n") + 1), true);

            return is_array($metadata)
                && mb_strlen($metadata['description']) === 8000
                && $metadata['samples'][0] === ['input' => '1', 'expected_output' => '2'];
        });
    }

    public function test_import_rejects_private_hosts_before_making_network_requests(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        Http::fake();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('judge.admin.problems.create'))
            ->post(route('judge.admin.problems.import'), [
                'url' => 'http://127.0.0.1/admin',
            ])
            ->assertSessionHasErrors('url');

        Http::assertNothingSent();
    }

    public function test_import_accepts_any_valid_public_http_host_without_allowlist_configuration(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        Http::fake([
            'https://8.8.8.8/problem' => Http::response('<html><title>Public Problem</title></html>', 200),
            'https://generativelanguage.googleapis.com/*' => Http::response($this->geminiResponse(), 200),
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('judge.admin.problems.import'), ['url' => 'https://8.8.8.8/problem'])
            ->assertRedirect(route('judge.problems.edit', 1));

        $this->assertDatabaseHas('judge_problems', ['title' => 'Fresh Original Problem']);
    }

    public function test_authorized_html_import_preserves_safe_statement_and_original_samples(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        Http::fake([
            'http://93.184.216.34/problem' => Http::response(
                '<html><body><main><h1>Licensed Pairing</h1><div class="problem-statement"><p>Find the pair described by the <strong>input data</strong>.</p><p>Time limit: 3 seconds. Memory limit: 128 MB.</p><h3>Sample Input 1</h3><pre>2 5
1 4</pre><h3>Sample Output 1</h3><pre>1 2</pre><script>alert(1)</script><p onclick="alert(1)">Keep this wording.</p></div></main></body></html>',
                200,
                ['Content-Type' => 'text/html']
            ),
            'https://generativelanguage.googleapis.com/*' => Http::response($this->additionalCasesResponse(), 200),
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('judge.admin.problems.import'), ['url' => 'http://93.184.216.34/problem'])
            ->assertRedirect(route('judge.problems.edit', 1));

        $problem = JudgeProblem::with('testCases')->firstOrFail();
        $this->assertSame('Licensed Pairing', $problem->title);
        $this->assertSame(3, $problem->time_limit);
        $this->assertSame(128, $problem->memory_limit);
        $this->assertStringContainsString('<strong>input data</strong>', $problem->description);
        $this->assertStringContainsString('Keep this wording.', $problem->description);
        $this->assertStringNotContainsString('onclick', $problem->description);
        $this->assertStringNotContainsString('<script', $problem->description);
        $this->assertSame("2 5\n1 4", $problem->testCases[0]->input);
        $this->assertSame('1 2', $problem->testCases[0]->expected_output);
        $this->assertSame(5, $problem->testCases->count());
        $this->get(route('judge.show', $problem->id))
            ->assertOk()
            ->assertSee('<strong>input data</strong>', false);
        $safeDescription = app(ProblemStatementSanitizer::class)->sanitize($problem->description);
        $this->assertStringNotContainsString('onclick', $safeDescription);
        $this->assertStringNotContainsString('alert(1)', $safeDescription);

        Http::assertSent(function ($request): bool {
            if (! str_contains($request->url(), 'generativelanguage.googleapis.com')) {
                return false;
            }

            return str_contains($request['contents'][0]['parts'][0]['text'], 'Find the pair described')
                && str_contains($request['contents'][0]['parts'][0]['text'], '"expected_output":"1 2"');
        });
    }

    public function test_blocked_problem_page_uses_url_metadata_fallback(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        Http::fake([
            'http://93.184.216.34/problem' => Http::response('Unavailable', 503),
            'https://generativelanguage.googleapis.com/*' => Http::response($this->geminiResponse(), 200),
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('judge.admin.problems.create'))
            ->post(route('judge.admin.problems.import'), [
                'url' => 'http://93.184.216.34/problem',
            ])
            ->assertRedirect(route('judge.problems.edit', 1));

        $this->assertDatabaseHas('judge_problems', ['title' => 'Fresh Original Problem']);
        Http::assertSent(function ($request): bool {
            if (! str_contains($request->url(), 'generativelanguage.googleapis.com')) {
                return false;
            }

            $prompt = $request['contents'][0]['parts'][0]['text'];

            return str_contains($prompt, '"source_domain":"93.184.216.34"')
                && str_contains($prompt, '"identifier":"problem"')
                && str_contains($prompt, 'standard output (stdout)');
        });
    }

    public function test_forbidden_problem_page_uses_url_metadata_fallback(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        Http::fake([
            'http://93.184.216.34/problem' => Http::response('Forbidden', 403),
            'https://generativelanguage.googleapis.com/*' => Http::response($this->geminiResponse(), 200),
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('judge.admin.problems.import'), [
                'url' => 'http://93.184.216.34/problem',
            ])
            ->assertRedirect(route('judge.problems.edit', 1));

        $this->assertDatabaseHas('judge_problems', ['title' => 'Fresh Original Problem']);
    }

    public function test_leetcode_import_preserves_graphql_statement_and_sample_data(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        Http::fake([
            'https://leetcode.com/graphql' => Http::response([
                'data' => [
                    'question' => [
                        'questionTitle' => 'Example pair task',
                        'difficulty' => 'Easy',
                        'content' => '<div class="problem-statement"><p>Return the exact pair described in this statement.</p><div class="sample-test"><div class="input"><pre>2 4
1 3</pre></div><div class="output"><pre>1 2</pre></div></div></div>',
                        'exampleTestcases' => "2 4\n1 3",
                        'metaData' => json_encode(['time_limit' => 2, 'memory_limit' => 96], JSON_THROW_ON_ERROR),
                        'topicTags' => [['name' => 'Arrays'], ['name' => 'Hash Table']],
                    ],
                ],
            ], 200),
            'https://generativelanguage.googleapis.com/*' => Http::response($this->additionalCasesResponse(), 200),
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('judge.admin.problems.import'), [
                'url' => 'https://leetcode.com/problems/example-pair-task/',
            ])
            ->assertRedirect(route('judge.problems.edit', 1));

        Http::assertSent(function ($request): bool {
            if ($request->url() !== 'https://leetcode.com/graphql') {
                return false;
            }

            return str_contains($request->header('User-Agent')[0] ?? '', 'Mozilla/5.0')
                && ($request->header('Referer')[0] ?? '') === 'https://leetcode.com/problems/example-pair-task/'
                && ($request->header('Content-Type')[0] ?? '') === 'application/json'
                && str_contains($request['query'], 'topicTags')
                && str_contains($request['query'], 'content')
                && str_contains($request['query'], 'exampleTestcases')
                && str_contains($request['query'], 'metaData');
        });

        $problem = JudgeProblem::with('testCases')->firstOrFail();
        $this->assertSame('Example pair task', $problem->title);
        $this->assertSame(2, $problem->time_limit);
        $this->assertSame(96, $problem->memory_limit);
        $this->assertStringContainsString('Return the exact pair described', $problem->description);
        $this->assertSame("2 4\n1 3", $problem->testCases[0]->input);
        $this->assertSame('1 2', $problem->testCases[0]->expected_output);

        Http::assertSent(function ($request): bool {
            if (! str_contains($request->url(), 'generativelanguage.googleapis.com')) {
                return false;
            }

            $prompt = $request['contents'][0]['parts'][0]['text'];

            return str_contains($prompt, '"topic_categories":["Arrays","Hash Table"]')
                && str_contains($prompt, '"title_slug":"example-pair-task"')
                && str_contains($prompt, '"title":"Example pair task"')
                && str_contains($prompt, 'Generate only 4 or 5 additional hidden test cases')
                && str_contains($prompt, '"expected_output":"1 2"');
        });

        Http::assertSent(function ($request): bool {
            if ($request->url() !== 'https://leetcode.com/graphql') {
                return false;
            }

            return $request['operationName'] === 'questionData'
                && $request['variables'] === ['titleSlug' => 'example-pair-task']
                && str_contains($request['query'], 'questionTitle')
                && str_contains($request['query'], 'topicTags')
                && str_contains($request['query'], 'difficulty')
                && str_contains($request['query'], 'content')
                && str_contains($request['query'], 'exampleTestcases')
                && str_contains($request['query'], 'metaData');
        });
    }

    public function test_authorized_json_or_graphql_response_can_supply_problem_content_and_samples(): void
    {
        config([
            'services.gemini.api_key' => 'test-key',
        ]);
        Http::fake([
            'http://93.184.216.34/problem.json' => Http::response([
                'data' => [
                    'problem' => [
                        'title' => 'JSON Source Problem',
                        'content' => '&lt;p&gt;Preserve this structured response and 2 &amp;lt;= 3.&lt;/p&gt;',
                        'timeLimit' => 1,
                        'memoryLimit' => 64,
                        'samples' => [
                            ['input' => "1\n", 'output' => "2\n"],
                        ],
                    ],
                ],
            ], 200, ['Content-Type' => 'application/json']),
            'https://generativelanguage.googleapis.com/*' => Http::response($this->additionalCasesResponse(), 200),
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('judge.admin.problems.import'), ['url' => 'http://93.184.216.34/problem.json'])
            ->assertRedirect(route('judge.problems.edit', 1));

        $problem = JudgeProblem::with('testCases')->firstOrFail();
        $this->assertSame('JSON Source Problem', $problem->title);
        $this->assertSame(
            '<p>Preserve this structured response and 2 &lt;= 3.</p><p><strong>Input / Output Format (Standard I/O)</strong><br>Read all input from standard input (stdin) and write the required result to standard output (stdout).</p>',
            $problem->description
        );
        $this->assertSame("1\n", $problem->testCases[0]->input);
        $this->assertSame("2\n", $problem->testCases[0]->expected_output);
        $this->assertSame(5, $problem->testCases->count());
        $this->assertStringContainsString('Input / Output Format (Standard I/O)', $problem->description);
        $this->get(route('judge.show', $problem->id))
            ->assertOk()
            ->assertSee('<p>Preserve this structured response and 2 &lt;= 3.</p>', false)
            ->assertSee('Input / Output Format (Standard I/O)');
    }

    public function test_gemini_retries_temporary_failures_using_ordered_model_fallbacks(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        $sequence = Http::sequence()
            ->push(['error' => ['message' => 'High demand']], 503)
            ->push(['error' => ['message' => 'Rate limit']], 429)
            ->push($this->geminiResponse(), 200);
        Http::fake([
            'http://93.184.216.34/problem' => Http::response('<html><title>Pair Finder</title></html>', 200),
            'https://generativelanguage.googleapis.com/*' => $sequence,
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('judge.admin.problems.import'), [
                'url' => 'http://93.184.216.34/problem',
            ])
            ->assertRedirect(route('judge.problems.edit', 1));

        $modelUrls = Http::recorded()
            ->map(fn ($pair) => $pair[0]->url())
            ->filter(fn (string $url) => str_contains($url, 'generativelanguage.googleapis.com'))
            ->values()
            ->all();

        $this->assertSame([
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash-lite:generateContent',
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent',
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.0-flash:generateContent',
        ], $modelUrls);
    }

    public function test_gemini_404_skips_to_next_model_without_restarting_retry_pass(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        Http::fake([
            'http://93.184.216.34/problem' => Http::response('<html><title>Pair Finder</title></html>', 200),
            'https://generativelanguage.googleapis.com/*' => Http::sequence()
                ->push(['error' => ['message' => 'Model not found']], 404)
                ->push($this->geminiResponse()),
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('judge.admin.problems.import'), ['url' => 'http://93.184.216.34/problem'])
            ->assertRedirect(route('judge.problems.edit', 1));

        $modelUrls = Http::recorded()
            ->map(fn ($pair) => $pair[0]->url())
            ->filter(fn (string $url) => str_contains($url, 'generativelanguage.googleapis.com'))
            ->values()
            ->all();
        $this->assertSame([
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash-lite:generateContent',
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent',
        ], $modelUrls);
    }

    public function test_gemini_reports_last_fallback_error_after_all_retryable_models_fail(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        $sequence = Http::sequence()
            ->push(['error' => ['message' => 'First model busy']], 503)
            ->push(['error' => ['message' => 'Second model rate limited']], 429)
            ->push(['error' => ['message' => 'Third model not found']], 404)
            ->push(['error' => ['message' => 'First model still busy']], 503)
            ->push(['error' => ['message' => 'Second model still rate limited']], 429)
            ->push(['error' => ['message' => 'Last model not found']], 404);
        Http::fake([
            'http://93.184.216.34/problem' => Http::response('<html><title>Pair Finder</title></html>', 200),
            'https://generativelanguage.googleapis.com/*' => $sequence,
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('judge.admin.problems.create'))
            ->post(route('judge.admin.problems.import'), [
                'url' => 'http://93.184.216.34/problem',
            ])
            ->assertSessionHasErrors([
                'url' => 'Google Gemini API error: Last model not found',
            ]);

        $this->assertDatabaseCount('judge_problems', 0);
        $this->assertSame(6, Http::recorded()->filter(
            fn ($pair) => str_contains($pair[0]->url(), 'generativelanguage.googleapis.com')
        )->count());
    }

    public function test_generated_latex_is_converted_to_plain_text_before_persistence(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        $response = $this->geminiResponse();
        $generated = json_decode($response['candidates'][0]['content']['parts'][0]['text'], true, 512, JSON_THROW_ON_ERROR);
        $generated['title'] = 'Bounded $N$ values';
        $generated['description'] = "Problem Statement\nCompute \$\\text{N} \\le 10^5\\$ values and multiply by \\mathrm{factor} \\times 2.\n\nInput Format\nRead \$N\$.\n\nOutput Format\nPrint \$N \\neq 0\$ and \$N \\cdot 2\$.\n\nConstraints\n\$0 \\le N \\le 10^5\$, with more values ...";
        $generated['test_cases'][0] = [
            'input' => 's = "babad"',
            'expected_output' => '"bab"',
        ];
        $response['candidates'][0]['content']['parts'][0]['text'] = json_encode($generated, JSON_THROW_ON_ERROR);
        Http::fake([
            'http://93.184.216.34/problem' => Http::response('<html><title>Pair Finder</title></html>', 200),
            'https://generativelanguage.googleapis.com/*' => Http::response($response, 200),
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('judge.admin.problems.import'), ['url' => 'http://93.184.216.34/problem'])
            ->assertRedirect(route('judge.problems.edit', 1));

        $problem = JudgeProblem::with('testCases')->firstOrFail();
        $this->assertSame('Bounded N values', $problem->title);
        $this->assertStringContainsString('Compute N <= 10^5 values', $problem->description);
        $this->assertStringContainsString('multiply by factor * 2', $problem->description);
        $this->assertStringContainsString('N != 0 and N * 2', $problem->description);
        $this->assertStringContainsString('0 <= N <= 10^5, with more values ...', $problem->description);
        $this->assertSame('babad', $problem->testCases[0]->input);
        $this->assertSame('bab', $problem->testCases[0]->expected_output);
        $this->assertStringContainsString('Input / Output Format (Standard I/O)', $problem->description);
    }

    private function geminiResponse(): array
    {
        return [
            'candidates' => [[
                'content' => ['parts' => [[
                    'text' => json_encode([
                        'title' => 'Fresh Original Problem',
                        'description' => "Problem Statement\nSolve a new task.\n\nInput Format\n...\n\nOutput Format\n...\n\nConstraints\n...",
                        'time_limit' => 2,
                        'memory_limit' => 256,
                        'test_cases' => [
                            ['input' => "1\n", 'expected_output' => "2\n"],
                            ['input' => "2\n", 'expected_output' => "3\n"],
                            ['input' => "3\n", 'expected_output' => "4\n"],
                            ['input' => "4\n", 'expected_output' => "5\n"],
                            ['input' => "5\n", 'expected_output' => "6\n"],
                        ],
                    ], JSON_THROW_ON_ERROR),
                ]]],
            ]],
        ];
    }

    private function additionalCasesResponse(): array
    {
        return [
            'candidates' => [[
                'content' => ['parts' => [[
                    'text' => json_encode([
                        'test_cases' => [
                            ['input' => "1 1\n1\n", 'expected_output' => "1\n"],
                            ['input' => "3 6\n1 2 4\n", 'expected_output' => "2 3\n"],
                            ['input' => "4 9\n1 3 5 8\n", 'expected_output' => "2 4\n"],
                            ['input' => "2 99\n4 7\n", 'expected_output' => "-1\n"],
                        ],
                    ], JSON_THROW_ON_ERROR),
                ]]],
            ]],
        ];
    }

    public function test_gemini_rejection_is_reported_without_persisting_a_problem(): void
    {
        config(['services.gemini.api_key' => 'invalid-key']);
        Http::fake([
            'http://93.184.216.34/problem' => Http::response(
                '<html><title>Pair Finder</title><meta name="description" content="Find values with a target relationship"></html>',
                200
            ),
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'error' => ['message' => 'API key is not valid for this model.'],
            ], 403),
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('judge.admin.problems.create'))
            ->post(route('judge.admin.problems.import'), [
                'url' => 'http://93.184.216.34/problem',
            ])
            ->assertSessionHasErrors([
                'url' => 'Google Gemini API error: API key is not valid for this model.',
            ]);

        $this->assertDatabaseCount('judge_problems', 0);
    }
}
