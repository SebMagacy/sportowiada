<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * Gość nie otrzymuje chronionej strony i zostaje skierowany do logowania.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    /**
     * Samo zalogowanie nie wystarcza do otwarcia panelu bez uprawnienia administratora.
     */
    public function test_regular_user_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]))->get(route('admin.dashboard'))->assertForbidden();
    }

    /**
     * Administrator otrzymuje pulpit z odnośnikiem do użytkowników i formularzem wylogowania.
     */
    public function test_admin_sees_dashboard_and_navigation(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(route('admin.dashboard'))->assertOk()->assertViewIs('admin.dashboard')
            ->assertSeeText('Panel administratora')->assertSee(route('admin.users.index'))
            ->assertSee(route('logout'));
    }
}
