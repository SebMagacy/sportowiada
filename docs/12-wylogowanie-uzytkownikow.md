# Wylogowanie użytkowników

Wylogowanie budujemy małymi krokami po logowaniu opisanym w [[10-logowanie-uzytkownikow]] i rejestracji opisanej w [[11-rejestracja-uzytkownikow]]. Dotychczas utworzono kontroler, dodano obsługę zakończenia sesji i zarejestrowano trasę POST. Użytkownik potwierdził obecność trasy wynikiem polecenia Artisan. Dodano formularz w panelu administratora i ręcznie potwierdzono przekierowanie po wylogowaniu oraz wymaganie ponownego logowania przy wejściu do panelu. Testy automatyczne są odłożone.

## 1. Utworzenie kontrolera

### 1.1. Polecenie Artisan

W katalogu projektu wykonujemy:

```bash
php artisan make:controller Auth/LogoutController --no-interaction
```

Polecenie tworzy kontroler w katalogu `Auth`, obok `LoginController` i `RegistrationController`. Opcja `--no-interaction` wyłącza pytania interaktywne.

### 1.2. Wygenerowany szkielet

Plik: `app/Http/Controllers/Auth/LogoutController.php`

Po utworzeniu plik zawierał:

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    //
}
```

Obecność tego szkieletu potwierdzono odczytem pliku. Samo utworzenie kontrolera nie udostępnia jeszcze wylogowania przez trasę.

## 2. Obsługa wylogowania

### 2.1. Importy i metoda kontrolera

Plik: `app/Http/Controllers/Auth/LogoutController.php`

Dodajemy importy `RedirectResponse` oraz `Auth`, a komentarz w klasie zastępujemy metodą `destroy()`. Kod po uzupełnieniu:

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
```

### 2.2. Znaczenie kolejnych instrukcji

- `Request $request` — Laravel przekazuje do metody bieżące żądanie; przez nie uzyskujemy dostęp do sesji.
- `RedirectResponse` — deklaruje, że metoda zwraca odpowiedź przekierowującą.
- `Auth::logout()` — usuwa informacje uwierzytelniające bieżącego użytkownika z sesji.
- `$request->session()->invalidate()` — usuwa dane sesji i zmienia jej identyfikator.
- `$request->session()->regenerateToken()` — generuje nowy token CSRF po wyczyszczeniu sesji.
- `return redirect('/')` — zwraca przekierowanie na stronę główną.

Wylogowanie kończy uwierzytelnienie w bieżącej sesji; nie usuwa konta użytkownika z bazy. Nie dodano mechanizmu wylogowania ze wszystkich urządzeń.

### 2.3. Stan po kroku

Zapis metody i importów potwierdzono przez odczyt kontrolera. Na tym etapie nie było jeszcze trasy wywołującej tę metodę ani potwierdzenia działania w przeglądarce.

## 3. Trasa wylogowania

### 3.1. Import kontrolera

Plik: `routes/web.php`

Obok importów pozostałych kontrolerów dodajemy:

```php
use App\Http\Controllers\Auth\LogoutController;
```

### 3.2. Definicja trasy

Plik: `routes/web.php`

Na końcu pliku dodajemy:

```php
Route::post('/logout', [LogoutController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');
```

- `Route::post('/logout', ...)` — obsługuje żądanie POST pod ścieżką `/logout`. Wylogowanie zmienia stan sesji, dlatego nie udostępniamy go przez GET.
- `[LogoutController::class, 'destroy']` — wskazuje kontroler i metodę obsługującą żądanie.
- `middleware('auth')` — wymaga zalogowania. Nie ograniczamy wylogowania do administratorów.
- `name('logout')` — pozwoli wygenerować adres formularza przez `route('logout')`.

Trasa znajduje się w `routes/web.php`, więc korzysta z grupy middleware `web`, obejmującej obsługę sesji i ochronę przed CSRF. Przyszły formularz POST powinien zawierać `@csrf`.

### 3.3. Sprawdzenie rejestracji trasy

W terminalu użytkownik wykonał:

```bash
php artisan route:list --path=logout --no-interaction
```

Wynik zawierał jeden wpis:

```text
POST logout ... logout › Auth\LogoutController@destroy
Showing [1] routes
```

Potwierdza to zarejestrowanie trasy POST o nazwie `logout` i powiązanie jej z metodą `destroy()`. Lista tras nie potwierdza jeszcze wykonania wylogowania ani działania middleware w rzeczywistym żądaniu.

## 4. Formularz wylogowania

### 4.1. Dodanie przycisku w panelu

Plik: `resources/views/components/admin-layout.blade.php`

Tuż po `</nav>`, przed `</aside>`, dodajemy:

```html
<form method="POST" action="{{ route('logout') }}">
    @csrf

    <button type="submit">Wyloguj się</button>
</form>
```

- `method="POST"` — odpowiada metodzie trasy wylogowania.
- `action="{{ route('logout') }}"` — generuje adres na podstawie nazwy trasy.
- `@csrf` — dodaje ukryte pole z tokenem sesji używanym przez ochronę przed CSRF. Szczegóły mechanizmu Laravel 13 opisano w [[10-logowanie-uzytkownikow]].
- `type="submit"` — kliknięcie przycisku wysyła formularz do kontrolera.

Obecność formularza potwierdzono odczytem pliku. Istniejący roboczy odnośnik „Użytkownicy” pozostawiono bez usuwania.

## 5. Ręczne sprawdzenie wylogowania

### 5.1. Przebieg i wyniki

Użytkownik sprawdził wylogowanie w przeglądarce i przekazał następujące wyniki:

| Czynność | Potwierdzony wynik |
| --- | --- |
| Kliknięcie „Wyloguj się” w panelu | Przekierowanie na stronę główną |
| Ponowne wejście na `/admin` po wylogowaniu | Wyświetlenie widoku logowania |

Wyniki potwierdzają podstawowy przebieg wylogowania i ponowny wymóg uwierzytelnienia przy dostępie do panelu w sprawdzanej sesji. Nie sprawdzano osobno zmiany identyfikatora sesji ani tokenu CSRF. Nie są to wyniki testów automatycznych.

## 6. Formatowanie kodu

### 6.1. Uruchomienie Pinta

Użytkownik wykonał:

```bash
vendor/bin/pint --dirty --format agent
```

Pint zwrócił `result: fixed` i poprawił:

- `app/Http/Controllers/Auth/LogoutController.php` — odstęp po importach (`single_line_after_imports`).
- `routes/web.php` — kolejność importów i zakończenie pliku (`ordered_imports`, `single_blank_line_at_eof`).

Odczyt plików po formatowaniu potwierdził te poprawki. Formatowanie nie zastępuje sprawdzenia działania aplikacji.

## 7. Bieżący stan

Kontroler, trasa POST i formularz wylogowania istnieją w kodzie. Podstawowy przepływ został sprawdzony ręcznie przez użytkownika, a kod PHP sformatowano Pintem.

Testów automatycznych nie tworzono ani nie uruchamiano. Nie potwierdzono jeszcze commitu ani pushu zmian wylogowania. Roboczy odnośnik „Użytkownicy” w układzie panelu pozostaje zachowany.
