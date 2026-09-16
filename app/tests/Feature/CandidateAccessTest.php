<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_sees_only_assigned_candidates_and_cannot_read_foreign_candidate(): void
    {
        $r = $this->person('r', UserRole::RECRUITER);
        $a = $this->person('a');
        $b = $this->person('b');
        $own = $this->candidate($r, $a);
        $other = $this->candidate($r, $b);
        $this->actingAs($a)->getJson('/api/candidates')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $own->id);
        $this->getJson('/api/candidates/'.$other->id)->assertForbidden();
        $this->getJson('/api/candidates/'.$other->id.'/assessments')->assertForbidden();
        $assessment = $this->completed($other, $b, 4, 4);
        $this->getJson('/api/assessments/'.$assessment->id)->assertForbidden();
        $this->postJson('/api/candidates/'.$other->id.'/assessments', [])->assertForbidden();
        $this->actingAs($r)->getJson('/api/candidates')->assertJsonCount(2, 'data');
    }

    public function test_manager_cannot_manage_users(): void
    {
        $a = $this->person('a');
        $this->actingAs($a)->getJson('/api/users')->assertForbidden();
        $this->postJson('/api/users', [])->assertForbidden();
    }


    public function test_manager_creates_candidate_with_automatic_self_assignment_and_retains_access(): void
    {
        $a = $this->person('a');
        $b = $this->person('b');
        $payload = ['fullName' => 'Manager candidate', 'position' => 'Designer', 'status' => 'ACTIVE', 'hiringManagerIds' => [$b->id], 'recruiterIds' => []];
        $id = $this->actingAs($a)->postJson('/api/candidates', $payload)->assertCreated()->assertJsonCount(2, 'data.hiringManagers')->json('data.id');
        $this->assertDatabaseHas('candidates', ['id' => $id, 'created_by' => $a->id]);
        $this->assertDatabaseHas('candidate_hiring_managers', ['candidate_id' => $id, 'user_id' => $a->id]);
        $this->getJson('/api/candidates/'.$id)->assertOk();
        $this->getJson('/api/candidates')->assertJsonPath('data.0.id', $id);
        $this->postJson('/api/candidates/'.$id.'/assessments', [])->assertCreated();
        $this->putJson('/api/candidates/'.$id, $payload)->assertForbidden();
        $this->deleteJson('/api/candidates/'.$id)->assertForbidden();
        $this->postJson('/api/candidates', [...$payload, 'hiringManagerIds' => []])->assertCreated()->assertJsonCount(1, 'data.hiringManagers')->assertJsonPath('data.hiringManagers.0.id', $a->id);
    }

    public function test_unauthenticated_api_is_json_401(): void
    {
        $this->get('/api/candidates')->assertUnauthorized();
    }
}
