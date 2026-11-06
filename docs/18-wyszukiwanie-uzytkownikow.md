# Wyszukiwanie użytkowników

## 1. Punkt wyjścia

Lista użytkowników i jej paginacja są opisane w [[14-lista-uzytkownikow]], a przygotowanie przykładowych kont w [[17-przykladowi-uzytkownicy]]. Rozpoczynamy osobny etap poświęcony wyszukiwaniu na liście użytkowników panelu administratora.

**Status:** wykonano przegląd kodu, odczyt frazy i filtrowanie po imieniu lub e-mailu w kontrolerze. Zrzuty ekranu użytkownika potwierdziły ręcznie wyszukanie konta po fragmencie e-maila oraz po imieniu. Dodano zachowanie frazy w linkach paginacji, a użytkownik potwierdził działanie. Dodano również zachowanie frazy przy przekierowaniu ze strony poza zakresem; po tej zmianie Pint i 10 dotychczasowych testów kontrolera przeszły. Użytkownik potwierdził przekierowanie z zachowaniem frazy zrzutem ekranu. Dodano formularz z lupą nad tabelą; zrzut ekranu użytkownika potwierdził wyszukanie Norberta i zachowanie frazy w polu. Poniższy zakres opisuje docelową funkcję.

## 2. Cel i proponowany zakres

Ułatwić odnajdywanie kont po imieniu lub adresie e-mail. Inspiracją dla wyglądu i umiejscowienia pola jest Filament; nie planujemy instalowania go ani zmiany zależności.

Proponowany pierwszy wariant:

- pole nad tabelą z ikoną lupy i tekstem „Szukaj użytkowników…”;
- wyszukiwanie po imieniu lub adresie e-mail;
- zatwierdzanie wyszukiwania klawiszem Enter;
- zachowanie wpisanej frazy podczas przechodzenia między stronami wyników;
- rozpoczęcie od pierwszej strony po zatwierdzeniu nowej frazy;
- komunikat przy braku pasujących wyników;
- możliwość powrotu do pełnej listy po wyczyszczeniu pola i zatwierdzeniu.

Automatyczne wyszukiwanie podczas pisania pozostaje możliwością na później, poza proponowanym pierwszym wariantem.

## 3. Wykonany krok — odczyt frazy w kontrolerze

Sprawdzono instrukcje projektu, status Git, kontroler listy, widok tabeli, trasę, tłumaczenia i istniejące testy. Potwierdzono Laravel 13.38.0 oraz deklarację Tailwind CSS 4 w `package.json`. Przeczytano odpowiednie skille i sprawdzono dokumentację Laravel przez Boost. Katalog `.ai/rules` nie istniał podczas przeglądu.

W pliku `app/Http/Controllers/Admin/UserController.php` dodano import:

```php
use Illuminate\Http\Request;
```

Metoda `index` przyjmuje teraz obiekt bieżącego żądania, który Laravel dostarcza automatycznie:

```php
public function index(Request $request): View|RedirectResponse
{
    $search = $request->string('search')->trim()->toString();

    // Dalsza część metody pozostaje bez zmian poza przekazaniem frazy do widoku.
}
```

`string('search')` odczytuje parametr jako obiekt tekstowy Laravel, `trim()` usuwa białe znaki z początku i końca, a `toString()` zwraca zwykły ciąg znaków PHP. Gdy parametr nie występuje, otrzymujemy pustą frazę.

Do danych widoku dodano `search`:

```php
return view('admin.users.index', [
    'users' => $users,
    'search' => $search,
]);
```

Po tym pierwszym kroku widok otrzymywał zmienną `$search`, ale jeszcze jej nie wykorzystywał. Zapytanie nadal zwracało pełną listę z paginacją po 10 kont. Sam odczyt frazy nie uruchamiał wyszukiwania.

### Potwierdzona weryfikacja

Użytkownik uruchomił:

```bash
vendor/bin/pint --dirty --format agent
php artisan test --compact tests/Feature/Http/Controllers/Admin/UserControllerTest.php
```

Pint uporządkował importy w kontrolerze (`ordered_imports`). Testy zakończyły się wynikiem **10 passed (38 assertions)**. Potwierdza to zachowanie dotychczasowych scenariuszy listy; nie stanowi jeszcze potwierdzenia działania wyszukiwania.

## 4. Wykonany krok — filtrowanie po imieniu lub e-mailu

W kontrolerze dodano import:

```php
use Illuminate\Database\Eloquent\Builder;
```

Dotychczasowe pobieranie użytkowników zastąpiono budowaniem zapytania z opcjonalnym filtrem:

```php
$query = User::query();

if ($search !== '') {
    $query->where(function (Builder $query) use ($search): void {
        $query->where('name', 'like', '%'.$search.'%')
            ->orWhere('email', 'like', '%'.$search.'%');
    });
}

$users = $query->orderBy('id')->paginate(10);
```

`User::query()` rozpoczyna budowanie zapytania. Dla niepustej frazy `where` i `orWhere` dopuszczają dopasowanie imienia lub e-maila. Operator `like` z `%` po obu stronach pozwala znaleźć fragment tekstu; same znaki `%` i `_` w podanej frazie również mają specjalne znaczenie wzorca SQL. Funkcja przekazana do zewnętrznego `where` grupuje warunki w nawiasach. `use ($search)` udostępnia frazę wewnątrz funkcji, a `Builder` określa typ obiektu zapytania.

Pusta fraza pomija filtr. Sortowanie po ID i paginacja po 10 kont pozostają zachowane. Wartość frazy jest przekazywana do zapytania przez mechanizm wiązania parametrów Eloquent.

### Potwierdzona weryfikacja

Użytkownik ponownie uruchomił Pint i ten sam plik testów poleceniami z poprzedniego kroku. Pint zastosował poprawki `concat_space` i `ordered_imports`. Wynik testów: **10 passed (38 assertions)**.

Odczyt aktualnego kontrolera potwierdził obecność filtrowania. Dotychczasowe testy potwierdzają zachowanie wcześniejszych scenariuszy; nie sprawdzają jeszcze dopasowania frazy po imieniu lub e-mailu.

### Potwierdzone sprawdzenie ręczne — fragment e-maila

Użytkownik otworzył listę z parametrem `?search=example-user-25`, bez parametru `page`, i przesłał zrzut ekranu. Tabela pokazała jedno konto: Norbert Borowski, `example-user-25@example.com`, ID 30, z rolą „Użytkownik”. Licznik wskazywał „Wyświetlono 1–1 z 1 wyników”.

Potwierdzono dopasowanie po fragmencie adresu e-mail i wyświetlenie licznika dla pojedynczego wyniku.

### Potwierdzone sprawdzenie ręczne — imię

Użytkownik zamienił parametr na `?search=Norbert` i przesłał kolejny zrzut ekranu. Tabela pokazała wyłącznie Norberta Borowskiego, `example-user-25@example.com`, ID 30. Licznik ponownie wskazywał „Wyświetlono 1–1 z 1 wyników”. Fraza występuje w imieniu, a nie w widocznym adresie e-mail, co potwierdza dopasowanie po polu `name`.

### Potwierdzone sprawdzenie ręczne — brak wyników

Użytkownik otworzył listę z parametrem `?search=brak-takiego-konta-xyz987` i przesłał zrzut ekranu. Tabela nie zawierała kont. Komunikat „Brak użytkowników.” był widoczny w tabeli oraz w stopce. Licznik zakresu i nawigacja paginacji nie były widoczne.

Potwierdzono obecną obsługę pustego wyniku po filtrowaniu. Osobny komunikat informujący o braku dopasowań wyszukiwania pozostaje do wykonania.

## 5. Wykonany krok — fraza w linkach paginacji

W `app/Http/Controllers/Admin/UserController.php` rozszerzono tworzenie paginatora:

```php
$users = $query->orderBy('id')->paginate(10)
    ->appends(['search' => $search]);
```

`appends()` dodaje parametr `search` do generowanych linków stron. Dzięki temu kliknięcie następnej strony przekazuje frazę ponownie do kontrolera i zachowuje filtrowanie.

Użytkownik potwierdził działanie po instrukcji wyszukania `example-user` i przejścia na drugą stronę. Odczyt kodu potwierdził dodanie `appends()`. Nie podano osobnego wyniku uruchomienia testów po tej zmianie.

Na tym etapie przekierowanie z numeru strony poza zakresem przekazywało tylko `page`. Obsługę frazy dodano w kolejnym kroku.

## 6. Wykonany krok — fraza przy przekierowaniu ze strony poza zakresem

W `app/Http/Controllers/Admin/UserController.php` rozszerzono parametry istniejącego przekierowania:

```php
return redirect()->route('admin.users.index', [
    'page' => $users->lastPage(),
    'search' => $search !== '' ? $search : null,
]);
```

Przekierowanie na ostatnią dostępną stronę zachowuje niepustą frazę wyszukiwania. Dla pustej frazy wartość `null` pozwala pominąć parametr `search` w adresie.

Odczyt kontrolera potwierdził zmianę. Użytkownik podał wynik Pint: **passed**, oraz testów kontrolera: **10 passed (38 assertions)**. Istniejące testy nie sprawdzają jeszcze przekierowania z niepustą frazą.

Użytkownik wykonał sprawdzenie według instrukcji wejścia z parametrami `?search=Norbert&page=999` i przesłał zrzut ekranu wyniku. Adres zawierał `?page=1&search=Norbert`, tabela pokazywała Norberta Borowskiego, `example-user-25@example.com`, ID 30, a licznik wskazywał „Wyświetlono 1–1 z 1 wyników”. Potwierdzono ręcznie przekierowanie na pierwszą stronę z zachowaniem frazy i filtrowania.

## 7. Przygotowanie tłumaczeń pola wyszukiwania

Użytkownik dodał klucze `search_label` i `search_placeholder` do `lang/pl/users.php` oraz `lang/en/users.php`. Etykieta będzie nazwą pola dla technologii asystujących, a placeholder podpowiedzią wyświetlaną w pustym polu.

Użytkownik uruchomił:

```bash
vendor/bin/pint --dirty --format agent
php -l lang/pl/users.php
php -l lang/en/users.php
```

Pint poprawił wcięcia tablicy w polskim pliku (`array_indentation`), a oba sprawdzenia składni zakończyły się komunikatem `No syntax errors detected`.

Pierwszy odczyt plików wykazał angielskie wartości obu nowych kluczy także w `lang/pl/users.php`. Użytkownik następnie potwierdził wykonanie korekty. Ponowny odczyt pliku potwierdził właściwe polskie wartości:

```php
'search_label' => 'Szukaj użytkowników',
'search_placeholder' => 'Szukaj użytkowników…',
```

Wersja angielska zawiera odpowiednio `Search users` i `Search users…`. Użytkownik nie wkleił osobnego wyniku poleceń po korekcie; poprawione wartości potwierdzono odczytem kodu.

## 8. Wykonany krok — formularz wyszukiwania nad tabelą

W `resources/views/admin/users/index.blade.php`, wewnątrz kontenera tabeli i przed elementem `<table>`, dodano pasek z formularzem:

```blade
<form method="GET" action="{{ route('admin.users.index') }}"
    class="w-full sm:w-80" role="search">
    <label for="users-search" class="sr-only">
        {{ __('users.search_label') }}
    </label>
    {{-- W widoku pole znajduje się w kontenerze z dekoracyjną ikoną lupy. --}}
    <input type="search" id="users-search" name="search"
        value="{{ $search }}"
        placeholder="{{ __('users.search_placeholder') }}">
</form>
```

Fragment pokazuje mechanizm formularza; pełny widok zawiera również klasy stylujące pole, kontener i ikonę SVG. Formularz `GET` przesyła frazę w parametrze `search` po naciśnięciu Enter. `value` zachowuje frazę po wyrenderowaniu wyników, z escapowaniem przez Blade. Brak pola `page` oznacza, że zatwierdzenie nowej frazy rozpoczyna wyszukiwanie od pierwszej strony.

Etykieta jest powiązana z polem przez `for` i `id`; klasa `sr-only` ukrywa ją wizualnie, zachowując dostępność dla czytników ekranu. Dekoracyjna lupa ma `aria-hidden="true"` i nie przechwytuje kliknięć dzięki `pointer-events-none`. Pole zajmuje pełną szerokość formularza, a od breakpointu `sm` formularz ma szerokość `w-80` i jest wyrównany do prawej strony paska.

Odczyt kodu potwierdził dodanie formularza. Po instrukcji wpisania `Norbert` i zatwierdzenia Enterem użytkownik przesłał zrzut ekranu: pole z lupą było widoczne nad tabelą po prawej stronie, zachowało tekst `Norbert`, adres zawierał `?search=Norbert`, a tabela pokazała jedno pasujące konto Norberta Borowskiego i licznik „Wyświetlono 1–1 z 1 wyników”.

### Potwierdzone sprawdzenie — nowa fraza z dalszej strony

Użytkownik wyszukał `example-user`, przeszedł na drugą stronę wyników, następnie zmienił frazę w polu na `Norbert` i zatwierdził Enterem. Potwierdził, że pojawił się Norbert Borowski, fraza pozostała w polu, a adres zawierał `search=Norbert` bez `page=2`. Potwierdza to rozpoczęcie nowego wyszukiwania od pierwszej strony dzięki pominięciu numeru strony w formularzu.

### Potwierdzone sprawdzenie — wyczyszczenie pola

Po instrukcji usunięcia frazy i zatwierdzenia Enterem użytkownik przesłał zrzut ekranu. Adres zawierał pusty parametr `?search=`, pole pokazywało polską podpowiedź „Szukaj użytkowników…”, a tabela ponownie wyświetlała pierwsze 10 kont. Licznik wskazywał „Wyświetlono 1–10 z 27 wyników”, a nawigacja zawierała trzy strony z aktywną stroną pierwszą. Potwierdzono powrót do pełnej listy po wyczyszczeniu frazy.

Nie potwierdzono jeszcze działania z czytnikiem ekranu.

## 9. Przygotowanie komunikatu braku dopasowań

Do `lang/pl/users.php` i `lang/en/users.php` dodano klucz `no_results`:

```php
// lang/pl/users.php
'no_results' => 'Nie znaleziono użytkowników pasujących do wyszukiwania.',

// lang/en/users.php
'no_results' => 'No users match your search.',
```

Komunikat pozwoli odróżnić brak dopasowań wyszukiwania od pustej listy bez filtra. Użytkownik uruchomił `vendor/bin/pint --dirty --format agent` (wynik `passed`) oraz `php -l lang/pl/users.php` i `php -l lang/en/users.php` (oba bez błędów składni). Wykorzystanie klucza w tabeli i stopce pozostaje następnym krokiem.

### Wykorzystanie komunikatu i błąd podczas weryfikacji

W widoku listy oba wywołania `__('users.empty')` (w tabeli i stopce) zastąpiono warunkiem:

```blade
{{ $search !== '' ? __('users.no_results') : __('users.empty') }}
```

Odczyt widoku potwierdził zmianę. Przesłany przez użytkownika plik `errors.txt` pokazał wynik testów: **6 failed, 4 passed (7 assertions)**. Wystąpił błąd zapisu do `storage/logs/laravel.log` (`Permission denied`) oraz `touch(): Utime failed: Operation not permitted`. Odczyt uprawnień wykazał właściciela `nobody:nogroup` dla pliku logu i części skompilowanych widoków. Weryfikacja nowego komunikatu pozostaje nieukończona do czasu usunięcia problemu z uprawnieniami i ponownego uruchomienia testów.

### Usunięcie przeszkody z uprawnieniami — potwierdzony wynik

Użytkownik wykonał:

```bash
sudo chown sebastian:sebastian storage/logs/laravel.log
php artisan view:clear
php artisan test --compact tests/Feature/Http/Controllers/Admin/UserControllerTest.php
```

Czyszczenie zakończyło się komunikatem `Compiled views cleared successfully`, a testy wynikiem **10 passed (38 assertions)**. Po zmianie właściciela logu i usunięciu skompilowanych widoków testy nie zgłosiły ponownie błędów uprawnień. Nie ustalono dokładnego momentu powstania różnicy właścicieli ani nie potwierdzono trwałego rozwiązania współdzielenia plików przez serwer WWW i terminal.

Dotychczasowe testy nie sprawdzają nowego komunikatu dla niepustej frazy bez dopasowań.

### Potwierdzone sprawdzenie — nowy komunikat braku dopasowań

Użytkownik wyszukał w formularzu `brak-takiego-konta-xyz987` i przesłał zrzut ekranu. Fraza pozostała w polu oraz parametrze `search`. W tabeli i stopce wyświetlił się komunikat „Nie znaleziono użytkowników pasujących do wyszukiwania.”. Nie było kont, licznika zakresu ani nawigacji paginacji. Potwierdzono ręcznie działanie nowego komunikatu po zmianie widoku.

### Korekta — pojedynczy komunikat i ukryta stopka dla pustego wyniku

Użytkownik wskazał, że powtarzanie komunikatu w tabeli i stopce jest zbędne. W `resources/views/admin/users/index.blade.php` otoczył cały kontener stopki (licznik i paginację) warunkiem `@if ($users->count() > 0)`. Odczyt kodu potwierdził prawidłowe położenie warunku i zamykającego `@endif`.

Przy pustym wyniku komunikat pozostaje w tabeli, a stopka nie jest renderowana. Użytkownik potwierdził działanie po instrukcji odświeżenia strony z niepasującą frazą: komunikat występuje raz, a dolny pasek znika.

Następnie użytkownik usunął wewnętrzny, powtórzony warunek liczby wyników oraz gałąź `@else`. Odczyt widoku potwierdził, że w stopce pozostał sam licznik i osobny warunek `$users->hasPages()` dla nawigacji, a zewnętrzny warunek nadal obejmuje cały kontener. Wcięcia są uporządkowane; `git diff --check` nie zgłosił problemów. Po uproszczeniu użytkownik uruchomił `php artisan test --compact tests/Feature/Http/Controllers/Admin/UserControllerTest.php` i podał wynik **10 passed (38 assertions)**. Potwierdzono przejście dotychczasowych scenariuszy kontrolera; testy nie obejmują jeszcze nowych przypadków wyszukiwania.

## 10. Obsługa klawiaturą i dostępna etykieta

Użytkownik potwierdził wykonanie sprawdzenia po odświeżeniu strony: przejście do pola klawiszem `Tab`, widoczną obwódkę zaznaczonego pola oraz wyszukanie `Norbert` po zaznaczeniu tekstu przez `Ctrl+A`, wpisaniu frazy i zatwierdzeniu Enterem. Obsługa pola klawiaturą działa w sprawdzonej przeglądarce.

Odczyt widoku potwierdził powiązanie `<label for="users-search">` z polem `id="users-search"`. Etykieta korzysta z tłumaczenia `users.search_label` i klasy `sr-only`, a dekoracyjna lupa ma `aria-hidden="true"`. Nie wykonywano sprawdzenia czytnikiem ekranu.

### Pozostała weryfikacja

Ręcznie potwierdzono podstawowe scenariusze etapu. Istniejące 10 testów kontrolera przechodzi, ale nie obejmuje jeszcze nowych przypadków wyszukiwania. Dobranie i dodanie testów filtrowania, zachowania frazy oraz komunikatu braku dopasowań pozostaje do wykonania.

### Sprawdzenie wąskiego ekranu — wykryte przepełnienie

Użytkownik przesłał zrzut wąskiego okna z pełną listą. Prawa część pola wyszukiwania i tabeli była poza widocznym obszarem. Nie potwierdzono poprawnego układu ani wyszukania Norberta w tym rozmiarze.

Odczyt `resources/views/components/admin-layout.blade.php` wykazał `<main class="flex-1 p-8">` wewnątrz kontenera flex. Użytkownik dodał `min-w-0`, aby zawartość tabeli nie wymuszała minimalnej szerokości całej kolumny:

```blade
<main class="min-w-0 flex-1 p-8">
```

Ponowny odczyt kodu potwierdził zmianę. Kolejny zrzut użytkownika pokazał całe pole z lupą oraz kontener tabeli mieszczący się w wąskim ekranie. Prawa część szerokiej tabeli pozostaje poza widocznym fragmentem jej kontenera. Użytkownik następnie potwierdził działanie przewijania poziomego przez `Shift` i kółko myszy. Wyszukiwanie na wąskim ekranie pozostaje do osobnego potwierdzenia.

Na zrzucie dołu tabeli nie było widocznego poziomego paska. Użytkownik początkowo zgłosił brak przewijania, następnie sprostował, że `Shift` i kółko myszy działały. Zaproponowana w międzyczasie zmiana na osobny kontener przewijania tabeli z `min-w-max` nie została wykonana — użytkownik to potwierdził. Wycofano tę propozycję i zachowano obecny wspólny kontener `overflow-x-auto`. Brak widocznego paska na zrzucie nie jest dowodem braku możliwości przewijania.

## 11. Test automatyczny — wyszukiwanie po fragmencie imienia

Do `tests/Feature/Http/Controllers/Admin/UserControllerTest.php` dodano metodę `test_admin_can_search_users_by_name_fragment`. Test tworzy administratora, Norberta Borowskiego i Annę Kowalską, z jawnymi adresami e-mail bez frazy `Nor`. Następnie wysyła jako administrator żądanie listy z `search=Nor`.

Sprawdza poprawną odpowiedź HTTP, obecność adresu Norberta oraz brak adresów Anny i administratora. Dzięki ustalonym danym fraza dopasowuje się wyłącznie do imienia, a test wykryje m.in. usunięcie filtrowania lub pominięcie wyszukiwania po polu `name`.

Użytkownik uruchomił:

```bash
vendor/bin/pint --dirty --format agent
php artisan test --compact tests/Feature/Http/Controllers/Admin/UserControllerTest.php --filter=test_admin_can_search_users_by_name_fragment
```

Pint zastosował poprawki `concat_space` i `no_whitespace_in_blank_line`. Wynik: **1 passed (4 assertions)**. Odczyt pliku potwierdził dodanie testu. Wynik dotyczy wyłącznie wskazanej metody, nie całego zestawu. Testy wyszukiwania po e-mailu, braku dopasowań i zachowania frazy pozostają do dodania.

## 12. Test automatyczny — wyszukiwanie po fragmencie e-maila

Do `tests/Feature/Http/Controllers/Admin/UserControllerTest.php` dodano metodę `test_admin_can_search_users_by_email_fragment`. Tworzy administratora, Annę Kowalską z adresem `anna.sport@example.com` i Jana Nowaka z adresem `jan@example.com`. Żądanie administratora z `search=anna.sport` ma pokazać adres Anny i pominąć pozostałe dwa konta. Fraza występuje wyłącznie w e-mailu, co pozwala wykryć pominięcie warunku wyszukiwania po kolumnie `email`.

Odczyt pliku potwierdził dodanie testu. Użytkownik uruchomił Pint (poprawka `concat_space`) oraz test z filtrem `--filter=test_admin_can_search_users_by_email_fragment`. Wynik: **1 passed (4 assertions)**. Nie jest to wynik uruchomienia całego pliku testów. Kolejnym przypadkiem do dodania jest brak dopasowań mimo istnienia kont w bazie.

## 13. Test automatyczny — brak dopasowań

Na wyraźną prośbę użytkownika asystent dodał do istniejącej klasy testowej metodę `test_search_without_matches_shows_no_results_message`. Wcześniejsze uruchomienie z filtrem zwróciło `No tests found`, ponieważ metoda nie była jeszcze zapisana w pliku; nie był to wynik wykonania testu.

Test tworzy administratora z ustalonym imieniem i adresem e-mail, następnie wysyła żądanie z niepasującą frazą `nieistniejace-konto-xyz987`. Sprawdza odpowiedź HTTP 200, obecność `users.no_results`, brak `users.empty`, brak adresu administratora w tabeli i zerową całkowitą liczbę wyników paginatora. Konto istnieje w bazie testowej, więc pusta lista wynika z filtrowania.

Asystent uruchomił Pint (`passed`) oraz test z filtrem `--filter=test_search_without_matches_shows_no_results_message`: **1 passed (5 assertions)**. Test korzysta z konfiguracji SQLite `:memory:`. Wynik dotyczy nowej metody, nie całego zestawu.

### Potwierdzone uruchomienie całego pliku testów

Użytkownik ponownie uruchomił test braku dopasowań: **1 passed (5 assertions)**. Następnie wykonał `php artisan test --compact tests/Feature/Http/Controllers/Admin/UserControllerTest.php` bez filtra. Wynik całego pliku: **13 passed (51 assertions)**. Obejmuje to 10 wcześniejszych testów oraz nowe przypadki wyszukiwania po imieniu, e-mailu i braku dopasowań.

## 14. Test automatyczny — fraza w linkach paginacji

Użytkownik dodał metodę `test_search_phrase_is_preserved_in_pagination_links` w istniejącej klasie testów kontrolera. Test tworzy administratora i 11 kont o imieniu `Pasujący użytkownik`, następnie wyszukuje `Pasujący`. Sprawdza odpowiedź HTTP 200 oraz obecność linku do drugiej strony zawierającego zarówno `search`, jak i `page=2`. Wykryje brak zachowania frazy w generowanych linkach, np. po usunięciu `appends()`.

Początkowe uruchomienie zwróciło `No tests found`, ponieważ metoda nie była jeszcze zapisana na dysku. Po porównaniu zapisanej wersji z kodem przesłanym przez użytkownika potwierdzono zachowanie wcześniejszych testów i dodanie nowej metody. Użytkownik zapisał plik i uruchomił Pint (poprawka `concat_space`) oraz test z filtrem `--filter=test_search_phrase_is_preserved_in_pagination_links`.

Wynik użytkownika: **1 passed (2 assertions)**. Odczyt pliku potwierdził obecność nowej metody. Następnie użytkownik uruchomił cały plik bez filtra: **14 passed (53 assertions)**. Potwierdzono wspólne przejście wcześniejszych scenariuszy i czterech nowych testów wyszukiwania. Zachowanie frazy przy przekierowaniu ze strony poza zakresem jest potwierdzone ręcznie, ale nie ma jeszcze osobnego testu automatycznego.

## 15. Test automatyczny — fraza przy przekierowaniu

Użytkownik dodał metodę `test_out_of_range_redirect_preserves_search_phrase`. Test tworzy administratora i Norberta Borowskiego, następnie żąda listy z `search=Norbert` i `page=999`. Sprawdza przekierowanie na `page=1` z zachowaniem `search=Norbert`. Wykryje utratę frazy podczas przekierowania ze strony poza zakresem.

Odczyt kodu potwierdził dodanie metody. Użytkownik uruchomił Pint (poprawka `concat_space`) oraz cały plik testów kontrolera. Wynik: **15 passed (55 assertions)**. Obejmuje to 10 wcześniejszych testów i 5 nowych przypadków wyszukiwania. Nie uruchamiano w tym kroku całego zestawu testów aplikacji.

### Stan etapu po weryfikacji

Zaimplementowano pole z lupą i etykietą, wyszukiwanie po fragmencie imienia lub e-maila zatwierdzane Enterem, zachowanie frazy w polu i linkach paginacji, rozpoczęcie nowego wyszukiwania od pierwszej strony, zachowanie frazy przy przekierowaniu oraz pojedynczy komunikat braku dopasowań z ukrytą stopką. Podstawowe scenariusze potwierdzono ręcznie; testy kontrolera przechodzą.

Dodano `min-w-0` do głównej kolumny panelu i potwierdzono zmieszczenie pola oraz przewijanie tabeli przez `Shift` i kółko myszy w wąskim oknie. Dalsze sprawdzanie tego mechanizmu na różnych urządzeniach odłożono do osobnego zadania. Nie potwierdzono utworzenia issue przez integrację GitHub (próba zakończyła się błędem 403); przygotowano treść do ręcznego dodania. Nie wykonywano sprawdzenia czytnikiem ekranu.

Przegląd zmian przed commitem i zapisanie ich w Git pozostają do wykonania przez użytkownika.

## 16. Sposób pracy

Pracujemy po jednym małym kroku: cel, mechanizm, kryterium sukcesu, następnie wynik użytkownika. Użytkownik wykonuje edycje aplikacji, polecenia i operacje Git; asystent wykonuje je tylko na wyraźną prośbę. Użytkownik udzielił stałej zgody na uzupełnianie istniejącej dokumentacji bieżącego etapu po potwierdzeniu wykonania każdego kroku, bez osobnej prośby za każdym razem. Rozróżniamy propozycje, wykonane zmiany i potwierdzone wyniki. Zasadę zapisano w `.ai/rules/dokumentowanie-krokow.md`.
