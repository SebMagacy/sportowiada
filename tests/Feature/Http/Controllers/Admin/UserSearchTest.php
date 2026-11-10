<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class UserSearchTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * Wyszukiwanie po fragmencie imienia pokazuje wyłącznie pasujące konto.
     */
    public function test_admin_can_search_users_by_name_fragment(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'name' => 'Administrator',
            'email' => 'admin@example.com',
        ]);

        $matchingUser = User::factory()->create([
            'name' => 'Norbert Borowski',
            'email' => 'matching@example.com',
        ]);

        $otherUser = User::factory()->create([
            'name' => 'Anna Kowalska',
            'email' => 'other@example.com',
        ]);

        $response = $this->actingAs($admin)->get(
            route('admin.users.index', ['search' => 'Nor'])
        );

        $response->assertOk()
            ->assertSeeText($matchingUser->email)
            ->assertDontSeeText($otherUser->email)
            ->assertDontSeeText($admin->email);
    }

    /**
     * Wyszukiwanie po fragmencie e-maila pokazuje wyłącznie pasujące konto.
     */
    public function test_admin_can_search_users_by_email_fragment(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'name' => 'Administrator',
            'email' => 'admin@example.com',
        ]);

        $matchingUser = User::factory()->create([
            'name' => 'Anna Kowalska',
            'email' => 'anna.sport@example.com',
        ]);

        $otherUser = User::factory()->create([
            'name' => 'Jan Nowak',
            'email' => 'jan@example.com',
        ]);

        $response = $this->actingAs($admin)->get(
            route('admin.users.index', ['search' => 'anna.sport'])
        );

        $response->assertOk()
            ->assertSeeText($matchingUser->email)
            ->assertDontSeeText($otherUser->email)
            ->assertDontSeeText($admin->email);
    }

    /**
     * Brak dopasowań pokazuje komunikat wyszukiwania mimo istnienia kont.
     */
    public function test_search_without_matches_shows_no_results_message(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'name' => 'Administrator',
            'email' => 'admin@example.com',
        ]);

        $response = $this->actingAs($admin)->get(
            route('admin.users.index', ['search' => 'nieistniejace-konto-xyz987'])
        );

        $response->assertOk()
            ->assertSeeText(__('users.no_results'))
            ->assertDontSeeText(__('users.empty'))
            ->assertDontSeeText($admin->email);

        $response->assertViewHas('users', function (LengthAwarePaginator $users): bool {
            return $users->total() === 0;
        });
    }

    /**
     * Link do drugiej strony zachowuje frazę wyszukiwania.
     */
    public function test_search_phrase_is_preserved_in_pagination_links(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'name' => 'Administrator',
            'email' => 'admin@example.com',
        ]);

        User::factory()->count(11)->create([
            'name' => 'Pasujący użytkownik',
        ]);

        $response = $this->actingAs($admin)->get(
            route('admin.users.index', ['search' => 'Pasujący'])
        );

        $secondPageUrl = route('admin.users.index', [
            'search' => 'Pasujący',
            'account_type' => 'all',
            'per_page' => '10', 'page' => 2,
        ]);

        $response->assertOk()
            ->assertSee('href="'.e($secondPageUrl).'"', false);
    }

    /**
     * Przekierowanie ze strony poza zakresem zachowuje wyszukiwanie.
     */
    public function test_out_of_range_redirect_preserves_search_phrase(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'name' => 'Administrator',
            'email' => 'admin@example.com',
        ]);

        User::factory()->create([
            'name' => 'Norbert Borowski',
            'email' => 'norbert@example.com',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index', [
                'search' => 'Norbert',
                'page' => 999,
            ]))
            ->assertRedirect(route('admin.users.index', [
                'page' => 1,
                'search' => 'Norbert',
                'account_type' => 'all', 'per_page' => '10',
            ]));
    }
}
