<?php

namespace Tests\Feature;

use App\Models\JudgeProblem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JudgeProblemMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_problem_diagrams_and_custom_pdf_and_students_can_view_pdf(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $problem = JudgeProblem::create([
            'title' => 'UVa diagram problem',
            'description' => '<p>Read the diagram and solve the task.</p>',
        ]);
        $problem->testCases()->create([
            'input' => "2 3\n",
            'expected_output' => "5\n",
        ]);

        $this->actingAs($admin)
            ->get(route('judge.admin.problems.create'))
            ->assertOk()
            ->assertSee('Auto-Generate an Original Problem from a Link (LeetCode / Codeforces / Online Judge)')
            ->assertSee('Auto-Generate a PDF Contest Problem from a Link (UVa Online Judge / PDF)')
            ->assertSee(route('judge.admin.problems.importPdfUrl'));

        $this->post(route('judge.admin.problems.media.images'), [
            'problem_id' => $problem->id,
            'images' => [UploadedFile::fake()->createWithContent(
                'diagram.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/l2cAAAAASUVORK5CYII=')
            )],
        ])->assertRedirect();

        $problem->refresh();
        $this->assertCount(1, $problem->images);
        Storage::disk('public')->assertExists($problem->images[0]);

        $this->post(route('judge.admin.problems.media.pdf.upload'), [
            'problem_id' => $problem->id,
            'pdf' => UploadedFile::fake()->createWithContent('statement.pdf', "%PDF-1.4\n%%EOF"),
        ])->assertRedirect();

        $problem->refresh();
        $this->assertNotNull($problem->pdf_path);
        Storage::disk('public')->assertExists($problem->pdf_path);

        auth()->logout();
        $this->get(route('judge.problem.pdf', $problem->id))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->get(route('judge.show', $problem->id))
            ->assertOk()
            ->assertDontSee('UVa PDF View')
            ->assertDontSee('HTML View')
            ->assertDontSee('data-problem-html')
            ->assertSee(asset('storage/'.$problem->pdf_path), false);

        $pdfPath = $problem->pdf_path;
        $this->actingAs($admin)
            ->delete(route('judge.admin.problems.media.pdf.remove', $problem->id))
            ->assertRedirect();
        Storage::disk('public')->assertMissing($pdfPath);
        $this->assertNull($problem->fresh()->pdf_path);
    }

    public function test_generated_pdf_contains_problem_content_and_delete_cleans_all_media_files(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $problem = JudgeProblem::create([
            'title' => 'Generated UVa PDF',
            'description' => '<p>Generated statement content.</p>',
            'images' => [],
        ]);
        $problem->testCases()->create([
            'input' => 'sample stdin',
            'expected_output' => 'sample stdout',
        ]);
        $imagePath = 'problem-images/'.$problem->id.'/diagram.txt';
        Storage::disk('public')->put($imagePath, 'diagram placeholder for cleanup test');
        $problem->update(['images' => [$imagePath]]);

        $this->actingAs($admin)
            ->post(route('judge.admin.problems.media.pdf.generate'), ['problem_id' => $problem->id])
            ->assertRedirect()
            ->assertSessionHas('success', 'UVa-style problem PDF generated successfully.');

        $problem->refresh();
        Storage::disk('public')->assertExists($problem->pdf_path);
        $this->assertStringStartsWith('%PDF-', Storage::disk('public')->get($problem->pdf_path));

        $pdfPath = $problem->pdf_path;
        $this->delete(route('judge.admin.problems.destroy', $problem->id))
            ->assertRedirect(route('judge.index'));

        Storage::disk('public')->assertMissing($pdfPath);
        Storage::disk('public')->assertMissing($imagePath);
    }
}
