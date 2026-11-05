# Widok użytkowników w panelu administratora

## 1. Punkt wyjścia

### 1.1. Potwierdzony stan przed implementacją

Przed rozpoczęciem tego etapu w menu panelu, w `resources/views/components/admin-layout.blade.php`, istniał roboczy odnośnik „Użytkownicy” korzystający z `url('/admin/users')`. Był lokalną zmianą poza commitami i został zachowany jako punkt wyjścia.

Nie było jeszcze widoku `resources/views/admin/users/index.blade.php` ani kontrolera `app/Http/Controllers/Admin/UserController.php`. Implementacja listy użytkowników nie była rozpoczęta.

Ostatni zapisany i wysłany commit to `e6c4c9b` na `origin/feature/admin-users`. Gałąź nie została scalona do `main`. Wcześniej zakończono wylogowanie, limitowanie logowania i dodanie linku do logowania na stronie głównej, opisane w [[10-logowanie-uzytkownikow]] i [[12-wylogowanie-uzytkownikow]].

### 1.2. Sposób pracy

Użytkownik sam wykonuje polecenia i edycje kodu, a asystent prowadzi po jednym małym kroku. Dokumentację uzupełniamy po wykonanych krokach. Testy automatyczne pozostają odłożone.

## 2. Minimalny widok użytkowników

### 2.1. Utworzenie i uzupełnienie widoku

Do utworzenia widoku podano polecenie:

```bash
php artisan make:view admin.users.index --no-interaction
```

Użytkownik potwierdził wykonanie kroku, a późniejszy odczyt repozytorium potwierdził istnienie pliku i jego zawartość.

Plik: `resources/views/admin/users/index.blade.php`

Wygenerowaną zawartość zastąpiono minimalnym widokiem:

```html
<x-admin-layout>
    <h1>Użytkownicy</h1>
</x-admin-layout>
```

Komponent `x-admin-layout` zapewnia istniejący układ panelu. Umieszczony między jego znacznikami nagłówek trafia do zmiennej `$slot`, wyświetlanej w głównej części układu. Zastosowano ten sam sposób budowania widoku co w `admin/dashboard.blade.php`.

## 3. Chroniona trasa

### 3.1. Powiązanie adresu z widokiem

Plik: `routes/web.php`

Dodano trasę:

```php
Route::view('/admin/users', 'admin.users.index')
    ->middleware(['auth', 'can:access-admin'])
    ->name('admin.users.index');
```

`Route::view()` wiąże adres `/admin/users` z widokiem `admin.users.index`, czyli plikiem `resources/views/admin/users/index.blade.php`. Był to pierwszy wariant trasy, bez kontrolera. W dalszej części etapu zastąpiono go trasą wywołującą kontroler (rozdział 6).

Middleware `auth` wymaga zalogowania. Następnie `can:access-admin` sprawdza istniejącą regułę Gate `access-admin`. Zalogowane konto bez uprawnień administratora otrzymuje odpowiedź 403. Nazwa `admin.users.index` pozwala generować odnośnik bez wpisywania ścieżki bezpośrednio w menu.

## 4. Odnośnik w menu

### 4.1. Użycie nazwy trasy

Plik: `resources/views/components/admin-layout.blade.php`

Po dodaniu nazwanej trasy zmieniono początek istniejącego odnośnika:

```html
<a href="{{ url('/admin/users') }}" aria-label="Użytkownicy">
```

na:

```html
<a href="{{ route('admin.users.index') }}" aria-label="Użytkownicy">
```

`route()` generuje adres na podstawie nazwy trasy. Późniejsza zmiana jej ścieżki nie będzie wymagała poprawiania tego odnośnika. Zachowano ikonę i etykietę „Użytkownicy”. Użytkownik potwierdził, że po zmianie link nadal otwiera widok.

## 5. Ręczne sprawdzenie działania i dostępu

### 5.1. Potwierdzone wyniki

Sprawdzenia wykonał użytkownik w przeglądarce:

| Scenariusz | Potwierdzony wynik |
| --- | --- |
| Administrator klika „Użytkownicy” po dodaniu trasy | Widok działa; nagłówek „Użytkownicy” jest wyświetlany w układzie panelu |
| Administrator klika odnośnik po zmianie na `route('admin.users.index')` | Widok nadal się otwiera |
| Niezalogowany gość otwiera adres widoku w oknie prywatnym | Przekierowanie do formularza logowania |
| Zwykły użytkownik loguje się w oknie prywatnym | Wyświetlenie strony głównej, zgodnie z istniejącym przekierowaniem po logowaniu |
| Ten sam zwykły użytkownik wpisuje następnie bezpośrednio ścieżkę `/admin/users` | Strona błędu 403 |

Samo przekierowanie zwykłego użytkownika na stronę główną po logowaniu nie sprawdza ochrony panelu. Dlatego osobno wykonano próbę bezpośredniego wejścia na chroniony adres.

### 5.2. Zakres weryfikacji

Powyższe wyniki pochodzą z ręcznych sprawdzeń potwierdzonych przez użytkownika. Nie są wynikami testów automatycznych ani pomiarem statusu HTTP odpowiedzi dla administratora. Odczyt repozytorium potwierdził zapis widoku, trasy i odnośnika.

Omówiono możliwość zapisania scenariuszy jako testów funkcjonalnych (Feature): przekierowanie gościa, odpowiedź 403 dla zwykłego użytkownika oraz odpowiedź 200 i właściwy widok dla administratora. Testów nie utworzono ani nie uruchomiono; temat pozostaje odłożony.

## 6. Kontroler użytkowników

### 6.1. Utworzenie szkieletu

Użytkownik wykonał polecenie:

```bash
php artisan make:controller Admin/UserController --no-interaction
```

Artisan potwierdził utworzenie `app/Http/Controllers/Admin/UserController.php`. Wygenerowany szkielet zawierał:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    //
}
```

### 6.2. Metoda wyświetlająca widok

Plik: `app/Http/Controllers/Admin/UserController.php`

Dodano metodę `index()` i zastąpiono nieużywany import `Request` importem `View`:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index');
    }
}
```

- `<?php` rozpoczyna kod PHP. W pliku zawierającym wyłącznie PHP pomijamy zamykający znacznik `?>`.
- `namespace App\Http\Controllers\Admin` określa przestrzeń nazw klasy, odpowiadającą jej położeniu w strukturze projektu.
- `use App\Http\Controllers\Controller` pozwala używać krótkiej nazwy bazowego kontrolera aplikacji.
- `use Illuminate\View\View` importuje klasę widoku używaną w deklaracji typu zwracanego.
- `class UserController extends Controller` definiuje kontroler dziedziczący po bazowym kontrolerze. Dziedziczenie samo nie zapewnia ochrony dostępu; tę zapewniają middleware trasy.
- `public function index(): View` deklaruje publiczną metodę bez argumentów, zwracającą obiekt widoku. `index` jest standardową nazwą akcji listy zasobów.
- `return view('admin.users.index')` tworzy i zwraca widok. Kropki odpowiadają katalogom w `resources/views`, a rozszerzenie `.blade.php` pomijamy. Laravel renderuje widok do odpowiedzi HTML.
- Klamry wyznaczają ciało klasy i metody, a średniki kończą instrukcje.

Podczas edycji pojawił się komunikat o niezdefiniowanym typie `App\Http\Controllers\Admin\View`. Brakowało importu `Illuminate\View\View`, więc nazwa `View` była rozwiązywana w przestrzeni nazw kontrolera. Poprawiono import zamiast zmieniać deklarowany typ metody.

### 6.3. Podłączenie kontrolera do trasy

Plik: `routes/web.php`

Dodano import:

```php
use App\Http\Controllers\Admin\UserController;
```

Dotychczasową trasę `Route::view()` zastąpiono:

```php
Route::get('/admin/users', [UserController::class, 'index'])
    ->middleware(['auth', 'can:access-admin'])
    ->name('admin.users.index');
```

`Route::get()` obsługuje otwarcie strony metodą GET. Tablica `[UserController::class, 'index']` wskazuje klasę kontrolera i metodę do wywołania; `::class` zwraca pełną nazwę klasy. Zachowano nazwę trasy i jej zabezpieczenia, więc odnośnik w menu nie wymagał kolejnej zmiany.

Początkowo pozostawiono jednocześnie starą i nową definicję. Usunięto stary blok `Route::view()`, aby dla tego adresu i nazwy została jedna trasa. Końcowy przebieg to: żądanie → middleware → `UserController::index()` → widok.

### 6.4. Sprawdzenie rejestracji trasy

Użytkownik wykonał:

```bash
php artisan route:list --path=admin/users -v --no-interaction
```

`--path` filtruje trasy według ścieżki, `-v` pokazuje middleware, a `--no-interaction` wyłącza pytania interaktywne. Wynik:

```text
GET|HEAD  admin/users  admin.users.index › Admin\UserController@index
          ⇂ web
          ⇂ auth
          ⇂ can:access-admin

Showing [1] routes
```

Wynik potwierdza jedną trasę, akcję kontrolera oraz przypisane middleware. `GET|HEAD` oznacza obsługę pobrania strony i samych nagłówków odpowiedzi. Grupa `web` zapewnia między innymi obsługę sesji potrzebną do logowania. Lista tras potwierdza konfigurację; nie jest testem wykonania metody kontrolera.

Ręczne wyniki z rozdziału 5 uzyskano przed zastąpieniem trasy widoku trasą kontrolera. Po zmianie użytkownik przekazał wynik `route:list`; nie przekazał osobnego jednoznacznego potwierdzenia ponownego otwarcia strony w przeglądarce.

## 7. Zamknięcie etapu i dalszy plan

### 7.1. Wykonane

Istnieją minimalny widok z nagłówkiem, kontroler zwracający ten widok, nazwana trasa chroniona middleware oraz korzystający z niej odnośnik menu. Zmiany tego etapu pozostają lokalne poza commitami na gałęzi `feature/admin-users` w chwili aktualizacji notatki 05.11.2026. W tej aktualizacji dokumentacji nie wykonano formatowania kodu, commitu ani pushu.

### 7.2. Kolejność dalszej pracy

Przed kolejnym etapem uzgodniono uporządkowanie i zapis obecnych zmian, tak aby rozpocząć dalszą pracę z czystym katalogiem roboczym Git. Formatowanie, przegląd zmian, commit i ewentualny push wymagają osobnych potwierdzonych wyników; nie są jeszcze wykonane w ramach tego zamknięcia.

Pobieranie użytkowników z bazy i przekazanie ich do widoku będzie osobnym etapem opisanym w nowej notatce. Jej utworzenie pozostaje planem. Obecny widok nie pobiera ani nie wyświetla rekordów użytkowników; nie ma tabeli, paginacji ani operacji CRUD. Testy automatyczne pozostają odłożone.
