<?php

namespace Tests\Feature;

use App\Models\Discipline;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DisciplineIndexTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.disciplines.index'))
            ->assertRedirect(route('login'));
    }

    public function test_regular_user_is_forbidden(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.disciplines.index'))
            ->assertForbidden();
    }

    public function test_admin_sees_disciplines_from_database_in_id_order(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        Discipline::factory()->create(['name' => 'Siatkówka']);
        Discipline::factory()->create(['name' => 'Koszykówka']);

        $this->actingAs($admin)
            ->get(route('admin.disciplines.index'))
            ->assertOk()
            ->assertViewIs('admin.disciplines.index')
            ->assertSeeTextInOrder(['Siatkówka', 'Koszykówka']);
    }

    public function test_admin_sees_an_empty_list_message(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get(route('admin.disciplines.index'))
            ->assertOk()
            ->assertSeeText('Brak dyscyplin.');
    }

    public function test_discipline_names_are_escaped(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $name = '<script>alert(1)</script> & Sport';
        Discipline::factory()->create(['name' => $name]);

        $this->actingAs($admin)->get(route('admin.disciplines.index'))
            ->assertOk()
            ->assertSee($name)
            ->assertDontSee($name, false);
    }

    public function test_english_interface_preserves_polish_database_names(): void
    {
        $this->app->setLocale('en');
        $admin = User::factory()->create(['is_admin' => true]);
        Discipline::factory()->create(['name' => 'Piłka nożna']);

        $this->actingAs($admin)->get(route('admin.disciplines.index'))
            ->assertOk()
            ->assertSeeText('Disciplines')
            ->assertSeeText('Name')
            ->assertSeeText('Piłka nożna')
            ->assertDontSeeText('Football');
    }
}
