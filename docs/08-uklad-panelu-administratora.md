# 08. Układ panelu administratora

## Cel i trasa

Przygotowaliśmy podstawowy układ panelu administratora. Filament jest
inspiracją wizualną; nie instalowaliśmy go w projekcie Sportowiada.

W `routes/web.php` zdefiniowaliśmy trasę:

```php
Route::view('/admin', 'admin.dashboard')->name('admin.dashboard');
```

Trasa wyświetla `resources/views/admin/dashboard.blade.php`. Na tym
etapie nie jest jeszcze chroniona logowaniem ani uprawnieniami administratora.

## Komponent układu Blade

Plik `resources/views/components/admin-layout.blade.php` zawiera szkielet
HTML, panel boczny i główny obszar treści. Widok pulpitu korzysta z niego
przez komponent:

```blade
<x-admin-layout>
    <h1>Panel administratora</h1>
</x-admin-layout>
```

Zawartość komponentu trafia do `{{ $slot }}` wewnątrz elementu `<main>`.
Link „Pulpit” używa nazwanej trasy `route('admin.dashboard')`.

## Style i Vite

Utworzyliśmy `resources/css/admin.css`, który importuje `app.css`,
a przez niego Tailwind CSS. Dodaliśmy `admin.css` do listy `input`
w `vite.config.js`. Układ ładuje zasoby przez:

```blade
@vite(['resources/css/admin.css', 'resources/js/app.js'])
```

Przyjęliśmy połączenie klas Tailwind i własnego CSS:

- `<body class="flex min-h-screen">` ustawia poziomy układ i minimalną
  wysokość równą wysokości okna;
- `<main class="flex-1 p-8">` wypełnia pozostałe miejsce i dodaje
  wewnętrzny odstęp 32 px;
- własny CSS opisuje panel, linki, przycisk i stan zwinięcia.

Pozostawiliśmy własną klasę przycisku, ponieważ jest dla nas czytelniejsza
niż długa lista klas narzędziowych.

## Zwijanie panelu bocznego

Panel ma identyfikator `admin-sidebar` i klasę `admin-sidebar`.
Rozwinięty ma szerokość 240 px i odstępy wewnętrzne 24 px.
Po dodaniu klasy `is-collapsed` ma szerokość 80 px oraz odstępy
24 px w pionie i 8 px w poziomie.

Przycisk ☰ znajduje się wewnątrz panelu i pozostaje dostępny po zwinięciu.
Jego klasa `admin-sidebar-toggle` odpowiada za wygląd, a identyfikator
`sidebar-toggle` służy do znalezienia go w JavaScript.

W `resources/js/app.js` dodaliśmy obsługę kliknięcia przez
`addEventListener()`. Wywołanie
`sidebar.classList.toggle('is-collapsed')` przełącza stan panelu.
Warunek sprawdza istnienie panelu i przycisku, ponieważ ten sam plik JS
jest ładowany także na stronie publicznej. Obsługa panelu korzysta
z JavaScript przeglądarki, niezależnie od istniejącego kodu Vue.

Link „Pulpit” zawiera ikonę domku SVG i etykietę w elemencie
`<span class="admin-nav-label">`. Po zwinięciu etykieta jest ukrywana,
a ikona pozostaje widoczna. `flex-shrink: 0` zapobiega ściskaniu ikony.

## Dostępność i interakcje

- `aria-controls="admin-sidebar"` wskazuje panel sterowany przyciskiem.
- `aria-expanded` jest aktualizowane po każdym kliknięciu.
- Nazwa przycisku w `aria-label` zmienia się między „Zwiń panel
  nawigacyjny” i „Rozwiń panel nawigacyjny”.
- Link ma `aria-label="Pulpit"`, więc zachowuje nazwę po ukryciu tekstu.
- Dekoracyjna ikona SVG ma `aria-hidden="true"`.
- Przycisk ma obszar 48 × 48 px, podświetlenie po najechaniu
  oraz obramowanie `:focus-visible` podczas obsługi klawiaturą.
  Aktualny kolor obramowania to `#d53636`.

## Sprawdzenie i obecne ograniczenia

W trakcie pracy sprawdzaliśmy zmiany w przeglądarce. Potwierdziliśmy
działanie zwijania oraz zachowanie wyglądu po przeniesieniu stylów
układu do klas Tailwind.

Stan panelu nie jest jeszcze zapamiętywany. Kliknięcie linku „Pulpit”
ponownie ładuje stronę i przywraca rozwinięty panel. Nie dodaliśmy
jeszcze osobnego zachowania dla telefonów, zapisu w `localStorage`
ani zarządzania użytkownikami.
