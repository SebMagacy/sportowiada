<?php

namespace Tests\Feature;

use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminTopBarTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[DataProvider('locales')]
    public function test_all_admin_pages_render_a_localized_public_site_link(string $locale, string $label, string $hint): void
    {
        $this->app->setLocale($locale);
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        foreach ([
            route('admin.dashboard'),
            route('admin.users.index'),
            route('admin.users.edit', $admin),
        ] as $url) {
            $response = $this->get($url);

            $response->assertOk();
            $xpath = $this->xpath($response->getContent());
            $this->assertSame(1, $xpath->query('//header')->length);
            $this->assertSame(1, $xpath->query('//header//h1')->length);
            $this->assertSame(0, $xpath->query('//main//h1')->length);
            $this->assertSame(1, $xpath->query('//header//a')->length);
            $this->assertSame(url('/'), $xpath->evaluate('string(//header//a/@href)'));
            $this->assertSame('_blank', $xpath->evaluate('string(//header//a/@target)'));
            $this->assertSame(1, $xpath->query('//header//a[contains(concat(" ", normalize-space(@rel), " "), " noopener ")]')->length);
            $this->assertSame($label, $xpath->evaluate('normalize-space(//header//a/span[1])'));
            $this->assertSame('('.$hint.')', $xpath->evaluate('normalize-space(//header//a/span[contains(concat(" ", @class, " "), " sr-only ")])'));
            $this->assertSame(1, $xpath->query('//header//a/span[@aria-hidden="true"]/svg')->length);
        }
    }

    public function test_public_home_is_accessible_to_guests(): void
    {
        $this->get(route('home'))->assertOk()->assertViewIs('home');
        $this->assertGuest();
    }

    public function test_visiting_public_home_preserves_admin_authentication(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $this->get(route('home'))->assertOk()->assertViewIs('home');

        $this->assertAuthenticatedAs($admin);
        $this->get(route('admin.users.index'))->assertOk();
    }

    /** @return array<string, array{string, string, string}> */
    public static function locales(): array
    {
        return [
            'Polish' => ['pl', 'Zobacz witrynę', 'otwiera się w nowej karcie'],
            'English' => ['en', 'View site', 'opens in a new tab'],
        ];
    }

    private function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument;
        $document->loadHTML('<meta charset="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);

        return new DOMXPath($document);
    }
}
