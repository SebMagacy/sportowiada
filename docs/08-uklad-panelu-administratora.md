# 08. Układ panelu administratora

## Cel

Celem tego etapu było przygotowanie podstawowego układu wizualnego
panelu administratora oraz oddzielenie wspólnego układu od zawartości
konkretnej strony. Panel budujemy samodzielnie w Laravelu.
Filament pozostaje inspiracją wizualną — nie instalujemy go.

## Gałąź i commit

Prace prowadziliśmy na gałęzi `feature/admin-layout`.
W aktualnej historii projektu etap jest zapisany w commicie:

```text
c748faf Dodaj układ panelu administratora ze stylami i trasą
```

## Trasa panelu

W pliku `routes/web.php` dodaliśmy:

```php
Route::view('/admin', 'admin.dashboard')->name('admin.dashboard');
```

Ścieżka `/admin` wyświetla widok `admin.dashboard`, a trasa otrzymała
nazwę `admin.dashboard`. W menu odwołujemy się do niej przez
`route('admin.dashboard')`, dzięki czemu nie wpisujemy ścieżki ręcznie.

Trasa nie ma jeszcze zabezpieczeń uwierzytelniania ani uprawnień.
Sama ścieżka `/admin` nie ogranicza dostępu do administratorów.

## Komponent układu i widok strony

Wykorzystaliśmy dwa pliki Blade:

- `resources/views/components/admin-layout.blade.php` — anonimowy komponent wspólnego układu;
- `resources/views/admin/dashboard.blade.php` — zawartość strony startowej panelu.

Widok dashboardu korzysta z komponentu:

```blade
<x-admin-layout>
    <h1>Panel administratora</h1>
</x-admin-layout>
```

Zawartość pomiędzy znacznikami komponentu trafia do `{{ $slot }}`
w jego szablonie. Dzięki temu kolejne strony mogą korzystać ze
wspólnego układu bez kopiowania całego dokumentu HTML.

Komponent zawiera dokument HTML z językiem `pl`, tytuł strony,
menu boczne w elemencie `<aside>` oraz obszar treści w `<main>`.
Menu ma etykietę `aria-label="Menu administratora"` i odnośnik „Pulpit”.

## Style panelu

Dodaliśmy arkusz `resources/css/admin.css`. Zaczyna się on od:

```css
@import './app.css';
```

Panel korzysta więc także ze wspólnych stylów z `app.css`.
Ma osobny plik wejściowy i własne reguły układu, ale jego style
nie są całkowicie niezależne od arkusza strony publicznej.

Klasa `.admin-layout` ustawia Flexbox i minimalną wysokość `100vh`.
Menu `.admin-sidebar` ma szerokość `240px`, wyłączone kurczenie,
odstępy wewnętrzne `24px`, ciemne tło i biały tekst.
Obszar `.admin-content` zajmuje pozostałe miejsce dzięki `flex: 1`
i ma odstępy wewnętrzne `32px`.

## Podłączenie Vite

W `vite.config.js` dodaliśmy `resources/css/admin.css` do tablicy
`input` obok `resources/css/app.css` i `resources/js/app.js`.
Komponent układu ładuje zasoby przez:

```blade
@vite(['resources/css/admin.css', 'resources/js/app.js'])
```

Panel ładuje obecnie ten sam plik JavaScript co strona publiczna.
Nie tworzyliśmy osobnego pliku JavaScript dla panelu.

## Weryfikacja

Do ponownego sprawdzenia projektu można użyć poleceń uruchamianych
w jego katalogu głównym:

```bash
npm run build
php artisan test --compact
vendor/bin/grumphp run
```

Po zmianach w plikach PHP należy również uruchomić formatter:

```bash
vendor/bin/pint --dirty --format agent
```

Podczas porównywania tego rozdziału z kodem sprawdziliśmy zawartość
plików. Nie uruchamialiśmy ponownie kompilacji ani testów;
powyższa lista poleceń nie stanowi potwierdzenia ich wyników.

## Dalsze prace

Na tym etapie istnieje szkielet panelu z menu bocznym i obszarem treści.
Do wykonania pozostają:

- uwierzytelnianie i kontrola uprawnień administratora;
- zabezpieczenie tras panelu;
- lista użytkowników oraz ich dodawanie, edycja i usuwanie;
- dostosowanie układu do urządzeń mobilnych.
