<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_recruiter_manages_users_with_hashed_passwords_and_role_search(): void
    {
        $r = $this->person('r', UserRole::RECRUITER);
        $payload = ['fullName' => 'New Manager', 'login' => 'newmanager', 'password' => 'password123', 'role' => 'MANAGER', 'isActive' => true];
        $response = $this->actingAs($r)->postJson('/api/users', $payload)->assertCreated()->assertJsonMissingPath('data.password');
        $id = $response->json('data.id');
        $this->assertTrue(Hash::check('password123', User::find($id)->password));
        $this->putJson('/api/users/'.$id, [...$payload, 'fullName' => 'Updated', 'password' => ''])->assertOk()->assertJsonPath('data.fullName', 'Updated');
        $this->getJson('/api/users/managers?search=Updated')->assertJsonCount(1, 'data');
        $this->getJson('/api/users/recruiters')->assertJsonCount(1, 'data');
        $this->deleteJson('/api/users/'.$id)->assertNoContent();
    }

    public function test_last_recruiter_cannot_be_disabled_or_deleted(): void
    {
        $r = $this->person('r', UserRole::RECRUITER);
        $payload = ['fullName' => 'r', 'login' => 'r', 'role' => 'RECRUITER', 'isActive' => false];
        $this->actingAs($r)->putJson('/api/users/'.$r->id, $payload)->assertUnprocessable()->assertJsonValidationErrors('isActive');
        $this->deleteJson('/api/users/'.$r->id)->assertUnprocessable();
    }
}
