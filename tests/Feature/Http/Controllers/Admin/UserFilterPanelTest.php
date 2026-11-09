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

class UserFilterPanelTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @param array<string, string|array<int, string>> $criteria */
    #[DataProvider('filterCounts')]
    public function test_counter_displays_only_applied_account_filters(array $criteria, int $expectedCount): void
    {
        $admin = User::factory()->make(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('admin.users.index', $criteria));

        $response->assertOk()->assertViewHas('activeFiltersCount', $expectedCount);
        $this->assertSame(
            $expectedCount === 0 ? '' : '(1)',
            $this->xpath($response->getContent())->evaluate('normalize-space(//button[@id="users-filters-toggle"]/span)'),
        );
    }

    /** @return array<string, array{0: array<string, string|array<int, string>>, 1: int}> */
    public static function filterCounts(): array
    {
        return [
            'brak parametrów' => [[], 0],
            'wszyscy' => [['account_type' => 'all'], 0],
            'administratorzy' => [['account_type' => 'admin'], 1],
            'użytkownicy' => [['account_type' => 'user'], 1],
            'nieznany typ' => [['account_type' => 'abc'], 0],
            'pusty typ' => [['account_type' => ''], 0],
            'tablica zamiast typu' => [['account_type' => ['admin']], 0],
            'sama fraza' => [['search' => 'Sport'], 0],
            'fraza i wszyscy' => [['search' => 'Sport', 'account_type' => 'all'], 0],
            'fraza i typ konta' => [['search' => 'Sport', 'account_type' => 'user'], 1],
        ];
    }

    /** @param array<string, string> $labels */
    #[DataProvider('panelLanguages')]
    public function test_panel_starts_hidden_and_has_accessible_translated_controls(string $locale, array $labels): void
    {
        $this->app->setLocale($locale);
        $admin = User::factory()->make(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('admin.users.index', ['account_type' => 'admin']));

        $response->assertOk();
        $xpath = $this->xpath($response->getContent());
        $panel = '//form[@role="search"]//div[@id="users-filters-panel"]';
        $toggle = '//form[@role="search"]//button[@id="users-filters-toggle"]';
        $this->assertSame(1.0, $xpath->evaluate('count('.$panel.'[@hidden])'));
        $this->assertSame('button', $xpath->evaluate('string('.$toggle.'/@type)'));
        $this->assertSame('false', $xpath->evaluate('string('.$toggle.'/@aria-expanded)'));
        $this->assertSame('users-filters-panel', $xpath->evaluate('string('.$toggle.'/@aria-controls)'));
        $this->assertSame('true', $xpath->evaluate('string('.$toggle.'/svg/@aria-hidden)'));
        $this->assertSame('(1) '.$labels['filters'], $xpath->evaluate('normalize-space('.$toggle.')'));
        $this->assertSame($labels['apply'], $xpath->evaluate('normalize-space('.$panel.'//button[@type="submit"])'));
        $this->assertSame($labels['clear'], $xpath->evaluate('normalize-space('.$panel.'//a)'));
        $this->assertSame(1.0, $xpath->evaluate('count('.$panel.'//select[@name="account_type" and not(@disabled)])'));
        $this->assertSame(1.0, $xpath->evaluate('count('.$panel.'/following-sibling::div//input[@name="search"])'));
        $this->assertSame(0.0, $xpath->evaluate('count('.$panel.'//input[@name="search"])'));
    }

    /** @return array<string, array{0: string, 1: array<string, string>}> */
    public static function panelLanguages(): array
    {
        return [
            'polski' => ['pl', ['filters' => 'Filtry', 'apply' => 'Zastosuj', 'clear' => 'Wyczyść filtry']],
            'angielski' => ['en', ['filters' => 'Filters', 'apply' => 'Apply', 'clear' => 'Clear filters']],
        ];
    }

    #[DataProvider('clearCriteria')]
    public function test_clear_link_restores_all_accounts_and_first_page_preserving_search_and_sorting(string $accountType, string $search, ?string $sort, ?string $direction): void
    {
        $admin = User::factory()->make(['is_admin' => true]);
        User::factory()->count(11)->sequence(
            fn (Sequence $sequence): array => ['name' => sprintf('Sport Admin %02d', $sequence->index)],
        )->create(['is_admin' => true]);
        User::factory()->count(11)->sequence(
            fn (Sequence $sequence): array => ['name' => sprintf('Sport User %02d', $sequence->index)],
        )->create(['is_admin' => false]);
        User::factory()->create(['name' => 'Other Account', 'is_admin' => false]);

        $response = $this->actingAs($admin)->get(route('admin.users.index', [
            'account_type' => $accountType, 'search' => $search, 'sort' => $sort, 'direction' => $direction, 'page' => 2,
        ]));

        $response->assertOk();
        $href = $this->xpath($response->getContent())->evaluate('string(//div[@id="users-filters-panel"]//a/@href)');
        $this->assertSame(route('admin.users.index', ['search' => $search, 'sort' => $sort, 'direction' => $direction]), $href);
        $cleared = $this->get($href);
        $cleared->assertOk()->assertViewHas('accountType', 'all')->assertViewHas('activeFiltersCount', 0)
            ->assertViewHas('search', $search)->assertViewHas('sort', $sort)->assertViewHas('direction', $direction);
        $cleared->assertViewHas('users', function (LengthAwarePaginator $users) use ($search): bool {
            return $users->currentPage() === 1 && $users->total() === ($search === '' ? 23 : 22);
        });
        $xpath = $this->xpath($cleared->getContent());
        $this->assertSame('all', $xpath->evaluate('string(//select[@name="account_type"]/option[@selected]/@value)'));
        $this->assertSame('', $xpath->evaluate('normalize-space(//button[@id="users-filters-toggle"]/span)'));
    }

    /** @return array<string, array{0: string, 1: string, 2: string|null, 3: string|null}> */
    public static function clearCriteria(): array
    {
        return [
            'użytkownicy z frazą i sortowaniem' => ['user', 'Sport', 'name', 'desc'],
            'administratorzy z frazą i sortowaniem' => ['admin', 'Sport', 'name', 'asc'],
            'bez sortowania' => ['user', 'Sport', null, null],
            'bez frazy' => ['admin', '', 'email', 'asc'],
        ];
    }

    public function test_search_phrase_is_escaped_in_clear_link(): void
    {
        $admin = User::factory()->make(['is_admin' => true]);
        $search = '"><script>alert(1)</script>&sort=id';

        $response = $this->actingAs($admin)->get(route('admin.users.index', [
            'search' => $search, 'account_type' => 'user', 'sort' => 'name', 'direction' => 'desc',
        ]));

        $response->assertOk()->assertDontSee('<script>alert(1)</script>', false);
        $xpath = $this->xpath($response->getContent());
        $this->assertSame($search, $xpath->evaluate('string(//input[@name="search"]/@value)'));
        $this->assertSame(
            route('admin.users.index', ['search' => $search, 'sort' => 'name', 'direction' => 'desc']),
            $xpath->evaluate('string(//div[@id="users-filters-panel"]//a/@href)'),
        );
    }

    private function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument;
        $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);

        return new DOMXPath($document);
    }
}
