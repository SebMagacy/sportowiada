# 08. Układ panelu administratora

## Cel i trasa

Przygotowaliśmy podstawowy układ panelu administratora aplikacji Sportowiada. Filament stanowi inspirację wizualną, ale nie jest instalowany ani wykorzystywany w projekcie.

W pliku `routes/web.php` zdefiniowaliśmy trasę:

```php
Route::view('/admin', 'admin.dashboard')->name('admin.dashboard');
```

Trasa wyświetla widok `resources/views/admin/dashboard.blade.php`.

Na obecnym etapie dostęp do panelu administratora nie jest jeszcze chroniony mechanizmem uwierzytelniania ani uprawnieniami administratora.

## Komponent układu Blade

Utworzyliśmy komponent:

`resources/views/components/admin-layout.blade.php`

Zawiera on podstawową strukturę HTML panelu administratora:

- boczny pasek nawigacyjny (`<aside>`);
- przycisk zwijania i rozwijania paska;
- menu nawigacyjne (`<nav>`);
- główny obszar treści (`<main>`).

Widok pulpitu wykorzystuje komponent w następujący sposób:

```blade
<x-admin-layout>
    <h1>Panel administratora</h1>
</x-admin-layout>
```

Zawartość umieszczona wewnątrz komponentu jest przekazywana przez zmienną Blade `{{ $slot }}` do elementu `<main>`.

Link „Pulpit” wykorzystuje nazwaną trasę:

```blade
{{ route('admin.dashboard') }}
```

Dzięki temu nie musimy wpisywać adresu `/admin` bezpośrednio w znaczniku odnośnika.

## Style i Vite

Utworzyliśmy plik:

`resources/css/admin.css`

Importuje on `app.css`, który udostępnia konfigurację Tailwind CSS.

W `vite.config.js` dodaliśmy `admin.css` do listy plików wejściowych.

Komponent Blade ładuje zasoby za pomocą dyrektywy:

```blade
@vite(['resources/css/admin.css', 'resources/js/app.js'])
```

Przyjęliśmy połączenie Tailwind CSS i własnych klas CSS.

Przykłady wykorzystania Tailwind:

- `<body class="flex min-h-screen">` — poziomy układ strony i minimalna wysokość odpowiadająca wysokości okna;
- `<main class="flex-1 p-8">` — wypełnienie pozostałej przestrzeni i wewnętrzny odstęp 32 px.

Własny CSS odpowiada za wygląd paska bocznego, nawigacji, przycisku oraz stanu zwinięcia.

Zachowaliśmy klasę `admin-sidebar-toggle`, ponieważ osobna definicja CSS jest w tym przypadku czytelniejsza niż rozbudowany zestaw klas narzędziowych Tailwind.

## Zwijanie panelu bocznego

Panel boczny ma identyfikator `admin-sidebar` i klasę `admin-sidebar`.

W stanie rozwiniętym ma szerokość 240 px oraz odstępy wewnętrzne 24 px.

W stanie zwiniętym ma szerokość 80 px oraz odstępy wewnętrzne 24 px w pionie i 8 px w poziomie.

Stan zwinięcia jest kontrolowany przez klasę `sidebar-collapsed`, umieszczaną na elemencie `<html>`.

W pliku `resources/css/admin.css` znajdują się reguły:

```css
.sidebar-collapsed .admin-sidebar {
    width: 80px;
    padding: 24px 8px;
}

.sidebar-collapsed .admin-sidebar .admin-nav-label {
    display: none;
}
```

Pierwsza reguła zmniejsza szerokość i odstępy panelu, a druga ukrywa tekst etykiet menu.

Link „Pulpit” zawiera ikonę domku w formacie SVG i tekst:

```html
<span class="admin-nav-label">Pulpit</span>
```

Po zwinięciu panelu etykieta zostaje ukryta, natomiast ikona pozostaje widoczna.

Własność `flex-shrink: 0` zapobiega zmniejszaniu ikony w układzie Flexbox.

Przycisk ☰ znajduje się wewnątrz panelu, dzięki czemu pozostaje dostępny również po jego zwinięciu.

## Obsługa JavaScript

Obsługę przycisku umieściliśmy w pliku:

`resources/js/app.js`

JavaScript wyszukuje elementy:

```javascript
const sidebar = document.getElementById('admin-sidebar');
const sidebarToggle = document.getElementById('sidebar-toggle');
```

Przed dodaniem obsługi kliknięcia sprawdzamy, czy obydwa elementy istnieją:

```javascript
if (sidebar && sidebarToggle) {
    // Obsługa panelu
}
```

Jest to konieczne, ponieważ `app.js` jest wykorzystywany również na stronie publicznej, która nie musi zawierać panelu administratora.

Zmiana stanu paska odbywa się przez:

```javascript
const isCollapsed = document.documentElement.classList.toggle('sidebar-collapsed');
```

Metoda `classList.toggle()` dodaje klasę, gdy jej nie ma, albo usuwa ją, gdy już istnieje.

Wartość zwracana przez tę metodę jest typu `boolean`:

- `true` — pasek jest zwinięty;
- `false` — pasek jest rozwinięty.

Nie korzystamy już z wcześniejszej klasy `is-collapsed` przypisywanej bezpośrednio do panelu.

## Zapamiętywanie ustawienia — localStorage

Wprowadziliśmy mechanizm zapamiętywania stanu paska bocznego.

Wykorzystujemy `localStorage`, czyli pamięć przeglądarki umożliwiającą przechowywanie niewielkich ilości danych pomiędzy kolejnymi otwarciami strony.

Zapisujemy ustawienie pod kluczem:

`admin-sidebar-collapsed`

Po kliknięciu przycisku JavaScript wykonuje:

```javascript
localStorage.setItem('admin-sidebar-collapsed', String(isCollapsed));
```

`setItem()` zapisuje wartość pod wskazanym kluczem.

Ponieważ `localStorage` przechowuje dane jako ciągi znaków, wartość logiczną `true` lub `false` zamieniamy na tekst za pomocą `String()`.

Odczytywanie zapisanej wartości odbywa się przez:

```javascript
localStorage.getItem('admin-sidebar-collapsed');
```

Poznaliśmy również metodę:

```javascript
localStorage.removeItem('admin-sidebar-collapsed');
```

Służy ona do usuwania zapisanego ustawienia.

Dane `localStorage` są przechowywane lokalnie dla danego pochodzenia strony i profilu przeglądarki. Nie są zapisywane w bazie MySQL i nie synchronizują się automatycznie pomiędzy urządzeniami.

## Wczesna inicjalizacja paska

Podczas pierwszych testów zauważyliśmy, że pasek po odświeżeniu strony na moment pojawiał się rozwinięty, a następnie przechodził do zapamiętanego stanu.

Problem wynikał z kolejności wykonywania JavaScript i renderowania strony.

Aby temu zapobiec, utworzyliśmy osobny plik:

`public/js/admin-sidebar-init.js`

Zawiera on kod:

```javascript
if (localStorage.getItem('admin-sidebar-collapsed') === 'true') {
    document.documentElement.classList.add('sidebar-collapsed');
}
```

Skrypt odczytuje zapisane ustawienie i, jeśli pasek powinien być zwinięty, dodaje odpowiednią klasę do elementu `<html>`.

W komponencie `admin-layout.blade.php`, w sekcji `<head>`, dodaliśmy:

```blade
<script src="{{ asset('js/admin-sidebar-init.js') }}"></script>
@vite(['resources/css/admin.css', 'resources/js/app.js'])
```

Funkcja Laravel `asset()` generuje adres URL pliku znajdującego się w katalogu `public`.

Skrypt nie korzysta z atrybutów `async` ani `defer`. Dzięki temu wykonuje się podczas przetwarzania sekcji `<head>`, zanim przeglądarka rozpocznie wyświetlanie zawartości `<body>`.

Pozwala to ustalić początkowy wygląd paska bez widocznego migania.

Plik inicjalizacyjny nie jest przetwarzany przez Vite, ponieważ jest ładowany bezpośrednio z katalogu `public`.

## Vue a panel administratora

Projekt wykorzystuje również Vue.

W pliku `resources/js/app.js` zmodyfikowaliśmy uruchamianie aplikacji Vue:

```javascript
const vueRoot = document.getElementById('app');

if (vueRoot) {
    createApp(App).mount(vueRoot);
}
```

Wcześniej Vue próbował uruchomić się bezwarunkowo, co powodowało ostrzeżenie na stronie administratora:

```text
[Vue warn]: Failed to mount app:
mount target selector "#app" returned null.
```

Dodanie warunku powoduje, że Vue jest montowany wyłącznie na stronach zawierających element `#app`.

Obecny pasek administratora nie jest komponentem Vue. Jego obsługę realizuje zwykły JavaScript.

W przyszłości, podczas budowania wielopoziomowego menu administratora, możemy rozważyć utworzenie osobnego komponentu Vue.

## Dostępność i interakcje

Podczas tworzenia panelu uwzględniliśmy podstawowe zasady dostępności.

Przycisk zawiera atrybuty:

- `aria-controls="admin-sidebar"` — wskazuje element kontrolowany przez przycisk;
- `aria-expanded` — informuje, czy panel jest rozwinięty;
- `aria-label` — opisuje działanie przycisku.

W zależności od stanu panelu etykieta przycisku przyjmuje wartość „Zwiń panel nawigacyjny” albo „Rozwiń panel nawigacyjny”.

Atrybuty są aktualizowane przez JavaScript zarówno podczas inicjalizacji, jak i po kliknięciu.

Dodatkowo:

- link do pulpitu ma `aria-label="Pulpit"`;
- dekoracyjna ikona SVG ma `aria-hidden="true"`;
- przycisk ma obszar 48 × 48 px;
- najechanie kursorem powoduje zmianę koloru tła;
- `:focus-visible` wyświetla obramowanie przy obsłudze klawiaturą.

W ostatniej sprawdzonej wersji CSS kolor obramowania wynosi `#ffffff`.

## Sprawdzenie działania

Przetestowaliśmy działanie panelu w przeglądarce.

Potwierdziliśmy:

1. Poprawne zwijanie i rozwijanie paska.
2. Ukrywanie etykiet menu przy zachowaniu widoczności ikon.
3. Zapisywanie stanu w `localStorage`.
4. Przywracanie zapamiętanego stanu po odświeżeniu strony.
5. Brak widocznego migania paska podczas ładowania.
6. Aktualizowanie atrybutów `aria`.
7. Brak wcześniejszego ostrzeżenia Vue dotyczącego elementu `#app`.

Przeanalizowaliśmy także zmiany w plikach:

- `resources/css/admin.css`;
- `resources/js/app.js`;
- `resources/views/components/admin-layout.blade.php`;
- `public/js/admin-sidebar-init.js`.

## Obecne ograniczenia i dalszy rozwój

Podstawowy układ panelu administratora oraz mechanizm zapamiętywania stanu paska są gotowe.

Na tym etapie nie zaimplementowaliśmy jeszcze:

- responsywnego zachowania paska dla urządzeń mobilnych;
- wielopoziomowego menu nawigacyjnego;
- zarządzania użytkownikami;
- ochrony panelu za pomocą logowania i uprawnień administratora;
- synchronizacji ustawień pomiędzy różnymi urządzeniami.

W przyszłości rozważymy przeniesienie bardziej zaawansowanych interakcji panelu do osobnego modułu JavaScript lub komponentów Vue.

Obecne rozwiązanie pozostaje proste, czytelne i wystarczające do dalszej rozbudowy Sportowiady.