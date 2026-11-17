<?php

namespace Tests\Feature;

use App\Models\Discipline;
use App\Models\League;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LeagueRelationshipsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_league_returns_its_discipline(): void
    {
        $discipline = Discipline::factory()->create();

        $league = League::factory()->create([
            'discipline_id' => $discipline->id,
        ]);

        $this->assertSame(
            $discipline->id,
            $league->discipline?->id,
        );
    }

    public function test_discipline_returns_all_its_leagues_and_excludes_other_disciplines(): void
    {
        $discipline = Discipline::factory()->create();

        $firstLeague = League::factory()->create([
            'discipline_id' => $discipline->id,
        ]);
        $secondLeague = League::factory()->create([
            'discipline_id' => $discipline->id,
        ]);
        League::factory()->create();

        $leagueIds = $discipline->leagues->sortBy('id')->values()->modelKeys();

        $this->assertSame([$firstLeague->id, $secondLeague->id], $leagueIds);
    }
}
