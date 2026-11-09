<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserAccountFilterTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @param list<string> $expectedNames */
    #[DataProvider('accountTypes')]
    public function test_admin_can_filter_users_by_account_type(?string $accountType, array $expectedNames): void
    {
        $admin = User::factory()->create(['name' => 'Administrator Testowy', 'is_admin' => true]);
        User::factory()->create(['name' => 'Użytkownik Testowy', 'is_admin' => false]);

        $response = $this->actingAs($admin)->get(route('admin.users.index', ['account_type' => $accountType]));

        $response->assertOk()->assertViewHas('accountType', $accountType ?? 'all');
        $response->assertViewHas('users', function (LengthAwarePaginator $users) use ($expectedNames): bool {
            return $users->total() === count($expectedNames)
                && $users->pluck('name')->all() === $expectedNames;
        });
        $this->assertMatchesRegularExpression(
            '/<option\s+value="'.($accountType ?? 'all').'"\s+selected\b/',
            $response->getContent(),
        );
    }

    /** @return array<string, array{0: string|null, 1: list<string>}> */
    public static function accountTypes(): array
    {
        return [
            'brak parametru' => [null, ['Administrator Testowy', 'Użytkownik Testowy']],
            'wszyscy' => ['all', ['Administrator Testowy', 'Użytkownik Testowy']],
            'administratorzy' => ['admin', ['Administrator Testowy']],
            'użytkownicy' => ['user', ['Użytkownik Testowy']],
        ];
    }

    /** @param string|array<int, string> $accountType */
    #[DataProvider('invalidAccountTypes')]
    public function test_invalid_account_type_falls_back_to_all(string|array $accountType): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($admin)->get(route('admin.users.index', ['account_type' => $accountType]));

        $response->assertOk()->assertViewHas('accountType', 'all');
        $response->assertViewHas('activeFiltersCount', 0);
        $response->assertViewHas('users', function (LengthAwarePaginator $users) use ($admin, $user): bool {
            return $users->total() === 2 && $users->pluck('id')->all() === [$admin->id, $user->id];
        });
        $this->assertMatchesRegularExpression('/<option\s+value="all"\s+selected\b/', $response->getContent());
        $response->assertSee('href="'.e(route('admin.users.index', [
            'search' => '', 'account_type' => 'all', 'sort' => 'name', 'direction' => 'asc',
        ])).'"', false);
    }

    /** @return array<string, array{0: string|array<int, string>}> */
    public static function invalidAccountTypes(): array
    {
        return [
            'nieznany tekst' => ['abc'],
            'pusta wartość' => [''],
            'tablica' => [['admin']],
        ];
    }

    /** @param list<string> $expectedEmails */
    #[DataProvider('searchCriteria')]
    public function test_search_and_account_type_are_applied_together(string $search, string $accountType, array $expectedEmails, bool $savedUserIsAdmin, string $expectedText): void
    {
        $admin = User::factory()->create([
            'is_admin' => true, 'name' => 'Sport Administrator', 'email' => 'sport.admin@example.com',
        ]);
        User::factory()->create([
            'is_admin' => $savedUserIsAdmin, 'name' => 'Sport Użytkownik', 'email' => 'sport.user@example.com',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.index', [
            'search' => $search, 'account_type' => $accountType,
        ]));

        $response->assertOk()->assertViewHas('search', $search)->assertViewHas('accountType', $accountType)
            ->assertSeeText($expectedText);
        $response->assertViewHas('users', function (LengthAwarePaginator $users) use ($expectedEmails): bool {
            return $users->total() === count($expectedEmails) && $users->pluck('email')->all() === $expectedEmails;
        });
    }

    /** @return array<string, array{0: string, 1: string, 2: list<string>, 3: bool, 4: string}> */
    public static function searchCriteria(): array
    {
        return [
            'imię i administratorzy' => ['Sport', 'admin', ['sport.admin@example.com'], false, 'Sport Administrator'],
            'e-mail i użytkownicy' => ['sport.', 'user', ['sport.user@example.com'], false, 'Sport Użytkownik'],
            'fraza pasuje tylko do innego typu' => ['Administrator', 'user', [], false, 'Nie znaleziono użytkowników spełniających wybrane kryteria.'],
            'sam filtr bez dopasowań' => ['', 'user', [], true, 'Nie znaleziono użytkowników spełniających wybrane kryteria.'],
        ];
    }

    public function test_pagination_and_out_of_range_redirect_preserve_all_criteria(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'name' => 'Sport Administrator']);
        $users = User::factory()->count(11)->sequence(
            fn (Sequence $sequence): array => ['name' => sprintf('Sport %02d', $sequence->index)],
        )->create(['is_admin' => false]);
        User::factory()->create(['is_admin' => false, 'name' => 'Inne konto', 'email' => 'other@example.com']);
        $criteria = ['search' => 'Sport', 'account_type' => 'user', 'sort' => 'name', 'direction' => 'desc'];
        $secondPageUrl = route('admin.users.index', [...$criteria, 'page' => 2]);

        $response = $this->actingAs($admin)->get(route('admin.users.index', $criteria));

        $response->assertOk()->assertSee('href="'.e($secondPageUrl).'"', false);
        $response->assertViewHas('users', function (LengthAwarePaginator $page) use ($users): bool {
            return $page->total() === 11 && $page->count() === 10
                && $page->first()->id === $users[10]->id && $page->last()->id === $users[1]->id;
        });

        $this->get(route('admin.users.index', [...$criteria, 'page' => 99]))
            ->assertRedirect(route('admin.users.index', ['page' => 2, ...$criteria]));

        $this->get($secondPageUrl)->assertOk()->assertViewHas('accountType', 'user')
            ->assertViewHas('users', function (LengthAwarePaginator $page) use ($users): bool {
                return $page->total() === 11 && $page->currentPage() === 2
                    && $page->pluck('id')->all() === [$users[0]->id];
            });
    }

    /** @param array<string, string> $nextSorting */
    #[DataProvider('sortingSteps')]
    public function test_sorting_links_preserve_filter_and_search_without_page(?string $direction, array $nextSorting): void
    {
        $admin = User::factory()->make(['is_admin' => true]);
        User::factory()->count(11)->create(['is_admin' => false, 'name' => 'Sport Test']);
        $filters = ['search' => 'Sport', 'account_type' => 'user'];
        $nextUrl = route('admin.users.index', [...$filters, ...$nextSorting]);

        $response = $this->actingAs($admin)->get(route('admin.users.index', [
            ...$filters, 'sort' => $direction === null ? null : 'name', 'direction' => $direction, 'page' => 2,
        ]));

        $response->assertOk()->assertSee('href="'.e($nextUrl).'"', false);
        $this->get($nextUrl)->assertOk()->assertViewHas('accountType', 'user')->assertViewHas('search', 'Sport')
            ->assertViewHas('users', function (LengthAwarePaginator $users): bool {
                return $users->currentPage() === 1 && $users->total() === 11;
            });
    }

    /** @return array<string, array{0: string|null, 1: array<string, string>}> */
    public static function sortingSteps(): array
    {
        return [
            'wybór rosnąco' => [null, ['sort' => 'name', 'direction' => 'asc']],
            'zmiana na malejąco' => ['asc', ['sort' => 'name', 'direction' => 'desc']],
            'reset' => ['desc', []],
        ];
    }

    /** @param array<string, string> $labels */
    #[DataProvider('formLanguages')]
    public function test_filter_form_is_translated_and_preserves_criteria_without_page(string $locale, array $labels): void
    {
        $this->app->setLocale($locale);
        $admin = User::factory()->make(['is_admin' => true]);
        User::factory()->count(11)->create(['is_admin' => true, 'name' => 'Sport Test']);

        $response = $this->actingAs($admin)->get(route('admin.users.index', [
            'search' => 'Sport', 'account_type' => 'admin', 'sort' => 'name', 'direction' => 'desc', 'page' => 2,
        ]));

        $response->assertOk();
        $document = new \DOMDocument;
        $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($document);
        $form = '//form[@role="search"]';
        $this->assertSame('GET', $xpath->evaluate('string('.$form.'/@method)'));
        $this->assertSame(route('admin.users.index'), $xpath->evaluate('string('.$form.'/@action)'));
        $this->assertSame('Sport', $xpath->evaluate('string('.$form.'//input[@name="search"]/@value)'));
        $this->assertSame('name', $xpath->evaluate('string('.$form.'//input[@name="sort"]/@value)'));
        $this->assertSame('desc', $xpath->evaluate('string('.$form.'//input[@name="direction"]/@value)'));
        $this->assertSame(0.0, $xpath->evaluate('count('.$form.'//*[@name="page"])'));
        $this->assertSame(1.0, $xpath->evaluate('count('.$form.'//select[@id="users-account-type" and @name="account_type"])'));
        $this->assertSame($labels['label'], $xpath->evaluate('normalize-space('.$form.'//label[@for="users-account-type"])'));
        $this->assertSame('admin', $xpath->evaluate('string('.$form.'//option[@selected]/@value)'));
        foreach (['all', 'admin', 'user'] as $value) {
            $this->assertSame($labels[$value], $xpath->evaluate('normalize-space('.$form.'//option[@value="'.$value.'"])'));
        }
        $this->assertSame($labels['submit'], $xpath->evaluate('normalize-space('.$form.'//button[@type="submit"])'));
    }

    /** @return array<string, array{0: string, 1: array<string, string>}> */
    public static function formLanguages(): array
    {
        return [
            'polski' => ['pl', ['label' => 'Typ konta', 'all' => 'Wszyscy', 'admin' => 'Administratorzy', 'user' => 'Użytkownicy', 'submit' => 'Zastosuj']],
            'angielski' => ['en', ['label' => 'Account type', 'all' => 'All', 'admin' => 'Administrators', 'user' => 'Users', 'submit' => 'Apply']],
        ];
    }
}
