# Rejestracja użytkowników

Rejestrację budujemy od początku, małymi krokami. Poniżej opisano trasy GET i POST, formularz, kontroler, wyświetlanie błędów oraz przywracanie imienia i adresu e-mail. Ręcznie potwierdzono podstawowy przepływ rejestracji i logowania, komunikaty dla zajętego adresu, niezgodnego potwierdzenia i zbyt krótkiego hasła oraz zachowanie danych formularza po błędzie. Nie oznacza to ukończenia wszystkich funkcji kont ani wykonania testów automatycznych.

## 1. Wyświetlenie formularza rejestracji

### 1.1. Stan początkowy

Punktem wyjścia są działające logowanie i kontrola dostępu opisane w [[09-kontrola-dostępu-do-panelu-administratora]] oraz [[10-logowanie-uzytkownikow]]. W `routes/web.php` brakowało tras rejestracji.

Zgodnie z ustaleniem użytkownika wcześniejsze robocze wersje formularza i kontrolera pomijamy w toku nauki. Oba pliki przygotujemy od początku, wyjaśniając kolejne fragmenty. Nie traktujemy ich dotychczasowej zawartości jako wykonanego etapu rejestracji. To ustalenie nie oznacza usunięcia plików z dysku.

### 1.2. Zdefiniowanie trasy

Plik: `routes/web.php`

Na końcu pliku dodajemy:

```php
Route::view('/register', 'auth.register')->name('register');
```

Poszczególne elementy zapisu oznaczają:

- `Route::view()` — definiuje trasę wyświetlającą wskazany widok; obsługuje GET i HEAD.
- `/register` — ścieżka formularza rejestracji.
- `auth.register` — nazwa widoku odpowiadająca plikowi `resources/views/auth/register.blade.php`.
- `name('register')` — nadaje trasie nazwę `register`, używaną do generowania adresu przez `route('register')`.

Nazwa trasy pozwoli później wygenerować adres `/register` przez `route('register')`. Dodana trasa służy do wyświetlania widoku. Przygotowanie tego widoku oraz osobnej trasy POST połączonej z kontrolerem opiszemy w kolejnych krokach.

### 1.3. Stan po kroku

Obecność definicji trasy potwierdzono przez odczyt `routes/web.php`. Nie wykonano jeszcze sprawdzenia listy tras ani wyświetlenia formularza w przeglądarce po tej zmianie.

Kryterium sprawdzenia trasy: zarejestrowana trasa GET|HEAD `register` o nazwie `register`. Wyświetlenie formularza sprawdzimy po przygotowaniu widoku od początku. Kontroler, wysłanie formularza i zapis użytkownika pozostają dalszymi krokami.

Testy automatyczne pozostają odłożone zgodnie z ustaleniem użytkownika.

## 2. Przygotowanie widoku formularza

### 2.1. Minimalny widok

Plik: `resources/views/auth/register.blade.php`

Tworzenie widoku od początku rozpoczynamy od nagłówka:

```html
<h1>Rejestracja</h1>
```

`<h1>` oznacza główny nagłówek strony. Nazwa widoku `auth.register` w trasie odpowiada temu plikowi Blade.

### 2.2. Szkielet formularza

Plik: `resources/views/auth/register.blade.php`

Pod nagłówkiem dodajemy formularz:

```html
<form method="POST" action="{{ route('register') }}">
    @csrf
</form>
```

Poszczególne elementy zapisu oznaczają:

- `<form>` i `</form>` — wyznaczają początek i koniec formularza. Pola dodamy pomiędzy nimi.
- `method="POST"` — określa metodę HTTP używaną przy wysłaniu danych.
- `action="{{ route('register') }}"` — generuje adres docelowy `/register` na podstawie nazwy trasy.
- `@csrf` — dyrektywa Blade dodająca ukryte pole `_token` z tokenem sesji, wykorzystywane przez ochronę przed CSRF.

W Laravel 13 domyślna ochrona najpierw sprawdza pochodzenie żądania, a w razie potrzeby porównuje token. Nie oznacza to bezwarunkowego sprawdzania tokenu przy każdym POST. Mechanizm i powód pozostawienia `@csrf` opisano w [[10-logowanie-uzytkownikow]].

Nazwa trasy generuje adres, a metoda formularza określa rodzaj wysyłanego żądania. Istniejąca trasa GET wyświetla widok; obsługa POST wymaga osobnej trasy, którą dodamy później.

### 2.3. Pole imienia i nazwiska

Plik: `resources/views/auth/register.blade.php`

Wewnątrz formularza, pod `@csrf`, dodajemy:

```html
<label for="name">Imię i nazwisko</label>
<input id="name" name="name" type="text" autocomplete="name" required>
```

- `for="name"` łączy etykietę z polem o `id="name"`. Kliknięcie etykiety ustawia fokus na polu.
- `name="name"` określa nazwę danych przesyłanych do Laravel.
- `type="text"` tworzy pole tekstowe.
- `autocomplete="name"` pozwala przeglądarce podpowiedzieć imię i nazwisko.
- `required` wymaga uzupełnienia pola w przeglądarce. Nie zastępuje walidacji serwerowej.

### 2.4. Pole adresu e-mail

Plik: `resources/views/auth/register.blade.php`

Pod polem imienia i nazwiska dodajemy:

```html
<label for="email">Adres e-mail</label>
<input id="email" name="email" type="email" autocomplete="email" required>
```

- `for="email"` łączy etykietę z polem o `id="email"`.
- `name="email"` określa klucz przesyłany do Laravel.
- `type="email"` pozwala przeglądarce sprawdzić podstawowy format adresu.
- `autocomplete="email"` umożliwia podpowiedź zapisanego adresu.
- `required` wymaga uzupełnienia pola.

Sprawdzenie formatu w przeglądarce nie potwierdza istnienia adresu ani jego dostępności w bazie. Walidację serwerową przygotujemy osobno.

### 2.5. Pole hasła

Plik: `resources/views/auth/register.blade.php`

Pod polem adresu e-mail dodajemy:

```html
<label for="password">Hasło</label>
<input id="password" name="password" type="password" autocomplete="new-password" required>
```

- `for="password"` łączy etykietę z polem o `id="password"`.
- `name="password"` określa nazwę, pod którą Laravel otrzyma hasło.
- `type="password"` maskuje wpisywane znaki; nie szyfruje przesyłanych danych.
- `autocomplete="new-password"` informuje menedżer haseł o tworzeniu nowego hasła.
- `required` wymaga uzupełnienia pola w przeglądarce.

Hasła nie uzupełniamy przez `value` ani `old()`.

### 2.6. Potwierdzenie hasła

Plik: `resources/views/auth/register.blade.php`

Pod polem hasła dodajemy:

```html
<label for="password_confirmation">Powtórz hasło</label>
<input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
```

Etykieta jest powiązana z polem przez zgodne wartości `for` i `id`. Pole maskuje znaki i jest wymagane.

Nazwa `password_confirmation` jest przygotowana do reguły walidacji Laravel `confirmed`, którą dodamy później do pola `password`. Reguła porówna obie wartości na serwerze. Samo dodanie pola nie sprawdza zgodności haseł.

### 2.7. Przycisk wysłania formularza

Plik: `resources/views/auth/register.blade.php`

Pod potwierdzeniem hasła, przed `</form>`, dodajemy:

```html
<button type="submit">Utwórz konto</button>
```

`type="submit"` powoduje wysłanie formularza metodą POST na adres wskazany w `action`. Sam przycisk nie tworzy konta — potrzebna będzie obsługa żądania po stronie Laravel.

### 2.8. Stan po przygotowaniu formularza

W toku nauki omówiono nagłówek, szkielet formularza, cztery pola i przycisk. Ich obecność potwierdzono przez odczyt `resources/views/auth/register.blade.php`.

Nie potwierdzono wyświetlenia widoku w przeglądarce ani wysłania formularza. Nie ma jeszcze trasy POST rejestracji. Przygotowanie kontrolera od początku pozostaje następnym krokiem nauki; wcześniejszej wersji roboczej nie traktujemy jako ukończonego etapu.

## 3. Obsługa danych rejestracji

### 3.1. Utworzenie kontrolera od początku

Plik: `app/Http/Controllers/Auth/RegistrationController.php`

W głównym katalogu projektu używamy polecenia Artisan:

```bash
php artisan make:controller Auth/RegistrationController --force --no-interaction
```

- `make:controller` — generuje szkielet kontrolera.
- `Auth/RegistrationController` — określa nazwę klasy i podkatalog `Auth`.
- `--force` — pozwala zastąpić istniejący plik. W tym konkretnym kroku celowo zastępujemy wcześniejszą wersję roboczą zgodnie z ustaleniem użytkownika. Przy tworzeniu nowego pliku ta opcja nie jest potrzebna.
- `--no-interaction` — wyłącza interaktywne pytania.

Użytkownik przekazał następujący szkielet:

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RegistrationController extends Controller
{
    //
}
```

`namespace` określa przestrzeń nazw klasy, a `extends Controller` oznacza dziedziczenie po bazowym kontrolerze aplikacji. Import `Request` przygotowuje klasę do odbierania danych żądania. Sam szkielet nie obsługuje jeszcze rejestracji.

### 3.2. Szkielet metody obsługującej formularz

Plik: `app/Http/Controllers/Auth/RegistrationController.php`

Do importów dodajemy:

```php
use Illuminate\Http\RedirectResponse;
```

Wewnątrz klasy, zamiast komentarza, dodajemy:

```php
public function store(Request $request): RedirectResponse
{
    //
}
```

- `public` — pozwala Laravel wywołać metodę podczas obsługi trasy.
- `store` — nazwa metody obsługującej dane rejestracji.
- `Request $request` — obiekt żądania, który Laravel przekaże automatycznie przy wywołaniu metody przez trasę.
- `: RedirectResponse` — deklaruje odpowiedź przekierowującą jako wynik metody.

To etap pośredni. Metoda wymaga jeszcze logiki i zwrócenia odpowiedzi zadeklarowanego typu.

### 3.3. Walidacja danych

Plik: `app/Http/Controllers/Auth/RegistrationController.php`

W metodzie `store()`, zamiast komentarza, dodajemy walidację. Reguły poznawaliśmy kolejno: imię i nazwisko, adres e-mail, a następnie hasło. Po ich dodaniu cały fragment wygląda tak:

```php
$validated = $request->validate([
    'name' => ['required', 'string', 'max:255'],
    'email' => ['required', 'email', 'max:255', 'unique:users,email'],
    'password' => ['required', 'string', 'min:8', 'confirmed'],
]);
```

Wszystkie trzy wpisy muszą znajdować się wewnątrz jednej tablicy, przed zamykającym `]);`.

- `required` — pole jest wymagane i nie może być puste.
- `string` — wartość musi być tekstem.
- `max:255` — ogranicza długość tekstu do 255 znaków.
- `email` — sprawdza format adresu e-mail.
- `unique:users,email` — sprawdza, czy adres nie występuje już w kolumnie `email` tabeli `users`.
- `min:8` — wymaga hasła o długości co najmniej ośmiu znaków.
- `confirmed` — porównuje hasło z polem `password_confirmation`. Nie dodajemy osobnej reguły dla potwierdzenia hasła.

`$request->validate()` wykonuje sprawdzenie na serwerze, niezależnie od ograniczeń formularza w przeglądarce. Przy niepowodzeniu Laravel przerywa dalsze wykonywanie metody i dla zwykłego żądania formularza przekierowuje z powrotem z błędami zapisanymi w sesji. Ich wyświetlanie w widoku wymaga osobnego przygotowania.

Po sukcesie `$validated` zawiera zwalidowane pola `name`, `email` i `password`. Walidacja adresu nie potwierdza własności skrzynki pocztowej. Na tym etapie metoda nadal wymaga zapisu konta i odpowiedzi przekierowującej.

Reguła `email` sprawdza format, bez potwierdzania istnienia skrzynki. `unique` wykonuje sprawdzenie przed zapisem, ale nie zastępuje indeksu unikalnego w bazie. Istniejąca migracja `users` definiuje taki indeks przez `$table->string('email')->unique()`. Indeks chroni również przed równoczesnym zapisem tego samego adresu. Obsługi takiego konfliktu jako przyjaznego komunikatu nie dodano w tym kroku.

### 3.4. Utworzenie użytkownika

Plik: `app/Http/Controllers/Auth/RegistrationController.php`

Do importów dodajemy model:

```php
use App\Models\User;
```

Pod zamykającym walidację `]);` dodajemy:

```php
User::create($validated);
```

`create()` tworzy i zapisuje rekord użytkownika na podstawie zwalidowanych danych. W istniejącym modelu `User` atrybut `Fillable` dopuszcza masowe przypisanie `name`, `email` i `password`, bez `is_admin`.

Model ma już cast `'password' => 'hashed'`, dlatego automatycznie haszuje przypisane hasło przed zapisem. Nie dodajemy osobnego haszowania w kontrolerze. Pole potwierdzenia hasła nie trafia do danych zapisywanego konta.

`create()` zapisuje rekord od razu, bez osobnego `save()`. Operacja może zakończyć się wyjątkiem bazy lub haszowania; samo przejście walidacji nie gwarantuje sukcesu zapisu. Przy pominięciu `is_admin` baza stosuje domyślną wartość `false` z migracji opisanej w [[09-kontrola-dostępu-do-panelu-administratora]].

### 3.5. Przekierowanie do logowania

Plik: `app/Http/Controllers/Auth/RegistrationController.php`

Bezpośrednio pod `User::create($validated);` dodajemy:

```php
return redirect()->route('login');
```

`route('login')` wskazuje nazwaną trasę formularza logowania. `return` kończy metodę i zwraca odpowiedź typu `RedirectResponse`. Utworzenie konta nie loguje użytkownika automatycznie — po zapisie użytkownik ma przejść do logowania.

### 3.6. Sprawdzenie kontrolera i stan etapu

Odczyt pliku potwierdził poprawne umieszczenie importów, reguł walidacji, zapisu konta i przekierowania. Wykonano kontrolę składni:

```bash
php -l app/Http/Controllers/Auth/RegistrationController.php
```

Wynik:

```text
No syntax errors detected in app/Http/Controllers/Auth/RegistrationController.php
```

Kontrola potwierdza poprawność składni PHP. Nie potwierdza wykonania walidacji, zapisu rekordu ani przekierowania podczas rzeczywistego żądania.

Na zakończenie pracy nad samym kontrolerem brakowało jeszcze trasy POST łączącej formularz z metodą `store()`. Jej dodanie opisano w sekcji 4. Nie potwierdzono jeszcze utworzenia konta przez formularz. Testy automatyczne pozostają odłożone.

Na etapie przygotowania kontrolera widok nie pokazywał jeszcze błędów walidacji ani nie przywracał imienia i adresu e-mail przez `old()`. Uzupełnienie widoku opisano w sekcji 6. Nie dodano jawnego limitowania żądań rejestracji ani weryfikacji adresu e-mail. Obecne `min:8` określa tylko minimalną długość hasła; nie jest pełną polityką haseł ani sprawdzeniem wszystkich ograniczeń używanego algorytmu haszowania.

## 4. Połączenie formularza z kontrolerem

### 4.1. Import kontrolera

Plik: `routes/web.php`

Na początku pliku, obok importu `LoginController`, dodajemy:

```php
use App\Http\Controllers\Auth\RegistrationController;
```

Import pozwala wskazać kontroler przez krótką nazwę `RegistrationController` w definicji trasy. Sam import nie rejestruje trasy ani nie wywołuje metody `store()`.

### 4.2. Trasa obsługująca wysłanie formularza

Plik: `routes/web.php`

Pod istniejącą trasą GET rejestracji dodajemy:

```php
Route::post('/register', [RegistrationController::class, 'store'])
    ->name('register.store');
```

- `Route::post()` — rejestruje trasę dla żądań HTTP POST.
- `/register` — ścieżka, na którą formularz wysyła dane.
- `[RegistrationController::class, 'store']` — wskazuje klasę kontrolera i metodę obsługującą żądanie. Laravel przekaże do niej obiekt `Request`.
- `name('register.store')` — nadaje trasie osobną nazwę, której można użyć do generowania adresu.

Obie trasy mają tę samą ścieżkę, ale różne metody HTTP i nazwy:

| Metoda HTTP | Ścieżka | Nazwa | Zadanie |
| --- | --- | --- | --- |
| GET\|HEAD | `/register` | `register` | Wyświetlenie widoku `auth.register` |
| POST | `/register` | `register.store` | Wywołanie `RegistrationController::store()` |

### 4.3. Adres docelowy formularza

Plik: `resources/views/auth/register.blade.php`

Pozostawiamy dotychczasowy znacznik formularza:

```html
<form method="POST" action="{{ route('register') }}">
```

`route('register')` generuje adres `/register`. Nazwa trasy nie narzuca metody HTTP wysyłanego żądania — określa ją `method="POST"`. Laravel dopasuje więc żądanie do nowej trasy POST i wywoła kontroler.

Użycie `route('register.store')` byłoby czytelniejszym wskazaniem celu formularza, ale na tym etapie nie zmienialiśmy jego `action`.

### 4.4. Stan po kroku

Odczyt `routes/web.php` potwierdził dodanie importu kontrolera i definicji trasy POST. W chwili dodania definicji nie wykonano jeszcze sprawdzenia listy tras ani wysłania formularza w przeglądarce. Wyniki późniejszej weryfikacji opisano w sekcji 5.

Kryterium dalszej weryfikacji: lista tras pokazuje GET|HEAD `register` o nazwie `register` oraz POST `register` o nazwie `register.store`, obsługiwany przez `Auth\RegistrationController@store`. Następnie należy osobno sprawdzić zachowanie formularza i kontrolera. Testy automatyczne pozostają odłożone.

## 5. Ręczna weryfikacja rejestracji

### 5.1. Sprawdzenie zarejestrowanych tras

W głównym katalogu projektu użytkownik wykonał:

```bash
php artisan route:list --path=register --no-interaction
```

Przekazany wynik zawierał dwie trasy:

| Metoda HTTP | Ścieżka | Nazwa | Obsługa |
| --- | --- | --- | --- |
| GET\|HEAD | `register` | `register` | Wyświetlenie formularza |
| POST | `register` | `register.store` | `Auth\RegistrationController@store` |

Opcja `--path=register` filtruje listę według ścieżki, a `--no-interaction` wyłącza interaktywne pytania. Lista potwierdza rejestrację tras; nie wysyła formularza ani nie tworzy konta.

Status: **obie trasy rejestracji potwierdzone wynikiem polecenia przekazanym przez użytkownika**.

### 5.2. Wyświetlenie formularza

Użytkownik otworzył ścieżkę `/register` w lokalnej Sportowiadzie. Przekazany zrzut ekranu potwierdził:

- nagłówek „Rejestracja”;
- pola „Imię i nazwisko”, „Adres e-mail”, „Hasło” i „Powtórz hasło”;
- przycisk „Utwórz konto”.

Formularz miał domyślny wygląd przeglądarki, bez osobnego stylowania. Sprawdzenie dotyczyło obecności elementów, a nie poprawności zapisu danych.

Status: **wyświetlenie formularza potwierdzone zrzutem ekranu**.

### 5.3. Poprawna rejestracja

Użytkownik wypełnił formularz i wysłał go przyciskiem „Utwórz konto”. Potwierdził przekierowanie do widoku logowania.

W istniejącym kontrolerze przekierowanie następuje po walidacji i `User::create($validated)`. Wynik jest zgodny z przejściem tej ścieżki. Możliwość użycia nowego konta sprawdzono osobno w następnym kroku; nie wykonywano bezpośredniego odczytu rekordu z bazy.

Status: **przekierowanie do logowania po wysłaniu poprawnych danych potwierdzone przez użytkownika**.

### 5.4. Logowanie na nowo utworzone konto

Po rejestracji użytkownik zalogował się danymi nowego konta. Potwierdził udane logowanie i przekierowanie na stronę główną `/`.

Sprawdzenie potwierdza możliwość użycia utworzonego konta do logowania oraz przekierowanie przewidziane dla zwykłego użytkownika. Nie jest osobnym sprawdzeniem wartości `is_admin` w bazie ani próbą wejścia nowego konta do panelu administratora.

Status: **podstawowy przepływ rejestracja → logowanie → strona główna potwierdzony ręcznie przez użytkownika**.

### 5.5. Zakres potwierdzenia i następny krok

Na zakończenie tej części potwierdzono rejestrację tras, wyświetlenie formularza, przekierowanie po rejestracji i możliwość zalogowania się na nowe konto. Scenariusze błędnych danych sprawdzono później, po zmianach widoku opisanych w sekcji 6.

Następnie dodano wyświetlanie błędów walidacji i przywracanie danych formularza. Testy automatyczne pozostają odłożone; powyższe wyniki pochodzą z ręcznych sprawdzeń użytkownika.

## 6. Błędy walidacji i zachowanie danych formularza

### 6.1. Wyświetlanie komunikatów błędów

Plik: `resources/views/auth/register.blade.php`

Pomiędzy nagłówkiem „Rejestracja” a formularzem dodajemy:

```html
@if ($errors->any())
    <ul>
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
@endif
```

- `$errors` — obiekt udostępniany widokowi przez middleware grupy `web`, zawierający błędy walidacji przekazane przez sesję.
- `any()` — sprawdza, czy są komunikaty błędów. Dzięki warunkowi pusta lista nie jest wyświetlana.
- `all()` — pobiera wszystkie komunikaty z domyślnego zbioru błędów.
- `@foreach` — przechodzi po komunikatach, przypisując każdy kolejny do `$error`.
- `<ul>` i `<li>` — tworzą listę komunikatów.
- `{{ $error }}` — wyświetla tekst z escapowaniem HTML.
- `@endforeach` i `@endif` — kończą pętlę i warunek Blade.

Po błędzie `$request->validate()` Laravel dla zwykłego formularza przekierowuje z powrotem i zapisuje błędy w sesji. Ten fragment renderuje je w HTML; nie zmienia reguł walidacji w kontrolerze.

### 6.2. Sprawdzenie zajętego adresu i niezgodnego potwierdzenia

Użytkownik wysłał formularz z różnymi hasłami. Zrzut ekranu pokazał dwa komunikaty:

```text
The email has already been taken.
The password field confirmation does not match.
```

Pierwszy komunikat odpowiada regule `unique:users,email`: użyty adres był już zajęty. Drugi odpowiada regule `confirmed`: hasło i jego potwierdzenie nie były identyczne. Oba błędy wystąpiły podczas tej samej próby; nie przedstawiamy jej jako dwóch niezależnych scenariuszy.

Status: **wyświetlenie obu komunikatów potwierdzone zrzutem ekranu**. Nie wykonywano osobnego odczytu bazy po tej próbie. Z kodu kontrolera wynika, że nieudana walidacja przerywa wykonywanie przed `User::create($validated)`.

### 6.3. Przywracanie imienia i nazwiska

Plik: `resources/views/auth/register.blade.php`

Do pola `name` dodajemy atrybut `value`:

```html
<input id="name" name="name" type="text" autocomplete="name" value="{{ old('name') }}" required>
```

`old('name')` pobiera poprzednią wartość pola zapisaną w sesji po błędzie walidacji. Nie jest odczytem danych konta z bazy. Dzięki temu użytkownik nie musi ponownie wpisywać imienia i nazwiska. Wyrażenie Blade escapuje wartość wstawianą do HTML.

### 6.4. Przywracanie adresu e-mail

Plik: `resources/views/auth/register.blade.php`

Do pola `email` dodajemy analogiczny atrybut:

```html
<input id="email" name="email" type="email" autocomplete="email" value="{{ old('email') }}" required>
```

`old('email')` przywraca adres przesłany w poprzedniej próbie. Przy standardowej obsłudze błędów walidacji Laravel zapisuje poprzednie dane wejściowe z pominięciem pól haseł. W kontrolerze rejestracji nie trzeba dodawać osobnego `withInput()` do tej ścieżki.

Do pól `password` i `password_confirmation` nie dodajemy `value` ani `old()`.

### 6.5. Sprawdzenie zachowania danych po błędzie

Użytkownik ponownie wypełnił formularz i wysłał niezgodne hasła. Potwierdził, że po powrocie:

- komunikaty błędów są widoczne;
- wpisane imię i nazwisko pozostają w formularzu;
- wpisany adres e-mail pozostaje w formularzu;
- oba pola haseł są puste.

Status: **przywracanie imienia i adresu e-mail oraz puste pola haseł potwierdzone ręcznie przez użytkownika**.

### 6.6. Sprawdzenie minimalnej długości hasła

Następnie użytkownik wykonał próbę z jednakowym hasłem krótszym niż osiem znaków w obu polach. Przekazany zrzut ekranu pokazał komunikat:

```text
The password field must be at least 8 characters.
```

Komunikat odpowiada regule `min:8` w kontrolerze. Na zrzucie widoczne były również zachowane imię i adres e-mail oraz puste pola haseł. Nie zapisujemy danych osobowych użytych w próbie ani haseł.

Status: **komunikat dla zbyt krótkiego hasła potwierdzony zrzutem ekranu**. Sam brak błędu formatu adresu e-mail nie potwierdza istnienia domeny ani skrzynki pocztowej.

### 6.7. Stan po etapie

Odczyt widoku potwierdził blok wyświetlania błędów i oba wywołania `old()`. Ręcznie potwierdzono podstawową rejestrację, logowanie na nowe konto, komunikaty dla zajętego adresu, niezgodnego potwierdzenia i zbyt krótkiego hasła oraz zachowanie danych po błędzie.

Komunikaty walidacji są obecnie po angielsku. Nie dodano ich polskich odpowiedników, osobnego stylowania formularza, jawnego limitowania żądań ani weryfikacji adresu e-mail. Nie sprawdzono osobno każdej reguły walidacji ani nie przeprowadzono pełnego przeglądu bezpieczeństwa kont. Testy automatyczne pozostają odłożone.


## 7. Bezpieczne przywracanie danych formularza po błędzie walidacji

### 7.1. Wykryty problem — 06.11.2026

Podczas rozszerzania testów wykryto, że poprzednia wartość pola nie musi być tekstem. Żądanie może zawierać tablicę zamiast imienia lub adresu e-mail, niezależnie od typu pola w HTML. Walidacja odrzuca takie dane, ale mogą one pozostać w sesji jako poprzednie dane formularza.

Wcześniejszy zapis:

```blade
value="{{ old('name') }}"
```

przekazywał wynik `old()` bezpośrednio do wyświetlenia. Jeśli była to tablica, renderowanie kończyło się błędem `htmlspecialchars(): Argument #1 ($string) must be of type string, array given`. Analogiczny problem dotyczył `old('email')` w rejestracji i logowaniu.

Walidacja danych i bezpieczne ponowne wyświetlenie formularza są osobnymi zadaniami. Odrzucenie nieprawidłowych danych nie gwarantuje, że można je bezpośrednio wstawić do atrybutu `value`.

### 7.2. Poprawione pola Blade

W `resources/views/auth/register.blade.php` zabezpieczono imię i adres e-mail:

```blade
<input id="name" name="name" type="text" autocomplete="name" value="{{ is_string(old('name')) ? old('name') : '' }}" required>

<input id="email" name="email" type="email" autocomplete="email" value="{{ is_string(old('email')) ? old('email') : '' }}" required>
```

W `resources/views/auth/login.blade.php` zastosowano tę samą zasadę dla adresu e-mail:

```blade
<input id="email" name="email" type="email" autocomplete="username" value="{{ is_string(old('email')) ? old('email') : '' }}" required>
```

Poszczególne części wyrażenia oznaczają:

- `old('name')` lub `old('email')` — pobranie poprzedniej wartości pola z sesji;
- `is_string(...)` — sprawdzenie, czy wartość jest tekstem;
- `? old(...)` — przywrócenie wartości, jeśli jest tekstem;
- `: ''` — użycie pustego tekstu dla pozostałych typów, w tym tablicy lub `null`;
- `{{ ... }}` — zachowanie escapowania HTML dla wybranej wartości tekstowej.

Poprawka nie zmienia reguł walidacji, nie przekształca tablicy w tekst i nie zapisuje jej w bazie. Zwykła wartość tekstowa nadal wraca do formularza, a wartość innego typu pozostawia pole puste. Pola haseł nadal nie korzystają z `old()`.

Przykłady z sekcji 6.3–6.4 opisują wcześniejszy etap; powyższe fragmenty przedstawiają późniejsze zabezpieczenie tych samych pól.

### 7.3. Testy odtwarzające problem i wynik poprawki

W `tests/Feature/AuthFormsTest.php` dodano metodę `test_array_old_input_renders_as_empty_field()` z trzema zestawami danych:

| Formularz | Pole |
| --- | --- |
| Rejestracja | `name` |
| Rejestracja | `email` |
| Logowanie | `email` |

Test zapisuje tablicę w sesji pod `_old_input` dla wybranego pola, a następnie wykonuje żądanie GET formularza. Sprawdza odpowiedź HTTP 200 i pusty atrybut `value` konkretnego pola. W ten sposób odtwarza stan renderowania po zachowaniu nieprawidłowej wartości; nie wysyła w tym samym scenariuszu żądania POST ani nie sprawdza komunikatu walidacji.

Przed poprawką wszystkie trzy przypadki ujawniły błąd renderowania. Po zmianie widoków przeszły poprawnie. Polecenie:

```bash
php artisan test --compact tests/Feature/AuthFormsTest.php
```

zakończyło się wynikiem **8 testów, 17 asercji — wszystkie zaliczone**. Wynik obejmuje również wcześniejsze testy tego pliku, dotyczące escapowania tekstowych danych i renderowania komunikatów błędów.

Po poprawce uruchomiono cały zestaw PHP: **57 testów, 282 asercje — wszystkie zaliczone**. Formatowanie `vendor/bin/pint --dirty --format agent`, PHPStan dla zmienionego pliku testowego i `git diff --check` również zakończyły się poprawnie. Nie potwierdzano tej poprawki osobnym ręcznym sprawdzeniem w przeglądarce.

Powyższe testy wykonano później niż pierwotne ręczne sprawdzenia opisane w sekcjach 1–6. Opis rozszerzenia całego zestawu testów pozostaje osobnym tematem.
