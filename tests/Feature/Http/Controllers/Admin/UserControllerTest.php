<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_open_an_empty_users_list(): void
    {
        /**
         * Administrator pozostaje niezapisany, aby lista użytkowników była pusta.
         */
        $admin = User::factory()->make([
            'is_admin' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.users.index'));

        /**
         * Administrator otrzymuje stronę listy bez przekierowania i błędu HTTP.
         */
        $response->assertStatus(200);

        /**
         * Strona informuje o braku użytkowników w aktualnym języku aplikacji.
         */
        $response->assertSeeText(__('users.empty'));

        /**
         * Pusta lista nie pokazuje licznika z brakującym początkiem i końcem zakresu.
         */
        $response->assertDontSeeText(__('pagination.overview', [
            'first' => null,
            'last' => null,
            'total' => 0,
        ]));

        /**
         * Całe zapytanie zwraca zero użytkowników, a nie tylko pustą stronę paginacji.
         */
        $response->assertViewHas('users', function (LengthAwarePaginator $users): bool {
            return $users->total() === 0;
        });

        /**
         * HTML nie zawiera nawigacji paginacji oznaczonej jej etykietą dostępności.
         * Etykieta jest escapowana jak w Blade, więc asercja nie escapuje jej ponownie.
         */
        $response->assertDontSee(
            'aria-label="'.e(__('pagination.navigation')).'"',
            false,
        );
    }

    /**
     * Gość nie otrzymuje chronionej strony i zostaje skierowany do logowania.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
    }

    /**
     * Zwykłe konto nie może odczytać listy użytkowników mimo poprawnego uwierzytelnienia.
     */
    public function test_regular_user_cannot_read_users_list(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]))->get(route('admin.users.index'))->assertForbidden();
    }

    /**
     * Lista pokazuje konta w kolejności ID, ich e-maile i etykiety ról.
     * Dwa konta mieszczą się na jednej stronie, więc nawigacja paginacji jest zbędna.
     */
    public function test_admin_sees_users_in_id_order_and_account_types(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'name' => 'Admin Testowy']);
        $user = User::factory()->create(['name' => 'Zwykły Testowy']);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk()->assertSeeTextInOrder([$admin->name, $user->name])
            ->assertSeeText($admin->email)->assertSeeText($user->email)
            ->assertSeeText(__('users.administrator'))->assertSeeText(__('users.user'))
            ->assertDontSee('aria-label="'.e(__('pagination.navigation')).'"', false);
        $response->assertViewHas('users', function (LengthAwarePaginator $users) use ($admin, $user): bool {
            return $users->pluck('id')->all() === [$admin->id, $user->id] && $users->total() === 2;
        });
    }

    /**
     * Jedenaście kont wymusza drugą stronę; pierwsza zawiera dokładnie dziesięć wyników i nawigację.
     */
    public function test_users_are_paginated_ten_per_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $users = User::factory()->count(10)->create();

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk()->assertSeeText($users[8]->email)->assertDontSeeText($users[9]->email)
            ->assertSeeText(__('pagination.overview', ['first' => 1, 'last' => 10, 'total' => 11]))
            ->assertSee('aria-label="'.e(__('pagination.navigation')).'"', false);
        $response->assertViewHas('users', function (LengthAwarePaginator $page): bool {
            return $page->count() === 10 && $page->total() === 11 && $page->currentPage() === 1;
        });
    }

    /**
     * Druga strona zawiera wyłącznie jedenaste konto oraz właściwy zakres licznika.
     */
    public function test_second_page_shows_remaining_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $users = User::factory()->count(10)->create();

        $response = $this->actingAs($admin)->get(route('admin.users.index', ['page' => 2]));

        $response->assertOk()->assertSeeText($users[9]->email)->assertDontSeeText($users[8]->email)
            ->assertSeeText(__('pagination.overview', ['first' => 11, 'last' => 11, 'total' => 11]));
        $response->assertViewHas('users', function (LengthAwarePaginator $page) use ($users): bool {
            return $page->pluck('id')->all() === [$users[9]->id];
        });
    }

    /**
     * Numer przekraczający zakres prowadzi do ostatniej dostępnej strony zamiast pustego widoku.
     */
    public function test_page_beyond_range_redirects_to_last_available_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->count(10)->create();

        $this->actingAs($admin)->get(route('admin.users.index', ['page' => 99]))
            ->assertRedirect(route('admin.users.index', ['page' => 2]));
    }

    /**
     * Przy zerowej liczbie wyników wejście na stronę drugą prowadzi do pierwszej.
     */
    public function test_out_of_range_empty_list_redirects_to_first_page(): void
    {
        /** Administrator bez zapisu pozwala zachować pusty wynik zapytania. */
        $admin = User::factory()->make(['is_admin' => true]);

        $this->actingAs($admin)->get(route('admin.users.index', ['page' => 2]))
            ->assertRedirect(route('admin.users.index', ['page' => 1]));
    }

    /**
     * Imię zawierające HTML musi zostać wyświetlone bez możliwości wstrzyknięcia skryptu.
     */
    public function test_user_name_is_escaped_in_table(): void
    {
        $name = '<script>alert("xss")</script>';
        $admin = User::factory()->create(['is_admin' => true, 'name' => $name]);

        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk()
            ->assertSee($name)->assertDontSee($name, false);
    }

    /**
     * Jawny wybór języka angielskiego sprawdza tłumaczenie tytułu i komunikatu pustej listy.
     */
    public function test_empty_list_uses_english_translations(): void
    {
        $this->app->setLocale('en');
        $admin = User::factory()->make(['is_admin' => true]);

        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk()
            ->assertSeeText(__('users.title'))->assertSeeText(__('users.empty'))
            ->assertDontSeeText('Brak użytkowników.');
    }

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
            'page' => 2,
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
            ]));
    }

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
            'sort' => 'name',
            'direction' => 'desc',
            'page' => 2,
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
                'sort' => 'name',
                'direction' => 'desc',
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
            'sort' => 'name',
            'direction' => 'asc',
        ]);
        $descendingUrl = route('admin.users.index', [
            'search' => '',
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
        $resetUrl = route('admin.users.index', ['search' => 'Sport']);

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
