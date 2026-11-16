<?php

namespace Tests\Feature;

use App\Models\Discipline;
use App\Models\League;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LeaguePersistenceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_same_owner_cannot_repeat_a_league_name_in_the_same_discipline(): void
    {
        $league = League::factory()->create([
            'name' => 'Liga Amatorska',
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        League::factory()->create([
            'name' => 'Liga Amatorska',
            'owner_id' => $league->owner_id,
            'discipline_id' => $league->discipline_id,
        ]);
    }

    public function test_different_owners_can_use_the_same_league_name_in_the_same_discipline(): void
    {
        $league = League::factory()->create([
            'name' => 'Liga Amatorska',
        ]);

        $otherOwner = User::factory()->create();

        $otherLeague = League::factory()->create([
            'name' => 'Liga Amatorska',
            'owner_id' => $otherOwner->id,
            'discipline_id' => $league->discipline_id,
        ]);

        $this->assertModelExists($otherLeague);
    }

    public function test_same_owner_can_use_the_same_league_name_in_different_disciplines(): void
    {
        $league = League::factory()->create(['name' => 'Liga Amatorska']);
        $otherDiscipline = Discipline::factory()->create();

        $otherLeague = League::factory()->create([
            'name' => 'Liga Amatorska',
            'owner_id' => $league->owner_id,
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
        $owner = User::factory()->create();

        $this->expectException(QueryException::class);
        $this->expectExceptionCode('23000');

        try {
            League::factory()->create([
                'discipline_id' => 0,
                'owner_id' => $owner->id,
            ]);
        } finally {
            $this->assertDatabaseCount('leagues', 0);
        }
    }

    public function test_league_cannot_reference_a_nonexistent_owner(): void
    {
        $discipline = Discipline::factory()->create();

        $this->expectException(QueryException::class);
        $this->expectExceptionCode('23000');

        try {
            League::factory()->create([
                'discipline_id' => $discipline->id,
                'owner_id' => 0,
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

    public function test_owner_of_a_league_cannot_be_deleted(): void
    {
        $owner = User::factory()->create();
        $league = League::factory()->create(['owner_id' => $owner->id]);

        $this->expectException(QueryException::class);
        $this->expectExceptionCode('23000');

        try {
            $owner->delete();
        } finally {
            $this->assertModelExists($owner);
            $this->assertModelExists($league);
        }
    }
}
