<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentPanelAccessTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * Gość zostaje skierowany do formularza logowania Filamentu.
     */
    public function test_guest_is_redirected_to_filament_login(): void
    {
        $this->get(route('filament.panel.pages.dashboard'))
            ->assertRedirect(route('filament.panel.auth.login'));
    }

    public function test_regular_user_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->get(route('filament.panel.pages.dashboard'))->assertForbidden();
    }

    public function test_admin_can_open_dashboard(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(route('filament.panel.pages.dashboard'))
            ->assertOk()->assertSee(route('filament.panel.resources.users.index'));
    }

    public function test_guest_is_redirected_from_users_to_login(): void
    {
        $this->get(route('filament.panel.resources.users.index'))
            ->assertRedirect(route('filament.panel.auth.login'));
    }

    public function test_regular_user_cannot_open_users(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->get(route('filament.panel.resources.users.index'))->assertForbidden();
    }

    public function test_admin_can_open_users(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $this->actingAs($user)->get(route('filament.panel.resources.users.index'))
            ->assertOk()->assertSeeText($user->email);
    }

    public function test_guest_can_open_login_form(): void
    {
        $this->get(route('filament.panel.auth.login'))
            ->assertOk()->assertSeeLivewire(Login::class);
    }

    public function test_admin_can_log_in_with_valid_credentials(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        Filament::setCurrentPanel('panel');

        Livewire::test(Login::class)
            ->set('data.email', $user->email)
            ->set('data.password', 'password')
            ->call('authenticate')
            ->assertHasNoErrors()
            ->assertRedirect(route('filament.panel.pages.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_regular_user_cannot_log_in_with_valid_credentials(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        Filament::setCurrentPanel('panel');

        Livewire::test(Login::class)
            ->set('data.email', $user->email)
            ->set('data.password', 'password')
            ->call('authenticate')
            ->assertHasErrors(['data.email'])
            ->assertSeeText(__('filament-panels::auth/pages/login.messages.failed'))
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_admin_cannot_log_in_with_wrong_password(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        Filament::setCurrentPanel('panel');

        Livewire::test(Login::class)
            ->set('data.email', $user->email)
            ->set('data.password', 'incorrect-password')
            ->call('authenticate')
            ->assertHasErrors(['data.email'])
            ->assertSeeText(__('filament-panels::auth/pages/login.messages.failed'))
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_logout_ends_session_and_protects_dashboard_again(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $this->actingAs($user)->post(route('filament.panel.auth.logout'))
            ->assertRedirect(route('filament.panel.auth.login'));

        $this->assertGuest();
        $this->get(route('filament.panel.pages.dashboard'))
            ->assertRedirect(route('filament.panel.auth.login'));
    }
}
