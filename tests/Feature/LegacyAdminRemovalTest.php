<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LegacyAdminRemovalTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[DataProvider('legacyEndpoints')]
    public function test_legacy_endpoints_are_gone(string $method, string $path): void
    {
        $this->actingAs($this->createPanelAdministrator());

        $this->call($method, $path)->assertNotFound();
    }

    public static function legacyEndpoints(): array
    {
        return [
            ['GET', '/legacy-admin'],
            ['GET', '/legacy-admin/users'],
            ['GET', '/legacy-admin/users/create'],
            ['POST', '/legacy-admin/users'],
            ['GET', '/legacy-admin/users/1/edit'],
            ['PATCH', '/legacy-admin/users/1'],
            ['GET', '/legacy-admin/disciplines'],
            ['GET', '/legacy-admin/disciplines/create'],
            ['POST', '/legacy-admin/disciplines'],
        ];
    }

    #[DataProvider('protectedPages')]
    public function test_panel_user_cannot_open_resource_pages(string $path): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('panel_user', 'web'));

        $this->actingAs($user)->get($path)->assertForbidden();
    }

    public static function protectedPages(): array
    {
        return [
            ['/admin/users'], ['/admin/disciplines'], ['/admin/seasons'],
            ['/admin/shield/roles'], ['/admin/disciplines/create'],
            ['/admin/seasons/create'], ['/admin/shield/roles/create'],
        ];
    }

    public function test_panel_user_can_open_dashboard_without_resource_navigation(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('panel_user', 'web'));

        $this->actingAs($user)->get(route('filament.panel.pages.dashboard'))
            ->assertOk()->assertDontSee(route('filament.panel.resources.users.index'));
    }
}
