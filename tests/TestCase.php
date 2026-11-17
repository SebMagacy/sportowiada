<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    protected function createPanelAdministrator(): User
    {
        $role = Role::findOrCreate('super_admin', 'web');
        foreach (['User', 'Role', 'Discipline', 'Season'] as $subject) {
            foreach (['ViewAny', 'View', 'Create', 'Update', 'Delete', 'DeleteAny', 'Restore', 'RestoreAny', 'ForceDelete', 'ForceDeleteAny', 'Replicate', 'Reorder'] as $ability) {
                $role->givePermissionTo(Permission::findOrCreate($ability.':'.$subject, 'web'));
            }
        }

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
