<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RegistrationControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * Formularz rejestracji jest dostępny dla gościa i zawiera potwierdzenie hasła.
     */
    public function test_guest_can_open_registration_form(): void
    {
        $this->get(route('register'))->assertOk()->assertViewIs('auth.register')
            ->assertSee('name="password_confirmation"', false);
    }

    /**
     * Rejestracja zapisuje zwykłe konto z zahaszowanym hasłem i kieruje do logowania.
     * Dodatkowe is_admin w żądaniu sprawdza, czy klient nie może sam nadać sobie uprawnień.
     */
    public function test_registration_creates_regular_account_with_hashed_password(): void
    {
        $payload = $this->validPayload();
        $payload['is_admin'] = true;

        $this->post(route('register.store'), $payload)->assertRedirect(route('login'))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('users', 1);
        $user = User::where('email', $payload['email'])->sole();
        $this->assertSame($payload['name'], $user->name);
        $this->assertFalse($user->is_admin);
        $this->assertTrue(Hash::check($payload['password'], $user->password));
        $this->assertGuest();
    }

    /**
     * Każdy błędny wariant musi zwrócić właściwy komunikat i pozostawić bazę bez nowego konta.
     * Hasło oraz jego potwierdzenie nie mogą zostać zachowane w sesji formularza.
     */
    #[DataProvider('invalidRegistration')]
    public function test_invalid_registration_does_not_create_account(array $overrides, string $field, string $rule, array $parameters): void
    {
        $payload = array_replace($this->validPayload(), $overrides);
        $message = __('validation.'.$rule, array_merge(['attribute' => __('validation.attributes.'.$field)], $parameters));

        $response = $this->from(route('register'))->post(route('register.store'), $payload);

        $response->assertRedirect(route('register'))->assertSessionHasErrors([$field => $message])
            ->assertSessionMissingInput('password')->assertSessionMissingInput('password_confirmation');
        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();

    }

    /**
     * Każdy wiersz dostarcza zmianę danych, pole błędu, klucz tłumaczenia i parametry komunikatu.
     */
    public static function invalidRegistration(): array
    {
        return [
            'required name' => [['name' => ''], 'name', 'required', []],
            'string name' => [['name' => ['invalid']], 'name', 'string', []],
            'long name' => [['name' => str_repeat('a', 256)], 'name', 'max.string', ['max' => 255]],
            'required email' => [['email' => ''], 'email', 'required', []],
            'invalid email' => [['email' => 'invalid'], 'email', 'email', []],
            'long email' => [['email' => str_repeat('a', 245).'@example.test'], 'email', 'max.string', ['max' => 255]],
            'required password' => [['password' => ''], 'password', 'required', []],
            'string password' => [['password' => ['invalid']], 'password', 'string', []],
            'short password' => [['password' => 'short', 'password_confirmation' => 'short'], 'password', 'min.string', ['min' => 8]],
            'different confirmation' => [['password_confirmation' => 'different'], 'password', 'confirmed', []],
            'missing confirmation' => [['password_confirmation' => ''], 'password', 'confirmed', []],
        ];
    }

    /**
     * Zajęty e-mail nie tworzy drugiego konta; formularz zachowuje imię i e-mail, ale nie hasła.
     */
    public function test_duplicate_email_is_rejected_and_safe_input_is_preserved(): void
    {
        $existing = User::factory()->create();
        $payload = $this->validPayload();
        $payload['email'] = $existing->email;
        $message = __('validation.unique', ['attribute' => __('validation.attributes.email')]);

        $response = $this->from(route('register'))->post(route('register.store'), $payload);

        $response->assertRedirect(route('register'))->assertSessionHasErrors(['email' => $message])
            ->assertSessionHasInput('name', $payload['name'])->assertSessionHasInput('email', $payload['email'])
            ->assertSessionMissingInput('password')->assertSessionMissingInput('password_confirmation');
        $this->assertDatabaseCount('users', 1);

    }

    /**
     * Dokładnie 255 znaków imienia i 8 znaków hasła nadal spełnia granice walidacji.
     */
    public function test_registration_accepts_boundary_name_and_password_lengths(): void
    {
        $payload = $this->validPayload();
        $payload['name'] = str_repeat('a', 255);
        $payload['password'] = $payload['password_confirmation'] = '12345678';

        $this->post(route('register.store'), $payload)->assertRedirect(route('login'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['name' => $payload['name'], 'email' => $payload['email']]);
    }

    /** @return array{name: string, email: string, password: string, password_confirmation: string} */
    private function validPayload(): array
    {
        return ['name' => 'Nowy Użytkownik', 'email' => 'new@example.test', 'password' => 'secure-password', 'password_confirmation' => 'secure-password'];
    }
}
