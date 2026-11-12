<?php

namespace Tests\Feature;

use App\Models\JudgeProblem;
use App\Models\JudgeTestCase;
use App\Models\User;
use App\Services\LocalJudgeRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery\MockInterface;
use Tests\TestCase;

class TimedJudgeProblemTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_admin_can_create_and_update_problem_schedule(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $this->get(route('judge.admin.problems.create'))
            ->assertOk()
            ->assertSee('Contest Start Time')
            ->assertSee('Contest End Time')
            ->assertSee('name="start_time"', false)
            ->assertSee('name="end_time"', false);

        $this->post(route('judge.admin.problems.store'), [
            'title' => 'Scheduled problem',
            'description' => 'A scheduled statement.',
            'time_limit' => 2,
            'memory_limit' => 256,
            'start_time' => '2026-11-15T10:00',
            'end_time' => '2026-11-15T12:00',
            'test_cases' => [['input' => '1', 'expected_output' => '1']],
        ])->assertRedirect(route('judge.show', 1));

        $problem = JudgeProblem::firstOrFail();
        $this->assertSame('2026-11-15 10:00:00', $problem->start_time->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-15 12:00:00', $problem->end_time->format('Y-m-d H:i:s'));
        $this->get(route('judge.problems.edit', $problem->id))
            ->assertOk()
            ->assertSee('Contest Start Time')
            ->assertSee('2026-11-15T10:00')
            ->assertSee('2026-11-15T12:00');

        $this->put(route('judge.problems.update', $problem->id), [
            'title' => $problem->title,
            'description' => $problem->description,
            'time_limit' => 2,
            'memory_limit' => 256,
            'start_time' => '',
            'end_time' => '',
            'test_cases' => [[
                'id' => $problem->testCases()->first()->id,
                'input' => '1',
                'expected_output' => '1',
            ]],
        ])->assertRedirect(route('judge.index'));

        $this->assertNull($problem->fresh()->start_time);
        $this->assertNull($problem->fresh()->end_time);
    }

    public function test_problem_schedule_rejects_end_time_not_after_start_time(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('judge.admin.problems.store'), [
            'title' => 'Invalid schedule',
            'description' => 'A statement.',
            'time_limit' => 2,
            'memory_limit' => 256,
            'start_time' => '2026-11-15T12:00',
            'end_time' => '2026-11-15T12:00',
            'test_cases' => [['input' => '', 'expected_output' => '']],
        ])->assertSessionHasErrors('end_time');

        $this->assertDatabaseCount('judge_problems', 0);
    }

    public function test_problem_page_shows_upcoming_and_running_countdown_states(): void
    {
        $problem = $this->createProblem([
            'start_time' => '2026-11-12 10:00:00',
            'end_time' => '2026-11-12 11:00:00',
        ]);

        Carbon::setTestNow('2026-11-12 09:30:00');
        $this->get(route('judge.show', $problem->id))
            ->assertOk()
            ->assertSee('Upcoming')
            ->assertSee('Starts in:')
            ->assertSee('disabled', false);

        Carbon::setTestNow('2026-11-12 10:30:00');
        $runningPage = $this->get(route('judge.show', $problem->id));
        $runningPage->assertOk()
            ->assertSee('Live')
            ->assertSee('Time Remaining:');
        $this->assertMatchesRegularExpression(
            '/<button\b(?=[^>]*data-submit-button)(?![^>]*\bdisabled\b)[^>]*>\s*Submit Code\s*<\/button>/s',
            $runningPage->getContent()
        );

        Carbon::setTestNow('2026-11-12 11:00:00');
        $expiredPage = $this->get(route('judge.show', $problem->id));
        $expiredPage->assertOk()->assertSee('Time Expired / Contest Ended');
        $this->assertMatchesRegularExpression(
            '/<button\b(?=[^>]*data-submit-button)(?=[^>]*\bdisabled\b)[^>]*>\s*Time Expired\s*<\/button>/s',
            $expiredPage->getContent()
        );
    }

    public function test_submission_is_rejected_before_start_and_after_end(): void
    {
        $problem = $this->createProblem([
            'start_time' => '2026-11-12 10:00:00',
            'end_time' => '2026-11-12 11:00:00',
        ]);

        $this->mock(LocalJudgeRunner::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('judge');
        });

        Carbon::setTestNow('2026-11-12 09:59:59');
        $this->from(route('judge.show', $problem->id))
            ->post(route('judge.submit'), $this->submissionData($problem))
            ->assertSessionHasErrors('schedule');

        Carbon::setTestNow('2026-11-12 11:00:00');
        $this->from(route('judge.show', $problem->id))
            ->post(route('judge.submit'), $this->submissionData($problem))
            ->assertSessionHasErrors('schedule');

        $this->assertDatabaseCount('judge_submissions', 0);
    }

    public function test_timed_submission_that_finishes_after_deadline_is_not_recorded(): void
    {
        $problem = $this->createProblem([
            'start_time' => '2026-11-12 10:00:00',
            'end_time' => '2026-11-12 11:00:00',
        ]);
        Carbon::setTestNow('2026-11-12 10:59:59');

        $this->mock(LocalJudgeRunner::class, function (MockInterface $mock): void {
            $mock->shouldReceive('judge')
                ->once()
                ->andReturnUsing(function (): array {
                    Carbon::setTestNow('2026-11-12 11:00:00');

                    return ['verdict' => 'Accepted', 'details' => null];
                });
        });

        $this->from(route('judge.show', $problem->id))
            ->post(route('judge.submit'), $this->submissionData($problem))
            ->assertSessionHasErrors('schedule');

        $this->assertDatabaseCount('judge_submissions', 0);
    }

    public function test_schedule_is_optional_for_unlimited_practice(): void
    {
        $problem = $this->createProblem();
        Carbon::setTestNow('2026-11-12 10:30:00');

        $this->mock(LocalJudgeRunner::class, function (MockInterface $mock): void {
            $mock->shouldReceive('judge')
                ->once()
                ->andReturn(['verdict' => 'Accepted', 'details' => null]);
        });

        $this->post(route('judge.submit'), $this->submissionData($problem))
            ->assertRedirect(route('judge.show', $problem->id))
            ->assertSessionHas('judge_result.verdict', 'Accepted');

        $this->assertDatabaseHas('judge_submissions', [
            'judge_problem_id' => $problem->id,
            'verdict' => 'Accepted',
        ]);
    }

    private function createProblem(array $schedule = []): JudgeProblem
    {
        $problem = JudgeProblem::create(array_merge([
            'title' => 'Timed problem',
            'description' => 'A statement.',
        ], $schedule));
        JudgeTestCase::create([
            'judge_problem_id' => $problem->id,
            'input' => '1',
            'expected_output' => '1',
        ]);

        return $problem;
    }

    private function submissionData(JudgeProblem $problem): array
    {
        return [
            'judge_problem_id' => $problem->id,
            'language' => 'python',
            'code' => 'print(input())',
        ];
    }
}
