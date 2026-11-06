<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * Gość otrzymuje formularz logowania z polami e-mail i hasła.
     */
    public function test_guest_can_open_login_form(): void
    {
        $this->get(route('login'))->assertOk()->assertViewIs('auth.login')
            ->assertSee('name="email"', false)->assertSee('name="password"', false);
    }

    /**
     * Poprawne logowanie uwierzytelnia konto i kieruje je na stronę właściwą dla roli.
     * Zmiana identyfikatora sesji chroni przed utrwaleniem sesji, a jej dane pozostają zachowane.
     */
    #[DataProvider('roles')]
    public function test_valid_credentials_authenticate_and_redirect_by_role(bool $isAdmin, string $destination): void
    {
        $user = User::factory()->create(['is_admin' => $isAdmin]);
        $this->withSession(['marker' => 'preserved']);
        $oldSessionId = session()->getId();

        $response = $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

        $response->assertRedirect($destination === '/' ? '/' : route($destination))->assertSessionHasNoErrors()
            ->assertSessionHas('marker', 'preserved');
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($oldSessionId, session()->getId());
    }

    /**
     * Każdy nazwany zestaw danych uruchamia powiązany test osobno dla wskazanej roli.
     */
    public static function roles(): array
    {
        return ['administrator' => [true, 'admin.dashboard'], 'user' => [false, '/']];
    }

    /**
     * Niepoprawne dane wracają do formularza z błędem; hasło nie trafia do starych danych wejściowych.
     */
    #[DataProvider('invalidCredentials')]
    public function test_invalid_credentials_are_rejected(array $overrides, string $field): void
    {
        $credentials = array_replace(['email' => 'reader@example.test', 'password' => 'password'], $overrides);

        $response = $this->from(route('login'))->post(route('login.store'), $credentials);

        $response->assertRedirect(route('login'))->assertSessionHasErrors($field)
            ->assertSessionMissingInput('password');
        $this->assertGuest();
    }

    /**
     * Przypadki sprawdzają wymagane pola, format e-maila i tekstowy typ hasła.
     */
    public static function invalidCredentials(): array
    {
        return [
            'missing email' => [['email' => ''], 'email'],
            'malformed email' => [['email' => 'invalid'], 'email'],
            'missing password' => [['password' => ''], 'password'],
            'password must be string' => [['password' => ['invalid']], 'password'],
        ];
    }

    /**
     * Nieznane konto i błędne hasło dają ten sam komunikat, bez ujawniania istnienia konta.
     * Zachowany zostaje e-mail, ale użytkownik nie uzyskuje uwierzytelnienia.
     */
    #[DataProvider('failedCredentials')]
    public function test_failed_login_keeps_email_and_displays_error(bool $existingUser): void
    {
        $email = 'reader@example.test';
        if ($existingUser) {
            User::factory()->create(['email' => $email]);
        }

        $response = $this->from(route('login'))->post(route('login.store'), ['email' => $email, 'password' => 'wrong-password']);

        $response->assertRedirect(route('login'))->assertSessionHasErrors(['email' => __('auth.failed')])
            ->assertSessionHasInput('email', $email)->assertSessionMissingInput('password');
        $this->assertGuest();

    }

    /**
     * Ten sam scenariusz obejmuje nieistniejące konto oraz złe hasło istniejącego konta.
     */
    public static function failedCredentials(): array
    {
        return ['unknown account' => [false], 'wrong password' => [true]];
    }

    /**
     * Po pięciu błędach nawet poprawne hasło nie omija czasowej blokady.
     * Zamrożenie czasu pozwala sprawdzić dokładną liczbę sekund bez oczekiwania.
     */
    public function test_five_failed_attempts_block_even_correct_credentials(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->from(route('login'))->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong'])
                ->assertSessionHasErrors(['email' => __('auth.failed')]);
        }

        $response = $this->from(route('login'))->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

        $response->assertRedirect(route('login'))->assertSessionHasErrors(['email' => __('auth.throttle', ['seconds' => 60])])
            ->assertSessionHasInput('email', $user->email)->assertSessionMissingInput('password');
        $this->assertGuest();
    }

    /**
     * Przygotowany licznik blokuje konto na minutę; przesunięcie czasu o 61 sekund zastępuje czekanie.
     */
    public function test_login_is_allowed_after_block_expires(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        RateLimiter::hit('login:'.mb_strtolower($user->email).'|127.0.0.1', 60);
        for ($attempt = 1; $attempt < 5; $attempt++) {
            RateLimiter::hit('login:'.mb_strtolower($user->email).'|127.0.0.1', 60);
        }
        $this->travel(61)->seconds();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    /**
     * Udane logowanie usuwa poprzednie błędy z licznika dla pary e-mail i adres IP.
     */
    public function test_successful_login_clears_failed_attempts(): void
    {
        $user = User::factory()->create();
        $key = 'login:'.mb_strtolower($user->email).'|127.0.0.1';
        RateLimiter::hit($key, 60);

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertRedirect('/');

        $this->assertSame(0, RateLimiter::attempts($key));
    }
}
