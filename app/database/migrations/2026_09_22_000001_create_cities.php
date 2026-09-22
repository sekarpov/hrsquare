<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
        });

        $names = [
            'Алматы', 'Астана', 'Шымкент', 'Караганда', 'Актобе', 'Атырау',
            'Актау', 'Павлодар', 'Усть-Каменогорск', 'Костанай', 'Кызылорда',
            'Тараз', 'Уральск', 'Петропавловск', 'Семей', 'Кокшетау',
            'Туркестан', 'Талдыкорган', 'Ташкент (Узбекистан)',
        ];
        DB::table('cities')->insert(array_map(fn ($name) => ['name' => $name], $names));

        // Keep previously entered cities available when editing existing candidates.
        DB::table('candidates')->whereNotNull('city')->where('city', '<>', '')
            ->select('city')->distinct()->orderBy('city')->chunk(500, function ($candidates) {
                DB::table('cities')->insertOrIgnore($candidates->map(fn ($candidate) => ['name' => $candidate->city])->all());
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
