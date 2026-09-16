<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_me_and_logout(): void
    {
        $u = $this->person('recruiter', UserRole::RECRUITER);
        $this->postJson('/api/login', ['login' => 'recruiter', 'password' => 'password123'])->assertOk()->assertJsonPath('data.role', 'RECRUITER')->assertJsonMissingPath('data.password');
        $this->getJson('/api/me')->assertOk()->assertJsonPath('data.id', $u->id);
        $this->postJson('/api/logout')->assertNoContent();
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $u = $this->person('inactive');
        $u->update(['is_active' => false]);
        $this->postJson('/api/login', ['login' => 'inactive', 'password' => 'password123'])->assertUnprocessable()->assertJsonValidationErrors('login');
    }

    public function test_existing_session_is_revoked_for_inactive_user(): void
    {
        $u = $this->person('inactive');
        $this->actingAs($u);
        $u->update(['is_active' => false]);
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', ['login' => 'missing', 'password' => 'wrong'])->assertUnprocessable();
        }$this->postJson('/api/login', ['login' => 'missing', 'password' => 'wrong'])->assertStatus(429);
    }

    public function test_csrf_is_required_for_login(): void
    {
        $this->app['env'] = 'local';
        $this->postJson('/api/login', ['login' => 'x', 'password' => 'x'])->assertStatus(419);
    }
}
