<?php

namespace Tests\Feature;

use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Resources\Roles\RoleResource;
use App\Models\User;
use BezhanSalleh\FilamentShield\Resources\Roles\Pages\CreateRole;
use BezhanSalleh\FilamentShield\Resources\Roles\Pages\EditRole;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FilamentRoleTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_search_without_matches_does_not_display_empty_catalog_guidance(): void
    {
        $this->actingAs($this->createPanelAdministrator());
        Filament::setCurrentPanel('panel');
        app()->setLocale('pl');
        $role = Role::findByName('super_admin', 'web');

        Livewire::test(ListRoles::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$role])
            ->assertActionVisible('create')
            ->assertDontSeeText('Nie utworzono jeszcze żadnej roli.')
            ->searchTable('zzzzbrakdopasowania')
            ->assertOk()
            ->assertCanNotSeeTableRecords([$role])
            ->assertActionVisible('create')
            ->assertDontSeeText('Nie utworzono jeszcze żadnej roli.')
            ->searchTable('')
            ->assertOk()
            ->assertCanSeeTableRecords([$role])
            ->assertActionVisible('create')
            ->assertDontSeeText('Nie utworzono jeszcze żadnej roli.');
    }

    public function test_polish_empty_view_displays_create_link_when_allowed(): void
    {
        app()->setLocale('pl');
        Filament::setCurrentPanel('panel');
        $createUrl = RoleResource::getUrl('create');

        $this->view('filament.roles.empty', ['canCreate' => true, 'createUrl' => $createUrl])
            ->assertSeeText('Nie utworzono jeszcze żadnej roli.')
            ->assertSeeText('Role określają zestawy uprawnień użytkowników. Dodaj rolę, aby przygotować odpowiedni zakres dostępu do funkcji panelu.')
            ->assertSeeText('Dodaj rolę')
            ->assertSee('href="'.$createUrl.'"', false);
    }

    public function test_english_empty_view_displays_create_link_when_allowed(): void
    {
        app()->setLocale('en');
        Filament::setCurrentPanel('panel');
        $createUrl = RoleResource::getUrl('create');

        $this->view('filament.roles.empty', ['canCreate' => true, 'createUrl' => $createUrl])
            ->assertSeeText('No roles have been created yet.')
            ->assertSeeText('Roles define sets of user permissions. Add a role to configure access to panel features.')
            ->assertSeeText('Add role')
            ->assertSee('href="'.$createUrl.'"', false);
    }

    public function test_empty_view_has_no_create_link_without_permission(): void
    {
        app()->setLocale('pl');

        $this->view('filament.roles.empty', ['canCreate' => false, 'createUrl' => '/unavailable'])
            ->assertSeeText('Nie utworzono jeszcze żadnej roli.')
            ->assertDontSee('<a ', false)
            ->assertDontSee('<button', false);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        Filament::setCurrentPanel('panel');

        $this->get(RoleResource::getUrl('index'))
            ->assertRedirect(route('filament.panel.auth.login'));
    }

    public function test_panel_user_without_role_permissions_cannot_open_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('panel_user', 'web'));
        $this->actingAs($user);
        Filament::setCurrentPanel('panel');

        $this->get(RoleResource::getUrl('index'))->assertForbidden();
    }

    public function test_read_only_user_cannot_create_roles(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('panel_user', 'web'));
        $user->givePermissionTo(Permission::findOrCreate('ViewAny:Role', 'web'));
        $this->actingAs($user);
        Filament::setCurrentPanel('panel');

        Livewire::test(ListRoles::class)
            ->assertOk()
            ->assertActionHidden('create');

        $this->get(RoleResource::getUrl('create'))->assertForbidden();
    }

    public function test_administrator_can_open_all_inherited_role_pages(): void
    {
        $this->actingAs($this->createPanelAdministrator());
        Filament::setCurrentPanel('panel');
        $role = Role::findOrCreate('editor', 'web');

        foreach (['index', 'create', 'view', 'edit'] as $page) {
            $this->get(RoleResource::getUrl($page, ['record' => $role]))->assertOk();
        }
    }

    public function test_panel_registers_only_application_role_resource(): void
    {
        $resources = Filament::getPanel('panel')->getResources();

        $this->assertContains(RoleResource::class, $resources);
        $this->assertNotContains(
            \BezhanSalleh\FilamentShield\Resources\Roles\RoleResource::class,
            $resources,
        );
    }

    public function test_administrator_can_create_edit_and_delete_role(): void
    {
        $this->actingAs($this->createPanelAdministrator());
        Filament::setCurrentPanel('panel');

        Livewire::test(CreateRole::class)
            ->fillForm(['name' => 'match_editor', 'guard_name' => 'web'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('roles', ['name' => 'match_editor', 'guard_name' => 'web']);
        $role = Role::findByName('match_editor', 'web');

        Livewire::test(EditRole::class, ['record' => $role->getRouteKey()])
            ->fillForm(['name' => 'result_editor'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'result_editor']);

        Livewire::test(EditRole::class, ['record' => $role->getRouteKey()])
            ->callAction('delete')
            ->assertHasNoErrors();

        $this->assertModelMissing($role);
    }

    public function test_read_only_user_cannot_invoke_bulk_deletion(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('panel_user', 'web'));
        $user->givePermissionTo(Permission::findOrCreate('ViewAny:Role', 'web'));
        $this->actingAs($user);
        Filament::setCurrentPanel('panel');
        $record = Role::findOrCreate('editor', 'web');
        $action = TestAction::make('delete')->table()->bulk();

        Livewire::test(ListRoles::class)
            ->assertOk()
            ->assertActionHidden($action)
            ->selectTableRecords([$record->getKey()])
            ->mountAction($action)
            ->assertActionNotMounted();

        $this->assertModelExists($record);
    }

    public function test_bulk_delete_removes_only_selected_records(): void
    {
        $this->actingAs($this->createPanelAdministrator());
        Filament::setCurrentPanel('panel');
        $selected = new Collection([Role::findOrCreate('editor', 'web'), Role::findOrCreate('organizer', 'web')]);
        $remaining = Role::findOrCreate('viewer', 'web');

        Livewire::test(ListRoles::class)
            ->assertActionVisible('create')
            ->assertActionVisible(TestAction::make('delete')->table()->bulk())
            ->selectTableRecords($selected->modelKeys())
            ->callAction(TestAction::make('delete')->table()->bulk())
            ->assertHasNoErrors();

        foreach ($selected as $record) {
            $this->assertModelMissing($record);
        }
        $this->assertModelExists($remaining);
    }

    public function test_user_with_bulk_delete_permissions_can_delete_selected_record(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('panel_user', 'web'));
        foreach (['ViewAny', 'DeleteAny', 'Delete'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission.':Role', 'web'));
        }
        $this->actingAs($user);
        Filament::setCurrentPanel('panel');
        $record = Role::findOrCreate('editor', 'web');

        Livewire::test(ListRoles::class)
            ->assertActionHidden('create')
            ->assertActionVisible(TestAction::make('delete')->table()->bulk())
            ->selectTableRecords([$record->getKey()])
            ->callAction(TestAction::make('delete')->table()->bulk())
            ->assertHasNoErrors();

        $this->assertModelMissing($record);
    }
}
