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
            ->assertRedirect(route('admin.users.index', [
                'page' => 2,
                'account_type' => 'all',
            ]));
    }

    /**
     * Przy zerowej liczbie wyników wejście na stronę drugą prowadzi do pierwszej.
     */
    public function test_out_of_range_empty_list_redirects_to_first_page(): void
    {
        /** Administrator bez zapisu pozwala zachować pusty wynik zapytania. */
        $admin = User::factory()->make(['is_admin' => true]);

        $this->actingAs($admin)->get(route('admin.users.index', ['page' => 2]))
            ->assertRedirect(route('admin.users.index', [
                'page' => 1,
                'account_type' => 'all',
            ]));
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
}
