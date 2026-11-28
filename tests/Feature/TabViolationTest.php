<?php

namespace Tests\Feature;

use App\Models\JudgeProblem;
use App\Models\LabTask;
use App\Models\TabViolation;
use App\Models\User;
use App\Services\LocalJudgeRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

class TabViolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_tab_guard_script_and_authorized_pdf_link_attribute_are_rendered(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $problem = JudgeProblem::create([
            'title' => 'PDF Tab Exception Problem',
            'description' => 'Read PDF.',
            'pdf_path' => 'problem-pdfs/sample.pdf',
        ]);

        $this->actingAs($student)
            ->get(route('judge.index'))
            ->assertOk()
            ->assertSee('data-allow-new-tab="true"', false)
            ->assertSee('/tab-violations', false)
            ->assertSee('BroadcastChannel', false)
            ->assertSee('visibilitychange', false);

        // Standalone submission portal also includes the tab guard
        $this->actingAs($student)
            ->get(route('labs.submit'))
            ->assertOk()
            ->assertSee('/tab-violations', false)
            ->assertSee('BroadcastChannel', false);
    }

    public function test_admin_is_exempt_from_tab_guard_and_cannot_report_tab_violation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('tab-violations.store'), false);

        $this->actingAs($admin)
            ->postJson(route('tab-violations.store'), [
                'violation_type' => 'tab_switched',
                'page_url' => '/judge',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('tab_violations', 0);
    }

    public function test_tab_violation_logs_to_master_and_blocks_judge_labs_and_submissions(): void
    {
        Storage::fake('public');
        Storage::fake('submissions');

        $student = User::factory()->create(['role' => 'student']);
        $otherStudent = User::factory()->create(['role' => 'student']);

        $problem = JudgeProblem::create([
            'title' => 'Guarded Problem',
            'description' => 'Secret statement.',
            'pdf_path' => 'problem-pdfs/guarded.pdf',
        ]);
        Storage::disk('public')->put('problem-pdfs/guarded.pdf', "%PDF-1.4\n%%EOF");

        $labTask = LabTask::create([
            'title' => 'Lab 1 Topology',
            'file_path' => 'labs/lab1.pkt',
            'file_name' => 'lab1.pkt',
            'file_size' => 128,
        ]);
        Storage::disk('public')->put('labs/lab1.pkt', 'pkt-bytes');

        $this->actingAs($student)
            ->postJson(route('tab-violations.store'), [
                'violation_type' => 'tab_switched',
                'page_url' => '/labs',
                'details' => 'Browser tab hidden after opening a new tab.',
            ])
            ->assertCreated()
            ->assertJson([
                'blocked' => true,
                'message' => 'Access Blocked due to Browser Tab Policy Violation',
            ]);

        $this->assertDatabaseHas('tab_violations', [
            'user_id' => $student->id,
            'violation_type' => 'tab_switched',
            'page_url' => '/labs',
            'is_blocked' => true,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Tab Policy Violation',
            'status' => 'danger',
        ]);

        $this->mock(LocalJudgeRunner::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('judge');
        });

        // Blocked on Judge pages & submission
        $this->get(route('judge.index'))
            ->assertForbidden()
            ->assertSee('Access Blocked due to Browser Tab Policy Violation');

        $this->get(route('judge.show', $problem->id))
            ->assertForbidden()
            ->assertSee('Access Blocked due to Browser Tab Policy Violation')
            ->assertDontSee('Secret statement.');

        $this->get(route('judge.problem.pdf', $problem->id))
            ->assertForbidden()
            ->assertSee('Access Blocked due to Browser Tab Policy Violation');

        $this->post(route('judge.submit'), [
            'judge_problem_id' => $problem->id,
            'language' => 'cpp',
            'code' => 'int main() { return 0; }',
        ])->assertForbidden();

        // Blocked on Lab Tasks, Downloads, and File Submissions
        $this->get(route('labs.index'))
            ->assertForbidden()
            ->assertSee('Access Blocked due to Browser Tab Policy Violation');

        $this->get(route('files.index'))->assertForbidden();

        $this->get(route('labs.download', $labTask->id))
            ->assertForbidden()
            ->assertSee('Access Blocked due to Browser Tab Policy Violation');

        $this->get(route('labs.submit'))
            ->assertForbidden()
            ->assertSee('Access Blocked due to Browser Tab Policy Violation');

        $this->get(route('submissions.create'))->assertForbidden();

        $this->post(route('submissions.store'), [
            'lab_task_id' => $labTask->id,
            'student_name' => $student->name,
            'student_id' => '2207114',
            'submission_file' => UploadedFile::fake()->create('solution.pkt', 10),
        ])->assertForbidden();

        $this->assertDatabaseCount('judge_submissions', 0);
        $this->assertDatabaseCount('submissions', 0);

        // Dashboard remains accessible with warning banner so student can see status or sign out
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Browser Tab Policy Violation');

        // Another unblocked student is completely unaffected
        $htmlProblem = JudgeProblem::create([
            'title' => 'HTML Problem',
            'description' => 'Unblocked HTML statement.',
        ]);

        $this->actingAs($otherStudent)
            ->get(route('judge.show', $htmlProblem->id))
            ->assertOk()
            ->assertSee('Unblocked HTML statement.');

        $this->actingAs($otherStudent)
            ->get(route('judge.problem.pdf', $problem->id))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($otherStudent)
            ->get(route('labs.index'))
            ->assertOk();
    }

    public function test_admin_can_view_and_unblock_student_from_tab_violation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $violation = TabViolation::create([
            'user_id' => $student->id,
            'violation_type' => 'duplicate_tab',
            'page_url' => '/judge',
            'details' => 'Multiple OfflineBridge tabs detected.',
            'is_blocked' => true,
            'ip_address' => '127.0.0.1',
        ]);

        $this->actingAs($admin)
            ->get(route('activities.index'))
            ->assertOk()
            ->assertSee('Browser Tab Policy Violation Security Log')
            ->assertSee($student->name)
            ->assertSee('Duplicate Tab')
            ->assertSee('Unblock Student');

        $this->actingAs($admin)
            ->get(route('admin.copy-paste-control'))
            ->assertOk()
            ->assertSee('Browser Tab Policy Violations')
            ->assertSee('1 blocked student')
            ->assertSee('Unblock Student');

        $this->actingAs($admin)
            ->from(route('activities.index'))
            ->patch(route('admin.tab-violations.unblock', $violation))
            ->assertRedirect(route('activities.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tab_violations', [
            'id' => $violation->id,
            'is_blocked' => false,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Tab Policy Unblocked',
            'status' => 'success',
        ]);

        $this->actingAs($student)
            ->get(route('labs.index'))
            ->assertOk();

        $this->actingAs($student)
            ->get(route('judge.index'))
            ->assertOk();
    }
}
