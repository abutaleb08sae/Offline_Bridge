<?php

namespace Tests\Feature;

use App\Models\LabTask;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LabSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_view_submission_page_from_both_routes(): void
    {
        LabTask::create([
            'title' => 'Routing Lab',
            'description' => 'Complete the routing topology.',
            'file_path' => 'labs/routing.pdf',
            'file_name' => 'routing.pdf',
            'file_size' => 100,
        ]);

        $this->get('/submit')
            ->assertOk()
            ->assertSee('Routing Lab')
            ->assertSee('Submit');

        $this->get('/labs/submit')
            ->assertOk()
            ->assertSee('Routing Lab')
            ->assertSee('Submit');
    }

    public function test_submission_file_is_saved_and_linked_to_authenticated_user(): void
    {
        Storage::fake('submissions');
        $student = User::factory()->create();
        $task = LabTask::create([
            'title' => 'Routing Lab',
            'description' => null,
            'file_path' => 'labs/routing.pdf',
            'file_name' => 'routing.pdf',
            'file_size' => 100,
        ]);

        $this->actingAs($student)
            ->post(route('submissions.store'), [
                'lab_task_id' => $task->id,
                'student_name' => 'Student Example',
                'student_id' => 'ST-42',
                'submission_file' => UploadedFile::fake()->createWithContent('solution.cpp', 'int main() {}'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $submission = Submission::firstOrFail();

        $this->assertSame($student->id, $submission->user_id);
        $this->assertSame($task->id, $submission->lab_task_id);
        $this->assertSame('127.0.0.1', $submission->client_ip);
        Storage::disk('submissions')->assertExists($submission->file_path);
        $this->assertDatabaseHas('submissions', [
            'id' => $submission->id,
            'student_name' => 'Student Example',
            'student_id' => 'ST-42',
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->get(route('submissions.index'))
            ->assertOk()
            ->assertSee('Total Submissions')
            ->assertSee('Unique Students')
            ->assertSee('Submission ID')
            ->assertSee('#'.$submission->id)
            ->assertSee('Routing Lab')
            ->assertDontSee('Open Student Portal View')
            ->assertDontSee('View Student Submission Portal');

        $this->get(route('submissions.download', $submission->id))
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_submissions_sidebar_item_routes_to_the_correct_page_for_each_role(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)
            ->get(route('judge.index'))
            ->assertOk()
            ->assertSee('href="'.route('labs.submit').'"', false)
            ->assertDontSee('href="'.route('submissions.index').'"', false);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('judge.index'))
            ->assertOk()
            ->assertSee('href="'.route('submissions.index').'"', false);
    }

    public function test_student_lab_library_is_download_only_with_role_specific_empty_state(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)
            ->get(route('labs.index'))
            ->assertOk()
            ->assertSee('Master Local Files Library')
            ->assertSee('No teacher-published files are available yet.')
            ->assertDontSee('Ready to submit your lab work?')
            ->assertDontSee('Submit Lab Work')
            ->assertDontSee('Upload First Lab Task')
            ->assertDontSee('Upload New Lab Task');

        LabTask::create([
            'title' => 'Routing Lab',
            'description' => 'Admin-only instructions.',
            'file_path' => 'labs/routing.pdf',
            'file_name' => 'routing.pdf',
            'file_size' => 100,
        ]);

        $this->get(route('labs.index'))
            ->assertOk()
            ->assertSee('Routing Lab')
            ->assertSee('routing.pdf')
            ->assertSee('Uploaded At')
            ->assertSee('Download')
            ->assertDontSee('Admin-only instructions.')
            ->assertDontSee('Delete')
            ->assertDontSee('Upload First Lab Task');
    }

    public function test_admin_retains_task_upload_and_empty_state_controls(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('labs.index'))
            ->assertOk()
            ->assertSee('Upload New Lab Task')
            ->assertSee('Click to choose or drag')
            ->assertSee('No tasks uploaded yet. Uploaded assignment packages and files will appear here for management and student access.')
            ->assertDontSee('Upload First Lab Task')
            ->assertDontSee('No teacher-published files are available yet.');
    }

    public function test_admin_can_upload_pka_file_and_user_can_download_it(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $pkaFile = UploadedFile::fake()->create('activity_01.pka', 500, 'application/octet-stream');

        $response = $this->actingAs($admin)
            ->post(route('labs.store'), [
                'title' => 'Packet Tracer OSPF Activity',
                'description' => 'Configure OSPF on Routers',
                'lab_file' => $pkaFile,
            ]);

        $response->assertRedirect(route('labs.index'))
            ->assertSessionHas('success');

        $task = LabTask::firstOrFail();
        $this->assertSame('Packet Tracer OSPF Activity', $task->title);
        $this->assertSame('activity_01.pka', $task->file_name);
        Storage::disk('public')->assertExists($task->file_path);

        // User / student can download the .pka file
        $student = User::factory()->create(['role' => 'student']);
        $downloadResponse = $this->actingAs($student)
            ->get(route('labs.download', $task->id));

        $downloadResponse->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=activity_01.pka')
            ->assertHeader('content-type', 'application/octet-stream');
    }

    public function test_student_can_submit_pka_solution_file(): void
    {
        Storage::fake('submissions');
        $student = User::factory()->create();
        $task = LabTask::create([
            'title' => 'Subnetting Lab',
            'file_path' => 'labs/subnetting.pka',
            'file_name' => 'subnetting.pka',
            'file_size' => 200,
        ]);

        $pkaSubmission = UploadedFile::fake()->create('solution.pka', 300, 'application/octet-stream');

        $this->actingAs($student)
            ->post(route('submissions.store'), [
                'lab_task_id' => $task->id,
                'student_name' => 'Alice',
                'student_id' => 'ST-99',
                'submission_file' => $pkaSubmission,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $submission = Submission::firstOrFail();
        $this->assertSame('PKA', $submission->file_extension);
        Storage::disk('submissions')->assertExists($submission->file_path);
    }

    public function test_admin_can_batch_download_all_submissions_as_zip(): void
    {
        Storage::fake('submissions');
        $admin = User::factory()->create(['role' => 'admin']);
        $task = LabTask::create([
            'title' => 'Routing Assignment',
            'file_path' => 'labs/routing.pka',
            'file_name' => 'routing.pka',
            'file_size' => 200,
        ]);

        $file1 = 'submissions/test_student_1_routing.pka';
        $file2 = 'submissions/test_student_2_routing.pkt';
        Storage::disk('submissions')->put($file1, 'fake pka content');
        Storage::disk('submissions')->put($file2, 'fake pkt content');

        Submission::create([
            'lab_task_id' => $task->id,
            'student_name' => 'Student One',
            'student_id' => 'ST-001',
            'client_ip' => '192.168.1.50',
            'file_path' => $file1,
        ]);

        Submission::create([
            'lab_task_id' => $task->id,
            'student_name' => 'Student Two',
            'student_id' => 'ST-002',
            'client_ip' => '192.168.1.51',
            'file_path' => $file2,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.tasks.downloadAll', $task->id));

        $response->assertOk()
            ->assertHeader('content-type', 'application/zip');
    }

    public function test_batch_download_returns_error_when_no_submissions_exist(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $task = LabTask::create([
            'title' => 'Empty Lab Task',
            'file_path' => 'labs/empty.pka',
            'file_name' => 'empty.pka',
            'file_size' => 100,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('labs.index'))
            ->get(route('admin.tasks.downloadAll', $task->id));

        $response->assertRedirect(route('labs.index'))
            ->assertSessionHas('error', 'No submissions found for this lab task.');
    }

    public function test_non_admin_cannot_access_batch_download(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $task = LabTask::create([
            'title' => 'Secured Task',
            'file_path' => 'labs/secured.pka',
            'file_name' => 'secured.pka',
            'file_size' => 100,
        ]);

        $response = $this->actingAs($student)
            ->get(route('admin.tasks.downloadAll', $task->id));

        $response->assertForbidden();
    }
}
