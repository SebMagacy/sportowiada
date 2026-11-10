<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class UserSortingTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * Sortowanie po imieniu zmienia kolejność niezależnie od ID.
     */
    public function test_admin_can_sort_users_by_name_ascending(): void
    {
        $admin = User::factory()->make(['is_admin' => true]);

        $lastUser = User::factory()->create(['name' => 'Zofia']);
        $firstUser = User::factory()->create(['name' => 'Anna']);

        $response = $this->actingAs($admin)->get(
            route('admin.users.index', [
                'sort' => 'name',
                'direction' => 'asc',
            ])
        );

        $response->assertOk();

        $response->assertViewHas(
            'users',
            function (LengthAwarePaginator $users) use ($firstUser, $lastUser): bool {
                return $users->pluck('id')->all() === [
                    $firstUser->id,
                    $lastUser->id,
                ];
            }
        );
    }

    /**
     * Kierunek malejący ustawia imiona od Z do A.
     */
    public function test_admin_can_sort_users_by_name_descending(): void
    {
        $admin = User::factory()->make(['is_admin' => true]);

        $anna = User::factory()->create(['name' => 'Anna']);
        $zofia = User::factory()->create(['name' => 'Zofia']);

        $response = $this->actingAs($admin)->get(
            route('admin.users.index', [
                'sort' => 'name',
                'direction' => 'desc',
            ])
        );

        $response->assertOk();

        $response->assertViewHas(
            'users',
            function (LengthAwarePaginator $users) use ($anna, $zofia): bool {
                return $users->pluck('id')->all() === [
                    $zofia->id,
                    $anna->id,
                ];
            }
        );
    }

    /**
     * Jednakowe imiona zachowują kolejność ID rosnąco,
     * także przy malejącym sortowaniu imion.
     */
    public function test_name_sorting_uses_id_to_break_ties(): void
    {
        $admin = User::factory()->make(['is_admin' => true]);

        $firstAnna = User::factory()->create(['name' => 'Anna']);
        $secondAnna = User::factory()->create(['name' => 'Anna']);
        $zofia = User::factory()->create(['name' => 'Zofia']);

        $response = $this->actingAs($admin)->get(
            route('admin.users.index', [
                'sort' => 'name',
                'direction' => 'desc',
            ])
        );

        $response->assertOk();

        $response->assertViewHas(
            'users',
            function (LengthAwarePaginator $users) use ($firstAnna, $secondAnna, $zofia): bool {
                return $users->pluck('id')->all() === [
                    $zofia->id,
                    $firstAnna->id,
                    $secondAnna->id,
                ];
            }
        );
    }

    /**
     * Nieznana kolumna przywraca domyślną kolejność bez błędu.
     */
    public function test_unknown_sort_column_restores_default_order(): void
    {
        $admin = User::factory()->make(['is_admin' => true]);

        $zofia = User::factory()->create(['name' => 'Zofia']);
        $anna = User::factory()->create(['name' => 'Anna']);

        $response = $this->actingAs($admin)->get(
            route('admin.users.index', [
                'sort' => 'unknown_column',
                'direction' => 'desc',
            ])
        );

        $response->assertOk();
        $response->assertViewHas('sort', fn (mixed $sort): bool => $sort === null);
        $response->assertViewHas('direction', fn (mixed $direction): bool => $direction === null);

        $response->assertViewHas(
            'users',
            function (LengthAwarePaginator $users) use ($zofia, $anna): bool {
                return $users->pluck('id')->all() === [
                    $zofia->id,
                    $anna->id,
                ];
            }
        );
    }

    /**
     * Nieprawidłowy kierunek zastępujemy sortowaniem rosnącym.
     */
    public function test_invalid_sort_direction_falls_back_to_ascending(): void
    {
        $admin = User::factory()->make(['is_admin' => true]);

        $zofia = User::factory()->create(['name' => 'Zofia']);
        $anna = User::factory()->create(['name' => 'Anna']);

        $response = $this->actingAs($admin)->get(
            route('admin.users.index', [
                'sort' => 'name',
                'direction' => 'invalid',
            ])
        );

        $response->assertOk();
        $response->assertViewHas('sort', 'name');
        $response->assertViewHas('direction', 'asc');

        $response->assertViewHas(
            'users',
            function (LengthAwarePaginator $users) use ($anna, $zofia): bool {
                return $users->pluck('id')->all() === [
                    $anna->id,
                    $zofia->id,
                ];
            }
        );
    }

    /**
     * Typ konta nie pozwala sortować także przez parametr adresu.
     */
    public function test_account_type_cannot_be_used_for_sorting(): void
    {
        $admin = User::factory()->make(['is_admin' => true]);

        $regularUser = User::factory()->create(['is_admin' => false]);
        $savedAdmin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(
            route('admin.users.index', [
                'sort' => 'is_admin',
                'direction' => 'desc',
            ])
        );

        $response->assertOk();
        $response->assertViewHas('sort', fn (mixed $sort): bool => $sort === null);
        $response->assertViewHas('direction', fn (mixed $direction): bool => $direction === null);

        $response->assertViewHas(
            'users',
            function (LengthAwarePaginator $users) use ($regularUser, $savedAdmin): bool {
                return $users->pluck('id')->all() === [
                    $regularUser->id,
                    $savedAdmin->id,
                ];
            }
        );
    }

    /**
     * Sortowanie obejmuje wyłącznie konta pasujące do wyszukiwania.
     */
    public function test_search_results_can_be_sorted_by_name(): void
    {
        $admin = User::factory()->make(['is_admin' => true]);

        $zofia = User::factory()->create([
            'name' => 'Zofia Sport',
        ]);
        $anna = User::factory()->create([
            'name' => 'Anna Sport',
        ]);
        User::factory()->create([
            'name' => 'Barbara',
            'email' => 'barbara@example.com',
        ]);

        $response = $this->actingAs($admin)->get(
            route('admin.users.index', [
                'search' => 'Sport',
                'sort' => 'name',
                'direction' => 'asc',
            ])
        );

        $response->assertOk();

        $response->assertViewHas(
            'users',
            function (LengthAwarePaginator $users) use ($anna, $zofia): bool {
                return $users->total() === 2
                    && $users->pluck('id')->all() === [
                        $anna->id,
                        $zofia->id,
                    ];
            }
        );
    }

    /**
     * Paginacja zachowuje wyszukiwanie, kolumnę i kierunek sortowania.
     */
    public function test_pagination_preserves_search_and_sorting(): void
    {
        $admin = User::factory()->make(['is_admin' => true]);

        User::factory()->count(11)->create([
            'name' => 'Sport Test',
        ]);

        $response = $this->actingAs($admin)->get(
            route('admin.users.index', [
                'search' => 'Sport',
                'sort' => 'name',
                'direction' => 'desc',
            ])
        );

        $secondPageUrl = route('admin.users.index', [
            'search' => 'Sport',
            'account_type' => 'all',
            'sort' => 'name',
            'direction' => 'desc',
            'per_page' => '10', 'page' => 2,
        ]);

        $response->assertOk()
            ->assertSee('href="'.e($secondPageUrl).'"', false);
    }

    /**
     * Przekierowanie ze strony poza zakresem zachowuje filtr i sortowanie.
     */
    public function test_out_of_range_redirect_preserves_search_and_sorting(): void
    {
        $admin = User::factory()->make(['is_admin' => true]);

        User::factory()->create(['name' => 'Anna Sport']);

        $this->actingAs($admin)
            ->get(route('admin.users.index', [
                'search' => 'Sport',
                'sort' => 'name',
                'direction' => 'desc',
                'page' => 999,
            ]))
            ->assertRedirect(route('admin.users.index', [
                'page' => 1,
                'search' => 'Sport',
                'account_type' => 'all',
                'sort' => 'name',
                'direction' => 'desc', 'per_page' => '10',
            ]));
    }

    /**
     * Trzecie kliknięcie resetuje sortowanie i stronę, zachowując filtr.
     */
    public function test_sort_reset_link_preserves_search_and_removes_page(): void
    {
        $admin = User::factory()->make(['is_admin' => true]);

        User::factory()->count(11)->create([
            'name' => 'Sport Test',
        ]);

        $response = $this->actingAs($admin)->get(
            route('admin.users.index', [
                'search' => 'Sport',
                'sort' => 'name',
                'direction' => 'desc',
                'page' => 2,
            ])
        );

        $resetUrl = route('admin.users.index', [
            'search' => 'Sport',
            'account_type' => 'all', 'per_page' => '10',
        ]);

        $response->assertOk()
            ->assertSee('href="'.e($resetUrl).'"', false);
    }

    /**
     * Sortowanie po e-mailu jest niezależne od imienia i ID.
     */
    public function test_admin_can_sort_users_by_email_ascending(): void
    {
        $admin = User::factory()->make(['is_admin' => true]);

        $anna = User::factory()->create([
            'name' => 'Anna',
            'email' => 'zofia@example.com',
        ]);
        $zofia = User::factory()->create([
            'name' => 'Zofia',
            'email' => 'anna@example.com',
        ]);

        $response = $this->actingAs($admin)->get(
            route('admin.users.index', [
                'sort' => 'email',
                'direction' => 'asc',
            ])
        );

        $response->assertOk();

        $response->assertViewHas(
            'users',
            function (LengthAwarePaginator $users) use ($anna, $zofia): bool {
                return $users->pluck('id')->all() === [
                    $zofia->id,
                    $anna->id,
                ];
            }
        );
    }

    /**
     * Kierunek malejący porządkuje adresy e-mail od Z do A.
     */
    public function test_admin_can_sort_users_by_email_descending(): void
    {
        $admin = User::factory()->make(['is_admin' => true]);

        $firstUser = User::factory()->create([
            'name' => 'Anna',
            'email' => 'anna@example.com',
        ]);
        $secondUser = User::factory()->create([
            'name' => 'Zofia',
            'email' => 'zofia@example.com',
        ]);

        $response = $this->actingAs($admin)->get(
            route('admin.users.index', [
                'sort' => 'email',
                'direction' => 'desc',
            ])
        );

        $response->assertOk();

        $response->assertViewHas(
            'users',
            function (LengthAwarePaginator $users) use ($firstUser, $secondUser): bool {
                return $users->pluck('id')->all() === [
                    $secondUser->id,
                    $firstUser->id,
                ];
            }
        );
    }

    /**
     * Sortowanie po ID malejąco pokazuje nowszy identyfikator jako pierwszy.
     */
    public function test_admin_can_sort_users_by_id_descending(): void
    {
        $admin = User::factory()->make(['is_admin' => true]);

        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $response = $this->actingAs($admin)->get(
            route('admin.users.index', [
                'sort' => 'id',
                'direction' => 'desc',
            ])
        );

        $response->assertOk();

        $response->assertViewHas(
            'users',
            function (LengthAwarePaginator $users) use ($firstUser, $secondUser): bool {
                return $users->pluck('id')->all() === [
                    $secondUser->id,
                    $firstUser->id,
                ];
            }
        );
    }

    /**
     * Jawne ID rosnąco zachowuje kolejność i oznacza aktywny nagłówek.
     */
    public function test_admin_can_explicitly_sort_users_by_id_ascending(): void
    {
        $admin = User::factory()->make(['is_admin' => true]);
        $firstUser = User::factory()->create(['name' => 'Zofia']);
        $secondUser = User::factory()->create(['name' => 'Anna']);

        $response = $this->actingAs($admin)->get(route('admin.users.index', [
            'sort' => 'id',
            'direction' => 'asc',
        ]));

        $response->assertOk()
            ->assertViewHas('sort', 'id')
            ->assertViewHas('direction', 'asc')
            ->assertSee('aria-sort="ascending"', false);
        $response->assertViewHas('users', function (LengthAwarePaginator $users) use ($firstUser, $secondUser): bool {
            return $users->pluck('id')->all() === [$firstUser->id, $secondUser->id];
        });
    }

    /**
     * Odnośniki nagłówka prowadzą od braku wyboru przez asc do desc.
     */
    public function test_name_header_links_cycle_from_unsorted_to_ascending_and_descending(): void
    {
        $admin = User::factory()->make(['is_admin' => true]);
        $ascendingUrl = route('admin.users.index', [
            'search' => '',
            'account_type' => 'all', 'per_page' => '10',
            'sort' => 'name',
            'direction' => 'asc',
        ]);
        $descendingUrl = route('admin.users.index', [
            'search' => '',
            'account_type' => 'all', 'per_page' => '10',
            'sort' => 'name',
            'direction' => 'desc',
        ]);

        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('href="'.e($ascendingUrl).'"', false);

        $this->get($ascendingUrl)
            ->assertOk()
            ->assertViewHas('sort', 'name')
            ->assertViewHas('direction', 'asc')
            ->assertSee('aria-sort="ascending"', false)
            ->assertSee('href="'.e($descendingUrl).'"', false);

        $this->get($descendingUrl)
            ->assertOk()
            ->assertViewHas('direction', 'desc')
            ->assertSee('aria-sort="descending"', false)
            ->assertSeeText(__('Reset sorting'));
    }

    /**
     * Przejście odnośnikiem resetu przywraca ID i usuwa aktywny nagłówek.
     */
    public function test_following_sort_reset_restores_default_order_and_inactive_headers(): void
    {
        $admin = User::factory()->make(['is_admin' => true]);
        $zofia = User::factory()->create(['name' => 'Zofia Sport']);
        $anna = User::factory()->create(['name' => 'Anna Sport']);
        $resetUrl = route('admin.users.index', [
            'search' => 'Sport',
            'account_type' => 'all', 'per_page' => '10',
        ]);

        $this->actingAs($admin)->get(route('admin.users.index', [
            'search' => 'Sport',
            'sort' => 'name',
            'direction' => 'desc',
        ]))->assertOk()->assertSee('href="'.e($resetUrl).'"', false);

        $response = $this->get($resetUrl);

        $response->assertOk()
            ->assertViewHas('search', 'Sport')
            ->assertViewHas('sort', fn (mixed $sort): bool => $sort === null)
            ->assertViewHas('direction', fn (mixed $direction): bool => $direction === null)
            ->assertDontSee('aria-sort=', false);
        $response->assertViewHas('users', function (LengthAwarePaginator $users) use ($zofia, $anna): bool {
            return $users->pluck('id')->all() === [$zofia->id, $anna->id];
        });
    }
}
