<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserClearSearchTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @param list<int> $expectedOrder */
    #[DataProvider('clearCriteria')]
    public function test_clear_address_restores_first_page_without_search_preserving_applied_criteria(string $accountType, ?string $sort, ?string $direction, array $expectedOrder): void
    {
        $admin = User::factory()->make(['is_admin' => true]);
        $accounts = User::factory()->count(11)->sequence(
            fn (Sequence $sequence): array => [
                'name' => sprintf('Sport %02d', $sequence->index),
                'email' => sprintf('sport%02d@example.com', $sequence->index),
            ],
        )->create(['is_admin' => $accountType === 'admin']);
        $accounts->push(User::factory()->create([
            'name' => 'A Outside', 'email' => 'a-outside@example.com', 'is_admin' => $accountType === 'admin',
        ]));
        $accounts->push(User::factory()->create([
            'name' => 'Z Other Type', 'email' => 'z-other@example.com', 'is_admin' => $accountType !== 'admin',
        ]));

        $response = $this->actingAs($admin)->get(route('admin.users.index', [
            'search' => 'Sport', 'page' => 2, 'account_type' => $accountType, 'sort' => $sort, 'direction' => $direction,
        ]));

        $response->assertOk();
        $address = $this->xpath($response->getContent())->evaluate('string(//button[@data-clear-search]/@data-clear-url)');
        $this->assertIsString($address);
        parse_str((string) parse_url($address, PHP_URL_QUERY), $criteria);
        $this->assertSame($accountType, $criteria['account_type']);
        $this->assertSame($sort, $criteria['sort'] ?? null);
        $this->assertSame($direction, $criteria['direction'] ?? null);
        $this->assertArrayNotHasKey('search', $criteria);
        $this->assertArrayNotHasKey('page', $criteria);

        $cleared = $this->get($address);
        $cleared->assertOk()->assertViewHas('search', '')->assertViewHas('accountType', $accountType)
            ->assertViewHas('sort', $sort)->assertViewHas('direction', $direction)
            ->assertViewHas('activeFiltersCount', $accountType === 'all' ? 0 : 1);
        $cleared->assertViewHas('users', function (LengthAwarePaginator $users) use ($accounts, $expectedOrder): bool {
            $expectedIds = array_map(fn (int $index): int => $accounts[$index]->id, array_slice($expectedOrder, 0, 10));

            return $users->currentPage() === 1
                && $users->total() === count($expectedOrder)
                && $users->getCollection()->pluck('id')->all() === $expectedIds;
        });
        $this->assertSame('', $this->xpath($cleared->getContent())->evaluate('string(//input[@name="search"]/@value)'));
    }

    /** @return array<string, array{string, string|null, string|null, list<int>}> */
    public static function clearCriteria(): array
    {
        return [
            'wszyscy, imię rosnąco' => ['all', 'name', 'asc', [11, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 12]],
            'użytkownicy, imię malejąco' => ['user', 'name', 'desc', [10, 9, 8, 7, 6, 5, 4, 3, 2, 1, 0, 11]],
            'administratorzy, domyślna kolejność' => ['admin', null, null, [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11]],
            'użytkownicy, email rosnąco' => ['user', 'email', 'asc', [11, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]],
        ];
    }

    #[DataProvider('languages')]
    public function test_clear_control_has_translated_accessible_label_outside_closed_filter_panel(string $locale, string $label): void
    {
        $this->app->setLocale($locale);
        $admin = User::factory()->make(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('admin.users.index', ['search' => 'Sport']));

        $response->assertOk();
        $xpath = $this->xpath($response->getContent());
        $button = '//form[@role="search"]//button[@data-clear-search]';
        $this->assertSame(1.0, $xpath->evaluate('count('.$button.')'));
        $this->assertSame('button', $xpath->evaluate('string('.$button.'/@type)'));
        $this->assertSame($label, $xpath->evaluate('string('.$button.'/@aria-label)'));
        $this->assertSame('true', $xpath->evaluate('string('.$button.'/svg/@aria-hidden)'));
        $this->assertSame(1.0, $xpath->evaluate('count(//div[@id="users-filters-panel" and @hidden])'));
        $this->assertSame(0.0, $xpath->evaluate('count(//div[@id="users-filters-panel"]//button[@data-clear-search])'));
    }

    /** @return array<string, array{string, string}> */
    public static function languages(): array
    {
        return ['polski' => ['pl', 'Wyczyść wyszukiwanie'], 'angielski' => ['en', 'Clear search']];
    }

    #[DataProvider('initialPhrases')]
    public function test_clear_control_initial_visibility_matches_normalized_search(string $search, bool $hidden): void
    {
        $admin = User::factory()->make(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('admin.users.index', ['search' => $search]));

        $response->assertOk();
        $xpath = $this->xpath($response->getContent());
        $this->assertSame(1.0, $xpath->evaluate('count(//button[@data-clear-search])'));
        $this->assertSame($hidden ? 1.0 : 0.0, $xpath->evaluate('count(//button[@data-clear-search and @hidden])'));
    }

    /** @return array<string, array{string, bool}> */
    public static function initialPhrases(): array
    {
        return ['pusta' => ['', true], 'białe znaki' => ['   ', true], 'fraza' => ['Sport', false]];
    }

    #[DataProvider('specialPhrases')]
    public function test_special_search_characters_are_preserved_safely_in_field_and_omitted_from_clear_address(string $search): void
    {
        $admin = User::factory()->make(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('admin.users.index', [
            'search' => $search, 'account_type' => 'user', 'sort' => 'name', 'direction' => 'desc',
        ]));

        $response->assertOk()->assertDontSee('<script>alert(1)</script>', false);
        $xpath = $this->xpath($response->getContent());
        $this->assertSame($search, $xpath->evaluate('string(//input[@name="search"]/@value)'));
        $address = $xpath->evaluate('string(//button[@data-clear-search]/@data-clear-url)');
        $this->assertSame(route('admin.users.index', [
            'account_type' => 'user', 'sort' => 'name', 'direction' => 'desc', 'per_page' => '10',
        ]), $address);
    }

    /** @return array<string, array{string}> */
    public static function specialPhrases(): array
    {
        return [
            'polskie znaki i parametry' => ['Zażółć gęślą & + ? # ='],
            'próba wstrzyknięcia HTML' => ['"><script>alert(1)</script>&sort=id'],
        ];
    }

    private function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument;
        $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);

        return new DOMXPath($document);
    }
}
