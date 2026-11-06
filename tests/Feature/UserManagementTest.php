<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ActivityLog;
use App\Models\JudgeSubmission;
use App\Models\JudgeProblem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_model_has_fillable_and_datetime_casts(): void
    {
        $user = new User();
        $fillable = $user->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('role', $fillable);
        $this->assertContains('status', $fillable);
        $this->assertContains('last_login_at', $fillable);

        $created = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'secret1234',
            'role' => 'student',
            'status' => 'active',
            'last_login_at' => now(),
        ]);

        $this->assertInstanceOf(\Carbon\CarbonInterface::class, $created->last_login_at);
        $this->assertEquals('active', $created->status);
    }

    public function test_login_updates_last_login_at_and_status(): void
    {
        $user = User::create([
            'name' => 'Login Student',
            'email' => 'student_login@example.com',
            'password' => 'password123',
            'role' => 'user',
            'status' => 'inactive',
            'last_login_at' => null,
        ]);

        $this->assertNull($user->last_login_at);
        $this->assertEquals('inactive', $user->status);

        $response = $this->post('/login', [
            'email' => 'student_login@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        $user->refresh();
        $this->assertNotNull($user->last_login_at);
        $this->assertEquals('active', $user->status);
    }

    public function test_non_admin_cannot_access_user_management(): void
    {
        $student = User::create([
            'name' => 'Regular Student',
            'email' => 'student@example.com',
            'password' => 'password123',
            'role' => 'user',
        ]);

        $this->actingAs($student)
            ->get('/users')
            ->assertForbidden();
    }

    public function test_admin_can_view_users_list(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'admin123456',
            'role' => 'admin',
        ]);

        $student = User::create([
            'name' => 'Target Student',
            'email' => 'target@example.com',
            'password' => 'password123',
            'role' => 'student',
            'status' => 'active',
            'last_login_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($admin)->get('/users');
        $response->assertOk();
        $response->assertSee('Target Student');
        $response->assertSee('target@example.com');
        $response->assertSee('ACTIVE');
        $response->assertSee('1 day ago');
    }

    public function test_admin_can_create_new_user(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'admin123456',
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->post('/users', [
            'name' => 'New Student',
            'email' => 'newstudent@example.com',
            'role' => 'student',
            'status' => 'active',
            'password' => 'secretPass123',
        ]);

        $response->assertRedirect('/users');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'name' => 'New Student',
            'email' => 'newstudent@example.com',
            'role' => 'student',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_update_user_ignoring_same_email(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'admin123456',
            'role' => 'admin',
        ]);

        $student = User::create([
            'name' => 'Old Name',
            'email' => 'same_email@example.com',
            'password' => 'oldPassword123',
            'role' => 'student',
            'status' => 'active',
        ]);

        $oldHashedPassword = $student->password;

        // Update without changing password
        $response = $this->actingAs($admin)->put("/users/{$student->id}", [
            'name' => 'Updated Name',
            'email' => 'same_email@example.com', // Keeps same email without failing unique check
            'role' => 'instructor',
            'status' => 'inactive',
            'password' => '', // Empty password leaves existing password untouched
        ]);

        $response->assertRedirect('/users');
        $response->assertSessionHas('success');

        $student->refresh();
        $this->assertEquals('Updated Name', $student->name);
        $this->assertEquals('same_email@example.com', $student->email);
        $this->assertEquals('instructor', $student->role);
        $this->assertEquals('inactive', $student->status);
        $this->assertEquals($oldHashedPassword, $student->password);
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => 'admin123456',
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->delete("/users/{$admin->id}");
        $response->assertRedirect('/users');
        $response->assertSessionHas('error', 'You cannot delete your own admin account.');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
        ]);
    }

    public function test_admin_can_delete_other_user_safely_handling_relations(): void
    {
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => 'admin123456',
            'role' => 'admin',
        ]);

        $student = User::create([
            'name' => 'Doomed User',
            'email' => 'doomed@example.com',
            'password' => 'secret123',
            'role' => 'student',
        ]);

        $problem = JudgeProblem::create([
            'title' => 'Test Problem',
            'description' => 'Test desc',
        ]);

        $sub = JudgeSubmission::create([
            'user_id' => $student->id,
            'judge_problem_id' => $problem->id,
            'language' => 'cpp',
            'code' => 'int main(){}',
            'verdict' => 'Accepted',
        ]);

        $response = $this->actingAs($admin)->delete("/users/{$student->id}");
        $response->assertRedirect('/users');
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('users', [
            'id' => $student->id,
        ]);

        $sub->refresh();
        $this->assertNull($sub->user_id);
    }
}
