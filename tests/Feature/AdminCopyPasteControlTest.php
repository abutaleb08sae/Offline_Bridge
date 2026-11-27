<?php

namespace Tests\Feature;

use App\Models\CopyPastePermission;
use App\Models\JudgeProblem;
use App\Models\ProblemBlock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCopyPasteControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_control_center_lists_student_permissions_and_violation_status(): void
    {
        $admin = User::factory()->create([
            'name' => 'Hidden Admin Account',
            'role' => 'admin',
        ]);
        $student = User::factory()->create([
            'name' => 'Control Center Student',
        ]);
        $instructor = User::factory()->create([
            'name' => 'Control Center Instructor',
            'role' => 'instructor',
        ]);
        $problem = JudgeProblem::create([
            'title' => 'Permission Matrix Problem',
            'description' => 'Statement.',
        ]);
        ProblemBlock::create([
            'user_id' => $student->id,
            'problem_id' => $problem->id,
            'violation_type' => 'cut',
            'ip_address' => '127.0.0.1',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.copy-paste-control'))
            ->assertOk()
            ->assertSee('Blocked Students &amp; Violation Logs', false)
            ->assertSee('1 active block')
            ->assertSee('Control Center Student')
            ->assertSee((string) $student->id)
            ->assertSee('Control Center Instructor')
            ->assertSee('INSTRUCTOR')
            ->assertViewHas('students', fn ($students) => $students->pluck('id')->all() === [
                $instructor->id,
                $student->id,
            ])
            ->assertSee('Permission Matrix Problem')
            ->assertSee('Cut')
            ->assertSee('Blocked')
            ->assertSee('Student Copy-Paste Permissions Matrix')
            ->assertSee('Global Permission')
            ->assertSee('Problem-Specific Permissions')
            ->assertSee('Unblock &amp; Allow Copy-Paste', false);

        $this->actingAs($student)
            ->get(route('admin.copy-paste-control'))
            ->assertForbidden();
    }

    public function test_admin_can_toggle_problem_specific_permission_for_one_student(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'user']);
        $problem = JudgeProblem::create([
            'title' => 'Allowed Exception',
            'description' => 'This student can use clipboard actions here.',
        ]);
        $otherProblem = JudgeProblem::create([
            'title' => 'Restricted Problem',
            'description' => 'This problem remains restricted.',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.copy-paste-control'))
            ->post(route('admin.copy-paste-control.permissions.toggle', [$student, $problem]))
            ->assertRedirect(route('admin.copy-paste-control'));

        $this->assertDatabaseHas('copy_paste_permissions', [
            'user_id' => $student->id,
            'problem_id' => $problem->id,
            'is_allowed' => true,
        ]);

        $this->actingAs($student)
            ->get(route('judge.show', $problem->id))
            ->assertOk()
            ->assertDontSee('Copy-paste is disabled for students. Please write your code manually.');

        $this->get(route('judge.show', $otherProblem->id))
            ->assertOk()
            ->assertSee('Copy-paste is disabled for students. Please write your code manually.');

        $this->actingAs($admin)
            ->postJson(route('admin.copy-paste-control.permissions.toggle', [$student, $problem]))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'is_allowed' => false,
            ]);

        $this->assertDatabaseHas('copy_paste_permissions', [
            'user_id' => $student->id,
            'problem_id' => $problem->id,
            'is_allowed' => false,
        ]);
    }

    public function test_specific_permission_allows_clipboard_and_prevents_false_violation_reports(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $problem = JudgeProblem::create([
            'title' => 'Specific Permission',
            'description' => 'Permitted problem.',
        ]);
        CopyPastePermission::create([
            'user_id' => $student->id,
            'problem_id' => $problem->id,
            'is_allowed' => true,
        ]);

        $this->actingAs($student)
            ->postJson(route('judge.problems.violation'), [
                'problem_id' => $problem->id,
                'violation_type' => 'paste',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('problem_blocks', 0);
    }

    public function test_unblock_and_allow_restores_access_and_grants_problem_permission(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $problem = JudgeProblem::create([
            'title' => 'Unblock and Allow Target',
            'description' => 'Now accessible.',
        ]);
        $firstBlock = ProblemBlock::create([
            'user_id' => $student->id,
            'problem_id' => $problem->id,
            'violation_type' => 'paste',
            'ip_address' => '127.0.0.1',
        ]);
        $secondBlock = ProblemBlock::create([
            'user_id' => $student->id,
            'problem_id' => $problem->id,
            'violation_type' => 'cut',
            'ip_address' => '127.0.0.1',
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.copy-paste-control.unblock-and-allow', $firstBlock))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'is_blocked' => false,
                'is_allowed' => true,
                'unblocked_records' => 2,
            ]);

        $this->assertDatabaseHas('copy_paste_permissions', [
            'user_id' => $student->id,
            'problem_id' => $problem->id,
            'is_allowed' => true,
        ]);
        $this->assertDatabaseHas('problem_blocks', [
            'id' => $firstBlock->id,
            'is_blocked' => false,
        ]);
        $this->assertDatabaseHas('problem_blocks', [
            'id' => $secondBlock->id,
            'is_blocked' => false,
        ]);

        $this->actingAs($student)
            ->get(route('judge.show', $problem->id))
            ->assertOk()
            ->assertSee('Now accessible.');
    }

    public function test_admin_header_button_displays_the_active_block_count(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $problem = JudgeProblem::create([
            'title' => 'Header Count Problem',
            'description' => 'Statement.',
        ]);
        ProblemBlock::create([
            'user_id' => $student->id,
            'problem_id' => $problem->id,
            'violation_type' => 'copy',
            'ip_address' => '127.0.0.1',
        ]);

        $this->actingAs($admin)
            ->get(route('judge.index'))
            ->assertOk()
            ->assertSee('Copy-Paste &amp; Block Control', false)
            ->assertSee(route('admin.copy-paste-control'))
            ->assertSee('1 active block');

        $this->get(route('judge.admin.submissions'))
            ->assertOk()
            ->assertSee('Copy-Paste &amp; Block Control', false)
            ->assertSee('1 active block');
    }

    public function test_admin_can_unblock_from_control_center_with_a_standard_form_post(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $problem = JudgeProblem::create([
            'title' => 'Standard Unblock Problem',
            'description' => 'Statement.',
        ]);
        $block = ProblemBlock::create([
            'user_id' => $student->id,
            'problem_id' => $problem->id,
            'violation_type' => 'copy',
            'ip_address' => '127.0.0.1',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.copy-paste-control'))
            ->post(route('admin.copy-paste-control.unblock', $block))
            ->assertRedirect(route('admin.copy-paste-control'))
            ->assertSessionHas('success', 'Problem access restored for this student.');

        $this->assertDatabaseHas('problem_blocks', [
            'id' => $block->id,
            'is_blocked' => false,
        ]);
    }
}
