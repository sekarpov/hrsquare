<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('position');
            foreach (['city', 'company', 'division', 'project'] as $field) {
                $table->string($field)->nullable()->index();
            }
            $table->enum('status', ['ACTIVE', 'HIRED', 'REJECTED'])->default('ACTIVE')->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
        foreach (['candidate_hiring_managers', 'candidate_recruiters'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->restrictOnDelete();
                $table->primary(['candidate_id', 'user_id']);
                $table->index(['user_id', 'candidate_id']);
            });
        }
        Schema::create('candidate_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->restrictOnDelete();
            $table->foreignId('evaluator_id')->constrained('users')->restrictOnDelete();
            $table->enum('status', ['DRAFT', 'COMPLETED'])->default('DRAFT');
            foreach (['task_scale', 'result_impact', 'personal_contribution', 'learning_agility', 'adaptability', 'initiative'] as $criterion) {
                $table->smallInteger($criterion.'_score')->nullable();
                $table->text($criterion.'_evidence')->nullable();
            }
            foreach (['result', 'potential'] as $dimension) {
                $table->decimal($dimension.'_average', 5, 2)->nullable();
                $table->enum($dimension.'_level', ['LOW', 'MEDIUM', 'HIGH'])->nullable();
            }
            $table->enum('nine_box_cell', ['M1', 'S1', 'B1', 'M2', 'S2', 'B2', 'M3', 'S3', 'B3'])->nullable();
            $table->enum('calibration_signal', ['NONE', 'ALIGNED', 'NEEDS_CALIBRATION'])->default('NONE');
            $table->enum('main_risk', ['NONE', 'DELIVERY', 'LEARNING', 'ADAPTABILITY', 'OWNERSHIP', 'MOTIVATION'])->default('NONE');
            $table->text('final_comment')->nullable();
            $table->timestamps();
            $table->timestampTz('completed_at')->nullable();
            $table->index(['candidate_id', 'status', 'completed_at', 'id'], 'assessment_current_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_assessments');
        Schema::dropIfExists('candidate_recruiters');
        Schema::dropIfExists('candidate_hiring_managers');
        Schema::dropIfExists('candidates');
    }
};
