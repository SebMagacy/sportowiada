<?php

namespace Tests\Feature;

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthFormsTest extends TestCase
{
    /**
     * Niebezpieczna treść zachowanego e-maila musi wystąpić jako tekst escapowany, bez surowego HTML.
     */
    #[DataProvider('forms')]
    public function test_form_escapes_old_email(string $routeName): void
    {
        $email = '"><script>alert("xss")</script>';

        $response = $this->withSession(['_old_input' => ['email' => $email]])
            ->get(route($routeName));

        $response->assertOk()->assertSee($email)->assertDontSee($email, false);
    }

    /**
     * Osobne renderowanie widoku sprawdza prezentację przekazanego worka błędów.
     * Ten test nie sprawdza przeniesienia błędów z sesji przez middleware.
     */
    #[DataProvider('forms')]
    public function test_form_view_renders_validation_message(string $routeName): void
    {
        $message = 'Przykładowy błąd formularza';
        $errors = (new ViewErrorBag)->put('default', new MessageBag(['email' => $message]));

        $this->view('auth.'.$routeName, ['errors' => $errors])->assertSeeText($message);
    }

    /**
     * Oba formularze przechodzą te same sprawdzenia jako oddzielne przypadki.
     */
    public static function forms(): array
    {
        return ['login' => ['login'], 'registration' => ['register']];
    }

    /**
     * Przywrócenie imienia po błędzie formularza nie może wstrzyknąć znacznika script do HTML.
     */
    public function test_registration_escapes_old_name(): void
    {
        $name = '<script>alert("name")</script>';

        $this->withSession(['_old_input' => ['name' => $name]])->get(route('register'))
            ->assertOk()->assertSee($name)->assertDontSee($name, false);
    }

    /**
     * Tablica zachowana po błędzie walidacji nie może spowodować błędu renderowania.
     * Pole przyjmuje wyłącznie poprzednią wartość tekstową; inne typy pozostają puste.
     */
    #[DataProvider('arrayInputFields')]
    public function test_array_old_input_renders_as_empty_field(string $routeName, string $field): void
    {
        $response = $this->withSession(['_old_input' => [$field => ['invalid']]])
            ->get(route($routeName));

        $response->assertOk();
        $this->assertMatchesRegularExpression(
            '/<input\b[^>]*name="'.$field.'"[^>]*value=""/',
            $response->getContent(),
        );
    }

    public static function arrayInputFields(): array
    {
        return [
            'registration name' => ['register', 'name'],
            'registration email' => ['register', 'email'],
            'login email' => ['login', 'email'],
        ];
    }
}
