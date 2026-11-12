---
tags:
  - sport
---

# Pierwszy widok dyscyplin

## Cel i kontekst

Użytkownik poprosił o rozpoczęcie widoku dyscyplin w osobnym czacie i nowym rozdziale w folderze `Sport/Dyscypliny`. Dotychczasowe definicje i otwarte wymagania pozostają w [rozdziale etapu 28](../../28-definiowanie-dyscyplin-sportowych.md), powiązanym z [issue #21](https://github.com/SebMagacy/sportowiada/issues/21). Issue #21 dotyczy analizy; nie zostało ukończone przez rozpoczęcie widoku.

## Zatwierdzony zakres pierwszego kroku

- Strona „Dyscypliny” w panelu administratora, dostępna z bocznego menu.
- Nagłówek zgodny z istniejącym panelem.
- Stała lista trzech uzgodnionych dyscyplin: piłka nożna, siatkówka i koszykówka.
- Bez bazy danych, formularzy i zarządzania dyscyplinami w tym kroku.

Użytkownik potwierdził powyższy zakres 12 listopada 2026 roku. Aktualny stan implementacji opisano poniżej.

## Plan i kryterium sukcesu

Użytkownik doprecyzował, że architektura listy dyscyplin ma być taka sama jak listy użytkowników. Przegląd kodu wykazał, że lista użytkowników otrzymuje dane i konfigurację kolumn z `Admin\\UserController::index`, a oprawa i tabela są obecnie zapisane bezpośrednio w widoku. Dalszy kierunek: kontroler dyscyplin przekazujący na razie stałe dane oraz wspólne komponenty oprawy i tabeli używane przez obie listy. Wygląd oprawy należy zachować zgodnie z użytkownikami: `rounded-xl border border-gray-200 bg-white shadow-sm`. Nie kopiować funkcji filtrowania, wyszukiwania, zaznaczania i paginacji do dyscyplin, dopóki nie wejdą do uzgodnionego zakresu. Wspólna oprawa jest już podłączona do obu widoków jako `x-lists.panel`; tabela pozostaje do wdrożenia.

Przygotować widok z wykorzystaniem istniejącego układu panelu, chronioną trasę oraz odnośnik w menu. Prowadzić użytkownika po jednej małej zmianie, wyjaśniając cel i działanie.

Kryterium sukcesu: administrator otwiera stronę z menu i widzi trzy uzgodnione dyscypliny. Sposób weryfikacji uzgodnić stosownie do wykonanych zmian.

## Stan wykonania i weryfikacji

### Krok 1 — chroniona trasa

Użytkownik dodał w `routes/web.php` trasę `Route::view('/admin/disciplines', 'admin.disciplines.index')`, nazwaną `admin.disciplines.index`, z middleware `auth` i `can:access-admin`. Trasa ma wyświetlać widok `resources/views/admin/disciplines/index.blade.php` bez kontrolera. Middleware wymagają zalogowania i uprawnień administratora.

Asystent potwierdził zapis w kodzie. Użytkownik uruchomił `php artisan route:list --name=admin.disciplines -v`; wynik pokazał jedną trasę GET|HEAD oraz middleware `web`, `auth` i `can:access-admin`. Potwierdza to rejestrację trasy, ale nie sprawdza jeszcze odpowiedzi HTTP ani działania zabezpieczeń.

#### Kod trasy i objaśnienie fragment po fragmencie

W pliku `routes/web.php` korzystamy z istniejącego importu fasady `Route`:

```php
use Illuminate\Support\Facades\Route;
```

Import pozwala pisać `Route::view()` zamiast pełnej nazwy `Illuminate\Support\Facades\Route::view()`. W pliku występuje już raz — nie dodajemy go ponownie dla każdej trasy.

Trasa strony dyscyplin:

```php
// DISCIPLINES

Route::view('/admin/disciplines', 'admin.disciplines.index')
    ->middleware(['auth', 'can:access-admin'])
    ->name('admin.disciplines.index');
```

1. **`// DISCIPLINES`** — komentarz porządkujący plik: oznacza początek sekcji tras dotyczących dyscyplin. Nie wpływa na działanie aplikacji.
2. **`Route::view(...)`** — rejestruje trasę wyświetlającą wskazany widok bez osobnego kontrolera. Obsługuje metody GET i HEAD; GET służy m.in. do otwarcia strony w przeglądarce.
3. **`'/admin/disciplines'`** — ścieżka żądania, którą Laravel ma dopasować. Jest częścią adresu po domenie i ewentualnym porcie aplikacji.
4. **`'admin.disciplines.index'` jako drugi argument** — nazwa widoku. Kropki odpowiadają kolejnym katalogom wewnątrz `resources/views`, dlatego wskazuje plik `resources/views/admin/disciplines/index.blade.php`.
5. **`->middleware(['auth', 'can:access-admin'])`** — przypisuje dwa zabezpieczenia. `auth` wymaga zalogowania, a `can:access-admin` sprawdza zdefiniowane w aplikacji uprawnienie `access-admin`. Samo zalogowanie nie wystarcza do wejścia na tę stronę.
6. **`->name('admin.disciplines.index')`** — nadaje trasie nazwę, której można użyć do wygenerowania adresu przez `route('admin.disciplines.index')`, np. w menu. Nazwa trasy i nazwa widoku są tutaj takie same dla spójności, ale pełnią różne funkcje: pierwsza identyfikuje trasę, druga wskazuje szablon.
7. **`->` i końcowy średnik** — kolejne wywołania konfigurują tę samą trasę. Średnik kończy całą instrukcję PHP, mimo że zapis zajmuje kilka wierszy.

Trasa znajduje się w `routes/web.php`, więc w obecnej konfiguracji aplikacji otrzymuje również grupę middleware `web`, widoczną w wyniku `route:list`. Zapewnia ona m.in. obsługę sesji, z której korzysta logowanie.

### Krok 2 — widok z tłumaczonym nagłówkiem

Użytkownik utworzył `resources/views/admin/disciplines/index.blade.php` z układem `x-admin-layout`, slotem `heading` i komponentem `x-page-heading`. Asystent sprawdził zapisany kod, a na wyraźne zlecenie użytkownika zastąpił tekst nagłówka wyrażeniem `{{ __('disciplines.title') }}` oraz dodał pliki `lang/pl/disciplines.php` i `lang/en/disciplines.php` z wartościami `Dyscypliny` i `Disciplines`.

Helper `__()` odczytuje klucz `title` z pliku `disciplines.php` dla aktualnego języka aplikacji. Blade wyświetla wynik przez `{{ }}`. Slot `heading` przekazuje nagłówek do istniejącego układu panelu, a komponent `x-page-heading` zapewnia jego wspólne formatowanie.

#### Kod widoku i objaśnienie fragment po fragmencie

Plik `resources/views/admin/disciplines/index.blade.php`:

```php
<x-admin-layout>
    <x-slot:heading>
        <x-page-heading>
            {{ __('disciplines.title') }}
        </x-page-heading>
    </x-slot:heading>
</x-admin-layout>
```

1. **`<x-admin-layout>`** — wywołuje istniejący komponent `resources/views/components/admin-layout.blade.php`. Zapewnia szkielet strony panelu: dokument HTML, zasoby CSS i JavaScript, menu boczne, górny pasek oraz obszar głównej treści. Dzięki temu widok dyscyplin nie powiela całego układu.
2. **`<x-slot:heading>`** — tworzy nazwany slot `heading`, czyli fragment treści przekazywany do komponentu układu. W `admin-layout.blade.php` zmienna `{{ $heading }}` wyświetla ten fragment w górnym pasku. Nazwa slotu wskazuje miejsce docelowe; sama nie tworzy nagłówka HTML.
3. **`<x-page-heading>`** — wywołuje komponent `resources/views/components/page-heading.blade.php`. Komponent tworzy element `<h1>` i nadaje mu wspólne formatowanie nagłówków panelu. Przekazany tekst trafia do jego domyślnego slotu `{{ $slot }}`. W tym kroku nie przekazujemy opcjonalnego slotu ikony.
4. **`__('disciplines.title')`** — wywołuje helper tłumaczeń Laravel. Część `disciplines` wskazuje plik `disciplines.php`, a `title` klucz w zwracanej przez niego tablicy. Aktualny język aplikacji decyduje, czy Laravel odczyta np. `lang/pl/disciplines.php`, czy `lang/en/disciplines.php`.
5. **`{{ ... }}`** — wyświetla wynik helpera w szablonie Blade, stosując escapowanie HTML. Do przeglądarki trafia tekst tłumaczenia, np. `Dyscypliny`.
6. **Znaczniki zamykające** — kończą kolejno komponent nagłówka, nazwany slot i komponent układu. Poza slotem `heading` nie umieszczono jeszcze treści, dlatego główny obszar strony pozostaje pusty.

#### Pliki tłumaczeń

Plik `lang/pl/disciplines.php`:

```php
<?php

return [
    'title' => 'Dyscypliny',
];
```

Plik `lang/en/disciplines.php`:

```php
<?php

return [
    'title' => 'Disciplines',
];
```

- **`<?php`** rozpoczyna kod PHP.
- **`return [...]`** zwraca tablicę tłumaczeń, którą odczytuje Laravel.
- **`'title'`** jest wspólnym kluczem w obu językach; widok odwołuje się do niego przez `disciplines.title`.
- **`'Dyscypliny'` / `'Disciplines'`** to tekst wyświetlany dla odpowiedniego języka. Dodanie plików nie tworzy przełącznika języka ani nie zmienia aktualnego języka aplikacji.

Użytkownik doprecyzował, że prośba o dodanie kodu i wyjaśnienia dotyczyła dokumentacji. Poprosił o pozostawienie już dodanych zmian aplikacji. Powyższy opis dokumentuje ich aktualny stan.

Weryfikacja asystenta: Pint przeszedł, `php artisan view:cache --no-interaction` skompilował szablony bez błędów, a `git diff --check` nie wykazał problemów. Następnie wyczyszczono cache widoków.

Weryfikacja użytkownika: po otwarciu `/admin/disciplines` jako administrator użytkownik potwierdził sukces — widoczny układ panelu, nagłówek „Dyscypliny” i pusta treść poniżej, bez błędu. Nie sprawdzono jeszcze wersji angielskiej ani odmowy dostępu dla gościa i użytkownika bez uprawnień administratora.

Lista dyscyplin i pozycja menu nie zostały jeszcze dodane. Nie wykonano testów nowej funkcji.

## Sposób pracy

### Ikony Material Symbols i odnośnik w menu

Na wyraźne zlecenie użytkownika zastąpiono uzgodnione ikony oficjalnymi SVG Google Material Symbols Outlined: `space_dashboard`, `group`, `edit`, `sports`, `logout`, `open_in_new` i `menu`. Źródło: https://github.com/google/material-design-icons/tree/master/symbols/web . Wariant: bez wypełnienia (Fill 0), Weight 400, Grade 0, Optical size 24. Wariant Outlined nadal jest zapisany technicznie jako wypełnione ścieżki SVG; nie należy dodawać do niego `fill="none"` ani zamieniać go ręcznie na kontur.

Pliki pozostają w `resources/svg`, pod nazwami odpowiadającymi zastosowaniu w aplikacji. Dodano `fill="currentColor"`, aby kolor dziedziczył się z otoczenia. Licencja Apache 2.0 znajduje się w `resources/svg/LICENSE-material-symbols.txt`. Nie instalowano zależności ani fontu ikon.

W `resources/views/components/icon.blade.php` dodano do istniejącego `match`:

```php
'disciplines' => resource_path('svg/disciplines.svg'),
'logout' => resource_path('svg/logout.svg'),
'open-in-new' => resource_path('svg/open-in-new.svg'),
'menu' => resource_path('svg/menu.svg'),
```

Każdy wpis mapuje dozwoloną nazwę na konkretny lokalny plik. Zachowano wyjątek dla nieznanej nazwy. Komponent nadal ukrywa dekoracyjną ikonę przed czytnikami ekranu przez `aria-hidden="true"`.

W `admin-layout.blade.php` zastąpiono osadzone SVG i znak menu wywołaniami `x-icon`. Menu, pulpit, użytkownicy i wylogowanie korzystają z `class="size-6"` (24 px), a odnośnik do witryny z `class="size-4"` (16 px). Nagłówki zachowują istniejący rozmiar 40 px. Ołówek przy edycji na liście użytkowników również korzysta z `x-icon`.

Dodana pozycja menu:

```blade
<a href="{{ route('admin.disciplines.index') }}" aria-label="{{ __('disciplines.title') }}">
    <x-icon name="disciplines" class="size-6" />
    <span class="admin-nav-label">{{ __('disciplines.title') }}</span>
</a>
```

- `route(...)` generuje adres istniejącej trasy dyscyplin.
- `aria-label` nadaje odnośnikowi nazwę także wtedy, gdy tekst znika po zwinięciu menu.
- `x-icon` pobiera gwizdek z `resources/svg/disciplines.svg`.
- `admin-nav-label` zachowuje wspólne zasady pokazywania i ukrywania etykiet menu.

Aktualny widok dyscyplin:

```blade
<x-admin-layout>
    <x-slot:heading>
        <x-page-heading>
            <x-slot:icon>
                <x-icon name="disciplines" class="size-full" />
            </x-slot:icon>

            {{ __('disciplines.title') }}
        </x-page-heading>
    </x-slot:heading>
</x-admin-layout>
```

Slot `icon` przekazuje gwizdek do komponentu nagłówka. `size-full` wypełnia jego istniejącą oprawę 40 × 40 px. Tytuł nadal pochodzi z tłumaczeń.

Zaktualizowano istniejące testy ikon do nowej geometrii i sposobu osadzenia `x-icon`. Weryfikacja asystenta: `PageHeadingTest`, `AdminTopBarTest` i `AdminDashboardTest` — 15 testów, 135 asercji, wszystkie przeszły. Pint, build Vite i `git diff --check` przeszły. Build zgłosił ostrzeżenie o opcjonalnym pakiecie `fontaine` dla optymalizacji fontów; nie instalowano go. Lista dyscyplin pozostaje do wykonania. Wygląd nowych ikon oraz nowa pozycja menu wymagają potwierdzenia użytkownika w przeglądarce.

## Wspólna architektura list — komponent `list-panel`

### Cel i kolejność wdrożenia

Wspólne komponenty wprowadzamy najpierw przy budowie widoku dyscyplin (`disciplines`), a następnie stosujemy je również w widoku użytkowników w ramach tego samego etapu. Nie pozostawiamy dwóch równoległych architektur. Zasada dotyczy wszystkich widoków objętych zmienianym wzorcem i została zapisana w `.ai/rules/views.md`.

Dotychczas lista użytkowników miała oprawę zapisaną bezpośrednio jako `div` w `resources/views/admin/users/index.blade.php`. Istniały osobne komponenty m.in. wyszukiwania, filtrów, sortowania i wyboru liczby wyników, ale nie wspólny komponent oprawy listy.

### Utworzenie komponentu

Polecenie do utworzenia anonimowego komponentu Blade:

```bash
php artisan make:component list-panel --view --no-interaction
```

- `make:component list-panel` wskazuje nazwę nowego komponentu.
- `--view` tworzy sam szablon Blade, bez osobnej klasy PHP.
- `--no-interaction` wyłącza pytania interaktywne polecenia.

Plik `resources/views/components/list-panel.blade.php`:

```blade
<div {{ $attributes->class(['rounded-xl border border-gray-200 bg-white shadow-sm']) }}>
    {{ $slot }}
</div>
```

Objaśnienie fragment po fragmencie:

1. **`<div ...>`** tworzy wspólną oprawę zawartości listy.
2. **`$attributes`** zawiera atrybuty przekazane podczas użycia komponentu, np. `id`, dodatkową klasę lub `data-row-selection` potrzebne liście użytkowników.
3. **`->class([...])`** łączy domyślne klasy oprawy z klasami przekazanymi przez widok. Pozostałe atrybuty również trafiają na zewnętrzny `div`.
4. **`rounded-xl`** nadaje narożnikom zaokrąglenie zgodne z dotychczasową listą użytkowników.
5. **`border border-gray-200`** dodaje cienką, jasnoszarą ramkę.
6. **`bg-white shadow-sm`** ustawia białe tło i delikatny cień.
7. **`{{ $slot }}`** wyświetla treść umieszczoną między `<x-list-panel>` i `</x-list-panel>`. Komponent odpowiada za oprawę, a widok przekazuje własną zawartość.

### Stan wykonania i następny krok

Asystent sprawdził plik: komponent istnieje i zawiera powyższy kod. Nie potwierdzono wykonania polecenia Artisan; potwierdzono obecność i zawartość pliku. Komponent nie został jeszcze podłączony do widoków ani zweryfikowany podczas wyświetlania strony.

#### Podłączenie oprawy do dyscyplin

W widoku dyscyplin po slocie nagłówka dodano:

```blade
<x-list-panel>
    <p class="px-6 py-3">Miejsce na listę dyscyplin</p>
</x-list-panel>
```

`x-list-panel` wyświetla wspólną oprawę. Akapit trafia do jej domyślnego slotu, a `px-6 py-3` nadaje mu odstępy wewnętrzne. Tekst jest tymczasowym elementem do sprawdzenia komponentu, przeznaczonym do zastąpienia tabelą.

#### Podłączenie oprawy do użytkowników

W `resources/views/admin/users/index.blade.php` użytkownik zastąpił zewnętrzny `div` oprawy wywołaniem komponentu:

```blade
<x-list-panel data-row-selection>
    {{-- Dotychczasowa zawartość listy użytkowników pozostaje tutaj. --}}
</x-list-panel>
```

Powyższy komentarz jedynie skraca przykład w dokumentacji. W rzeczywistym widoku między znacznikami pozostały formularz, pasek zaznaczenia, tabela i stopka.

`data-row-selection` jest przekazywany przez `$attributes` na zewnętrzny `div` komponentu. Dzięki temu skrypt zaznaczania nadal odnajduje kontener listy. Klasy wyglądu pochodzą teraz ze wspólnego `list-panel`, zamiast być powielone w widoku.

Asystent potwierdził w kodzie otwierający i zamykający znacznik komponentu oraz zachowanie `data-row-selection`. Użytkownik potwierdził sukces po sprawdzeniu wyglądu listy, zaznaczania wierszy i czyszczenia zaznaczenia. Oba widoki korzystają ze wspólnej oprawy; ten krok został ukończony. Nie uruchamiano w tym kroku testów automatycznych.

### Uporządkowanie komponentów w katalogu `lists`

Na zlecenie użytkownika asystent przeniósł pliki:

- `resources/views/components/list-panel.blade.php` → `resources/views/components/lists/panel.blade.php`;
- `resources/views/components/list-table.blade.php` → `resources/views/components/lists/table.blade.php`.

W obu widokach — dyscyplin i użytkowników — zmieniono otwierające i zamykające znaczniki oprawy na `x-lists.panel`. Przykład użytkowników:

```blade
<x-lists.panel data-row-selection>
    {{-- Dotychczasowa zawartość listy. --}}
</x-lists.panel>
```

Kropka w nazwie komponentu odpowiada podkatalogowi: `lists.panel` wskazuje `components/lists/panel.blade.php`. Zachowano `data-row-selection`, klasy oprawy i całą zawartość widoków. Wcześniejsze przykłady w rozdziale opisują nazwę sprzed przeniesienia.

Plik `lists/table.blade.php` istniał jako domyślny szablon wygenerowany przez Artisan; przeniesiono go bez zmiany zawartości. Nie zawiera jeszcze implementacji wspólnej tabeli i nie jest podłączony do widoków.

Weryfikacja asystenta: 19 testów (170 asercji) w `AdminDashboardTest`, `PageHeadingTest`, `AdminTopBarTest` i `UserSelectionTest` przeszło. Kompilacja Blade, Pint i `git diff --check` przeszły; cache widoków wyczyszczono po kompilacji. W widokach nie pozostały odwołania do `x-list-panel` ani `x-list-table`. Nie wykonano nowego sprawdzenia w przeglądarce.

### Uporządkowanie pozostałych komponentów Blade

Na zlecenie użytkownika przeanalizowano wszystkie pozostałe komponenty i pogrupowano je według odpowiedzialności. Zawartość komponentów pozostała taka sama; zmieniono ich położenie i wszystkie wywołania w widokach oraz bezpośrednich testach komponentów.

| Dotychczasowy plik w `components/` | Nowy plik w `components/` | Aktualne wywołanie |
| --- | --- | --- |
| `admin-layout.blade.php` | `layouts/admin.blade.php` | `<x-layouts.admin>` |
| `icon.blade.php` | `ui/icon.blade.php` | `<x-ui.icon>` |
| `page-heading.blade.php` | `ui/page-heading.blade.php` | `<x-ui.page-heading>` |
| `per-page-select.blade.php` | `lists/per-page-select.blade.php` | `<x-lists.per-page-select>` |
| `sortable-column.blade.php` | `lists/sortable-column.blade.php` | `<x-lists.sortable-column>` |
| `search-input.blade.php` | `lists/search-input.blade.php` | `<x-lists.search-input>` |
| `user-filters.blade.php` | `users/filters.blade.php` | `<x-users.filters>` |

`layouts` zawiera układ strony, `ui` elementy wspólne także poza listami, a `lists` elementy list niezależne od rodzaju danych. `users/filters` pozostaje komponentem właściwym użytkownikom, ponieważ zna typy kont i trasę użytkowników. Nie przenoszono go do uniwersalnych filtrów list.

Przykład aktualnej kompozycji:

```blade
<x-layouts.admin>
    <x-slot:heading>
        <x-ui.page-heading>
            <x-slot:icon>
                <x-ui.icon name="disciplines" class="size-full" />
            </x-slot:icon>
            {{ __('disciplines.title') }}
        </x-ui.page-heading>
    </x-slot:heading>
    <x-lists.panel>
        <p class="px-6 py-3">Miejsce na listę dyscyplin</p>
    </x-lists.panel>
</x-layouts.admin>
```

Kropki w nazwach wskazują podkatalogi komponentów. Sloty, props i atrybuty zachowują dotychczasowe znaczenie. Wcześniejsze fragmenty rozdziału przedstawiają nazwy obowiązujące przed reorganizacją; powyższa tabela wskazuje aktualne odpowiedniki.

Weryfikacja asystenta po reorganizacji: wszystkie 209 testów PHP (1162 asercje) przeszły, podobnie 5 plików testów frontendowych uruchomionych przez Node. Pint, kompilacja Blade i `git diff --check` przeszły. Cache widoków wyczyszczono. W widokach i testach nie pozostały wywołania komponentów pod dawnymi nazwami. Wyglądu po tej reorganizacji nie sprawdzano w przeglądarce.

Użytkownik również potwierdził, że wszystkie uruchomione przez niego testy przeszły poprawnie. Nie podał osobnego zestawienia poleceń ani liczby testów. To potwierdzenie testów, nie nowego sprawdzenia wyglądu w przeglądarce. Dodatkowo analiza PHPStan wykonana przez asystenta po reorganizacji zakończyła się bez błędów.

## Sposób dalszej pracy

### Publikacja zmian — podział na commity

Uzgodniono trzy commity: reguły architektury, powiązane zmiany kodu aplikacji oraz dokumentację. Niewdrożony szablon `resources/views/components/lists/table.blade.php` pozostaje poza commitami.

Użytkownik utworzył commit `09dd101` — `Ustal wspólną architekturę list panelu`, obejmujący `.ai/rules/controllers.md` i `.ai/rules/views.md`. Przekazany wynik potwierdza przejście PHPStan w hooku GrumPHP. Push nie został jeszcze potwierdzony.

Następnie użytkownik utworzył commit `dc196fa` — `Dodaj stronę dyscyplin i uporządkuj komponenty panelu`, obejmujący 25 plików: stronę i tłumaczenia dyscyplin, trasę, ikony z licencją, reorganizację komponentów oraz dostosowane widoki i testy. `git diff --cached --check` i PHPStan w hooku GrumPHP przeszły. Dokumentacja i niewdrożony szablon tabeli pozostały poza tym commitem. Kolejny krok to commit dokumentacji, a następnie push przygotowanych commitów.

Użytkownik wykonuje zmiany aplikacji, polecenia i Git, chyba że wyraźnie zleci je asystentowi. Asystent uzupełnia ten rozdział po potwierdzonych ustaleniach i wynikach, odróżniając plany od wykonania i weryfikacji. Bez nowych zależności, Filament, nowej gałęzi i worktree.
