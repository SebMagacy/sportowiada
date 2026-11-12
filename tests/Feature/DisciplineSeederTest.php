<?php

namespace Tests\Feature;

use App\Models\Discipline;
use Database\Seeders\DisciplineSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DisciplineSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_seeder_creates_three_polish_disciplines(): void
    {
        $this->seed(DisciplineSeeder::class);

        $this->assertDatabaseCount('disciplines', 3);
        $this->assertDatabaseHas('disciplines', ['alias' => 'pilka-nozna', 'name' => 'Piłka nożna']);
        $this->assertDatabaseHas('disciplines', ['alias' => 'siatkowka', 'name' => 'Siatkówka']);
        $this->assertDatabaseHas('disciplines', ['alias' => 'koszykowka', 'name' => 'Koszykówka']);
    }

    public function test_repeated_seeding_preserves_record_ids_without_duplicates(): void
    {
        $this->seed(DisciplineSeeder::class);
        $ids = Discipline::query()->orderBy('alias')->pluck('id', 'alias')->all();

        $this->seed(DisciplineSeeder::class);

        $this->assertDatabaseCount('disciplines', 3);
        $this->assertSame($ids, Discipline::query()->orderBy('alias')->pluck('id', 'alias')->all());
    }

    public function test_seeder_updates_old_translation_key_and_preserves_other_disciplines(): void
    {
        $football = Discipline::factory()->create([
            'alias' => 'pilka-nozna',
            'name' => 'disciplines.names.football',
        ]);
        $other = Discipline::factory()->create(['alias' => 'tenis', 'name' => 'Tenis']);

        $this->seed(DisciplineSeeder::class);

        $this->assertDatabaseCount('disciplines', 4);
        $this->assertDatabaseHas('disciplines', ['id' => $football->id, 'alias' => 'pilka-nozna', 'name' => 'Piłka nożna']);
        $this->assertDatabaseHas('disciplines', ['id' => $other->id, 'alias' => 'tenis', 'name' => 'Tenis']);
    }
}
