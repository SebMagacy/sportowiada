<?php

namespace Database\Seeders;

use App\Models\Discipline;
use Illuminate\Database\Seeder;

class DisciplineSeeder extends Seeder
{
    public function run(): void
    {
        $disciplines = [
            'pilka-nozna' => 'Piłka nożna',
            'siatkowka' => 'Siatkówka',
            'koszykowka' => 'Koszykówka',
        ];

        foreach ($disciplines as $alias => $name) {
            Discipline::updateOrCreate(
                ['alias' => $alias],
                ['name' => $name],
            );
        }
    }
}
