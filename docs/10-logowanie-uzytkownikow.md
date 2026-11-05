# Logowanie użytkowników

Opis budowy logowania od trasy i formularza po kontroler oraz ręczne sprawdzenia. Fragmenty pośrednie uzupełniamy w kolejnych podsekcjach. Testy automatyczne pozostają odłożone zgodnie z ustaleniem użytkownika.

## 1. Wyświetlenie formularza logowania

### 1.1. Zdefiniowanie trasy

Plik: `routes/web.php`

Dodajemy trasę:

```php
Route::view('/login', 'auth.login')->name('login');
```

- `Route::view()` — definiuje trasę wyświetlającą wskazany widok.
- `/login` — ścieżka, pod którą będzie dostępny formularz.
- `auth.login` — nazwa widoku odpowiadająca plikowi
  `resources/views/auth/login.blade.php`.
- `name('login')` — nadaje trasie nazwę `login`. Middleware `auth`
  wykorzystuje tę nazwę, aby przekierować gościa do logowania.

Trasa obsługuje wyświetlenie strony. Nie sprawdza jeszcze adresu e-mail ani hasła. Widok formularza przygotowujemy w następnym kroku.

### 1.2. Przygotowanie widoku formularza

Plik: `resources/views/auth/login.blade.php`

W katalogu `resources/views/auth` tworzymy plik `login.blade.php`. Zawiera on formularz z polami adresu e-mail i hasła, wysyłany metodą POST na adres `/login`. Dyrektywa `@csrf` dodaje token chroniący formularz przed CSRF.

Na tym etapie formularz jest przygotowany do wyświetlenia. Obsługę przesłanych danych dodajemy w kolejnym kroku.

```html
<h1>Logowanie</h1>

<form method="POST" action="{{ route('login') }}">
    @csrf
    <label for="email">Adres e-mail</label>
    <input id="email" name="email" type="email" autocomplete="username" required>

    <label for="password">Hasło</label>
    <input id="password" name="password" type="password" autocomplete="current-password" required>

    <button type="submit">Zaloguj się</button>
</form>
```

Poszczególne elementy formularza oznaczają:

- `method="POST"` — wysyła dane do serwera w treści żądania.
- `action="{{ route('login') }}"` — wyznacza adres docelowy na podstawie nazwy trasy `login`, czyli `/login`.
- `@csrf` — dodaje ukryte pole `_token` z tokenem sesji, wykorzystywane przez ochronę przed CSRF.
- `for` w etykiecie i odpowiadające mu `id` pola — łączą etykietę z polem formularza.
- `name="email"` i `name="password"` — określają nazwy danych przesyłanych do serwera.
- `type="email"` — oznacza pole adresu e-mail, a `type="password"` ukrywa wpisywane znaki hasła.
- `autocomplete="username"` i `autocomplete="current-password"` — pomagają przeglądarce i menedżerom haseł rozpoznać dane logowania.
- `required` — wymaga uzupełnienia pola w przeglądarce; nie zastępuje walidacji na serwerze.
- `type="submit"` — wskazuje przycisk wysyłający formularz.

Trasa wyświetlająca formularz obsługuje GET `/login`. Formularz wysyła POST `/login`, dlatego potrzebna jest osobna trasa obsługująca tę metodę. Samo przygotowanie formularza nie oznacza jeszcze działającego logowania.

W Laravel 13 domyślne middleware `PreventRequestForgery` w grupie `web` najpierw sprawdza pochodzenie żądania przez `Sec-Fetch-Site`. Żądanie `same-origin` może zostać dopuszczone bez porównania tokenów; w pozostałych przypadkach standardowa konfiguracja przechodzi do sprawdzenia tokenu. Dlatego nie zakładamy, że każde żądanie POST zawsze porównuje `_token`. Dyrektywę `@csrf` pozostawiamy w formularzu. [Ochrona CSRF w Laravel 13](https://laravel.com/docs/13.x/csrf#preventing-csrf-requests).

## 2. Obsługa danych logowania

### 2.1. Utworzenie kontrolera

Plik: `app/Http/Controllers/Auth/LoginController.php`

W głównym katalogu projektu wykonujemy polecenie:

```bash
php artisan make:controller Auth/LoginController --no-interaction
```

- `make:controller` — tworzy klasę kontrolera.
- `Auth/LoginController` — określa nazwę kontrolera i umieszcza go w podkatalogu `Auth`.
- `--no-interaction` — wyłącza interaktywne pytania polecenia.

Kontroler będzie odpowiadał za obsługę danych przesłanych z formularza logowania. Samo wygenerowanie pliku nie dodaje jeszcze logiki logowania ani trasy wywołującej kontroler.

Artisan tworzy następujący szkielet kontrolera:

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    //
}
```

Poszczególne elementy szkieletu oznaczają:

- `<?php` — rozpoczyna kod PHP.
- `namespace App\Http\Controllers\Auth` — określa przestrzeń nazw klasy, zgodną z jej położeniem w katalogu `app/Http/Controllers/Auth`.
- `use App\Http\Controllers\Controller` — importuje bazowy kontroler aplikacji.
- `use Illuminate\Http\Request` — importuje klasę reprezentującą żądanie HTTP. Na tym etapie nie jest jeszcze używana; wykorzystamy ją podczas obsługi danych formularza.
- `class LoginController extends Controller` — definiuje klasę kontrolera dziedziczącą po bazowej klasie `Controller`.
- `//` — komentarz wskazujący miejsce na własny kod.

Wygenerowany kontroler nie ma jeszcze żadnej metody obsługującej żądanie. Logikę logowania dodajemy ręcznie w kolejnych krokach.

### 2.2. Dodanie szkieletu metody obsługującej formularz

Plik: `app/Http/Controllers/Auth/LoginController.php`

Do importów na początku pliku dodajemy klasę odpowiedzi przekierowującej:

```php
use Illuminate\Http\RedirectResponse;
```

Wewnątrz klasy `LoginController`, zamiast komentarza `//`, dodajemy szkielet metody:

```php
public function store(Request $request): RedirectResponse
{
    //
}
```

Poszczególne elementy deklaracji oznaczają:

- `public` — metoda jest dostępna spoza klasy, dzięki czemu Laravel może ją wywołać podczas obsługi trasy.
- `function` — słowo kluczowe PHP rozpoczynające deklarację funkcji; funkcję należącą do klasy nazywamy metodą.
- `store` — wybrana przez nas nazwa metody, która będzie obsługiwać dane przesłane z formularza logowania.
- `Request $request` — parametr wymagający obiektu żądania HTTP. Laravel przekaże go automatycznie podczas wywołania metody przez trasę. Przez ten obiekt uzyskamy dostęp do danych formularza.
- `: RedirectResponse` — deklaracja typu zwracanej wartości: metoda ma zwrócić odpowiedź przekierowującą użytkownika na inną stronę.

Jest to etap pośredni: metoda nie zawiera jeszcze logiki ani instrukcji zwracającej odpowiedź wymaganego typu. Nie jest gotowa do obsługi logowania. Jej zawartość oraz połączenie z trasą POST dodajemy w kolejnych krokach.

### 2.3. Walidacja danych formularza

Plik: `app/Http/Controllers/Auth/LoginController.php`

W metodzie `store()`, zamiast komentarza `//`, dodajemy sprawdzenie danych przesłanych z formularza:

```php
$credentials = $request->validate([
    'email' => ['required', 'email'],
    'password' => ['required', 'string'],
]);
```

Poszczególne elementy zapisu oznaczają:

- `$request->validate()` — sprawdza dane żądania według podanych reguł. Walidacja odbywa się na serwerze i działa niezależnie od ograniczeń pól formularza w przeglądarce.
- `'email'` i `'password'` po lewej stronie `=>` — nazwy sprawdzanych pól, odpowiadające atrybutom `name` w formularzu.
- `required` — pole musi być obecne i nie może być puste.
- `email` — wartość musi mieć poprawny format adresu e-mail.
- `string` — wartość musi być tekstem.
- `$credentials` — zmienna, do której trafia tablica danych po poprawnej walidacji. W tym przypadku zawiera adres e-mail i hasło.

Walidacja sprawdza wymaganą postać danych. Nie potwierdza jeszcze, czy konto istnieje ani czy podane hasło jest prawidłowe.

Jeśli dane są niepoprawne, Laravel przerywa dalsze wykonywanie metody i dla zwykłego żądania formularza przekierowuje użytkownika z powrotem, zapisując błędy walidacji w sesji. Wyświetlanie tych komunikatów w widoku wymaga osobnego przygotowania.

Ta ścieżka jest odrębna od nieudanego `Auth::attempt()`. Domyślna obsługa błędów walidacji zachowuje również poprzednie dane wejściowe z pominięciem pól haseł. Dla żądania oczekującego JSON walidacja zwraca standardowo HTTP 422 z błędami zamiast przekierowania.

Po poprawnej walidacji wykonywanie metody jest kontynuowane. Na tym etapie nie dodaliśmy jeszcze próby logowania ani odpowiedzi przekierowującej wymaganej przez deklarację `RedirectResponse`, więc metoda pozostaje niekompletna.

### 2.4. Próba logowania i obsługa nieprawidłowych danych

Plik: `app/Http/Controllers/Auth/LoginController.php`

Do importów na początku pliku dodajemy fasadę `Auth`, udostępniającą mechanizm uwierzytelniania Laravel:

```php
use Illuminate\Support\Facades\Auth;
```

W metodzie `store()`, po walidacji danych, dodajemy:

```php
if (! Auth::attempt($credentials)) {
    return back()->withErrors([
        'email' => 'Nieprawidłowy adres e-mail lub hasło.',
    ])->onlyInput('email');
}
```

Poszczególne elementy zapisu oznaczają:

- `Auth::attempt($credentials)` — wyszukuje użytkownika na podstawie adresu e-mail i sprawdza podane hasło względem skrótu zapisanego w bazie. Po sukcesie loguje użytkownika i zwraca `true`; przy nieprawidłowych danych zwraca `false`.
- `!` — neguje wynik, dlatego blok `if` wykonuje się przy nieudanej próbie logowania.
- `return` — zwraca odpowiedź i kończy wykonywanie metody w tej ścieżce.
- `back()` — tworzy odpowiedź przekierowującą z powrotem do poprzedniej strony, w tym scenariuszu do formularza logowania.
- `withErrors()` — zapisuje komunikat błędu w sesji, przypisując go do pola `email`. Wspólny komunikat nie wskazuje, czy przyczyną był nieznany adres e-mail, czy błędne hasło.
- `onlyInput('email')` — zapisuje w sesji do ponownego wypełnienia formularza wyłącznie adres e-mail, bez hasła. Przywrócenie tej wartości w polu formularza wymaga użycia `old('email')` w widoku.

Walidacja z poprzedniego kroku sprawdza postać danych, natomiast `Auth::attempt()` sprawdza dane uwierzytelniające użytkownika. Logowanie jest wspólne dla zwykłego użytkownika i administratora; reguła dostępu do panelu jest sprawdzana osobno.

Widok formularza nie wyświetla jeszcze komunikatów błędów ani nie przywraca adresu e-mail. Przygotujemy te elementy osobno.

Po udanej próbie logowania warunek `if` jest fałszywy i wykonywanie metody jest kontynuowane. Obsługę tej ścieżki dodajemy w kolejnym kroku; metoda nadal pozostaje niekompletna.

### 2.5. Regeneracja sesji po udanym logowaniu

Plik: `app/Http/Controllers/Auth/LoginController.php`

W metodzie `store()`, za całym blokiem `if` obsługującym nieudaną próbę logowania, dodajemy:

```php
$request->session()->regenerate();
```

- `$request->session()` — daje dostęp do bieżącej sesji użytkownika.
- `regenerate()` — zmienia identyfikator sesji, zachowując jej dane.

W użytej wersji Laravel metoda odświeża również token CSRF. Samo udane `Auth::attempt()` z domyślnym guardem sesyjnym wywołuje już wewnętrzną regenerację sesji przez `SessionGuard::updateSession()`. Jawne wywołanie w kontrolerze pozostaje zgodne z przykładem ręcznego logowania w dokumentacji; nie jest jedynym miejscem, w którym framework zmienia identyfikator sesji. [Ręczne uwierzytelnianie](https://laravel.com/docs/13.x/authentication#authenticating-users).

Sesja pozwala aplikacji rozpoznawać zalogowanego użytkownika podczas kolejnych żądań. Zmiana jej identyfikatora po logowaniu chroni przed atakiem utrwalenia sesji (session fixation), w którym napastnik próbuje wykorzystać identyfikator znany mu przed zalogowaniem użytkownika.

Ta linia znajduje się poza blokiem `if` i wykonuje się po udanym logowaniu. Nieudana próba kończy metodę wcześniej przez `return`.

Regeneracja sesji nie nadaje uprawnienia administratora ani nie przekierowuje użytkownika. Odpowiedź po udanym logowaniu dodajemy w kolejnym kroku; metoda nadal pozostaje niekompletna.

### 2.6. Przekierowanie po udanym logowaniu

Plik: `app/Http/Controllers/Auth/LoginController.php`

W metodzie `store()`, pod regeneracją sesji, dodajemy:

```php
if ($request->user()?->is_admin) {
    return redirect()->route('admin.dashboard');
}

return redirect('/');
```

Poszczególne elementy zapisu oznaczają:

- `$request->user()` — pobiera aktualnie zalogowanego użytkownika.
- `?->is_admin` — odczytuje atrybut `is_admin`. Operator `?->` zwraca `null`, jeśli obiektu użytkownika nie ma, zamiast próbować odczytać jego atrybut. Po udanym logowaniu oczekujemy obecności użytkownika.
- `if` — wykonuje blok przekierowania do panelu, gdy `is_admin` ma wartość `true`.
- `redirect()->route('admin.dashboard')` — tworzy odpowiedź przekierowującą do nazwanej trasy panelu administratora.
- `return` — zwraca odpowiedź i kończy wykonywanie metody, więc administrator nie przechodzi do ostatniej instrukcji.
- `return redirect('/')` — kieruje zwykłego użytkownika na stronę główną.

Ten fragment wybiera stronę docelową po logowaniu. Nie nadaje uprawnień ani nie zastępuje ochrony panelu: dostęp do jego trasy nadal kontrolują middleware `auth` i `can:access-admin`.

Po tym kroku metoda ma odpowiedź przekierowującą zarówno dla nieudanej próby logowania, jak i dla obu ścieżek po sukcesie. Sam opis kodu nie potwierdza jeszcze działania logowania — połączenie formularza z kontrolerem przez trasę POST oraz weryfikację scenariuszy opisujemy osobno.

### 2.7. Połączenie formularza z kontrolerem

Plik: `routes/web.php`

Na początku pliku dodajemy import kontrolera:

```php
use App\Http\Controllers\Auth\LoginController;
```

Pod istniejącą trasą wyświetlającą formularz dodajemy trasę obsługującą jego wysłanie:

```php
Route::post('/login', [LoginController::class, 'store'])->name('login.store');
```

Poszczególne elementy zapisu oznaczają:

- `Route::post()` — rejestruje trasę obsługującą żądania HTTP POST.
- `'/login'` — ścieżka, na którą formularz wysyła dane.
- `[LoginController::class, 'store']` — wskazuje klasę kontrolera i metodę, którą Laravel wywoła podczas obsługi żądania. Laravel przekaże do niej obiekt `Request`.
- `name('login.store')` — nadaje trasie nazwę, której można używać przy generowaniu adresu docelowego formularza.

Obie trasy korzystają z tej samej ścieżki, ale obsługują różne metody HTTP:

| Metoda HTTP | Ścieżka | Zadanie |
| --- | --- | --- |
| GET | `/login` | Wyświetla widok formularza `auth.login` |
| POST | `/login` | Wywołuje metodę `store()` w `LoginController` |

W formularzu opisanym w sekcji 1.2 pozostaje `action="{{ route('login') }}"`. Ta nazwa generuje adres `/login`, a `method="POST"` decyduje, że wysłanie formularza zostanie obsłużone przez trasę POST. Nazwa trasy służy do generowania adresu; wybór trasy przychodzącego żądania zależy od jego metody HTTP i ścieżki.

Po zdefiniowaniu tej trasy formularz ma połączenie z logiką kontrolera. Działanie całego przepływu wymaga osobnej weryfikacji; dodanie definicji nie jest jeszcze potwierdzeniem poprawnego logowania.

## 3. Weryfikacja tras i formularza logowania

### 3.1. Sprawdzenie rejestracji tras

W głównym katalogu projektu wykonano:

```bash
php artisan route:list --path=login --no-interaction
```

Wynik przekazany przez użytkownika zawierał dwie trasy:

| Metoda HTTP | Ścieżka | Nazwa | Obsługa |
| --- | --- | --- | --- |
| GET\|HEAD | `login` | `login` | Wyświetlenie formularza |
| POST | `login` | `login.store` | `Auth\LoginController@store` |

Potwierdzono rejestrację obu tras. Lista tras nie sprawdza poprawności danych logowania ani działania kontrolera po wysłaniu formularza.

### 3.2. Wyświetlenie formularza po próbie wejścia do panelu

Użytkownik wykonał scenariusz wejścia na `/admin` jako gość i potwierdził przekierowanie do `/login`. Na przekazanym zrzucie ekranu widoczne były:

- końcowa ścieżka `/login` w pasku adresu;
- nagłówek „Logowanie”;
- pola „Adres e-mail” i „Hasło”;
- przycisk „Zaloguj się”.

Potwierdzono wyświetlenie formularza i przekierowanie gościa. Scenariusz ochrony panelu opisano również w [[09-kontrola-dostępu-do-panelu-administratora]].

### 3.3. Nieudana próba logowania i wyświetlenie błędu

W formularzu podano testowy adres e-mail i hasło, a następnie kliknięto „Zaloguj się”. Początkowo formularz wyglądał tak samo jak przed wysłaniem, ponieważ widok nie wyświetlał komunikatów błędów.

Na zrzucie z Laravel Debugbar potwierdzono żądanie POST `/login` ze statusem HTTP 302, a następnie GET `/login`. Oznaczało to wysłanie formularza i przekierowanie z powrotem. Sam status 302 nie rozstrzygał, czy przyczyną była walidacja, czy odrzucenie danych przez `Auth::attempt()`.

Plik: `resources/views/auth/login.blade.php`

Pod nagłówkiem „Logowanie” dodajemy:

```html
@error('email')
    <p>{{ $message }}</p>
@enderror
```

- `@error('email')` — sprawdza, czy istnieje błąd przypisany do pola `email`. Jeśli tak, wyświetla zawartość bloku.
- `$message` — zawiera treść komunikatu błędu.
- `{{ $message }}` — wyświetla komunikat, stosując escapowanie znaków HTML.
- `@enderror` — kończy blok obsługi błędu.

Po ponownym wysłaniu nieprawidłowych danych użytkownik potwierdził na zrzucie ekranu komunikat: **„Nieprawidłowy adres e-mail lub hasło.”** Jest to komunikat z bloku obsługującego nieudaną próbę `Auth::attempt()` w kontrolerze.

Potwierdzono wysłanie formularza, odrzucenie nieprawidłowych danych logowania, przekierowanie z powrotem i wyświetlenie błędu. Poprawne logowanie i przekierowania sprawdzono osobno w kolejnych scenariuszach.

### 3.4. Zachowanie adresu e-mail po nieudanej próbie

Plik: `resources/views/auth/login.blade.php`

Do pola adresu e-mail dodajemy atrybut `value` korzystający z funkcji `old()`:

```html
<input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required>
```

`old('email')` odczytuje poprzednią wartość pola zapisaną w sesji. Przy nieudanej próbie logowania kontroler zachowuje ją przez `onlyInput('email')`. Dzięki temu użytkownik nie musi ponownie wpisywać adresu e-mail.

Hasła nie przywracamy w formularzu.

Kryterium sprawdzenia: po kolejnej nieudanej próbie logowania formularz wyświetla komunikat błędu i zachowuje wpisany adres e-mail, a pole hasła pozostaje puste.

Wynik potwierdzony przez użytkownika: po nieudanej próbie komunikat błędu jest widoczny, wpisany adres e-mail pozostaje w formularzu, a pole hasła jest puste.

Status: **zachowanie adresu e-mail i puste pole hasła potwierdzone w przeglądarce**.

### 3.5. Poprawne logowanie administratora

Użytkownik podał właściwe dane konta administratora i potwierdził udane logowanie oraz przekierowanie do `/admin`, gdzie uzyskał dostęp do panelu.

Wynik odpowiada gałęzi metody `store()`, która dla użytkownika z `is_admin` równym `true` zwraca przekierowanie do trasy `admin.dashboard`.

Status: **logowanie administratora i przekierowanie do panelu potwierdzone przez użytkownika w przeglądarce**.

### 3.6. Poprawne logowanie zwykłego użytkownika

Użytkownik zalogował się na zwykłe konto i potwierdził przekierowanie na stronę główną (`/`). Wynik odpowiada końcowej instrukcji `return redirect('/')` w metodzie `store()`.

Następnie sprawdzono bezpośrednie wejście tego użytkownika na `/admin`. Serwer odmówił dostępu, zwracając HTTP 403 z komunikatem „This action is unauthorized.”. Ten scenariusz autoryzacji opisano w [[09-kontrola-dostępu-do-panelu-administratora]].

Status: **logowanie zwykłego użytkownika, przekierowanie na stronę główną i odmowa dostępu do panelu potwierdzone przez użytkownika w przeglądarce**.

Opisane wyniki pochodzą z ręcznych sprawdzeń wykonanych przez użytkownika. Nie są wynikiem automatycznych testów logowania.

## 4. Stan po etapie i ograniczenia

### 4.1. Zakres implementacji

Zaimplementowano podstawowe logowanie sesyjne i przekierowania. Formularz pokazuje błędy przypisane do `email`, ale nie renderuje osobnego komunikatu dla `password`. Samo zapisanie błędów w sesji nie sprawia, że pojawią się w HTML.

Trasy logowania nie mają jeszcze jawnego ograniczania liczby prób ani middleware `guest`. Przekierowanie po sukcesie sprawdza bezpośrednio `is_admin`; ochronę panelu nadal zapewnia oddzielny Gate. Nie dodano wylogowania, odzyskiwania hasła ani weryfikacji adresu e-mail. Ręczne sprawdzenie podstawowych scenariuszy nie oznacza ukończenia całego mechanizmu kont.
