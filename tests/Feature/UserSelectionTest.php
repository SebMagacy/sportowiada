<?php

namespace Tests\Feature;

use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserSelectionTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[DataProvider('locales')]
    public function test_selection_controls_are_localized_and_names_are_escaped(string $locale, string $pageLabel, string $rowLabel, string $countLabel, string $clearLabel): void
    {
        $this->app->setLocale($locale);
        $name = '\"><script>alert("xss")</script>';
        $admin = User::factory()->create(['is_admin' => true, 'name' => $name]);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk()->assertDontSee('<script>alert("xss")</script>', false);
        $xpath = $this->xpath($response->getContent());
        $this->assertSame(1, $xpath->query('//*[@data-row-selection]')->length);
        $this->assertSame($pageLabel, $xpath->evaluate('string(//input[@data-select-page]/@aria-label)'));
        $this->assertSame(str_replace([':name', ':id'], [$name, (string) $admin->id], $rowLabel), $xpath->evaluate('string(//input[@data-select-user]/@aria-label)'));
        $this->assertSame((string) $admin->id, $xpath->evaluate('string(//input[@data-select-user]/@value)'));
        $this->assertSame(0, $xpath->query('//input[@data-select-user][@checked]')->length);
        $this->assertSame(1, $xpath->query('//*[@data-row-selection]//*[@data-selection-toolbar][@hidden]')->length);
        $this->assertSame($countLabel.' 0', $xpath->evaluate('normalize-space(//*[@data-selection-toolbar]//*[@role="status"])'));
        $this->assertSame($clearLabel, $xpath->evaluate('normalize-space(//button[@data-clear-selection])'));
        $this->assertSame('button', $xpath->evaluate('string(//button[@data-clear-selection]/@type)'));
        $this->assertSame(6, $xpath->query('//table/thead/tr/th')->length);
        $this->assertSame(6, $xpath->query('//table/tbody/tr[1]/td')->length);
    }

    public function test_only_users_on_the_current_page_have_selection_controls(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $users = User::factory()->count(10)->create();

        $response = $this->actingAs($admin)->get(route('admin.users.index', ['page' => 2]));

        $response->assertOk();
        $xpath = $this->xpath($response->getContent());
        $this->assertSame(1, $xpath->query('//input[@data-select-user]')->length);
        $this->assertSame((string) $users[9]->id, $xpath->evaluate('string(//input[@data-select-user]/@value)'));
    }

    public function test_empty_results_keep_the_header_disabled_and_message_spans_every_column(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('admin.users.index', ['search' => 'no-matching-account-987654']));

        $response->assertOk()->assertSeeText(__('users.no_results'));
        $xpath = $this->xpath($response->getContent());
        $this->assertSame(0, $xpath->query('//input[@data-select-user]')->length);
        $this->assertSame(1, $xpath->query('//input[@data-select-page][@disabled]')->length);
        $this->assertSame('6', $xpath->evaluate('string(//table/tbody/tr/td/@colspan)'));
        $this->assertSame(1, $xpath->query('//*[@data-selection-toolbar][@hidden]')->length);
    }

    /** @return array<string, array{string, string, string, string, string}> */
    public static function locales(): array
    {
        return [
            'polish' => ['pl', 'Zaznacz wszystkich użytkowników na tej stronie', 'Zaznacz użytkownika: :name (ID: :id)', 'Zaznaczono:', 'Wyczyść zaznaczenie'],
            'english' => ['en', 'Select all users on this page', 'Select user: :name (ID: :id)', 'Selected:', 'Clear selection'],
        ];
    }

    private function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);

        return new DOMXPath($document);
    }
}
