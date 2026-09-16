<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordAndAdminTest extends TestCase
{
    use RefreshDatabase;

    private function userPayload(User $user, array $changes = []): array
    {
        return ['fullName' => $user->full_name, 'login' => $user->login, 'role' => $user->role->value, 'isActive' => $user->is_active, ...$changes];
    }

    public function test_default_password_requires_change_before_any_business_api(): void
    {
        $admin = $this->person('admin', UserRole::ADMIN);
        $response = $this->actingAs($admin)->postJson('/api/users', ['fullName' => 'Новый менеджер', 'login' => 'new', 'role' => 'MANAGER', 'isActive' => true])
            ->assertCreated()->assertJsonPath('data.mustChangePassword', true)->assertJsonMissingPath('data.password')->assertJsonMissingPath('data.auth_version');
        $manager = User::findOrFail($response->json('data.id'));
        $this->assertTrue(Hash::check(UserService::DEFAULT_PASSWORD, $manager->password));
        $this->postJson('/api/logout')->assertNoContent();
        $this->postJson('/api/login', ['login' => 'new', 'password' => UserService::DEFAULT_PASSWORD])->assertOk()->assertJsonPath('data.mustChangePassword', true);
        $this->getJson('/api/me')->assertOk();
        foreach (['candidates', 'users', 'users/managers', 'assessment-methodology', 'assessments/999'] as $path) {
            $this->getJson('/api/'.$path)->assertForbidden()->assertJsonPath('code', 'PASSWORD_CHANGE_REQUIRED');
        }
        $this->postJson('/api/assessment-preview', $this->scores())->assertForbidden();
        $this->postJson('/api/candidates', [])->assertForbidden();
        $this->putJson('/api/me/password', ['password' => 'MyPersonalPass123', 'passwordConfirmation' => 'MyPersonalPass123'])
            ->assertOk()->assertJsonPath('data.mustChangePassword', false)->assertJsonMissingPath('data.password');
        $this->getJson('/api/candidates')->assertOk();
        $this->assertTrue(Hash::check('MyPersonalPass123', $manager->fresh()->password));
        $this->postJson('/api/logout')->assertNoContent();
        $this->postJson('/api/login', ['login' => 'new', 'password' => UserService::DEFAULT_PASSWORD])->assertUnprocessable();
        $this->postJson('/api/login', ['login' => 'new', 'password' => 'MyPersonalPass123'])->assertOk();
    }

    public function test_personal_password_change_validates_current_confirmation_and_reuse(): void
    {
        $manager = $this->person('manager');
        $this->actingAs($manager);
        $this->putJson('/api/me/password', ['password' => 'NewPass123', 'passwordConfirmation' => 'NewPass123'])->assertUnprocessable()->assertJsonValidationErrors('currentPassword');
        $this->putJson('/api/me/password', ['currentPassword' => 'wrong', 'password' => 'NewPass123', 'passwordConfirmation' => 'NewPass123'])->assertUnprocessable()->assertJsonValidationErrors('currentPassword');
        $this->putJson('/api/me/password', ['currentPassword' => 'password123', 'password' => 'NewPass123', 'passwordConfirmation' => 'different'])->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->putJson('/api/me/password', ['currentPassword' => 'password123', 'password' => 'password123', 'passwordConfirmation' => 'password123'])->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->putJson('/api/me/password', ['currentPassword' => 'password123', 'password' => 'NewPass123', 'passwordConfirmation' => 'NewPass123'])->assertOk();
        $this->assertSame(1, $manager->fresh()->auth_version);
        $this->getJson('/api/me')->assertOk();
        $this->withSession(['auth_version' => 0])->getJson('/api/me')->assertUnauthorized();
    }

    public function test_first_login_cannot_reuse_default_or_choose_short_password(): void
    {
        $user = $this->person('manager');
        $user->update(['must_change_password' => true]);
        $this->actingAs($user);
        foreach (['short', UserService::DEFAULT_PASSWORD] as $password) {
            $this->putJson('/api/me/password', ['password' => $password, 'passwordConfirmation' => $password])->assertUnprocessable()->assertJsonValidationErrors('password');
        }
        $this->getJson('/api/candidates')->assertForbidden();
        $this->postJson('/api/logout')->assertNoContent();
    }

    public function test_password_reset_revokes_sessions_and_requires_new_personal_password(): void
    {
        $admin = $this->person('admin', UserRole::ADMIN);
        $manager = $this->person('manager');
        $this->actingAs($admin)->putJson('/api/users/'.$manager->id, $this->userPayload($manager, ['password' => 'ResetPassword123']))->assertOk()->assertJsonPath('data.mustChangePassword', true);
        $this->actingAs($manager->fresh())->withSession(['auth_version' => 0])->getJson('/api/me')->assertUnauthorized();
        $this->postJson('/api/login', ['login' => 'manager', 'password' => 'ResetPassword123'])->assertOk();
        $this->getJson('/api/candidates')->assertForbidden();
    }

    public function test_admin_access_and_completed_immutability(): void
    {
        $admin = $this->person('admin', UserRole::ADMIN);
        $recruiter = $this->person('recruiter', UserRole::RECRUITER);
        $manager = $this->person('manager');
        $candidate = $this->candidate($recruiter, $manager);
        $this->actingAs($manager);
        $draftId = $this->postJson('/api/candidates/'.$candidate->id.'/assessments')->assertCreated()->json('data.id');
        $this->actingAs($admin)->getJson('/api/candidates')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/candidates/'.$candidate->id)->assertOk();
        $this->getJson('/api/users')->assertOk();
        $this->putJson('/api/assessments/'.$draftId, $this->scores())->assertOk();
        $this->postJson('/api/assessments/'.$draftId.'/complete', $this->scores())->assertOk();
        $this->putJson('/api/assessments/'.$draftId, $this->scores(4, 4))->assertForbidden();
    }

    public function test_only_admin_can_promote_to_admin_or_modify_admin_account(): void
    {
        $admin = $this->person('admin', UserRole::ADMIN);
        $recruiter = $this->person('recruiter', UserRole::RECRUITER);
        $manager = $this->person('manager');
        $this->actingAs($recruiter)->putJson('/api/users/'.$manager->id, $this->userPayload($manager, ['role' => 'ADMIN']))->assertForbidden();
        $this->postJson('/api/users', ['fullName' => 'Admin', 'login' => 'rogue', 'role' => 'ADMIN', 'isActive' => true])->assertForbidden();
        $this->putJson('/api/users/'.$admin->id, $this->userPayload($admin, ['role' => 'MANAGER']))->assertForbidden();
        $this->deleteJson('/api/users/'.$admin->id)->assertForbidden();
        $this->actingAs($manager)->putJson('/api/users/'.$recruiter->id, $this->userPayload($recruiter))->assertForbidden();
        $this->actingAs($admin)->putJson('/api/users/'.$manager->id, $this->userPayload($manager, ['role' => 'ADMIN']))->assertOk()->assertJsonPath('data.role', 'ADMIN');
    }

    public function test_last_admin_is_protected_but_other_roles_can_change(): void
    {
        $admin = $this->person('admin', UserRole::ADMIN);
        $this->actingAs($admin)->putJson('/api/users/'.$admin->id, $this->userPayload($admin, ['role' => 'MANAGER']))->assertUnprocessable()->assertJsonValidationErrors('role');
        $this->putJson('/api/users/'.$admin->id, $this->userPayload($admin, ['isActive' => false]))->assertUnprocessable();
        $this->deleteJson('/api/users/'.$admin->id)->assertUnprocessable();
        $other = $this->person('other', UserRole::ADMIN);
        $this->putJson('/api/users/'.$other->id, $this->userPayload($other, ['role' => 'RECRUITER']))->assertOk();
    }

    public function test_role_change_preserves_candidate_relations_and_allows_editing(): void
    {
        $admin = $this->person('admin', UserRole::ADMIN);
        $recruiter = $this->person('recruiter', UserRole::RECRUITER);
        $manager = $this->person('manager');
        $candidate = $this->candidate($recruiter, $manager);
        $this->actingAs($admin)->putJson('/api/users/'.$manager->id, $this->userPayload($manager, ['role' => 'ADMIN']))->assertOk();
        $this->assertDatabaseHas('candidate_hiring_managers', ['candidate_id' => $candidate->id, 'user_id' => $manager->id]);
        $payload = ['fullName' => 'Edited', 'position' => 'Engineer', 'status' => 'ACTIVE', 'hiringManagerIds' => [$manager->id], 'recruiterIds' => [$recruiter->id]];
        $this->putJson('/api/candidates/'.$candidate->id, $payload)->assertOk();
        $this->postJson('/api/candidates', $payload)->assertUnprocessable()->assertJsonValidationErrors('hiringManagerIds.0');
    }

    public function test_password_change_is_rate_limited(): void
    {
        $this->actingAs($this->person('manager'));
        $payload = ['currentPassword' => 'incorrect', 'password' => 'Changed123', 'passwordConfirmation' => 'Changed123'];
        for ($i = 0; $i < 5; $i++) {
            $this->putJson('/api/me/password', $payload)->assertUnprocessable();
        }
        $this->putJson('/api/me/password', $payload)->assertStatus(429);
    }

    public function test_existing_users_keep_password_and_force_flag_when_only_role_changes(): void
    {
        $admin = $this->person('admin', UserRole::ADMIN);
        $manager = $this->person('manager');
        $hash = $manager->password;
        $this->actingAs($admin)->putJson('/api/users/'.$manager->id, $this->userPayload($manager, ['role' => 'RECRUITER']))
            ->assertOk()->assertJsonPath('data.mustChangePassword', false);
        $this->assertSame($hash, $manager->fresh()->password);
        $this->assertSame(0, $manager->fresh()->auth_version);
    }

    public function test_password_change_requires_authentication_and_csrf(): void
    {
        $this->putJson('/api/me/password', [])->assertUnauthorized();
        $this->actingAs($this->person('manager'));
        $this->app['env'] = 'local';
        $this->putJson('/api/me/password', ['currentPassword' => 'password123', 'password' => 'Changed123', 'passwordConfirmation' => 'Changed123'])->assertStatus(419);
    }
}
