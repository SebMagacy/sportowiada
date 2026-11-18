<?php

namespace Tests\Feature;

use App\Filament\Resources\Leagues\LeagueResource;
use App\Filament\Resources\Leagues\Pages\CreateLeague;
use App\Filament\Resources\Leagues\Pages\EditLeague;
use App\Filament\Resources\Leagues\Pages\ListLeagues;
use App\Models\Discipline;
use App\Models\League;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FilamentLeagueTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('panel');
    }

    public function test_empty_catalog_displays_guidance_for_creating_first_league(): void
    {
        $this->actingAs($this->createPanelAdministrator());
        app()->setLocale('pl');

        Livewire::test(ListLeagues::class)
            ->assertOk()
            ->assertActionHidden('create')
            ->assertSeeText('Dodaj ligę')
            ->assertSeeText('Nie utworzono jeszcze żadnej ligi.')
            ->assertSeeText(
                'Liga porządkuje rozgrywki w wybranej dyscyplinie sportu. Dodaj ją do katalogu, aby później tworzyć jej edycje w kolejnych sezonach.',
            );
    }

    public function test_english_empty_catalog_links_to_creation_and_changes_to_table_after_first_record(): void
    {
        $this->actingAs($this->createPanelAdministrator());
        app()->setLocale('en');

        $page = Livewire::test(ListLeagues::class)
            ->assertOk()
            ->assertActionHidden('create')
            ->assertSeeText('No leagues have been created yet.')
            ->assertSeeText('A league organizes competitions within a sport. Add it to the catalog to create editions for individual seasons later.')
            ->assertSeeText('Add league')
            ->assertSee('href="'.LeagueResource::getUrl('create').'"', false);

        $record = League::factory()->create();

        $page->refresh()
            ->assertOk()
            ->assertCanSeeTableRecords([$record])
            ->assertActionVisible('create')
            ->assertDontSeeText('No leagues have been created yet.');
    }

    public function test_search_without_matches_does_not_display_empty_catalog_guidance(): void
    {
        $this->actingAs($this->createPanelAdministrator());
        app()->setLocale('pl');
        $league = League::factory()->create(['name' => 'Liga halowa']);

        Livewire::test(ListLeagues::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$league])
            ->assertDontSeeText('Nie utworzono jeszcze żadnej ligi.')
            ->searchTable('zzzzbrakdopasowania')
            ->assertOk()
            ->assertCanNotSeeTableRecords([$league])
            ->assertDontSeeText('Nie utworzono jeszcze żadnej ligi.')
            ->searchTable('')
            ->assertOk()
            ->assertCanSeeTableRecords([$league])
            ->assertDontSeeText('Nie utworzono jeszcze żadnej ligi.');
    }

    public function test_super_admin_can_create_league_without_description(): void
    {
        $this->actingAs($this->createPanelAdministrator());
        $discipline = Discipline::factory()->create();

        Livewire::test(CreateLeague::class)
            ->fillForm([
                'name' => 'Liga testowa',
                'discipline_id' => $discipline->id,
                'description' => null,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('leagues', [
            'name' => 'Liga testowa',
            'discipline_id' => $discipline->id,
            'description' => null,
        ]);
        $this->assertDatabaseCount('leagues', 1);
    }

    public function test_duplicate_name_in_same_discipline_is_rejected(): void
    {
        $this->actingAs($this->createPanelAdministrator());
        $league = League::factory()->create([
            'name' => 'Liga testowa',
        ]);

        Livewire::test(CreateLeague::class)
            ->fillForm([
                'name' => 'Liga testowa',
                'discipline_id' => $league->discipline_id,
                'description' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => 'unique'])
            ->assertNoRedirect();

        $this->assertDatabaseCount('leagues', 1);
    }

    public function test_same_name_is_allowed_in_different_disciplines(): void
    {
        $this->actingAs($this->createPanelAdministrator());
        $existingLeague = League::factory()->create([
            'name' => 'Liga testowa',
        ]);
        $otherDiscipline = Discipline::factory()->create();

        Livewire::test(CreateLeague::class)
            ->fillForm([
                'name' => 'Liga testowa',
                'discipline_id' => $otherDiscipline->id,
                'description' => null,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('leagues', [
            'id' => $existingLeague->id,
            'name' => 'Liga testowa',
            'discipline_id' => $existingLeague->discipline_id,
        ]);
        $this->assertDatabaseHas('leagues', [
            'name' => 'Liga testowa',
            'discipline_id' => $otherDiscipline->id,
        ]);
        $this->assertDatabaseCount('leagues', 2);
    }

    public function test_edit_allows_unchanged_name_and_saves_description(): void
    {
        $this->actingAs($this->createPanelAdministrator());
        $league = League::factory()->create([
            'name' => 'Liga testowa',
            'description' => null,
        ]);

        Livewire::test(EditLeague::class, [
            'record' => $league->getRouteKey(),
        ])
            ->assertFormSet([
                'name' => 'Liga testowa',
                'discipline_id' => $league->discipline_id,
            ])
            ->fillForm([
                'description' => 'Opis testowy ligi futsalowej',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertRedirect(LeagueResource::getUrl('index'));

        $this->assertDatabaseHas('leagues', [
            'id' => $league->id,
            'name' => 'Liga testowa',
            'discipline_id' => $league->discipline_id,
            'description' => 'Opis testowy ligi futsalowej',
        ]);
        $this->assertDatabaseCount('leagues', 1);
    }

    public function test_edit_rejects_duplicate_after_changing_discipline(): void
    {
        $this->actingAs($this->createPanelAdministrator());
        $existingLeague = League::factory()->create([
            'name' => 'Liga testowa',
        ]);
        $editedLeague = League::factory()->create([
            'name' => 'Liga testowa',
        ]);

        Livewire::test(EditLeague::class, [
            'record' => $editedLeague->getRouteKey(),
        ])
            ->fillForm([
                'discipline_id' => $existingLeague->discipline_id,
            ])
            ->call('save')
            ->assertHasFormErrors(['name' => 'unique']);

        $this->assertDatabaseHas('leagues', [
            'id' => $editedLeague->id,
            'name' => 'Liga testowa',
            'discipline_id' => $editedLeague->discipline_id,
        ]);
        $this->assertDatabaseCount('leagues', 2);
    }

    public function test_name_and_discipline_are_required(): void
    {
        $this->actingAs($this->createPanelAdministrator());

        Livewire::test(CreateLeague::class)
            ->fillForm([
                'name' => '',
                'discipline_id' => null,
                'description' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'name' => 'required',
                'discipline_id' => 'required',
            ])
            ->assertNoRedirect();

        $this->assertDatabaseCount('leagues', 0);
    }

    public function test_name_of_255_characters_and_multiline_description_are_saved(): void
    {
        $this->actingAs($this->createPanelAdministrator());
        $discipline = Discipline::factory()->create();
        $name = str_repeat('ą', 255);
        $description = "Pierwszy wiersz.\nDrugi wiersz.";

        Livewire::test(CreateLeague::class)
            ->fillForm(['name' => $name, 'discipline_id' => $discipline->id, 'description' => $description])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('leagues', ['name' => $name, 'discipline_id' => $discipline->id, 'description' => $description]);
        Livewire::test(EditLeague::class, ['record' => League::query()->sole()->getRouteKey()])
            ->assertFormSet(['name' => $name, 'description' => $description]);
    }

    /** @return array<string, array{array<string, mixed>, array<string, string>|list<string>}> */
    public static function invalidInputs(): array
    {
        return [
            'name too long' => [['name' => str_repeat('ą', 256)], ['name' => 'max']],
            'discipline does not exist' => [['discipline_id' => 999999], ['discipline_id']],
        ];
    }

    #[DataProvider('invalidInputs')]
    public function test_invalid_creation_does_not_save_league(array $overrides, array $errors): void
    {
        $this->actingAs($this->createPanelAdministrator());
        $discipline = Discipline::factory()->create();
        $data = [...['name' => 'Nowa liga', 'discipline_id' => $discipline->id], ...$overrides];

        Livewire::test(CreateLeague::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasFormErrors($errors)
            ->assertNoRedirect();

        $this->assertDatabaseCount('leagues', 0);
    }

    /** @return array<string, array{array<string, mixed>, array<string, string>|list<string>}> */
    public static function invalidEdits(): array
    {
        return [
            'empty name' => [['name' => ''], ['name' => 'required']],
            'name too long' => [['name' => str_repeat('ą', 256)], ['name' => 'max']],
            'empty discipline' => [['discipline_id' => null], ['discipline_id' => 'required']],
            'nonexistent discipline' => [['discipline_id' => 999999], ['discipline_id']],
        ];
    }

    #[DataProvider('invalidEdits')]
    public function test_invalid_edit_preserves_saved_data(array $data, array $errors): void
    {
        $this->actingAs($this->createPanelAdministrator());
        $league = League::factory()->create(['name' => 'Oryginalna liga', 'description' => 'Oryginalny opis']);

        Livewire::test(EditLeague::class, ['record' => $league->getRouteKey()])
            ->fillForm([...$data, 'description' => 'Nie zapisuj tego opisu'])
            ->call('save')
            ->assertHasFormErrors($errors);

        $this->assertDatabaseHas('leagues', [
            'id' => $league->id,
            'name' => 'Oryginalna liga',
            'discipline_id' => $league->discipline_id,
            'description' => 'Oryginalny opis',
        ]);
        $this->assertDatabaseCount('leagues', 1);
    }

    public function test_edit_saves_new_name_and_discipline_and_clears_description(): void
    {
        $this->actingAs($this->createPanelAdministrator());
        $league = League::factory()->create(['description' => 'Stary opis']);
        $discipline = Discipline::factory()->create();
        $name = str_repeat('b', 255);

        Livewire::test(EditLeague::class, ['record' => $league->getRouteKey()])
            ->fillForm(['name' => $name, 'discipline_id' => $discipline->id, 'description' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('leagues', [
            'id' => $league->id,
            'name' => $name,
            'discipline_id' => $discipline->id,
            'description' => null,
        ]);
        $this->assertDatabaseCount('leagues', 1);
    }

    public function test_edit_rejects_name_of_another_league_in_same_discipline(): void
    {
        $this->actingAs($this->createPanelAdministrator());
        $existing = League::factory()->create(['name' => 'Zajęta nazwa']);
        $edited = League::factory()->for($existing->discipline)->create(['name' => 'Oryginalna nazwa']);

        Livewire::test(EditLeague::class, ['record' => $edited->getRouteKey()])
            ->fillForm(['name' => 'Zajęta nazwa'])
            ->call('save')
            ->assertHasFormErrors(['name' => 'unique']);

        $this->assertDatabaseHas('leagues', ['id' => $edited->id, 'name' => 'Oryginalna nazwa', 'discipline_id' => $edited->discipline_id]);
        $this->assertDatabaseCount('leagues', 2);
    }

    public function test_list_displays_and_searches_league_and_discipline_names(): void
    {
        $this->actingAs($this->createPanelAdministrator());
        $futsal = Discipline::factory()->create(['name' => 'Futsal']);
        $basketball = Discipline::factory()->create(['name' => 'Koszykówka']);
        $first = League::factory()->for($futsal)->create(['name' => 'Liga halowa']);
        $second = League::factory()->for($basketball)->create(['name' => 'Liga koszykarska']);

        Livewire::test(ListLeagues::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$first, $second])
            ->assertSeeText('Futsal')
            ->assertSeeText('Koszykówka')
            ->searchTable('halowa')
            ->assertCanSeeTableRecords([$first])
            ->assertCanNotSeeTableRecords([$second])
            ->searchTable('Koszykówka')
            ->assertCanSeeTableRecords([$second])
            ->assertCanNotSeeTableRecords([$first]);
    }

    public function test_delete_waits_for_confirmation_and_can_be_cancelled(): void
    {
        $this->actingAs($this->createPanelAdministrator());
        $league = League::factory()->create();

        $page = Livewire::test(EditLeague::class, ['record' => $league->getRouteKey()])
            ->mountAction('delete')
            ->assertActionMounted('delete')
            ->assertSeeText('Anuluj');

        $this->assertModelExists($league);
        $page->call('unmountAction')->assertActionNotMounted();
        $this->assertModelExists($league);
    }

    public function test_super_admin_can_confirm_deletion(): void
    {
        $this->actingAs($this->createPanelAdministrator());
        $league = League::factory()->create();
        $discipline = $league->discipline;

        Livewire::test(EditLeague::class, ['record' => $league->getRouteKey()])
            ->mountAction('delete')
            ->callMountedAction()
            ->assertHasNoErrors()
            ->assertRedirect(LeagueResource::getUrl('index'));

        $this->assertModelMissing($league);
        $this->assertModelExists($discipline);
    }

    public function test_guest_is_redirected_from_all_league_pages(): void
    {
        $league = League::factory()->create();

        foreach (['index', 'create', 'edit'] as $page) {
            $this->get(LeagueResource::getUrl($page, ['record' => $league]))
                ->assertRedirect(route('filament.panel.auth.login'));
        }
    }

    /** @return array<string, array{?string}> */
    public static function deniedRoles(): array
    {
        return ['no role' => [null], 'panel user' => ['panel_user']];
    }

    #[DataProvider('deniedRoles')]
    public function test_users_without_super_admin_cannot_open_league_pages(?string $role): void
    {
        $user = User::factory()->create();
        if ($role !== null) {
            $user->assignRole(Role::findOrCreate($role, 'web'));
        }
        $this->actingAs($user);
        $league = League::factory()->create();

        foreach (['index', 'create', 'edit'] as $page) {
            $this->get(LeagueResource::getUrl($page, ['record' => $league]))->assertForbidden();
        }
    }

    public function test_super_admin_can_open_all_league_pages(): void
    {
        $this->actingAs($this->createPanelAdministrator());
        $league = League::factory()->create();

        foreach (['index', 'create', 'edit'] as $page) {
            $this->get(LeagueResource::getUrl($page, ['record' => $league]))->assertOk();
        }
    }

    /** @return array<string, array{?string, bool}> */
    public static function policyRoles(): array
    {
        return ['super admin' => ['super_admin', true], 'panel user' => ['panel_user', false], 'no role' => [null, false]];
    }

    #[DataProvider('policyRoles')]
    public function test_only_super_admin_is_authorized_for_league_operations(?string $role, bool $allowed): void
    {
        $user = User::factory()->create();
        if ($role !== null) {
            $user->assignRole(Role::findOrCreate($role, 'web'));
        }
        $league = League::factory()->create();

        foreach (['viewAny', 'create'] as $ability) {
            $this->assertSame($allowed, Gate::forUser($user)->allows($ability, League::class), $ability);
        }
        foreach (['view', 'update', 'delete'] as $ability) {
            $this->assertSame($allowed, Gate::forUser($user)->allows($ability, $league), $ability);
        }
        foreach (['restore', 'forceDelete'] as $ability) {
            $this->assertFalse(Gate::forUser($user)->allows($ability, $league), $ability);
        }
    }

    public function test_panel_user_with_explicit_league_permissions_still_cannot_manage_leagues(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('panel_user', 'web'));
        foreach (['ViewAny', 'View', 'Create', 'Update', 'Delete'] as $ability) {
            $user->givePermissionTo(Permission::findOrCreate($ability.':League', 'web'));
        }
        $league = League::factory()->create();

        foreach (['viewAny', 'create'] as $ability) {
            $this->assertFalse(Gate::forUser($user)->allows($ability, League::class));
        }
        foreach (['view', 'update', 'delete'] as $ability) {
            $this->assertFalse(Gate::forUser($user)->allows($ability, $league));
        }
        $this->actingAs($user);
        Livewire::test(CreateLeague::class)->assertForbidden();
        Livewire::test(EditLeague::class, ['record' => $league->getRouteKey()])->assertForbidden();
        $this->assertModelExists($league);
        $this->assertDatabaseCount('leagues', 1);
    }
}
