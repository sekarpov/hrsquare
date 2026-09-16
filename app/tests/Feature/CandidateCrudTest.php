<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_update_relationships_and_delete(): void
    {
        $r = $this->person('r', UserRole::RECRUITER);
        $r2 = $this->person('r2', UserRole::RECRUITER);
        $a = $this->person('a');
        $b = $this->person('b');
        $payload = ['fullName' => 'Jane Doe', 'position' => 'Designer', 'city' => 'Алматы', 'company' => 'Square', 'division' => 'Product', 'project' => 'MVP', 'status' => 'ACTIVE', 'hiringManagerIds' => [$a->id, $b->id], 'recruiterIds' => [$r->id, $r2->id]];
        $response = $this->actingAs($r)->postJson('/api/candidates', $payload)->assertCreated()->assertJsonCount(2, 'data.hiringManagers')->assertJsonCount(2, 'data.recruiters');
        $id = $response->json('data.id');
        $this->putJson('/api/candidates/'.$id, [...$payload, 'fullName' => 'Jane Smith', 'status' => 'HIRED', 'hiringManagerIds' => [$b->id]])->assertOk()->assertJsonPath('data.status', 'HIRED')->assertJsonCount(1, 'data.hiringManagers');
        $this->deleteJson('/api/candidates/'.$id)->assertNoContent();
        $this->assertDatabaseMissing('candidates', ['id' => $id]);
    }

    public function test_manager_role_validation_and_required_fields(): void
    {
        $r = $this->person('r', UserRole::RECRUITER);
        $a = $this->person('a');
        $this->actingAs($r)->postJson('/api/candidates', ['status' => 'ACTIVE', 'hiringManagerIds' => [$r->id], 'recruiterIds' => [$a->id]])->assertUnprocessable()->assertJsonValidationErrors(['fullName', 'position', 'hiringManagerIds.0', 'recruiterIds.0'])->assertJsonPath('message', 'Validation failed');
        $this->postJson('/api/candidates', ['fullName' => 'X', 'position' => 'Y', 'status' => 'ACTIVE', 'hiringManagerIds' => [], 'recruiterIds' => []])->assertUnprocessable()->assertJsonValidationErrors('hiringManagerIds');
    }

    public function test_candidate_history_is_not_destroyed_by_delete(): void
    {
        $r = $this->person('r', UserRole::RECRUITER);
        $a = $this->person('a');
        $c = $this->candidate($r, $a);
        $this->completed($c, $a, 3, 3);
        $this->actingAs($r)->deleteJson('/api/candidates/'.$c->id)->assertUnprocessable();
        $this->assertDatabaseHas('candidates',['id' => $c->id]);
    }
}
