<?php

use App\Domain\NineBoxCalculator;
use App\Enums\AssessmentLevel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Frozen migration semantics: never depend on future configurable thresholds.
    private const FIELDS = ['task_scale', 'result_impact', 'personal_contribution', 'learning_agility', 'adaptability', 'initiative'];

    public function up(): void
    {
        $this->shift(1, 0, 3, 2.5, 3.5);
        foreach (self::FIELDS as $field) {
            DB::statement("ALTER TABLE candidate_assessments ADD CONSTRAINT {$field}_score_range CHECK ({$field}_score IS NULL OR {$field}_score BETWEEN 1 AND 4)");
        }
    }

    public function down(): void
    {
        foreach (self::FIELDS as $field) {
            DB::statement("ALTER TABLE candidate_assessments DROP CONSTRAINT {$field}_score_range");
        }
        $this->shift(-1, 1, 4, 1.5, 2.5);
    }

    private function shift(int $delta, int $minimum, int $maximum, float $medium, float $high): void
    {
        // PostgreSQL runs this migration in one transaction. Block concurrent writes.
        DB::statement('LOCK TABLE candidate_assessments IN ACCESS EXCLUSIVE MODE');
        DB::table('candidate_assessments')->orderBy('id')->chunkById(500, function ($rows) use ($delta, $minimum, $maximum, $medium, $high) {
            foreach ($rows as $row) {
                $data = [];
                foreach (self::FIELDS as $field) {
                    $value = $row->{$field.'_score'};
                    if ($value !== null && ($value < $minimum || $value > $maximum)) {
                        throw new RuntimeException('Unexpected legacy score in assessment '.$row->id.'. Migration aborted without data loss.');
                    }
                    $data[$field.'_score'] = $value === null ? null : $value + $delta;
                }
                foreach (['result' => array_slice(self::FIELDS, 0, 3), 'potential' => array_slice(self::FIELDS, 3)] as $dimension => $fields) {
                    $scores = array_map(fn ($field) => $data[$field.'_score'], $fields);
                    $average = in_array(null, $scores, true) ? null : array_sum($scores) / 3;
                    $data[$dimension.'_average'] = $average;
                    $data[$dimension.'_level'] = $average === null ? null : ($average < $medium ? 'LOW' : ($average < $high ? 'MEDIUM' : 'HIGH'));
                }
                $data['nine_box_cell'] = $data['result_level'] === null || $data['potential_level'] === null ? null : app(NineBoxCalculator::class)->calculate(AssessmentLevel::from($data['result_level']), AssessmentLevel::from($data['potential_level']))->value;
                // Explicit data migration bypasses model immutability; audit dates/content stay intact.
                DB::table('candidate_assessments')->where('id', $row->id)->update($data);
            }
        });
    }
};
