<?php

namespace Tests\Feature;

use App\Models\Discipline;
use App\Models\League;
use App\Models\User;
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

    public function test_league_returns_its_owner(): void
    {
        $owner = User::factory()->create();

        $league = League::factory()->create([
            'owner_id' => $owner->id,
        ]);

        $this->assertSame(
            $owner->id,
            $league->owner?->id,
        );
    }

    public function test_discipline_returns_all_its_leagues_and_excludes_other_disciplines(): void
    {
        $discipline = Discipline::factory()->create();
        $owner = User::factory()->create();
        $firstLeague = League::factory()->create([
            'discipline_id' => $discipline->id,
            'owner_id' => $owner->id,
        ]);
        $secondLeague = League::factory()->create([
            'discipline_id' => $discipline->id,
        ]);
        League::factory()->create(['owner_id' => $owner->id]);

        $leagueIds = $discipline->leagues->sortBy('id')->values()->modelKeys();

        $this->assertSame([$firstLeague->id, $secondLeague->id], $leagueIds);
    }

    public function test_owner_returns_all_its_leagues_and_excludes_other_owners(): void
    {
        $owner = User::factory()->create();
        $discipline = Discipline::factory()->create();
        $firstLeague = League::factory()->create([
            'owner_id' => $owner->id,
            'discipline_id' => $discipline->id,
        ]);
        $secondLeague = League::factory()->create(['owner_id' => $owner->id]);
        League::factory()->create(['discipline_id' => $discipline->id]);

        $leagueIds = $owner->leagues->sortBy('id')->values()->modelKeys();

        $this->assertSame([$firstLeague->id, $secondLeague->id], $leagueIds);
    }
}
