<?php

namespace Tests;

use App\Enums\UserRole;
use App\Models\Candidate;
use App\Models\CandidateAssessment;
use App\Models\User;
use App\Services\AssessmentService;
use Illuminate\Support\Str;

abstract class TestCase extends \Illuminate\Foundation\Testing\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'pgsql' || config('database.connections.pgsql.database') !== 'hrsquare_test') {
            throw new \RuntimeException('Tests require isolated PostgreSQL database hrsquare_test');
        }
        $this->withoutVite();
    }

    protected function person(string $login, UserRole $role = UserRole::MANAGER): User
    {
        return User::create(['full_name' => $login, 'login' => $login, 'password' => 'password123', 'role' => $role, 'is_active' => true]);
    }

    protected function candidate(User $creator, User $manager, array $extra = []): Candidate
    {
        $c = new Candidate(['full_name' => 'Candidate '.$manager->login, 'position' => 'Engineer', 'status' => 'ACTIVE', ...$extra]);
        $c->created_by = $creator->id;
        $c->save();
        $c->hiringManagers()->attach($manager);
        $c->recruiters()->attach($creator);

        return $c;
    }

    protected function scores(int $result = 2, int $potential = 2): array
    {
        return ['taskScaleScore' => $result, 'resultImpactScore' => $result, 'personalContributionScore' => $result, 'learningAgilityScore' => $potential, 'adaptabilityScore' => $potential, 'initiativeScore' => $potential];
    }

    protected function completed(Candidate $candidate, User $evaluator, int $result, int $potential): CandidateAssessment
    {
        $data = collect($this->scores($result, $potential))->mapWithKeys(fn ($v, $k) => [Str::snake($k) => $v])->all();
        $service = app(AssessmentService::class);
        $a = $service->create($candidate, $evaluator, $data);

        return $service->save($a, $evaluator, [], true);
    }
}
