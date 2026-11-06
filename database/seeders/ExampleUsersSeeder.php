<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class ExampleUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($number = 1; $number <= 25; $number++) {
            $email = "example-user-{$number}@example.com";

            if (User::where('email', $email)->exists()) {
                continue;
            }

            User::factory()->create([
                'email' => $email,
                'is_admin' => false,
            ]);
        }
    }
}
