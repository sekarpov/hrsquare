<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AssessmentScaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_criterion_accepts_only_one_to_four_and_nullable_drafts(): void
    {
        $r = $this->person('r', UserRole::RECRUITER);
        $a = $this->person('a');
        $c = $this->candidate($r, $a);
        $id = $this->actingAs($a)->postJson('/api/candidates/'.$c->id.'/assessments', [])->assertCreated()->json('data.id');
        foreach ([0, 5, -1, 1.5] as $invalid) {
            foreach (array_keys(config('assessment.criteria')) as $key) {
                $this->putJson('/api/assessments/'.$id, [$key.'Score' => $invalid])->assertUnprocessable()->assertJsonValidationErrors($key.'Score');
                $this->postJson('/api/assessments/'.$id.'/complete', [...$this->scores(3, 3), $key.'Score' => $invalid])->assertUnprocessable()->assertJsonValidationErrors($key.'Score');
            }
        }
        foreach ([1, 2, 3, 4] as $score) {
            $this->putJson('/api/assessments/'.$id, $this->scores($score, $score))->assertOk()->assertJsonPath('data.resultAverage', $score);
        }
        $nulls = array_fill_keys(array_keys($this->scores()), null);
        $this->putJson('/api/assessments/'.$id, $nulls)->assertOk()->assertJsonPath('data.resultAverage', null);
        $this->postJson('/api/assessments/'.$id.'/complete', $nulls)->assertUnprocessable()->assertJsonValidationErrors(array_keys($nulls));
        $this->postJson('/api/assessments/'.$id.'/complete', [...$this->scores(3, 3), 'resultImpactScore' => 2])->assertOk()->assertJsonPath('data.resultAverage', 2.67)->assertJsonPath('data.resultLevel', 'MEDIUM');
    }

    public function test_demo_seeder_uses_new_scale_and_is_repeatable(): void
    {
        $this->seed(DemoSeeder::class);
        $count = DB::table('candidate_assessments')->count();
        $this->assertGreaterThanOrEqual(10, DB::table('candidates')->count());
        foreach (array_keys(config('assessment.criteria')) as $key) {
            $field = Str::snake($key).'_score';
            $this->assertGreaterThanOrEqual(1, DB::table('candidate_assessments')->min($field));
            $this->assertLessThanOrEqual(4, DB::table('candidate_assessments')->max($field));
        }
        $this->seed(DemoSeeder::class);
        $this->assertSame($count, DB::table('candidate_assessments')->count());
    }

    public function test_level_uses_unrounded_average_even_when_display_rounding_crosses_threshold(): void
    {
        config(['assessment.high_threshold' => 3.332]);
        $r = $this->person('r', UserRole::RECRUITER);
        $a = $this->person('a');
        $c = $this->candidate($r, $a);
        $this->actingAs($a)->postJson('/api/candidates/'.$c->id.'/assessments', [...$this->scores(3, 3), 'taskScaleScore' => 4])->assertCreated()->assertJsonPath('data.resultAverage', 3.33)->assertJsonPath('data.resultLevel', 'HIGH');
    }

    public function test_legacy_data_migration_preserves_classification_nulls_and_audit_trail(): void
    {
        $r = $this->person('r', UserRole::RECRUITER);
        $a = $this->person('a');
        $c = $this->candidate($r, $a);
        $assessment = $this->completed($c, $a, 4, 3);
        $draft = $this->actingAs($a)->postJson('/api/candidates/'.$c->id.'/assessments', ['taskScaleScore' => 1])->json('data.id');
        $examples = [];
        foreach ([1, 3, 4] as $result) {
            foreach ([1, 3, 4] as $potential) {
                $item = $this->completed($c, $a, $result, $potential);
                $examples[$item->id] = [$result, $potential, $item->nine_box_cell->value];
            }
        }
        $before = DB::table('candidate_assessments')->where('id', $assessment->id)->first();
        $migration = require database_path('migrations/2026_09_16_000002_shift_assessment_scale.php');
        $migration->down();
        $this->assertDatabaseHas('candidate_assessments', ['id' => $assessment->id, 'task_scale_score' => 3, 'learning_agility_score' => 2, 'nine_box_cell' => 'B2']);
        $this->assertDatabaseHas('candidate_assessments', ['id' => $draft, 'task_scale_score' => 0, 'result_impact_score' => null]);
        $migration->up();
        foreach ($examples as $id => [$result, $potential, $cell]) {
            $this->assertDatabaseHas('candidate_assessments', ['id' => $id, 'task_scale_score' => $result, 'learning_agility_score' => $potential, 'nine_box_cell' => $cell]);
        }
        $after = DB::table('candidate_assessments')->where('id', $assessment->id)->first();
        foreach (['status', 'candidate_id', 'evaluator_id', 'created_at', 'updated_at', 'completed_at', 'nine_box_cell', 'result_level', 'potential_level'] as $field) {
            $this->assertSame($before->$field, $after->$field);
        }
        $this->assertEquals(4, $after->result_average);
        $this->assertEquals(3, $after->potential_average);
        $this->assertDatabaseHas('candidate_assessments', ['id' => $draft, 'task_scale_score' => 1, 'result_impact_score' => null, 'result_average' => null]);
        $this->putJson('/api/assessments/'.$assessment->id, ['taskScaleScore' => 2])->assertForbidden();
    }
}
