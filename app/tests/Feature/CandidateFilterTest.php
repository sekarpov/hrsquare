<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CandidateFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_filters_use_current_completed_assessment_in_database(): void
    {
        $r = $this->person('r', UserRole::RECRUITER);
        $a = $this->person('a');
        $c = $this->candidate($r, $a, ['status' => 'HIRED', 'city' => 'Алматы', 'company' => 'Square', 'division' => 'Tech', 'project' => 'Demo']);
        $this->travelTo(now()->subDays(2));
        $this->completed($c, $a, 4, 4);
        $this->travel(1)->days();
        $latest = $this->completed($c, $a, 4, 3);
        $this->actingAs($a)->postJson('/api/candidates/'.$c->id.'/assessments', $this->scores(1, 1));
        $other = $this->candidate($r, $a, ['full_name' => 'Other', 'status' => 'HIRED']);
        $this->completed($other, $a, 3, 3);
        $params = ['status' => 'HIRED', 'resultLevel' => 'HIGH', 'potentialLevel' => 'MEDIUM', 'nineBoxCell' => 'B2', 'city' => 'Алматы', 'company' => 'Square', 'division' => 'Tech', 'project' => 'Demo', 'managerId' => $a->id, 'recruiterId' => $r->id];
        $this->actingAs($r)->getJson('/api/candidates?'.http_build_query($params))->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.currentAssessment.id', $latest->id);
        $this->getJson('/api/candidates?nineBoxCell=B1')->assertJsonCount(0, 'data');
    }

    public function test_pagination_search_sort_and_invalid_sort(): void
    {
        $r = $this->person('r', UserRole::RECRUITER);
        $a = $this->person('a');
        foreach (['Alpha', 'Beta', 'Gamma'] as $name) {
            $this->candidate($r, $a, ['full_name' => $name]);
        }
        $this->actingAs($r)->getJson('/api/candidates?perPage=2&page=2&sort=fullName&direction=asc')->assertJsonCount(1, 'data')->assertJsonPath('data.0.fullName', 'Gamma')->assertJsonPath('meta.total', 3);
        $this->getJson('/api/candidates?search=alpha')->assertJsonCount(1, 'data');
        $this->getJson('/api/candidates?sort=invalid')->assertUnprocessable();
        $this->getJson('/api/candidates?perPage=1000')->assertUnprocessable();
    }

    public function test_query_count_is_bounded_independent_of_candidate_count(): void
    {
        $r = $this->person('r', UserRole::RECRUITER);
        $a = $this->person('a');
        for ($i = 0; $i < 12; $i++) {
            $c = $this->candidate($r, $a);
            $this->completed($c, $a, 3, 3);
        }
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->actingAs($r)->getJson('/api/candidates')->assertOk()->assertJsonCount(12, 'data');
        $this->assertLessThanOrEqual(8, count(DB::getQueryLog()));
        DB::disableQueryLog();
    }
}
