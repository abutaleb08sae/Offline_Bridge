<?php

namespace Tests\Feature;

use App\Models\JudgeProblem;
use App\Models\JudgeSubmission;
use App\Models\JudgeTestCase;
use App\Models\User;
use App\Services\LocalJudgeRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class LocalJudgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_problem_list_and_statement_are_available(): void
    {
        $problem = JudgeProblem::create([
            'title' => 'Add two numbers',
            'description' => 'Read two integers and print their sum.',
        ]);
        JudgeTestCase::create([
            'judge_problem_id' => $problem->id,
            'input' => "2 3\n",
            'expected_output' => "5\n",
        ]);

        $this->get(route('judge.index'))
            ->assertOk()
            ->assertSee('Add two numbers')
            ->assertSee('Solve Problem');

        $this->get(route('judge.show', $problem->id))
            ->assertOk()
            ->assertSee('Read two integers and print their sum.')
            ->assertSee('2 3')
            ->assertSee('Submit Code');
    }

    public function test_student_problem_page_renders_encoded_safe_html_and_strips_scripts(): void
    {
        $problem = JudgeProblem::create([
            'title' => 'Formatted statement',
            'description' => '&amp;lt;p&amp;gt;Use &amp;lt;code&amp;gt;x &amp;amp;lt;= 5&amp;lt;/code&amp;gt;.&amp;lt;/p&amp;gt;&amp;lt;script&amp;gt;alert(1)&amp;lt;/script&amp;gt;&amp;lt;ul&amp;gt;&amp;lt;li&amp;gt;First item&amp;lt;/li&amp;gt;&amp;lt;/ul&amp;gt;',
        ]);

        $this->get(route('judge.show', $problem->id))
            ->assertOk()
            ->assertSee('<p>Use <code>x &lt;= 5</code>.</p>', false)
            ->assertSee('<ul><li>First item</li></ul>', false)
            ->assertDontSee('alert(1)');
    }

    public function test_student_problem_statement_collapses_empty_paragraphs_and_uses_normal_whitespace(): void
    {
        $problem = JudgeProblem::create([
            'title' => 'Compact statement',
            'description' => '<p>First paragraph.</p><p>&nbsp;</p><p>   </p><pre>line one
line two</pre>',
        ]);

        $this->get(route('judge.show', $problem->id))
            ->assertOk()
            ->assertSee('class="problem-description problem-statement" style="line-height:', false)
            ->assertSee('<p>First paragraph.</p><pre>line one', false)
            ->assertDontSee('<p>&nbsp;</p>', false)
            ->assertDontSee('<p>   </p>', false);
    }

    public function test_student_problem_page_shows_at_most_two_sample_cases_and_hides_remaining_cases(): void
    {
        $problem = JudgeProblem::create([
            'title' => 'Multiple samples',
            'description' => 'A problem with hidden judge cases.',
        ]);
        foreach ([
            ['input' => 'sample input one', 'expected_output' => 'sample output one'],
            ['input' => 'sample input two', 'expected_output' => 'sample output two'],
            ['input' => 'hidden input three', 'expected_output' => 'hidden output three'],
        ] as $testCase) {
            $problem->testCases()->create($testCase);
        }

        $this->get(route('judge.show', $problem->id))
            ->assertOk()
            ->assertSee('Sample Input 1')
            ->assertSee('Sample Output 1')
            ->assertSee('sample input one')
            ->assertSee('sample output one')
            ->assertSee('Sample Input 2')
            ->assertSee('Sample Output 2')
            ->assertSee('sample input two')
            ->assertSee('sample output two')
            ->assertDontSee('hidden input three')
            ->assertDontSee('hidden output three');
    }

    public function test_student_problem_page_supports_a_single_sample_case(): void
    {
        $problem = JudgeProblem::create([
            'title' => 'Single sample',
            'description' => 'A problem with one sample.',
        ]);
        $problem->testCases()->create([
            'input' => 'only sample input',
            'expected_output' => 'only sample output',
        ]);

        $this->get(route('judge.show', $problem->id))
            ->assertOk()
            ->assertSee('Sample Input 1')
            ->assertSee('Sample Output 1')
            ->assertSee('only sample input')
            ->assertSee('only sample output')
            ->assertDontSee('Sample Input 2');
    }

    public function test_forbidden_command_pattern_is_saved_as_security_violation(): void
    {
        $problem = JudgeProblem::create([
            'title' => 'Sample',
            'description' => 'A sample problem.',
        ]);
        JudgeTestCase::create([
            'judge_problem_id' => $problem->id,
            'input' => '',
            'expected_output' => '',
        ]);

        $this->post(route('judge.submit'), [
            'judge_problem_id' => $problem->id,
            'language' => 'python',
            'code' => "import os\nos.system('whoami')",
        ])
            ->assertRedirect(route('judge.show', $problem->id))
            ->assertSessionHas('judge_result.verdict', 'Security Violation');

        $this->assertDatabaseHas('judge_submissions', [
            'judge_problem_id' => $problem->id,
            'language' => 'python',
            'verdict' => 'Security Violation',
        ]);
    }

    public function test_runner_verdict_is_saved_and_returned_to_the_student(): void
    {
        $problem = JudgeProblem::create([
            'title' => 'Sample',
            'description' => 'A sample problem.',
        ]);
        JudgeTestCase::create([
            'judge_problem_id' => $problem->id,
            'input' => '3',
            'expected_output' => '3',
        ]);

        $this->mock(LocalJudgeRunner::class, function (MockInterface $mock): void {
            $mock->shouldReceive('judge')
                ->once()
                ->andReturn(['verdict' => 'Accepted', 'details' => null]);
        });

        $this->post(route('judge.submit'), [
            'judge_problem_id' => $problem->id,
            'language' => 'python',
            'code' => 'print(input())',
        ])
            ->assertRedirect(route('judge.show', $problem->id))
            ->assertSessionHas('judge_result.verdict', 'Accepted');

        $this->assertDatabaseHas('judge_submissions', [
            'judge_problem_id' => $problem->id,
            'language' => 'python',
            'code' => 'print(input())',
            'verdict' => 'Accepted',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Judge Submission',
        ]);
    }

    public function test_submission_is_saved_without_execution_when_vm_runner_is_unconfigured(): void
    {
        config(['judge.vm_runner' => null]);
        $this->mock(LocalJudgeRunner::class, function (MockInterface $mock): void {
            $mock->shouldReceive('judge')
                ->once()
                ->andReturn([
                    'verdict' => 'Runner Unavailable',
                    'details' => 'No isolated runner is configured.',
                ]);
        });

        $problem = JudgeProblem::create([
            'title' => 'Sample',
            'description' => 'A sample problem.',
        ]);
        JudgeTestCase::create([
            'judge_problem_id' => $problem->id,
            'input' => '3',
            'expected_output' => '3',
        ]);

        $this->post(route('judge.submit'), [
            'judge_problem_id' => $problem->id,
            'language' => 'python',
            'code' => 'print(input())',
        ])
            ->assertRedirect(route('judge.show', $problem->id))
            ->assertSessionHas('judge_result.verdict', 'Runner Unavailable');

        $this->assertDatabaseHas('judge_submissions', [
            'judge_problem_id' => $problem->id,
            'language' => 'python',
            'verdict' => 'Runner Unavailable',
        ]);
    }

    public function test_admin_routes_require_login_and_admin_role(): void
    {
        $this->get(route('judge.admin.problems.create'))
            ->assertRedirect(route('login'));
        $this->get(route('judge.problems.edit', 1))
            ->assertRedirect(route('login'));
        $this->put(route('judge.problems.update', 1), [])
            ->assertRedirect(route('login'));

        $student = User::factory()->create();
        $this->actingAs($student)
            ->get(route('judge.admin.submissions'))
            ->assertForbidden();
        $this->get(route('judge.problems.edit', 1))->assertForbidden();
    }

    public function test_admin_can_create_problem_and_manage_submission_details(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        $this->actingAs($admin);

        $this->get(route('judge.admin.problems.create'))->assertOk();

        $this->post(route('judge.admin.problems.store'), [
            'title' => 'Empty input sample',
            'description' => 'Test an empty input/output case.',
            'time_limit' => 2,
            'memory_limit' => 256,
            'test_cases' => [
                ['input' => '', 'expected_output' => ''],
            ],
        ])->assertRedirect(route('judge.show', 1));

        $this->assertDatabaseHas('judge_problems', ['title' => 'Empty input sample']);
        $this->assertDatabaseHas('judge_test_cases', [
            'judge_problem_id' => 1,
            'input' => '',
            'expected_output' => '',
        ]);

        $submission = JudgeSubmission::create([
            'user_id' => $admin->id,
            'judge_problem_id' => 1,
            'language' => 'cpp',
            'code' => 'int main() {}',
            'verdict' => 'Accepted',
        ]);

        $this->get(route('judge.admin.submissions'))
            ->assertOk()
            ->assertSee('Empty input sample')
            ->assertSee('Accepted');

        $this->get(route('judge.admin.submissions.show', $submission->id))
            ->assertOk()
            ->assertSee('int main() {}');
    }

    public function test_admin_can_edit_problem_and_synchronize_test_cases(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        $this->actingAs($admin);

        $problem = JudgeProblem::create([
            'title' => 'Original title',
            'description' => 'Original description.',
            'time_limit' => 2,
            'memory_limit' => 256,
        ]);
        $updatedCase = $problem->testCases()->create([
            'input' => 'old input',
            'expected_output' => 'old output',
        ]);
        $removedCase = $problem->testCases()->create([
            'input' => 'removed input',
            'expected_output' => 'removed output',
        ]);

        $this->get(route('judge.problems.edit', $problem->id))
            ->assertOk()
            ->assertSee('Original title')
            ->assertSee('old input')
            ->assertSee('Save Changes')
            ->assertSee('+ Add Test Case');

        $this->put(route('judge.problems.update', $problem->id), [
            'title' => 'Updated title',
            'description' => 'Updated description.',
            'time_limit' => 3,
            'memory_limit' => 512,
            'test_cases' => [
                [
                    'id' => $updatedCase->id,
                    'input' => 'updated input',
                    'expected_output' => 'updated output',
                ],
                [
                    'input' => 'new input',
                    'expected_output' => 'new output',
                ],
            ],
        ])
            ->assertRedirect(route('judge.index'))
            ->assertSessionHas('success', 'Problem updated successfully.');

        $this->assertDatabaseHas('judge_problems', [
            'id' => $problem->id,
            'title' => 'Updated title',
            'description' => 'Updated description.',
            'time_limit' => 3,
            'memory_limit' => 512,
        ]);
        $this->assertDatabaseHas('judge_test_cases', [
            'id' => $updatedCase->id,
            'judge_problem_id' => $problem->id,
            'input' => 'updated input',
            'expected_output' => 'updated output',
        ]);
        $this->assertDatabaseMissing('judge_test_cases', ['id' => $removedCase->id]);
        $this->assertDatabaseHas('judge_test_cases', [
            'judge_problem_id' => $problem->id,
            'input' => 'new input',
            'expected_output' => 'new output',
        ]);

        $this->get(route('judge.index'))
            ->assertOk()
            ->assertSee('Updated title')
            ->assertSee(route('judge.problems.edit', $problem->id));
    }

    public function test_admin_can_delete_problem_with_test_cases_and_submissions(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        $this->actingAs($admin);

        $problem = JudgeProblem::create([
            'title' => 'Problem to delete',
            'description' => 'This problem and its children should be removed.',
        ]);
        $testCase = $problem->testCases()->create([
            'input' => '1',
            'expected_output' => '1',
        ]);
        $submission = $problem->submissions()->create([
            'user_id' => $admin->id,
            'language' => 'cpp',
            'code' => 'int main() {}',
            'verdict' => 'Accepted',
        ]);

        $this->get(route('judge.index'))
            ->assertOk()
            ->assertSee('Delete')
            ->assertSee(route('judge.admin.problems.destroy', $problem->id))
            ->assertSee('name="_method" value="DELETE"', false)
            ->assertSee('Are you sure you want to delete this problem and all its test cases?');

        $this->delete(route('judge.admin.problems.destroy', $problem->id))
            ->assertRedirect(route('judge.index'))
            ->assertSessionHas('success', 'Problem deleted successfully.');

        $this->assertDatabaseMissing('judge_problems', ['id' => $problem->id]);
        $this->assertDatabaseMissing('judge_test_cases', ['id' => $testCase->id]);
        $this->assertDatabaseMissing('judge_submissions', ['id' => $submission->id]);
    }

    public function test_non_admin_cannot_delete_judge_problem(): void
    {
        $user = User::factory()->create();
        $problem = JudgeProblem::create([
            'title' => 'Protected problem',
            'description' => 'Only admins may delete this.',
        ]);

        $this->actingAs($user)
            ->delete(route('judge.admin.problems.destroy', $problem->id))
            ->assertForbidden();

        $this->assertDatabaseHas('judge_problems', ['id' => $problem->id]);
    }

    public function test_admin_can_log_in_and_log_out(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();

        $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($admin);

        $this->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_login_redirects_to_dashboard_instead_of_a_saved_api_url(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();

        $this->get('/api/activities/stream?limit=8')
            ->assertRedirect(route('login'));

        $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));
    }

    public function test_registration_creates_a_non_admin_user_and_signs_them_in(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('register'));

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('name="name"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false);

        $this->post(route('register.store'), [
            'name' => 'New Student',
            'email' => 'student@example.local',
            'password' => 'a-strong-password',
            'password_confirmation' => 'a-strong-password',
        ])->assertRedirect(route('judge.index'));

        $user = User::where('email', 'student@example.local')->firstOrFail();
        $this->assertSame('user', $user->role);
        $this->assertFalse($user->isAdmin());
        $this->assertAuthenticatedAs($user);
    }

    public function test_non_admin_routes_are_protected_and_sidebar_is_role_aware(): void
    {
        foreach ([
            '/users',
            '/clients',
            '/activities',
            '/logs',
            '/settings',
            '/backup',
            '/admin/submissions',
            '/api/activities/stream',
        ] as $path) {
            $this->get($path)->assertRedirect(route('login'));
        }

        $student = User::factory()->create();
        $this->actingAs($student)
            ->get(route('judge.index'))
            ->assertOk()
            ->assertSee('Local Judge / Online Judge')
            ->assertSee('Sign Out')
            ->assertDontSee('>Users</span>', false)
            ->assertDontSee('>Activities</span>', false);

        foreach ([
            '/users',
            '/clients',
            '/activities',
            '/logs',
            '/settings',
            '/backup',
            '/admin/submissions',
            '/api/activities/stream',
        ] as $path) {
            $this->get($path)->assertForbidden();
        }

        $this->post('/labs/upload')->assertForbidden();
    }

    public function test_logged_in_judge_submission_is_associated_with_user(): void
    {
        $student = User::factory()->create();
        $problem = JudgeProblem::create([
            'title' => 'Sample',
            'description' => 'A sample problem.',
        ]);
        JudgeTestCase::create([
            'judge_problem_id' => $problem->id,
            'input' => '3',
            'expected_output' => '3',
        ]);

        $this->mock(LocalJudgeRunner::class, function (MockInterface $mock): void {
            $mock->shouldReceive('judge')
                ->once()
                ->andReturn(['verdict' => 'Accepted', 'details' => null]);
        });

        $this->actingAs($student)->post(route('judge.submit'), [
            'judge_problem_id' => $problem->id,
            'language' => 'python',
            'code' => 'print(input())',
        ])->assertRedirect(route('judge.show', $problem->id));

        $this->assertDatabaseHas('judge_submissions', [
            'user_id' => $student->id,
            'judge_problem_id' => $problem->id,
            'verdict' => 'Accepted',
        ]);
    }
}
