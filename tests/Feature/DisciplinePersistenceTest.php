<?php

namespace Tests\Feature;

use App\Models\Discipline;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DisciplinePersistenceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_two_disciplines_cannot_share_an_alias(): void
    {
        Discipline::factory()->create(['alias' => 'pilka-nozna']);

        $this->expectException(UniqueConstraintViolationException::class);

        Discipline::factory()->create(['alias' => 'pilka-nozna']);
    }
}
