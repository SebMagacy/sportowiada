---
tags:
  - filament
  - logowanie
  - rejestracja
  - reset-hasla
---

# 48. Logowanie do panelu Filament

Publicznie dostępny ekran `/admin/login` umożliwia przejście do rejestracji i odzyskanie dostępu do konta. Zmiany opublikowano na `main` w commicie [`9ca323c`](https://github.com/SebMagacy/sportowiada/commit/9ca323cc8630e2f7957d7518285c7c0b0755a1e9). [Zgłoszenie #47](https://github.com/SebMagacy/sportowiada/issues/47)

GitHub potwierdził zamknięcie zgłoszenia #47 jako ukończonego 23 listopada 2026 r. [Podsumowanie weryfikacji i zrzuty](https://github.com/SebMagacy/sportowiada/issues/47#issuecomment-7098411581)

## Rejestracja z ekranu logowania

W [`AdminPanelProvider.php`](../app/Providers/Filament/AdminPanelProvider.php) punkt rozszerzenia `PanelsRenderHook::AUTH_LOGIN_FORM_AFTER` wstawia [widok linku](../resources/views/filament/auth/registration-link.blade.php) pod formularzem. Komponent `<x-filament::link>` wyświetla „Zarejestruj się” po tekście „Nie masz konta?” i pobiera adres `/register` z nazwanej trasy `register`.

W opublikowanej wersji z commita `9ca323c` rejestracja kierowała użytkownika do publicznego `/login`. Zwykłe konto po zalogowaniu trafiało na stronę główną. Dostęp do panelu wymaga roli `panel_user` lub `super_admin`.

## Reset hasła

Wywołanie `->passwordReset()` w konfiguracji panelu dodaje odnośnik „Nie pamiętam hasła” i dwie trasy:

- `/admin/password-reset/request` wyświetla formularz zgłoszenia resetu. Filament kolejkuje powiadomienie dla uprawnionego konta i wyświetla ogólny komunikat po zgłoszeniu.
- `/admin/password-reset/reset` otwiera formularz ustawienia nowego hasła po przejściu z linku zawierającego token.

W teście lokalnym przy `MAIL_MAILER=log` wiadomość trafiła do `storage/logs/laravel.log`. Przy `QUEUE_CONNECTION=database` wpis pojawił się po uruchomieniu `php artisan queue:work`. Dostarczenie do skrzynki e-mail wymaga skonfigurowanego transportu poczty i osobnej weryfikacji.

## Potwierdzona weryfikacja

- Użytkownik przeszedł klawiszem Tab do linku rejestracji, zobaczył widoczny fokus i otworzył `/register` klawiszem Enter w sesji gościa.
- Użytkownik założył nowe konto i potwierdził przekierowanie na stronę główną po zalogowaniu. Osobno otworzył link resetujący z logu, ustawił nowe hasło i zalogował się nim do panelu.
- Zrzuty z telefonu i komputera pokazują komunikat „Błędny login lub hasło”, pola formularza, opcję „Zapamiętaj mnie”, przycisk logowania i oba linki. [Komentarz ze zrzutami w #47](https://github.com/SebMagacy/sportowiada/issues/47#issuecomment-7098411581)
- Użytkownik uruchomił [`FilamentPanelAccessTest.php`](../tests/Feature/FilamentPanelAccessTest.php). Przeszło 11 testów i 37 asercji, w tym kontrola tekstu i adresu odnośnika do rejestracji. Pint i PHPStan również przeszły.

## Ujednolicenie logowania w wersji roboczej

Link na stronie głównej, przekierowanie po rejestracji i obsługa gościa w `bootstrap/app.php` wskazują na `/admin/login`. Własny widok Blade i jego kontroler usunięto, a `/login` zwraca 404. Rozszerzony formularz Filamentu loguje również zwykłe konta. Odpowiedź po logowaniu kieruje je na `/`, a kontrola dostępu do `/admin` nadal sprawdza role panelu.

Użytkownik potwierdził test logowania zwykłego konta oraz 15 testów rejestracji. Asystent potwierdził wynik testów strony głównej i wylogowania po usunięciu `/login` (5 testów, 20 asercji). Reset hasła dla zwykłego konta pozostaje do sprawdzenia.

Planowany przepływ rejestracji zaczyna się od linku na stronie głównej. Po założeniu konta w Filamencie użytkownik ma zostać automatycznie zalogowany i wrócić na `/`, gdzie zobaczy treści dostępne po zalogowaniu. [Zgłoszenie #48](https://github.com/SebMagacy/sportowiada/issues/48)
