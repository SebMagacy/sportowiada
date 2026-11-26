<?php

namespace Tests\Feature;

use App\Filament\Pages\Register;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FilamentRegistrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_can_register_with_default_role_and_is_logged_in(): void
    {
        Filament::setCurrentPanel('panel');
        $this->fakePasswordLookup();

        $this->assertDatabaseMissing('roles', [
            'name' => 'user',
            'guard_name' => 'web',
        ]);

        Livewire::test(Register::class)
            ->fillForm([
                'name' => 'Jan Testowy',
                'email' => 'jan@example.com',
                'password' => 'BezpieczneHaslo123!',
                'passwordConfirmation' => 'BezpieczneHaslo123!',
            ])
            ->call('register')
            ->assertHasNoFormErrors()
            ->assertRedirect(route('home'));

        $user = User::query()
            ->where('email', 'jan@example.com')
            ->firstOrFail();

        $this->assertSame('Jan Testowy', $user->name);
        $this->assertTrue(Hash::check('BezpieczneHaslo123!', $user->password));
        $this->assertAuthenticatedAs($user, 'web');

        $this->assertDatabaseHas('roles', [
            'name' => 'user',
            'guard_name' => 'web',
        ]);

        $this->assertSame(['user'], $user->getRoleNames()->all());
    }

    public function test_guest_can_open_registration_form(): void
    {
        $this->get(route('filament.panel.auth.register'))
            ->assertOk()->assertSeeLivewire(Register::class);
    }

    public function test_registration_reuses_existing_user_role(): void
    {
        Filament::setCurrentPanel('panel');
        $this->fakePasswordLookup();
        $role = Role::findOrCreate('user', 'web');

        Livewire::test(Register::class)->fillForm($this->validPayload())
            ->call('register')->assertHasNoFormErrors()->assertRedirect(route('home'));

        $user = User::query()->where('email', 'new@example.test')->sole();
        $this->assertTrue($user->hasRole($role));
        $this->assertDatabaseCount('roles', 1);
        $this->assertAuthenticatedAs($user, 'web');
    }

    /** @param array<string, mixed> $overrides */
    #[DataProvider('invalidRegistration')]
    public function test_invalid_registration_does_not_create_account(array $overrides, string $field, string $rule): void
    {
        Filament::setCurrentPanel('panel');
        $this->fakePasswordLookup();

        Livewire::test(Register::class)
            ->fillForm(array_replace($this->validPayload(), $overrides))
            ->call('register')->assertHasFormErrors([$field => $rule]);

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('roles', 0);
        $this->assertGuest('web');
    }

    /** @return array<string, array{array<string, mixed>, string, string}> */
    public static function invalidRegistration(): array
    {
        return [
            'required name' => [['name' => ''], 'name', 'required'],
            'string name' => [['name' => ['invalid']], 'name', 'string'],
            'long name' => [['name' => str_repeat('a', 256)], 'name', 'max'],
            'required email' => [['email' => ''], 'email', 'required'],
            'invalid email' => [['email' => 'invalid'], 'email', 'email'],
            'long email' => [['email' => str_repeat('a', 245).'@example.test'], 'email', 'max'],
            'required password' => [['password' => ''], 'password', 'required'],
            'string password' => [['password' => ['invalid']], 'password', Password::class],
            'short password' => [['password' => '12345678901234', 'passwordConfirmation' => '12345678901234'], 'password', Password::class],
            'different confirmation' => [['passwordConfirmation' => 'different'], 'password', 'same'],
            'missing confirmation' => [['passwordConfirmation' => ''], 'passwordConfirmation', 'required'],
        ];
    }

    public function test_duplicate_email_is_rejected_and_safe_input_is_preserved(): void
    {
        Filament::setCurrentPanel('panel');
        $this->fakePasswordLookup();
        $existing = User::factory()->create();
        $payload = array_replace($this->validPayload(), ['email' => $existing->email]);

        Livewire::test(Register::class)->fillForm($payload)->call('register')
            ->assertHasFormErrors(['email' => 'unique'])
            ->assertSet('data.name', $payload['name'])->assertSet('data.email', $payload['email']);

        $this->assertDatabaseCount('users', 1);
        $this->assertGuest('web');
    }

    public function test_registration_accepts_boundary_name_and_password_lengths(): void
    {
        Filament::setCurrentPanel('panel');
        $this->fakePasswordLookup();
        $payload = array_replace($this->validPayload(), [
            'name' => str_repeat('a', 255), 'password' => '123456789012345', 'passwordConfirmation' => '123456789012345',
        ]);

        Livewire::test(Register::class)->fillForm($payload)->call('register')
            ->assertHasNoFormErrors()->assertRedirect(route('home'));

        $this->assertDatabaseHas('users', ['name' => $payload['name'], 'email' => $payload['email']]);
    }

    public function test_registration_cannot_assign_privileged_roles_or_permissions(): void
    {
        Filament::setCurrentPanel('panel');
        $this->fakePasswordLookup();
        Role::findOrCreate('super_admin', 'web');

        Livewire::test(Register::class)->fillForm($this->validPayload())
            ->set('data.roles', ['super_admin'])->set('data.permissions', ['Update:User'])
            ->call('register')->assertHasNoFormErrors()->assertRedirect(route('home'));

        $user = User::query()->where('email', 'new@example.test')->sole();
        $this->assertSame(['user'], $user->getRoleNames()->all());
        $this->assertSame(0, $user->permissions()->count());
        $this->get(route('filament.panel.pages.dashboard'))->assertForbidden();
    }

    public function test_legacy_registration_is_unavailable(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', $this->validPayload())->assertNotFound();
    }

    public function test_home_escapes_registered_user_name(): void
    {
        $name = '<script>alert("name")</script>';
        $user = User::factory()->create(['name' => $name]);

        $this->actingAs($user)->get(route('home'))
            ->assertOk()->assertSee($name)->assertDontSee($name, false);
    }

    public function test_compromised_password_is_rejected_without_creating_an_account(): void
    {
        Filament::setCurrentPanel('panel');
        $password = '123456789012345';
        $hash = strtoupper(sha1($password));
        $url = 'https://api.pwnedpasswords.com/range/'.substr($hash, 0, 5);
        Http::preventStrayRequests();
        Http::fake([$url => Http::response(substr($hash, 5).':42', 200)]);

        Livewire::test(Register::class)
            ->fillForm(array_replace($this->validPayload(), [
                'password' => $password, 'passwordConfirmation' => $password,
            ]))
            ->call('register')
            ->assertHasFormErrors(['password' => Password::class])
            ->assertSeeText('To hasło pojawiło się w wycieku danych. Wybierz inne hasło.');

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('roles', 0);
        $this->assertGuest('web');
        Http::assertSentCount(1);
    }

    private function fakePasswordLookup(): void
    {
        Http::preventStrayRequests();
        Http::fake(['https://api.pwnedpasswords.com/range/*' => Http::response('', 200)]);
    }

    /** @return array{name: string, email: string, password: string, passwordConfirmation: string} */
    private function validPayload(): array
    {
        return ['name' => 'Nowy Użytkownik', 'email' => 'new@example.test', 'password' => 'secure-password', 'passwordConfirmation' => 'secure-password'];
    }
}
