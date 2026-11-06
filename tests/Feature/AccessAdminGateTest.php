<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccessAdminGateTest extends TestCase
{
    /**
     * Sprawdzenie samej bramki obejmuje obie role, bez wykonywania żądania HTTP i zapisu modelu.
     */
    #[DataProvider('roles')]
    public function test_gate_allows_only_administrator(bool $isAdmin): void
    {
        $user = User::factory()->make(['is_admin' => $isAdmin]);

        $this->assertSame($isAdmin, Gate::forUser($user)->allows('access-admin'));
    }

    /**
     * Każdy nazwany zestaw danych uruchamia powiązany test osobno dla wskazanej roli.
     */
    public static function roles(): array
    {
        return ['administrator' => [true], 'regular user' => [false]];
    }

    /**
     * Brak uwierzytelnionego użytkownika nie daje uprawnienia access-admin.
     */
    public function test_gate_denies_guest(): void
    {
        $this->assertFalse(Gate::allows('access-admin'));
    }
}
