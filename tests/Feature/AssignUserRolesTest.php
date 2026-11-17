<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AssignUserRolesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_administrator_can_assign_and_remove_roles(): void
    {
        $this->actingAs($this->createPanelAdministrator());
        Filament::setCurrentPanel('panel');
        $target = User::factory()->create();
        Role::findOrCreate('panel_user', 'web');

        Livewire::test(ListUsers::class)
            ->callAction(TestAction::make('assignRoles')->table($target), data: ['roles' => ['panel_user']])
            ->assertHasNoActionErrors()->assertNotified('Role użytkownika zostały zapisane');

        $this->assertTrue($target->fresh()->hasRole('panel_user'));

        Livewire::test(ListUsers::class)
            ->mountAction(TestAction::make('assignRoles')->table($target))
            ->assertSchemaStateSet(['roles' => ['panel_user']])
            ->fillForm(['roles' => []])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $this->assertSame(0, $target->fresh()->roles()->count());
    }

    public function test_user_with_list_and_update_permissions_cannot_assign_roles(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('panel_user', 'web'));
        foreach (['ViewAny:User', 'Update:User'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $target = User::factory()->create();
        Role::findOrCreate('super_admin', 'web');
        $this->actingAs($user);
        Filament::setCurrentPanel('panel');

        $this->assertFalse(Gate::allows('assignRoles', $target));
        Livewire::test(ListUsers::class)
            ->assertActionHidden(TestAction::make('assignRoles')->table($target))
            ->call('mountAction', 'assignRoles', [], ['table' => true, 'recordKey' => $target->getKey()])
            ->assertActionNotMounted();

        $this->assertSame(0, $target->fresh()->roles()->count());
    }

    public function test_roles_column_displays_assignments_and_empty_state(): void
    {
        $this->actingAs($this->createPanelAdministrator());
        Filament::setCurrentPanel('panel');
        $target = User::factory()->create();
        $target->assignRole(Role::findOrCreate('panel_user', 'web'));
        User::factory()->create();

        Livewire::test(ListUsers::class)
            ->assertSee('panel_user')->assertSee('Brak ról')->assertDontSee('Typ konta');
    }
}
