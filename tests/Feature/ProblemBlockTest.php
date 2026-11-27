<?php

namespace Tests\Feature;

use App\Models\JudgeProblem;
use App\Models\JudgeSubmission;
use App\Models\ProblemBlock;
use App\Models\User;
use App\Services\LocalJudgeRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class ProblemBlockTest extends TestCase
{
    use RefreshDatabase;

    public function test_violation_is_logged_and_blocks_only_the_reporting_students_problem(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'allow_copy_paste' => false,
        ]);
        $blockedProblem = JudgeProblem::create([
            'title' => 'Blocked statement',
            'description' => 'This statement must no longer be visible.',
        ]);
        $otherProblem = JudgeProblem::create([
            'title' => 'Other statement',
            'description' => 'This problem remains available.',
        ]);

        $this->actingAs($student)
            ->postJson(route('judge.problems.violation'), [
                'problem_id' => $blockedProblem->id,
                'violation_type' => 'paste',
            ])
            ->assertCreated()
            ->assertJson([
                'blocked' => true,
                'message' => 'Access Blocked due to Copy-Paste Policy Violation',
            ]);

        $this->assertDatabaseHas('problem_blocks', [
            'user_id' => $student->id,
            'problem_id' => $blockedProblem->id,
            'violation_type' => 'paste',
            'is_blocked' => true,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Copy-Paste Violation',
            'status' => 'warning',
        ]);

        $this->get(route('judge.show', $blockedProblem->id))
            ->assertForbidden()
            ->assertSee('Access Blocked due to Copy-Paste Policy Violation')
            ->assertDontSee('This statement must no longer be visible.');

        $this->get(route('judge.problem.pdf', $blockedProblem->id))
            ->assertForbidden()
            ->assertSee('Access Blocked due to Copy-Paste Policy Violation');

        $this->get(route('judge.show', $otherProblem->id))
            ->assertOk()
            ->assertSee('This problem remains available.');

        $anotherStudent = User::factory()->create(['role' => 'student']);
        $this->actingAs($anotherStudent)
            ->get(route('judge.show', $blockedProblem->id))
            ->assertOk()
            ->assertSee('This statement must no longer be visible.');
    }

    public function test_blocked_student_cannot_submit_code(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $problem = JudgeProblem::create([
            'title' => 'Blocked submission',
            'description' => 'No code should run.',
        ]);
        ProblemBlock::create([
            'user_id' => $student->id,
            'problem_id' => $problem->id,
            'violation_type' => 'copy',
            'ip_address' => '127.0.0.1',
        ]);

        $this->mock(LocalJudgeRunner::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('judge');
        });

        $this->actingAs($student)
            ->post(route('judge.submit'), [
                'judge_problem_id' => $problem->id,
                'language' => 'cpp',
                'code' => 'int main() { return 0; }',
            ])
            ->assertForbidden()
            ->assertSee('Access Blocked due to Copy-Paste Policy Violation');

        $this->assertDatabaseCount('judge_submissions', 0);
    }

    public function test_admin_can_review_and_unblock_all_violation_records_for_a_problem(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $problem = JudgeProblem::create([
            'title' => 'Reviewable problem',
            'description' => 'Problem statement.',
        ]);
        $blocks = collect(['copy', 'cut'])->map(function (string $type) use ($student, $problem) {
            return ProblemBlock::create([
                'user_id' => $student->id,
                'problem_id' => $problem->id,
                'violation_type' => $type,
                'ip_address' => '127.0.0.1',
            ]);
        });

        $this->actingAs($admin)
            ->get(route('activities.index'))
            ->assertOk()
            ->assertSee('Copy-Paste Violation Security Log')
            ->assertSee($student->name)
            ->assertSee((string) $student->id)
            ->assertSee($problem->title)
            ->assertSee('Copy')
            ->assertSee('Cut')
            ->assertSee('Blocked')
            ->assertSee('Unblock Problem');

        $this->patchJson(route('admin.problem-blocks.unblock', $blocks->first()))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'is_blocked' => false,
                'unblocked_records' => 2,
                'message' => 'Problem access restored for this student.',
            ]);

        $newBlock = ProblemBlock::create([
            'user_id' => $student->id,
            'problem_id' => $problem->id,
            'violation_type' => 'paste',
            'ip_address' => '127.0.0.1',
        ]);
        $this->actingAs($admin)
            ->from(route('activities.index'))
            ->post(route('admin.problem-blocks.unblock', $newBlock))
            ->assertRedirect(route('activities.index'))
            ->assertSessionHas('success', 'Problem access restored for this student.');

        $this->assertDatabaseHas('problem_blocks', [
            'id' => $newBlock->id,
            'is_blocked' => false,
        ]);
        $this->assertDatabaseHas('problem_blocks', [
            'id' => $blocks->last()->id,
            'is_blocked' => false,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Copy-Paste Problem Unblocked',
            'status' => 'success',
        ]);

        $this->actingAs($student)
            ->get(route('judge.show', $problem->id))
            ->assertOk()
            ->assertSee('Problem statement.');
    }

    public function test_judge_submissions_page_has_inline_permission_and_unblock_controls(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $problem = JudgeProblem::create([
            'title' => 'Submission controls problem',
            'description' => 'Problem statement.',
        ]);
        $submission = JudgeSubmission::create([
            'user_id' => $student->id,
            'judge_problem_id' => $problem->id,
            'language' => 'cpp',
            'code' => 'int main() {}',
            'verdict' => 'Accepted',
        ]);
        $block = ProblemBlock::create([
            'user_id' => $student->id,
            'problem_id' => $problem->id,
            'violation_type' => 'copy',
            'ip_address' => '127.0.0.1',
        ]);

        $this->actingAs($admin)
            ->get(route('judge.admin.submissions'))
            ->assertOk()
            ->assertSee('COPY-PASTE')
            ->assertSee('OFF')
            ->assertSee('Unblock')
            ->assertSee(route('judge.admin.submissions.show', $submission->id));

        $this->actingAs($admin)
            ->from(route('judge.admin.submissions'))
            ->post(route('admin.users.toggle-copy-paste', $student))
            ->assertRedirect(route('judge.admin.submissions'));

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'allow_copy_paste' => true,
        ]);
        $this->actingAs($admin)
            ->get(route('judge.admin.submissions'))
            ->assertSee('ON');

        $this->actingAs($admin)
            ->from(route('judge.admin.submissions'))
            ->post(route('admin.problem-blocks.unblock', $block))
            ->assertRedirect(route('judge.admin.submissions'));

        $this->assertDatabaseHas('problem_blocks', [
            'id' => $block->id,
            'is_blocked' => false,
        ]);
    }

    public function test_allowed_students_and_admins_cannot_report_violations(): void
    {
        $problem = JudgeProblem::create([
            'title' => 'Report target',
            'description' => 'Problem statement.',
        ]);
        $allowedStudent = User::factory()->create([
            'role' => 'student',
            'allow_copy_paste' => true,
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($allowedStudent)
            ->postJson(route('judge.problems.violation'), [
                'problem_id' => $problem->id,
                'violation_type' => 'copy',
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->postJson(route('judge.problems.violation'), [
                'problem_id' => $problem->id,
                'violation_type' => 'copy',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('problem_blocks', 0);
    }
}
