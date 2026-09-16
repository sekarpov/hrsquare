<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AssessmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_completion_backend_calculation_and_immutability(): void
    {
        $r = $this->person('r', UserRole::RECRUITER);
        $a = $this->person('a');
        $c = $this->candidate($r, $a);
        $draft = $this->actingAs($a)->postJson('/api/candidates/'.$c->id.'/assessments', ['taskScaleScore' => 0])->assertCreated()->assertJsonPath('data.status', 'DRAFT')->assertJsonPath('data.resultAverage', null);
        $id = $draft->json('data.id');
        $this->postJson('/api/assessments/'.$id.'/complete', [])->assertUnprocessable()->assertJsonValidationErrors('resultImpactScore');
        $payload = [...$this->scores(2, 2), 'taskScaleScore' => 1, 'mainRisk' => 'LEARNING', 'calibrationSignal' => 'NEEDS_CALIBRATION', 'finalComment' => 'Confirmed facts'];
        $this->putJson('/api/assessments/'.$id, $payload)->assertOk()->assertJsonPath('data.resultAverage', 1.67)->assertJsonPath('data.potentialAverage', 2)->assertJsonPath('data.nineBoxCell', 'S2');
        $this->postJson('/api/assessments/'.$id.'/complete', [])->assertOk()->assertJsonPath('data.status', 'COMPLETED')->assertJsonPath('data.nineBoxCell', 'S2');
        $this->putJson('/api/assessments/'.$id, $this->scores(3, 3))->assertForbidden();
        $this->postJson('/api/assessments/'.$id.'/complete', [])->assertForbidden();
        $this->assertDatabaseHas('candidate_assessments', ['id' => $id, 'final_comment' => 'Confirmed facts', 'status' => 'COMPLETED']);
    }

    public function test_latest_completed_wins_and_draft_does_not_replace_it(): void
    {
        $r = $this->person('r', UserRole::RECRUITER);
        $a = $this->person('a');
        $b = $this->person('b');
        $c = $this->candidate($r, $a);
        $c->hiringManagers()->attach($b);
        $this->travelTo(now()->subDays(3));
        $first = $this->completed($c, $a, 3, 2);
        $this->travel(1)->days();
        $second = $this->completed($c, $b, 2, 2);
        $this->travel(1)->days();
        $third = $this->completed($c, $a, 3, 3);
        $this->actingAs($b)->postJson('/api/candidates/'.$c->id.'/assessments', $this->scores(0, 0))->assertCreated();
        $this->getJson('/api/candidates/'.$c->id)->assertJsonPath('data.currentAssessment.id', $third->id)->assertJsonPath('data.currentAssessment.nineBoxCell', 'B1');
        $this->getJson('/api/candidates/'.$c->id.'/assessments')->assertJsonCount(4, 'data');
        $this->assertDatabaseHas('candidate_assessments', ['id' => $first->id, 'nine_box_cell' => 'B2']);
        $this->assertDatabaseHas('candidate_assessments', ['id' => $second->id, 'nine_box_cell' => 'S2']);
    }

    public function test_draft_can_only_be_edited_by_its_author_and_scores_are_validated(): void
    {
        $r = $this->person('r', UserRole::RECRUITER);
        $a = $this->person('a');
        $b = $this->person('b');
        $c = $this->candidate($r, $a);
        $c->hiringManagers()->attach($b);
        $id = $this->actingAs($a)->postJson('/api/candidates/'.$c->id.'/assessments', [])->json('data.id');
        $this->actingAs($b)->putJson('/api/assessments/'.$id, $this->scores())->assertForbidden();
        $this->actingAs($a)->putJson('/api/assessments/'.$id, ['taskScaleScore' => 4])->assertUnprocessable();
        $this->putJson('/api/assessments/'.$id, ['taskScaleScore' => -1])->assertUnprocessable();
        $this->putJson('/api/assessments/'.$id, ['status' => 'COMPLETED'])->assertUnprocessable();
    }

    public function test_model_blocks_changes_to_completed_assessment(): void
    {
        $r = $this->person('r', UserRole::RECRUITER);
        $a = $this->person('a');
        $c = $this->candidate($r, $a);
        $assessment = $this->completed($c,$a,3,3);
        $this->expectException(ValidationException::class);
        $assessment->update(['final_comment' => 'Overwrite']);
    }
}
