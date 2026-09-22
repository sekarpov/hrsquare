<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CityTest extends TestCase
{
    use RefreshDatabase;

    public function test_directory_contains_all_requested_cities(): void
    {
        $response = $this->actingAs($this->person('manager'))->getJson('/api/cities')
            ->assertOk()->assertJsonCount(19, 'data');
        $this->assertEqualsCanonicalizing([
            'Алматы', 'Астана', 'Шымкент', 'Караганда', 'Актобе', 'Атырау',
            'Актау', 'Павлодар', 'Усть-Каменогорск', 'Костанай', 'Кызылорда',
            'Тараз', 'Уральск', 'Петропавловск', 'Семей', 'Кокшетау',
            'Туркестан', 'Талдыкорган', 'Ташкент (Узбекистан)',
        ], array_column($response->json('data'), 'name'));
    }

    public function test_all_candidate_creator_roles_can_add_cities_and_duplicates_are_rejected(): void
    {
        foreach (UserRole::cases() as $role) {
            $name = 'Новый город '.$role->value;
            $this->actingAs($this->person($role->value, $role))
                ->postJson('/api/cities', ['name' => '  '.$name.'  '])
                ->assertCreated()->assertJsonPath('data.name', $name);
            $this->postJson('/api/cities', ['name' => $name])
                ->assertUnprocessable()->assertJsonValidationErrors('name');
            $this->getJson('/api/cities')->assertOk()->assertJsonFragment(['name' => $name]);
        }
        foreach ([null, '', '   ', str_repeat('я', 256), ['Астана']] as $name) {
            $this->postJson('/api/cities', ['name' => $name])
                ->assertUnprocessable()->assertJsonValidationErrors('name');
        }
    }

    public function test_city_selection_is_validated_and_can_be_changed_or_cleared(): void
    {
        $recruiter = $this->person('recruiter', UserRole::RECRUITER);
        $manager = $this->person('manager');
        $payload = ['fullName' => 'Кандидат', 'position' => 'Инженер', 'city' => 'Алматы', 'status' => 'ACTIVE', 'hiringManagerIds' => [$manager->id], 'recruiterIds' => []];
        $id = $this->actingAs($recruiter)->postJson('/api/candidates', $payload)
            ->assertCreated()->assertJsonPath('data.city', 'Алматы')->json('data.id');
        $this->postJson('/api/candidates', [...$payload, 'city' => 'Бишкек'])
            ->assertUnprocessable()->assertJsonValidationErrors('city');
        $this->putJson('/api/candidates/'.$id, [...$payload, 'city' => 'Бишкек'])
            ->assertUnprocessable()->assertJsonValidationErrors('city');
        $this->postJson('/api/cities', ['name' => 'Бишкек'])->assertCreated();
        $this->putJson('/api/candidates/'.$id, [...$payload, 'city' => 'Бишкек'])
            ->assertOk()->assertJsonPath('data.city', 'Бишкек');
        $this->getJson('/api/candidates/'.$id)->assertOk()->assertJsonPath('data.city', 'Бишкек');
        $this->putJson('/api/candidates/'.$id, [...$payload, 'city' => null])
            ->assertOk()->assertJsonPath('data.city', null);
        $this->postJson('/api/candidates', [...$payload, 'city' => null])->assertCreated();
        $this->assertDatabaseHas('candidates', ['id' => $id, 'city' => null]);
    }

    public function test_city_endpoints_require_an_active_account_with_a_changed_password(): void
    {
        $this->getJson('/api/cities')->assertUnauthorized();
        $this->postJson('/api/cities', ['name' => 'Бишкек'])->assertUnauthorized();
        $user = $this->person('manager');
        $user->update(['must_change_password' => true]);
        $this->actingAs($user)->getJson('/api/cities')->assertForbidden();
        $this->postJson('/api/cities', ['name' => 'Бишкек'])->assertForbidden();
        $user->update(['must_change_password' => false, 'is_active' => false]);
        $this->actingAs($user)->getJson('/api/cities')->assertUnauthorized();
        $this->actingAs($user)->postJson('/api/cities', ['name' => 'Бишкек'])->assertUnauthorized();
    }

    public function test_migration_preserves_existing_candidate_cities_and_rollback_keeps_candidate_data(): void
    {
        $manager = $this->person('manager');
        $candidate = $this->candidate($manager, $manager, ['city' => 'Бишкек']);
        $this->candidate($manager, $manager, ['city' => 'Алматы']);
        $this->candidate($manager, $manager, ['city' => 'Бишкек']);
        $this->candidate($manager, $manager, ['city' => null]);
        $migration = require database_path('migrations/2026_09_22_000001_create_cities.php');
        $migration->down();
        $this->assertDatabaseHas('candidates', ['id' => $candidate->id, 'city' => 'Бишкек']);
        $migration->up();
        $this->assertDatabaseCount('cities', 20);
        $this->assertDatabaseHas('cities', ['name' => 'Бишкек']);
        $this->assertDatabaseHas('candidates', ['id' => $candidate->id, 'city' => 'Бишкек']);
        $this->actingAs($manager)->postJson('/api/candidates', [
            'fullName' => 'Кандидат', 'position' => 'Инженер', 'city' => 'Бишкек',
            'status' => 'ACTIVE', 'hiringManagerIds' => [], 'recruiterIds' => [],
        ])->assertCreated()->assertJsonPath('data.city', 'Бишкек');
    }
}
