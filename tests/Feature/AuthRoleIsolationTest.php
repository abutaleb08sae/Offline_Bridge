<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthRoleIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_all_admin_panel_pages_and_receives_no_store_cache_headers(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'password' => 'admin-secret-123',
        ]);

        $this->actingAs($admin);

        foreach ([
            '/users',
            '/clients',
            '/applications',
            '/activities',
            '/logs',
            '/settings',
            '/backup',
            '/admin/submissions',
            '/admin/copy-paste-control',
            '/judge/problems/create',
            '/judge/submissions',
        ] as $path) {
            $response = $this->get($path);
            $response->assertOk();
            $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        }
    }

    public function test_case_insensitive_admin_role_is_accepted_by_admin_middleware(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);

        $this->actingAs($admin)
            ->get('/users')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/logs')
            ->assertOk();
    }

    public function test_logging_in_as_student_while_admin_is_signed_in_does_not_keep_admin_session(): void
    {
        $admin = User::factory()->create([
            'name' => 'Master Admin',
            'email' => 'master@example.local',
            'password' => 'master-pass-123',
            'role' => 'admin',
        ]);
        $student = User::factory()->create([
            'name' => 'Gopal Bhar',
            'email' => 'gopal@example.local',
            'password' => 'student-pass-123',
            'role' => 'student',
        ]);

        // Log in as Admin first
        $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'master-pass-123',
        ])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($admin);

        // Log in as Student without hitting logout first (e.g., from another open login tab)
        $this->post(route('login.store'), [
            'email' => $student->email,
            'password' => 'student-pass-123',
        ])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($student);

        // Must see Student Workspace, NOT Master Admin Dashboard
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Student Lab Workspace')
            ->assertDontSee('Master Dashboard Overview');

        // Clicking admin page shows friendly 403 page with HTTP 403
        $this->get('/users')
            ->assertForbidden()
            ->assertSee('Administrator Access Required')
            ->assertSee('Gopal Bhar');

        // Switching back to Admin credentials cleanly enters Admin panel
        $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'master-pass-123',
        ])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($admin);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Master Dashboard Overview');
    }

    public function test_login_page_displays_active_session_banner_when_already_signed_in(): void
    {
        $admin = User::factory()->create([
            'name' => 'Administrator',
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->get(route('login'))
            ->assertOk()
            ->assertSee('Active Session:')
            ->assertSee('Administrator');
    }
}
