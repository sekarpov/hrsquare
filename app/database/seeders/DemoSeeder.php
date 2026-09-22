<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Candidate;
use App\Models\City;
use App\Models\User;
use App\Services\AssessmentService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing', 'demo'])) {
            throw new \RuntimeException('Demo seeding is disabled in production.');
        }
        $r = User::firstOrCreate(['login' => 'recruiter'], ['full_name' => 'Анна Смирнова', 'password' => 'recruiter', 'role' => UserRole::RECRUITER, 'is_active' => true]);
        $a = User::firstOrCreate(['login' => 'manager1'], ['full_name' => 'Алексей Волков', 'password' => 'manager1', 'role' => UserRole::MANAGER, 'is_active' => true]);
        $b = User::firstOrCreate(['login' => 'manager2'], ['full_name' => 'Мария Соколова', 'password' => 'manager2', 'role' => UserRole::MANAGER, 'is_active' => true]);
        $names = ['Дарья Кузнецова', 'Илья Петров', 'Елена Орлова', 'Максим Лебедев', 'Ольга Попова', 'Артём Морозов', 'София Новикова', 'Дмитрий Павлов', 'Алина Васильева', 'Никита Фёдоров', 'Ксения Белова', 'Роман Зайцев'];
        $service = app(AssessmentService::class);
        $reference = now();
        City::firstOrCreate(['name' => 'Москва']);
        foreach ($names as $i => $name) {
            $c = Candidate::firstOrCreate(['full_name' => $name, 'project' => 'HRSquare Demo'], ['position' => ['Product Manager', 'Frontend Developer', 'HR Business Partner', 'Data Analyst'][$i % 4], 'city' => ['Алматы', 'Астана', 'Москва'][$i % 3], 'company' => ['Square Labs', 'Northstar', 'Orbit'][$i % 3], 'division' => ['Product', 'Engineering', 'People'][$i % 3], 'status' => ['ACTIVE', 'HIRED', 'REJECTED'][$i % 3], 'created_by' => $r->id]);
            $c->hiringManagers()->sync($i % 2 ? [$b->id] : [$a->id, $b->id]);
            $c->recruiters()->sync([$r->id]);
            if ($c->assessments()->exists()) {
                continue;
            }
            for ($j = 0; $j <= ($i % 3); $j++) {
                $manager = $j % 2 ? $b : ($i % 2 ? $b : $a);
                $data = ['calibration_signal' => 'NEEDS_CALIBRATION', 'main_risk' => 'NONE', 'final_comment' => 'Подтверждены конкретные примеры из интервью.'];
                foreach (array_keys(config('assessment.criteria')) as $key) {
                    $snake = Str::snake($key);
                    $data[$snake.'_score'] = config('assessment.criteria.'.$key.'.group') === 'RESULT' ? 2 + (($i + $j) % 3) : 2 + ((intdiv($i, 3) + $j) % 3);
                    $data[$snake.'_evidence'] = 'Кандидат привёл пример проекта с измеримым результатом и объяснил личный вклад.';
                }
                Carbon::withTestNow($reference->copy()->subDays(10 - $j)->subMinutes($i), function () use ($service, $c, $manager, $data) {
                    $assessment = $service->create($c, $manager, $data);
                    $service->save($assessment, $manager, [], true);
                });
            }
            if ($i % 4 === 0) {
                $service->create($c, $b, ['final_comment' => 'Продолжить интервью — это черновик, он не меняет текущий результат.']);
            }
        }
    }
}
