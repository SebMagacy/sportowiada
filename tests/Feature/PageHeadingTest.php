<?php

namespace Tests\Feature;

use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\View\ViewException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PageHeadingTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[DataProvider('icons')]
    public function test_named_icon_renders_the_selected_svg_with_caller_attributes(string $name, string $shape): void
    {
        $view = $this->blade('<x-ui.icon :name="$name" class="size-10 text-blue-600" data-testid="icon" />', ['name' => $name]);

        $xpath = $this->xpath((string) $view);
        $this->assertSame(1, $xpath->query('//span[@data-testid="icon" and @aria-hidden="true"]/svg')->length);
        $this->assertSame(1, $xpath->query('//span[contains(concat(" ", @class, " "), " size-10 ") and contains(concat(" ", @class, " "), " text-blue-600 ")]')->length);
        $this->assertSame(1, $xpath->query('//svg[@viewbox="0 -960 960 960" and @fill="currentColor"]/'.$shape)->length);
    }

    /** @return array<string, array{string, string}> */
    public static function icons(): array
    {
        return [
            'edit' => ['edit', 'path[starts-with(@d, "M200-200h57")]'],
            'users' => ['users', 'path[starts-with(@d, "M40-160v-112")]'],
            'dashboard' => ['dashboard', 'path[starts-with(@d, "M200-120q-33")]'],
        ];
    }

    public function test_unknown_icon_name_cannot_select_an_arbitrary_file(): void
    {
        $this->expectException(ViewException::class);
        $this->expectExceptionMessage('Nieznana ikona.');

        $this->blade('<x-ui.icon name="../../.env" />');
    }

    public function test_heading_renders_title_without_an_icon_and_preserves_attributes(): void
    {
        $view = $this->blade('<x-ui.page-heading id="page-title" class="text-blue-600">Użytkownicy</x-ui.page-heading>');

        $xpath = $this->xpath((string) $view);
        $this->assertSame(1, $xpath->query('//h1[@id="page-title" and contains(@class, "text-blue-600")]')->length);
        $this->assertSame('Użytkownicy', $xpath->evaluate('normalize-space(//h1)'));
        $this->assertSame(0, $xpath->query('//h1//*[@aria-hidden="true"]')->length);
    }

    public function test_heading_renders_a_decorative_icon_without_hiding_the_title(): void
    {
        $view = $this->blade(<<<'BLADE'
            <x-ui.page-heading>
                <x-slot:icon>
                    <x-ui.icon name="edit" class="size-full" />
                </x-slot:icon>
                Edycja
            </x-ui.page-heading>
            BLADE);

        $xpath = $this->xpath((string) $view);
        $this->assertSame(1, $xpath->query('//h1')->length);
        $this->assertSame(1, $xpath->query('//h1//*[@aria-hidden="true"]//svg')->length);
        $this->assertSame(1, $xpath->query('//h1/span[not(@aria-hidden) and normalize-space(.)="Edycja"]')->length);
        $this->assertSame(0, $xpath->query('//h1[@aria-hidden="true"]')->length);
    }

    #[DataProvider('locales')]
    public function test_admin_pages_render_localized_headings_and_escape_the_edited_name(string $locale, string $dashboard, string $users, string $edit): void
    {
        $this->app->setLocale($locale);
        $admin = User::factory()->create(['is_admin' => true]);
        $name = 'Jan <script>alert(1)</script> & Kowalski';
        $user = User::factory()->create(['name' => $name]);
        $this->actingAs($admin);

        foreach ([
            [route('admin.dashboard'), $dashboard],
            [route('admin.users.index'), $users],
            [route('admin.users.edit', $user), $edit.$name],
        ] as [$url, $title]) {
            $response = $this->get($url);

            $response->assertOk();
            $xpath = $this->xpath($response->getContent());
            $this->assertSame(1, $xpath->query('//header//h1')->length);
            $this->assertSame($title, $xpath->evaluate('normalize-space(//header//h1)'));
            $this->assertSame(1, $xpath->query('//header//h1//svg')->length);
            $this->assertSame(0, $xpath->query('//header//h1//script')->length);
        }

        $response->assertSee($name)->assertDontSee($name, false);
    }

    /** @return array<string, array{string, string, string, string}> */
    public static function locales(): array
    {
        return [
            'Polish' => ['pl', 'Panel administratora', 'Użytkownicy', 'Edycja: '],
            'English' => ['en', 'Admin panel', 'Users', 'Edit: '],
        ];
    }

    private function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument;
        $document->loadHTML('<meta charset="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);

        return new DOMXPath($document);
    }
}
