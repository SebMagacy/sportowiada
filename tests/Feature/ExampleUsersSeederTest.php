<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\ExampleUsersSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ExampleUsersSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * Seeder tworzy 25 kont bez uprawnień administratora.
     */
    public function test_creates_25_regular_users(): void
    {
        $this->seed(ExampleUsersSeeder::class);

        $this->assertDatabaseCount('users', 25);
        $this->assertDatabaseMissing('users', [
            'is_admin' => true,
        ]);
    }

    /**
     * Ponowne uruchomienie seedera nie tworzy kolejnych kont.
     */
    public function test_running_again_does_not_duplicate_users(): void
    {
        $this->seed(ExampleUsersSeeder::class);

        $this->seed(ExampleUsersSeeder::class);

        $this->assertDatabaseCount('users', 25);
    }

    /**
     * Seeder zachowuje istniejące konta, także przy zgodnym adresie e-mail.
     */
    public function test_preserves_existing_users_and_administrator(): void
    {
        $administrator = User::factory()->create([
            'email' => 'example-user-1@example.com',
            'is_admin' => true,
        ]);

        $regularUser = User::factory()->create([
            'email' => 'existing-user@example.com',
            'is_admin' => false,
        ]);

        $administratorAttributes = $administrator->getAttributes();
        $regularUserAttributes = $regularUser->getAttributes();

        $this->seed(ExampleUsersSeeder::class);

        $this->assertDatabaseCount('users', 26);
        $this->assertDatabaseHas('users', $administratorAttributes);
        $this->assertDatabaseHas('users', $regularUserAttributes);
    }
}
