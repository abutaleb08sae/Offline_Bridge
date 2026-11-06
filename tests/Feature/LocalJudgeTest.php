<?php

namespace Tests\Feature;

use App\Models\JudgeProblem;
use App\Models\JudgeTestCase;
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
}
