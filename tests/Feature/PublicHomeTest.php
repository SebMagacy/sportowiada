<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicHomeTest extends TestCase
{
    /**
     * Odpowiedź strony głównej udostępnia kontener Vue i odnośnik do logowania.
     * Wykonanie JavaScriptu jest sprawdzane oddzielnie.
     */
    public function test_home_provides_vue_mount_point_and_login_link(): void
    {
        $this->get('/')->assertOk()->assertViewIs('home')
            ->assertSee('id="app"', false)->assertSee(route('filament.panel.auth.login'));
    }

    public function test_legacy_login_is_not_found(): void
    {
        $this->get('/login')->assertNotFound();
    }
}
