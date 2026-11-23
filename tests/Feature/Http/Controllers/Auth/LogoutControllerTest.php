<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LogoutControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * Wylogowanie usuwa uwierzytelnienie i dane sesji, zmienia jej identyfikator oraz token CSRF.
     * Ponowna próba wejścia do panelu musi prowadzić do logowania.
     */
    public function test_logout_removes_authentication_and_invalidates_session(): void
    {
        $user = $this->createPanelAdministrator();
        $this->actingAs($user)->withSession(['private_marker' => 'secret']);
        $oldSessionId = session()->getId();
        $oldToken = session()->token();

        $response = $this->post(route('logout'));

        $response->assertRedirect('/')->assertSessionMissing('private_marker');
        $this->assertGuest();
        $this->assertNotSame($oldSessionId, session()->getId());
        $this->assertNotSame($oldToken, session()->token());
        $this->get(route('filament.panel.pages.dashboard'))->assertRedirect(route('filament.panel.auth.login'));
    }

    /**
     * Middleware auth odsyła niezalogowaną osobę do formularza logowania.
     */
    public function test_guest_cannot_execute_logout(): void
    {
        $this->post(route('logout'))->assertRedirect(route('filament.panel.auth.login'));
        $this->assertGuest();
    }

    /**
     * Samo otwarcie adresu przez GET nie może wylogować konta; operacja wymaga POST.
     */
    public function test_get_request_cannot_log_user_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('logout'))->assertMethodNotAllowed();

        $this->assertAuthenticatedAs($user);
    }
}
