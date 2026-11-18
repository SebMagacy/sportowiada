<?php

namespace Tests\Feature;

use App\Filament\Resources\Seasons\Pages\CreateSeason;
use App\Filament\Resources\Seasons\Pages\EditSeason;
use App\Filament\Resources\Seasons\Pages\ListSeasons;
use App\Filament\Resources\Seasons\SeasonResource;
use App\Models\Season;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FilamentSeasonTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('panel');
    }

    public function test_empty_catalog_displays_guidance_for_creating_first_season(): void
    {
        $this->signInAdmin();
        app()->setLocale('pl');

        Livewire::test(ListSeasons::class)
            ->assertOk()
            ->assertActionHidden('create')
            ->assertSeeText('Dodaj sezon')
            ->assertSeeText('Nie utworzono jeszcze żadnego sezonu.')
            ->assertSeeText(
                'Sezon określa edycję czasową rozgrywek, na przykład 2026/2027. Dodaj pierwszy sezon, aby przypisywać do niego projekty rozgrywek.',
            );
    }

    public function test_search_without_matches_does_not_display_empty_catalog_guidance(): void
    {
        $this->signInAdmin();
        app()->setLocale('pl');
        $season = Season::factory()->create(['name' => '2026/2027']);

        Livewire::test(ListSeasons::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$season])
            ->assertDontSeeText('Nie utworzono jeszcze żadnego sezonu.')
            ->searchTable('zzzzbrakdopasowania')
            ->assertOk()
            ->assertCanNotSeeTableRecords([$season])
            ->assertDontSeeText('Nie utworzono jeszcze żadnego sezonu.')
            ->searchTable('')
            ->assertOk()
            ->assertCanSeeTableRecords([$season])
            ->assertDontSeeText('Nie utworzono jeszcze żadnego sezonu.');
    }

    public function test_guest_cannot_open_any_season_page(): void
    {
        $season = Season::factory()->create();

        foreach (['index', 'create', 'edit'] as $page) {
            $this->get(SeasonResource::getUrl($page, ['record' => $season]))
                ->assertRedirect(route('filament.panel.auth.login'));
        }
    }

    public function test_regular_user_cannot_open_any_season_page(): void
    {
        $season = Season::factory()->create();
        $this->actingAs(User::factory()->create());

        foreach (['index', 'create', 'edit'] as $page) {
            $this->get(SeasonResource::getUrl($page, ['record' => $season]))->assertForbidden();
        }
    }

    public function test_admin_can_open_all_season_pages(): void
    {
        $season = Season::factory()->create();
        $this->signInAdmin();

        foreach (['index', 'create', 'edit'] as $page) {
            $this->get(SeasonResource::getUrl($page, ['record' => $season]))->assertOk();
        }
    }

    public function test_list_displays_ids_and_searches_names(): void
    {
        $this->signInAdmin();
        $matching = Season::factory()->create(['name' => 'Wiosna 2027']);
        $other = Season::factory()->create(['name' => 'Edycja 3']);

        Livewire::test(ListSeasons::class)
            ->assertCanSeeTableRecords([$matching, $other])
            ->assertTableColumnExists('id')
            ->searchTable('Wiosna')
            ->assertCanSeeTableRecords([$matching])
            ->assertCanNotSeeTableRecords([$other]);
    }

    /** @return array<string, array{string}> */
    public static function validNames(): array
    {
        return [
            'two years' => ['2026/2027'],
            'single year' => ['2027'],
            'named period' => ['Wiosna 2027'],
            'edition' => ['Edycja 3'],
        ];
    }

    #[DataProvider('validNames')]
    public function test_admin_can_create_season_and_return_to_list(string $name): void
    {
        $this->signInAdmin();

        Livewire::test(CreateSeason::class)
            ->fillForm(['name' => $name])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(SeasonResource::getUrl('index'));

        $this->assertDatabaseHas('seasons', ['name' => $name]);
        $this->assertDatabaseCount('seasons', 1);
    }

    /** @return array<string, array{string, string}> */
    public static function invalidNames(): array
    {
        return [
            'required' => ['', 'required'],
            'too long' => [str_repeat('a', 256), 'max'],
        ];
    }

    #[DataProvider('invalidNames')]
    public function test_invalid_creation_does_not_save(string $name, string $rule): void
    {
        $this->signInAdmin();

        Livewire::test(CreateSeason::class)
            ->fillForm(['name' => $name])
            ->call('create')
            ->assertHasFormErrors(['name' => $rule])
            ->assertNoRedirect();

        $this->assertDatabaseCount('seasons', 0);
    }

    public function test_duplicate_name_is_rejected_on_creation(): void
    {
        $this->signInAdmin();
        Season::factory()->create(['name' => '2026/2027']);

        Livewire::test(CreateSeason::class)
            ->fillForm(['name' => '2026/2027'])
            ->call('create')
            ->assertHasFormErrors(['name' => 'unique'])
            ->assertNoRedirect();

        $this->assertDatabaseCount('seasons', 1);
    }

    public function test_edit_allows_unchanged_name(): void
    {
        $this->signInAdmin();
        $season = Season::factory()->create(['name' => '2026/2027']);

        Livewire::test(EditSeason::class, ['record' => $season->getRouteKey()])
            ->assertFormSet(['name' => '2026/2027'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('seasons', ['id' => $season->id, 'name' => '2026/2027']);
    }

    public function test_admin_can_rename_season(): void
    {
        $this->signInAdmin();
        $season = Season::factory()->create(['name' => '2026/2027']);

        Livewire::test(EditSeason::class, ['record' => $season->getRouteKey()])
            ->fillForm(['name' => 'Lato 2027'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('seasons', ['id' => $season->id, 'name' => 'Lato 2027']);
    }

    #[DataProvider('invalidNames')]
    public function test_invalid_edit_preserves_name(string $name, string $rule): void
    {
        $this->signInAdmin();
        $season = Season::factory()->create(['name' => '2026/2027']);

        Livewire::test(EditSeason::class, ['record' => $season->getRouteKey()])
            ->fillForm(['name' => $name])
            ->call('save')
            ->assertHasFormErrors(['name' => $rule]);

        $this->assertDatabaseHas('seasons', ['id' => $season->id, 'name' => '2026/2027']);
    }

    public function test_duplicate_name_on_edit_preserves_original_name(): void
    {
        $this->signInAdmin();
        Season::factory()->create(['name' => '2026/2027']);
        $edited = Season::factory()->create(['name' => '2027/2028']);

        Livewire::test(EditSeason::class, ['record' => $edited->getRouteKey()])
            ->fillForm(['name' => '2026/2027'])
            ->call('save')
            ->assertHasFormErrors(['name' => 'unique']);

        $this->assertDatabaseHas('seasons', ['id' => $edited->id, 'name' => '2027/2028']);
        $this->assertDatabaseCount('seasons', 2);
    }

    public function test_admin_can_delete_season(): void
    {
        $this->signInAdmin();
        $season = Season::factory()->create();

        Livewire::test(EditSeason::class, ['record' => $season->getRouteKey()])
            ->callAction('delete')
            ->assertHasNoErrors();

        $this->assertModelMissing($season);
    }

    public function test_bulk_delete_removes_only_selected_seasons(): void
    {
        $this->signInAdmin();
        $selected = Season::factory()->count(2)->create();
        $remaining = Season::factory()->create();

        Livewire::test(ListSeasons::class)
            ->selectTableRecords($selected->modelKeys())
            ->callAction(TestAction::make('delete')->table()->bulk())
            ->assertHasNoErrors();

        foreach ($selected as $season) {
            $this->assertModelMissing($season);
        }
        $this->assertModelExists($remaining);
    }

    private function signInAdmin(): void
    {
        $this->actingAs($this->createPanelAdministrator());
    }
}
