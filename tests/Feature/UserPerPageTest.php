<?php

namespace Tests\Feature;

use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserPerPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @param array<string, mixed> $parameters */
    #[DataProvider('limits')]
    public function test_applies_only_allowed_limits(array $parameters, int $expected): void
    {
        $admin = User::factory()->make(['is_admin' => true]);
        User::factory()->count(51)->create();

        $response = $this->actingAs($admin)->get(route('admin.users.index', $parameters));

        $response->assertOk()->assertViewHas('perPage', (string) $expected);
        $response->assertViewHas('users', fn (LengthAwarePaginator $users): bool => $users->perPage() === $expected && $users->count() === $expected && $users->total() === 51);
        $xpath = $this->xpath($response->getContent());
        $this->assertSame((float) $expected, $xpath->evaluate('count(//tbody/tr)'));
        $this->assertSame((string) $expected, $xpath->evaluate('string(//select[@name="per_page"]/option[@selected]/@value)'));
    }

    /** @return array<string, array{array<string, mixed>, int}> */
    public static function limits(): array
    {
        return [
            'default' => [[], 10],
            'limit 10' => [['per_page' => '10'], 10],
            'limit 25' => [['per_page' => '25'], 25],
            'limit 50' => [['per_page' => '50'], 50],
            'outside list' => [['per_page' => '999'], 10],
            'zero' => [['per_page' => '0'], 10],
            'negative' => [['per_page' => '-10'], 10],
            'text' => [['per_page' => 'abc'], 10],
            'empty' => [['per_page' => ''], 10],
            'decimal' => [['per_page' => '25.0'], 10],
            'leading zero' => [['per_page' => '025'], 10],
            'array' => [['per_page' => ['25']], 10],
            'nested array' => [['per_page' => [['25']]], 10],
        ];
    }

    public function test_pagination_and_all_list_actions_preserve_limit_and_applied_criteria(): void
    {
        $admin = User::factory()->make(['is_admin' => true]);
        User::factory()->count(26)->create(['name' => 'Sport', 'is_admin' => false]);
        User::factory()->create(['name' => 'Other', 'is_admin' => false]);
        User::factory()->create(['name' => 'Sport admin', 'is_admin' => true]);
        $criteria = ['search' => 'Sport', 'account_type' => 'user', 'sort' => 'id', 'direction' => 'desc', 'per_page' => '25'];

        $response = $this->actingAs($admin)->get(route('admin.users.index', $criteria));

        $response->assertOk();
        $xpath = $this->xpath($response->getContent());
        $next = $xpath->evaluate('string(//a[@rel="next"]/@href)');
        $this->assertSame($criteria + ['page' => '2'], $this->criteria($next));
        $this->get($next)->assertOk()->assertViewHas('users', fn (LengthAwarePaginator $users): bool => $users->count() === 1 && $users->currentPage() === 2 && $users->perPage() === 25);

        $sortUrl = $xpath->evaluate('string((//thead//th/a)[1]/@href)');
        $this->assertSame(['search' => 'Sport', 'account_type' => 'user', 'per_page' => '25', 'sort' => 'name', 'direction' => 'asc'], $this->criteria($sortUrl));
        $this->get($sortUrl)->assertOk()->assertViewHas('perPage', '25')->assertViewHas('sort', 'name');

        $clearSearch = $xpath->evaluate('string(//button[@data-clear-search]/@data-clear-url)');
        $this->assertSame(['account_type' => 'user', 'sort' => 'id', 'direction' => 'desc', 'per_page' => '25'], $this->criteria($clearSearch));
        $this->get($clearSearch)->assertOk()->assertViewHas('search', '')->assertViewHas('perPage', '25');

        $clearFilters = $xpath->evaluate('string(//div[@id="users-filters-panel"]/a/@href)');
        $this->assertSame(['search' => 'Sport', 'sort' => 'id', 'direction' => 'desc', 'per_page' => '25'], $this->criteria($clearFilters));
        $this->get($clearFilters)->assertOk()->assertViewHas('accountType', 'all')->assertViewHas('perPage', '25');

        $searchParameters = $this->formParameters($xpath, '//form[@role="search"]');
        $this->assertSame($criteria, array_replace($criteria, $searchParameters));
        $this->assertCount(count($criteria), $searchParameters);
        $this->get(route('admin.users.index', $searchParameters))->assertOk()->assertViewHas('perPage', '25')->assertViewHas('accountType', 'user');

        $limitParameters = $this->formParameters($xpath, '//form[@data-per-page-select]');
        $this->assertSame($criteria, array_replace($criteria, $limitParameters));
        $this->assertCount(count($criteria), $limitParameters);
        $limitParameters['per_page'] = '10';
        $this->get(route('admin.users.index', $limitParameters))->assertOk()->assertViewHas('search', 'Sport')->assertViewHas('accountType', 'user')->assertViewHas('sort', 'id')->assertViewHas('direction', 'desc')
            ->assertViewHas('users', fn (LengthAwarePaginator $users): bool => $users->currentPage() === 1 && $users->count() === 10 && $users->total() === 26);
    }

    public function test_out_of_range_redirect_preserves_limit_and_criteria(): void
    {
        $admin = User::factory()->make(['is_admin' => true]);
        User::factory()->count(26)->create(['name' => 'Sport', 'is_admin' => false]);
        $criteria = ['search' => 'Sport', 'account_type' => 'user', 'sort' => 'name', 'direction' => 'desc', 'per_page' => '25'];

        $response = $this->actingAs($admin)->get(route('admin.users.index', $criteria + ['page' => 99]));

        $response->assertRedirect(route('admin.users.index', ['page' => 2] + $criteria));
        $this->get($response->headers->get('Location'))->assertOk()->assertViewHas('users', fn (LengthAwarePaginator $users): bool => $users->currentPage() === 2 && $users->count() === 1 && $users->perPage() === 25);
    }

    #[DataProvider('languages')]
    public function test_empty_list_retains_translated_accessible_control(string $locale, string $label, string $hint): void
    {
        $this->app->setLocale($locale);
        $admin = User::factory()->make(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('admin.users.index', ['search' => 'missing', 'per_page' => '50']));

        $response->assertOk()->assertViewHas('users', fn (LengthAwarePaginator $users): bool => $users->total() === 0);
        $xpath = $this->xpath($response->getContent());
        $this->assertSame(1.0, $xpath->evaluate('count(//form[@data-per-page-select])'));
        $this->assertSame($label, $xpath->evaluate('normalize-space(//label[@for="users-per-page"])'));
        $this->assertSame('users-per-page-hint', $xpath->evaluate('string(//select[@id="users-per-page"]/@aria-describedby)'));
        $this->assertSame($hint, $xpath->evaluate('normalize-space(//span[@id="users-per-page-hint"])'));
        $this->assertSame('50', $xpath->evaluate('string(//select[@name="per_page"]/option[@selected]/@value)'));
        $this->assertSame(0.0, $xpath->evaluate('count(//form[@data-per-page-select]/ancestor::form)'));
        $this->assertSame(0.0, $xpath->evaluate('count(//form[@data-per-page-select]//input[@name="page"])'));
    }

    /** @return array<string, array{string, string, string}> */
    public static function languages(): array
    {
        return [
            'Polish' => ['pl', 'Na stronie', 'Zmiana liczby wyników automatycznie odświeża listę.'],
            'English' => ['en', 'Per page', 'Changing the number of results automatically refreshes the list.'],
        ];
    }

    /** @return array<string, string> */
    private function criteria(string $url): array
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $parameters);

        return $parameters;
    }

    /** @return array<string, string> */
    private function formParameters(DOMXPath $xpath, string $form): array
    {
        $parameters = [];
        foreach ($xpath->query($form.'//input | '.$form.'//select') as $field) {
            if (! $field instanceof \DOMElement) {
                throw new \LogicException('Expected a form field element.');
            }

            $parameters[$field->getAttribute('name')] = $field->tagName === 'select'
                ? $xpath->evaluate('string(option[@selected]/@value)', $field)
                : $field->getAttribute('value');
        }

        return $parameters;
    }

    private function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument;
        $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);

        return new DOMXPath($document);
    }
}
