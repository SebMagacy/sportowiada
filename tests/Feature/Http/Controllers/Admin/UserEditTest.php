<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserEditTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_open_edit_form_with_current_user_data(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create([
            'name' => 'Jan Testowy',
            'email' => 'jan@example.com',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.users.edit', $user));

        $response->assertOk()
            ->assertViewIs('admin.users.edit')
            ->assertSee('value="'.e($user->name).'"', false)
            ->assertSee('value="'.e($user->email).'"', false);
    }

    #[DataProvider('savedEmails')]
    public function test_admin_saves_allowed_fields(string $email): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['email' => 'before@example.com']);

        $response = $this->actingAs($admin)->patch(route('admin.users.update', $user), [
            'name' => 'Nowe Imię',
            'email' => $email,
            'is_admin' => '0',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('status', __('users.updated'));
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Nowe Imię', 'email' => $email]);
    }

    /** @return array<string, array{string}> */
    public static function savedEmails(): array
    {
        return ['current email' => ['before@example.com'], 'new email' => ['after@example.com']];
    }

    #[DataProvider('deniedRequests')]
    public function test_guest_or_regular_user_cannot_access_editing(bool $authenticated, string $method): void
    {
        $user = User::factory()->create();
        $before = $user->fresh()->getAttributes();
        if ($authenticated) {
            $this->actingAs(User::factory()->create(['is_admin' => false]));
        }

        $response = $method === 'get'
            ? $this->get(route('admin.users.edit', $user))
            : $this->patch(route('admin.users.update', $user), ['name' => 'Changed', 'email' => 'changed@example.com', 'is_admin' => '1']);

        if ($authenticated) {
            $response->assertForbidden();
        } else {
            $response->assertRedirect(route('login'));
        }
        $this->assertSame($before, $user->fresh()->getAttributes());
    }

    /** @return array<string, array{bool, string}> */
    public static function deniedRequests(): array
    {
        return ['guest edit' => [false, 'get'], 'guest update' => [false, 'patch'],
            'user edit' => [true, 'get'], 'user update' => [true, 'patch']];
    }

    #[DataProvider('missingRoutes')]
    public function test_missing_account_returns_404(string $method): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);
        $id = $admin->id + 1;

        $response = $method === 'get'
            ? $this->get(route('admin.users.edit', $id))
            : $this->patch(route('admin.users.update', $id), ['name' => 'Changed', 'email' => 'new@example.com', 'is_admin' => '0']);

        $response->assertNotFound();
    }

    /** @return array<string, array{string}> */
    public static function missingRoutes(): array
    {
        return ['edit' => ['get'], 'update' => ['patch']];
    }

    #[DataProvider('invalidFields')]
    public function test_invalid_data_is_rejected_without_changing_account(string $field, mixed $value, string $rule): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $before = $user->fresh()->getAttributes();
        $data = ['name' => 'Changed', 'email' => 'changed@example.com', 'is_admin' => '0', $field => $value];
        $url = route('admin.users.edit', $user);
        $message = __('validation.'.$rule, ['attribute' => __('validation.attributes.'.$field), 'max' => 255]);

        $response = $this->actingAs($admin)->from($url)->patch(route('admin.users.update', $user), $data);

        $response->assertRedirect($url)->assertSessionHasErrors([$field => $message]);
        $this->assertSame($before, $user->fresh()->getAttributes());
    }

    /** @return array<string, array{string, mixed, string}> */
    public static function invalidFields(): array
    {
        return [
            'empty name' => ['name', '', 'required'],
            'null name' => ['name', null, 'required'],
            'array name' => ['name', ['unexpected'], 'string'],
            'long name' => ['name', str_repeat('a', 256), 'max.string'],
            'empty email' => ['email', '', 'required'],
            'null email' => ['email', null, 'required'],
            'array email' => ['email', ['unexpected'], 'email'],
            'invalid email' => ['email', 'not-an-email', 'email'],
            'long email' => ['email', str_repeat('a', 64).'@'.str_repeat('b', 63).'.'.str_repeat('c', 63).'.'.str_repeat('d', 60).'.com', 'max.string'],
        ];
    }

    public function test_missing_fields_are_rejected(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $before = $user->fresh()->getAttributes();

        $response = $this->actingAs($admin)->patch(route('admin.users.update', $user), []);

        $response->assertSessionHasErrors(['name', 'email', 'is_admin']);
        $this->assertSame($before, $user->fresh()->getAttributes());
    }

    public function test_email_owned_by_another_account_is_rejected(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $before = $user->fresh()->getAttributes();
        $url = route('admin.users.edit', $user);

        $response = $this->actingAs($admin)->from($url)->patch(route('admin.users.update', $user), [
            'name' => 'Changed', 'email' => $admin->email, 'is_admin' => '0',
        ]);

        $response->assertRedirect($url)->assertSessionHasErrors(['email' => 'Taki adres e-mail już występuje.']);
        $this->assertSame($before, $user->fresh()->getAttributes());
    }

    public function test_update_ignores_fields_outside_edit_scope(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_admin' => false]);
        $before = $user->fresh()->getAttributes();

        $response = $this->actingAs($admin)->patch(route('admin.users.update', $user), [
            'name' => 'Allowed', 'email' => 'allowed@example.com',
            'id' => 987654, 'is_admin' => true, 'password' => 'new-secret-password',
            'remember_token' => 'changed', 'email_verified_at' => null,
            'created_at' => '2000-01-01 00:00:00', 'updated_at' => '2000-01-01 00:00:00',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect(route('admin.users.index'));
        $saved = $user->fresh();
        foreach (['id', 'password', 'remember_token', 'email_verified_at', 'created_at'] as $field) {
            $this->assertSame($before[$field], $saved->getAttributes()[$field], $field);
        }
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Allowed', 'email' => 'allowed@example.com', 'is_admin' => true]);
    }

    public function test_list_links_cancel_and_form_preserve_list_settings(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'name' => 'Target Admin']);
        User::factory()->count(25)->create(['name' => 'Target Person']);
        $parameters = ['search' => 'Target', 'account_type' => 'all', 'sort' => 'id',
            'direction' => 'desc', 'per_page' => '25', 'page' => '2'];

        $list = $this->actingAs($admin)->get(route('admin.users.index', $parameters));

        $list->assertOk()->assertSee(route('admin.users.edit', ['user' => $admin, ...$parameters]));
        $edit = $this->get(route('admin.users.edit', ['user' => $admin, ...$parameters]));
        $edit->assertOk();
        $xpath = $this->xpath($edit->getContent());
        $this->assertSame(route('admin.users.index', $parameters), $xpath->evaluate('string(//a[normalize-space(.)="Anuluj"]/@href)'));
        $this->assertSame(route('admin.users.update', ['user' => $admin, ...$parameters]), $xpath->evaluate('string(//form[input[@name="_method"]]/@action)'));
        $this->assertSame('PATCH', $xpath->evaluate('string(//input[@name="_method"]/@value)'));
        $this->assertSame(1, $xpath->query('//form[input[@name="_method"]]/input[@name="_token"]')->length);
    }

    public function test_save_preserves_settings_and_discards_unknown_parameters(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $parameters = ['search' => 'Target', 'account_type' => 'user', 'sort' => 'name',
            'direction' => 'desc', 'per_page' => '25', 'page' => '2'];

        $response = $this->actingAs($admin)->patch(route('admin.users.update', [
            'user' => $user, ...$parameters, 'return_url' => 'https://example.org',
        ]), ['name' => 'Target Updated', 'email' => $user->email, 'is_admin' => '0']);

        $response->assertSessionHasNoErrors()->assertRedirect(route('admin.users.index', $parameters));
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Target Updated']);
    }

    public function test_cancel_ignores_unknown_and_array_parameters(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('admin.users.edit', [
            'user' => $admin, 'search' => ['bad'], 'page' => ['bad'],
            'per_page' => '25', 'return_url' => 'https://example.org',
        ]));

        $response->assertOk();
        $this->assertSame(route('admin.users.index', ['per_page' => '25']),
            $this->xpath($response->getContent())->evaluate('string(//a[normalize-space(.)="Anuluj"]/@href)'));
    }

    public function test_validation_preserves_empty_email_new_name_and_list_settings(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $before = $user->fresh()->getAttributes();
        $parameters = ['per_page' => '25', 'page' => '2'];
        $url = route('admin.users.edit', ['user' => $user, ...$parameters]);

        $response = $this->actingAs($admin)->from($url)->patch(route('admin.users.update', ['user' => $user, ...$parameters]), [
            'name' => 'Not Saved', 'email' => '', 'is_admin' => '1',
        ]);

        $response->assertRedirect($url)->assertSessionHasErrors('email');
        $edit = $this->get($url);
        $edit->assertOk();
        $xpath = $this->xpath($edit->getContent());
        $this->assertSame('Not Saved', $xpath->evaluate('string(//input[@name="name"]/@value)'));
        $this->assertSame('', $xpath->evaluate('string(//input[@name="email"]/@value)'));
        $this->assertSame('1', $xpath->evaluate('string(//select[@name="is_admin"]/option[@selected]/@value)'));
        $this->assertSame($before, $user->fresh()->getAttributes());
    }

    #[DataProvider('locales')]
    public function test_form_and_success_message_use_selected_language(string $locale, string $title, string $save, string $cancel, string $success): void
    {
        $this->app->setLocale($locale);
        $admin = User::factory()->create(['is_admin' => true, 'name' => 'Test Person']);

        $this->actingAs($admin)->get(route('admin.users.edit', $admin))->assertOk()
            ->assertSeeText($title)->assertSeeText($save)->assertSeeText($cancel);
        $response = $this->patch(route('admin.users.update', $admin), ['name' => 'Saved', 'email' => $admin->email, 'is_admin' => '1']);

        $response->assertSessionHasNoErrors()->assertSessionHas('status', $success);
        $this->get(route('admin.users.index'))->assertOk()->assertSeeText($success);
        $this->get(route('admin.users.index'))->assertOk()->assertDontSeeText($success);
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'name' => 'Saved']);
    }

    /** @return array<string, array{string, string, string, string, string}> */
    public static function locales(): array
    {
        return ['Polish' => ['pl', 'Edycja użytkownika: Test Person', 'Zapisz zmiany', 'Anuluj', 'Dane użytkownika zostały zapisane.'],
            'English' => ['en', 'Edit user: Test Person', 'Save changes', 'Cancel', 'User details have been saved.']];
    }

    public function test_user_data_is_escaped_in_form(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $name = '\"><script>alert(1)</script>';
        $user = User::factory()->create(['name' => $name]);

        $response = $this->actingAs($admin)->get(route('admin.users.edit', $user));

        $response->assertOk()->assertSee($name)->assertDontSee($name, false);
        $this->assertSame($name, $this->xpath($response->getContent())->evaluate('string(//input[@name="name"]/@value)'));
    }

    /**
     * Renderowanie worka błędów sprawdzamy osobno, jak w AuthFormsTest.
     */
    #[DataProvider('errorFields')]
    public function test_form_renders_errors_in_selected_language(string $locale, string $field, string $message): void
    {
        $this->app->setLocale($locale);
        $user = User::factory()->create();
        $errors = (new ViewErrorBag)->put('default', new MessageBag([$field => $message]));

        $this->view('admin.users.edit', ['user' => $user, 'listParameters' => [], 'errors' => $errors])
            ->assertSeeText($message);
    }

    /** @return array<string, array{string, string, string}> */
    public static function errorFields(): array
    {
        return [
            'Polish name' => ['pl', 'name', 'Pole imię jest wymagane.'],
            'Polish email' => ['pl', 'email', 'Pole adres e-mail jest wymagane.'],
            'English name' => ['en', 'name', 'The name field is required.'],
            'English email' => ['en', 'email', 'The email field is required.'],
        ];
    }

    #[DataProvider('accountTypes')]
    public function test_admin_can_change_another_accounts_type(mixed $value, bool $expected): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_admin' => ! $expected]);

        $response = $this->actingAs($admin)->patch(route('admin.users.update', $user), [
            'name' => $user->name, 'email' => $user->email, 'is_admin' => $value,
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect(route('admin.users.index'));
        $this->assertSame($expected, $user->fresh()->is_admin);
        $edit = $this->get(route('admin.users.edit', $user));
        $edit->assertOk();
        $this->assertSame($expected ? '1' : '0', $this->xpath($edit->getContent())
            ->evaluate('string(//select[@name="is_admin"]/option[@selected]/@value)'));
    }

    /** @return array<string, array{mixed, bool}> */
    public static function accountTypes(): array
    {
        return [
            'promote string' => ['1', true], 'demote string' => ['0', false],
            'promote integer' => [1, true], 'demote integer' => [0, false],
            'promote boolean' => [true, true], 'demote boolean' => [false, false],
        ];
    }

    #[DataProvider('invalidAccountTypes')]
    public function test_invalid_account_type_does_not_change_any_fields(mixed $value): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $before = $user->fresh()->getAttributes();

        $response = $this->actingAs($admin)->patch(route('admin.users.update', $user), [
            'name' => 'Not Saved', 'email' => 'not-saved@example.com', 'is_admin' => $value,
        ]);

        $response->assertSessionHasErrors('is_admin');
        $this->assertSame($before, $user->fresh()->getAttributes());
    }

    /** @return array<string, array{mixed}> */
    public static function invalidAccountTypes(): array
    {
        return ['null' => [null], 'empty' => [''], 'array' => [['1']],
            'unexpected number' => [2], 'role name' => ['admin'], 'boolean word' => ['false']];
    }

    #[DataProvider('selfDemotions')]
    public function test_admin_cannot_demote_self_even_with_direct_request(mixed $value): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $before = $admin->fresh()->getAttributes();
        $url = route('admin.users.edit', $admin);

        $response = $this->actingAs($admin)->from($url)->patch(route('admin.users.update', $admin), [
            'name' => 'Not Saved', 'email' => 'not-saved@example.com', 'is_admin' => $value,
        ]);

        $response->assertRedirect($url)->assertSessionHasErrors([
            'is_admin' => 'Nie możesz odebrać sobie uprawnień administratora.',
        ]);
        $this->assertSame($before, $admin->fresh()->getAttributes());
        $this->get(route('admin.users.index'))->assertOk();
    }

    /** @return array<string, array{mixed}> */
    public static function selfDemotions(): array
    {
        return ['string zero' => ['0'], 'integer zero' => [0], 'boolean false' => [false]];
    }

    public function test_self_demotion_message_uses_english_translation(): void
    {
        $this->app->setLocale('en');
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->patch(route('admin.users.update', $admin), [
            'name' => $admin->name, 'email' => $admin->email, 'is_admin' => '0',
        ]);

        $response->assertSessionHasErrors([
            'is_admin' => 'You cannot revoke your own administrator privileges.',
        ]);
        $this->assertSame(true, $admin->fresh()->is_admin);
    }

    public function test_regular_user_cannot_promote_self(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $before = $user->fresh()->getAttributes();

        $response = $this->actingAs($user)->patch(route('admin.users.update', $user), [
            'name' => 'Not Saved', 'email' => $user->email, 'is_admin' => '1',
        ]);

        $response->assertForbidden();
        $this->assertSame($before, $user->fresh()->getAttributes());
    }

    public function test_success_message_survives_redirect_from_page_removed_by_role_change(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->count(24)->create(['is_admin' => true]);
        $user = User::factory()->create(['is_admin' => true]);
        $parameters = ['account_type' => 'admin', 'per_page' => '25', 'page' => '2'];

        $response = $this->actingAs($admin)->followingRedirects()->patch(
            route('admin.users.update', ['user' => $user, ...$parameters]),
            ['name' => $user->name, 'email' => $user->email, 'is_admin' => '0'],
        );

        $response->assertOk()->assertSeeText(__('users.updated'));
        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_admin' => false]);
    }

    private function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument;
        $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);

        return new DOMXPath($document);
    }
}
