<?php

namespace Tests\Feature;

use App\Models\Discipline;
use App\Models\League;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LeaguePersistenceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_league_name_cannot_repeat_in_the_same_discipline(): void
    {
        $league = League::factory()->create([
            'name' => 'Liga Amatorska',
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        League::factory()->create([
            'name' => 'Liga Amatorska',
            'discipline_id' => $league->discipline_id,
        ]);
    }

    public function test_league_name_can_repeat_in_different_disciplines(): void
    {
        League::factory()->create([
            'name' => 'Liga Amatorska',
        ]);
        $otherDiscipline = Discipline::factory()->create();

        $otherLeague = League::factory()->create([
            'name' => 'Liga Amatorska',
            'discipline_id' => $otherDiscipline->id,
        ]);

        $this->assertModelExists($otherLeague);
    }

    public function test_league_can_be_saved_without_a_description(): void
    {
        $league = League::factory()->create(['description' => null]);

        $this->assertDatabaseHas('leagues', [
            'id' => $league->id,
            'description' => null,
        ]);
    }

    public function test_league_cannot_reference_a_nonexistent_discipline(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionCode('23000');

        try {
            League::factory()->create([
                'discipline_id' => 0,
            ]);
        } finally {
            $this->assertDatabaseCount('leagues', 0);
        }
    }

    public function test_discipline_used_by_a_league_cannot_be_deleted(): void
    {
        $discipline = Discipline::factory()->create();
        $league = League::factory()->create(['discipline_id' => $discipline->id]);

        $this->expectException(QueryException::class);
        $this->expectExceptionCode('23000');

        try {
            $discipline->delete();
        } finally {
            $this->assertModelExists($discipline);
            $this->assertModelExists($league);
        }
    }
}
