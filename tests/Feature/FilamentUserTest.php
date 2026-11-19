<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentUserTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_search_without_matches_does_not_display_empty_catalog_guidance(): void
    {
        $administrator = $this->createPanelAdministrator();
        $this->actingAs($administrator);
        Filament::setCurrentPanel('panel');
        app()->setLocale('pl');

        Livewire::test(ListUsers::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$administrator])
            ->assertDontSeeText('Brak użytkowników do wyświetlenia.')
            ->searchTable('zzzzbrakdopasowania')
            ->assertOk()
            ->assertCanNotSeeTableRecords([$administrator])
            ->assertDontSeeText('Brak użytkowników do wyświetlenia.')
            ->searchTable('')
            ->assertOk()
            ->assertCanSeeTableRecords([$administrator])
            ->assertDontSeeText('Brak użytkowników do wyświetlenia.');
    }

    public function test_empty_catalog_view_displays_polish_guidance_without_create_action(): void
    {
        app()->setLocale('pl');

        $this->view('filament.users.empty')
            ->assertSeeText('Brak użytkowników do wyświetlenia.')
            ->assertSeeText(
                'Tutaj pojawią się użytkownicy. Osoby z odpowiednimi uprawnieniami mogą przypisywać im role określające dostęp do funkcji panelu.',
            )
            ->assertDontSee('<button', false)
            ->assertDontSee('<a ', false);
    }

    public function test_empty_catalog_view_displays_english_guidance_without_create_action(): void
    {
        app()->setLocale('en');

        $this->view('filament.users.empty')
            ->assertSeeText('No users to display.')
            ->assertSeeText(
                'Users will appear here. Authorized users can assign roles that determine access to panel features.',
            )
            ->assertDontSee('<button', false)
            ->assertDontSee('<a ', false);
    }

    public function test_guest_is_redirected_to_login_from_user_list(): void
    {
        Filament::setCurrentPanel('panel');

        $this->get(UserResource::getUrl('index'))
            ->assertRedirect(route('filament.panel.auth.login'));
    }

    public function test_regular_user_cannot_open_user_list(): void
    {
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel('panel');

        $this->get(UserResource::getUrl('index'))->assertForbidden();
    }

    public function test_administrator_can_open_user_list_without_create_action(): void
    {
        $this->actingAs($this->createPanelAdministrator());
        Filament::setCurrentPanel('panel');

        $this->get(UserResource::getUrl('index'))->assertOk();

        Livewire::test(ListUsers::class)
            ->assertOk()
            ->assertActionDoesNotExist('create')
            ->assertTableBulkActionDoesNotExist('delete')
            ->assertDontSeeText('Działania');
    }
}
