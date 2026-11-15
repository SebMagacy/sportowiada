<?php

namespace Tests\Feature;

use App\Models\Season;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SeasonPersistenceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_two_seasons_cannot_share_a_name(): void
    {
        Season::factory()->create(['name' => '2026/2027']);

        $this->expectException(UniqueConstraintViolationException::class);

        Season::factory()->create(['name' => '2026/2027']);
    }
}
