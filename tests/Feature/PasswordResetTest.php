<?php

namespace Tests\Feature;

use App\Filament\Pages\Login;
use App\Filament\Pages\RequestPasswordReset;
use App\Filament\Pages\ResetPassword;
use App\Models\User;
use Filament\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Filament\Facades\Filament;
use Illuminate\Auth\Events\PasswordReset as PasswordResetEvent;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel('panel');
    }

    public function test_guest_routes_use_both_custom_reset_forms(): void
    {
        $this->get(route('filament.panel.auth.password-reset.request'))
            ->assertOk()->assertSeeLivewire(RequestPasswordReset::class);
        $this->get(Filament::getResetPasswordUrl('example-token', User::factory()->make()))
            ->assertOk()->assertSeeLivewire(ResetPassword::class);
    }

    #[DataProvider('roles')]
    public function test_existing_account_receives_valid_reset_link(string $role): void
    {
        $user = $this->userWithRole($role);
        Notification::fake();

        Livewire::test(RequestPasswordReset::class)->fillForm(['email' => $user->email])->call('request')
            ->assertHasNoFormErrors()->assertNotified(__('passwords.request_received'));

        Notification::assertSentTo($user, ResetPasswordNotification::class,
            function (ResetPasswordNotification $notification) use ($user): bool {
                $this->assertSame(Filament::getResetPasswordUrl($notification->token, $user), $notification->url);

                return Password::broker()->tokenExists($user, $notification->token);
            });
        $this->assertGuest();
    }

    #[DataProvider('languages')]
    public function test_missing_account_gets_generic_message_without_delivery(string $locale, string $message): void
    {
        app()->setLocale($locale);
        Notification::fake();

        Livewire::test(RequestPasswordReset::class)->fillForm(['email' => 'missing@example.test'])->call('request')
            ->assertHasNoFormErrors()->assertNotified($message);

        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    #[DataProvider('roles')]
    public function test_reset_changes_password_and_login_preserves_role_access(string $role): void
    {
        $user = $this->userWithRole($role);
        $oldRememberToken = $user->remember_token;
        $token = Password::broker()->createToken($user);
        $this->fakePasswordLookup('NoweBezpieczneHaslo49!');
        Event::fake([PasswordResetEvent::class]);

        Livewire::test(ResetPassword::class, ['email' => $user->email, 'token' => $token])
            ->fillForm(['password' => 'NoweBezpieczneHaslo49!', 'passwordConfirmation' => 'NoweBezpieczneHaslo49!'])
            ->call('resetPassword')->assertHasNoFormErrors()->assertNotified(__('passwords.reset'))
            ->assertRedirect(route('filament.panel.auth.login'));

        $user->refresh();
        $this->assertTrue(Hash::check('NoweBezpieczneHaslo49!', $user->password));
        $this->assertFalse(Hash::check('password', $user->password));
        $this->assertNotSame($oldRememberToken, $user->remember_token);
        $this->assertSame([$role], $user->getRoleNames()->all());
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        Event::assertDispatched(PasswordResetEvent::class, fn (PasswordResetEvent $event): bool => $event->user->is($user));

        Livewire::test(Login::class)->fillForm(['email' => $user->email, 'password' => 'NoweBezpieczneHaslo49!'])
            ->call('authenticate')->assertHasNoErrors()
            ->assertRedirect($role === 'user' ? '/' : route('filament.panel.pages.dashboard'));
        $this->assertAuthenticatedAs($user);
        $response = $this->get(route('filament.panel.pages.dashboard'));
        if ($role === 'user') {
            $response->assertForbidden();
        } else {
            $response->assertOk();
        }
    }

    public function test_used_token_cannot_change_password_again(): void
    {
        $user = $this->userWithRole('user');
        $token = Password::broker()->createToken($user);
        $this->fakePasswordLookup('NoweBezpieczneHaslo49!');
        Livewire::test(ResetPassword::class, ['email' => $user->email, 'token' => $token])
            ->fillForm(['password' => 'NoweBezpieczneHaslo49!', 'passwordConfirmation' => 'NoweBezpieczneHaslo49!'])
            ->call('resetPassword')->assertRedirect(route('filament.panel.auth.login'));
        $savedHash = $user->fresh()->password;
        $this->fakePasswordLookup('KolejneBezpieczneHaslo49!');
        Event::fake([PasswordResetEvent::class]);

        Livewire::test(ResetPassword::class, ['email' => $user->email, 'token' => $token])
            ->fillForm(['password' => 'KolejneBezpieczneHaslo49!', 'passwordConfirmation' => 'KolejneBezpieczneHaslo49!'])
            ->call('resetPassword')->assertNotified(__('passwords.token'))->assertNoRedirect();

        $this->assertSame($savedHash, $user->fresh()->password);
        Event::assertNotDispatched(PasswordResetEvent::class);
    }

    public function test_unused_token_expires_after_configured_lifetime(): void
    {
        $this->freezeTime();
        $user = $this->userWithRole('user');
        $savedHash = $user->password;
        $token = Password::broker()->createToken($user);
        $this->assertSame(60, config('auth.passwords.users.expire'));
        $this->travel(61)->minutes();
        $this->fakePasswordLookup('NoweBezpieczneHaslo49!');
        Event::fake([PasswordResetEvent::class]);

        Livewire::test(ResetPassword::class, ['email' => $user->email, 'token' => $token])
            ->fillForm(['password' => 'NoweBezpieczneHaslo49!', 'passwordConfirmation' => 'NoweBezpieczneHaslo49!'])
            ->call('resetPassword')->assertNotified(__('passwords.token'))->assertNoRedirect();

        $this->assertSame($savedHash, $user->fresh()->password);
        Event::assertNotDispatched(PasswordResetEvent::class);
    }

    #[DataProvider('invalidPasswords')]
    public function test_invalid_new_password_does_not_change_account(string $password, string $confirmation, bool $compromised, string $rule): void
    {
        $user = $this->userWithRole('user');
        $savedHash = $user->password;
        $token = Password::broker()->createToken($user);
        $this->fakePasswordLookup($password, $compromised);
        Event::fake([PasswordResetEvent::class]);

        Livewire::test(ResetPassword::class, ['email' => $user->email, 'token' => $token])
            ->fillForm(['password' => $password, 'passwordConfirmation' => $confirmation])
            ->call('resetPassword')->assertHasFormErrors(['password' => $rule])->assertNoRedirect();

        $this->assertSame($savedHash, $user->fresh()->password);
        $this->assertTrue(Password::broker()->tokenExists($user, $token));
        Event::assertNotDispatched(PasswordResetEvent::class);
    }

    public function test_repeated_requests_do_not_send_additional_links_and_form_limits_attempts(): void
    {
        $this->freezeTime();
        $user = $this->userWithRole('user');
        Notification::fake();
        $form = Livewire::test(RequestPasswordReset::class);
        $form->fillForm(['email' => $user->email])->call('request')->assertNotified(__('passwords.request_received'));

        $form->fillForm(['email' => $user->email])->call('request')->assertNotified(__('passwords.request_received'));
        $form->fillForm(['email' => $user->email])->call('request')
            ->assertNotified(__('filament-panels::auth/pages/password-reset/request-password-reset.notifications.throttled.title', ['seconds' => 60, 'minutes' => 1]));

        Notification::assertSentToTimes($user, ResetPasswordNotification::class, 1);
    }

    /** @return array<string, array{string}> */
    public static function roles(): array
    {
        return ['regular user' => ['user'], 'panel user' => ['panel_user'], 'administrator' => ['super_admin']];
    }

    /** @return array<string, array{string, string}> */
    public static function languages(): array
    {
        return [
            'Polish' => ['pl', 'Jeśli konto z podanym adresem istnieje, otrzymasz wiadomość z linkiem do resetowania hasła.'],
            'English' => ['en', 'If an account with that email address exists, you will receive a password reset link.'],
        ];
    }

    /** @return array<string, array{string, string, bool, string}> */
    public static function invalidPasswords(): array
    {
        return [
            'short password' => ['12345678901234', '12345678901234', false, PasswordRule::class],
            'compromised password' => ['123456789012345', '123456789012345', true, PasswordRule::class],
            'different confirmation' => ['NoweBezpieczneHaslo49!', 'InneBezpieczneHaslo49!', false, 'same'],
        ];
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate($role, 'web'));

        return $user;
    }

    private function fakePasswordLookup(string $password, bool $compromised = false): void
    {
        $hash = strtoupper(sha1($password));
        Http::preventStrayRequests();
        Http::fake(['https://api.pwnedpasswords.com/range/'.substr($hash, 0, 5) => Http::response($compromised ? substr($hash, 5).':42' : '', 200)]);
    }
}
