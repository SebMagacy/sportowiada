# Układ panelu administratora

Opis budowy układu panelu przed dodaniem logowania i autoryzacji. Ochronę trasy dodajemy w [[09-kontrola-dostępu-do-panelu-administratora]]. Sekcje 1–11 opisują pierwotny etap układu i jego sprawdzenia. Sekcja 12 opisuje późniejszą zmianę wyglądu przycisku wylogowania.

## 1. Cel i trasa

### 1.1. Cel układu

Przygotowaliśmy podstawowy układ panelu administratora aplikacji Sportowiada. Filament stanowi inspirację wizualną, ale nie jest instalowany ani wykorzystywany w projekcie.

### 1.2. Trasa pulpitu

Plik: `routes/web.php`

Zdefiniowaliśmy trasę:

```php
Route::view('/admin', 'admin.dashboard')->name('admin.dashboard');
```

Trasa wyświetla widok `resources/views/admin/dashboard.blade.php`.

Na etapie opisanym w tym rozdziale dostęp do panelu administratora nie jest jeszcze chroniony mechanizmem uwierzytelniania ani uprawnieniami administratora.

## 2. Komponent układu Blade

### 2.1. Struktura komponentu

Plik: `resources/views/components/admin-layout.blade.php`

Utworzyliśmy komponent układu.

Zawiera on podstawową strukturę HTML panelu administratora:

- boczny pasek nawigacyjny (`<aside>`);
- przycisk zwijania i rozwijania paska;
- menu nawigacyjne (`<nav>`);
- główny obszar treści (`<main>`).

### 2.2. Treść pulpitu i slot

Plik: `resources/views/admin/dashboard.blade.php`

Widok pulpitu wykorzystuje komponent w następujący sposób:

```html
<x-admin-layout>
    <h1>Panel administratora</h1>
</x-admin-layout>
```

Zawartość umieszczona wewnątrz komponentu jest przekazywana przez zmienną Blade `{{ $slot }}` do elementu `<main>`.

### 2.3. Nazwany odnośnik

Plik: `resources/views/components/admin-layout.blade.php`

Link „Pulpit” wykorzystuje nazwaną trasę:

```html
{{ route('admin.dashboard') }}
```

Dzięki temu nie musimy wpisywać adresu `/admin` bezpośrednio w znaczniku odnośnika.

## 3. Style i Vite

### 3.1. Arkusz panelu

Plik: `resources/css/admin.css`

Utworzyliśmy osobny arkusz panelu.

Importuje on `app.css`, który ładuje Tailwind CSS i wspólne ustawienia stylów. Oddzielny plik wejściowy panelu nie oznacza pełnej izolacji: zmiany wspólnego arkusza mogą wpłynąć na obie części aplikacji.

### 3.2. Wejście Vite i ładowanie zasobów

Plik: `vite.config.js`

W konfiguracji dodaliśmy `admin.css` do listy plików wejściowych.

Plik: `resources/views/components/admin-layout.blade.php`

Komponent Blade ładuje zasoby za pomocą dyrektywy:

```html
@vite(['resources/css/admin.css', 'resources/js/app.js'])
```

### 3.3. Podział odpowiedzialności stylów

Przyjęliśmy połączenie Tailwind CSS i własnych klas CSS.

Przykłady wykorzystania Tailwind:

- `<body class="flex min-h-screen">` — poziomy układ strony i minimalna wysokość odpowiadająca wysokości okna;
- `<main class="flex-1 p-8">` — elastyczne wykorzystanie pozostałej przestrzeni oraz odstęp wewnętrzny wynikający ze skali Tailwind.

W Tailwind 4 `p-8` odpowiada `calc(var(--spacing) * 8)`. Przy domyślnym `--spacing: 0.25rem` daje to `2rem`, czyli 32 px dopiero przy bazowym rozmiarze 16 px. Nie jest to bezwarunkowo stała wartość 32 px. [Dokumentacja odstępów Tailwind](https://tailwindcss.com/docs/padding).

Własny CSS odpowiada za wygląd paska bocznego, nawigacji, przycisku oraz stanu zwinięcia.

Zachowaliśmy klasę `admin-sidebar-toggle`, ponieważ osobna definicja CSS jest w tym przypadku czytelniejsza niż rozbudowany zestaw klas narzędziowych Tailwind.

## 4. Zwijanie panelu bocznego

### 4.1. Stany szerokości panelu

Plik: `resources/css/admin.css`

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

### 4.2. Etykiety i ikony

Plik: `resources/views/components/admin-layout.blade.php`

Link „Pulpit” zawiera ikonę domku w formacie SVG i tekst:

```html
<span class="admin-nav-label">Pulpit</span>
```

Po zwinięciu panelu etykieta zostaje ukryta, natomiast ikona pozostaje widoczna.

Własność `flex-shrink: 0` zapobiega zmniejszaniu ikony w układzie Flexbox.

Przycisk ☰ znajduje się wewnątrz panelu, dzięki czemu pozostaje dostępny również po jego zwinięciu.

## 5. Obsługa JavaScript

### 5.1. Odnalezienie elementów

Plik: `resources/js/app.js`

W tym pliku umieściliśmy obsługę przycisku.

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

### 5.2. Przełączanie klasy

Plik: `resources/js/app.js`

Zmiana stanu paska odbywa się przez:

```javascript
const isCollapsed = document.documentElement.classList.toggle('sidebar-collapsed');
```

Metoda `classList.toggle()` dodaje klasę, gdy jej nie ma, albo usuwa ją, gdy już istnieje.

Wartość zwracana przez tę metodę jest typu `boolean`:

- `true` — pasek jest zwinięty;
- `false` — pasek jest rozwinięty.

Nie korzystamy już z wcześniejszej klasy `is-collapsed` przypisywanej bezpośrednio do panelu.

## 6. Zapamiętywanie ustawienia — localStorage

### 6.1. Zapis ustawienia

Plik: `resources/js/app.js`

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

### 6.2. Odczyt i usunięcie ustawienia

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

Pochodzenie obejmuje schemat, host i port. Dlatego ustawienie dla HTTP nie jest automatycznie tym samym ustawieniem dla HTTPS. Obecny kod zakłada dostępność `localStorage`; nie obsługuje wyjątków przy zablokowanym magazynie. Opisane wyniki dotyczą przeglądarki użytej do sprawdzenia.

## 7. Wczesna inicjalizacja paska

### 7.1. Przyczyna migania

Podczas pierwszych testów zauważyliśmy, że pasek po odświeżeniu strony na moment pojawiał się rozwinięty, a następnie przechodził do zapamiętanego stanu.

Problem wynikał z kolejności wykonywania JavaScript i renderowania strony.

### 7.2. Skrypt inicjalizacyjny

Plik: `public/js/admin-sidebar-init.js`

Aby ustawić stan przed wyświetleniem treści, utworzyliśmy osobny skrypt.

Zawiera on kod:

```javascript
if (localStorage.getItem('admin-sidebar-collapsed') === 'true') {
    document.documentElement.classList.add('sidebar-collapsed');
}
```

Skrypt odczytuje zapisane ustawienie i, jeśli pasek powinien być zwinięty, dodaje odpowiednią klasę do elementu `<html>`.

### 7.3. Ładowanie przed treścią strony

Plik: `resources/views/components/admin-layout.blade.php`

W sekcji `<head>` dodaliśmy:

```html
<script src="{{ asset('js/admin-sidebar-init.js') }}"></script>
@vite(['resources/css/admin.css', 'resources/js/app.js'])
```

Funkcja Laravel `asset()` generuje adres URL pliku znajdującego się w katalogu `public`.

Skrypt nie korzysta z atrybutów `async` ani `defer`. Dzięki temu wykonuje się podczas przetwarzania sekcji `<head>`, zanim przeglądarka rozpocznie wyświetlanie zawartości `<body>`.

Pozwala to ustalić początkowy wygląd paska bez widocznego migania.

Plik inicjalizacyjny nie jest przetwarzany przez Vite, ponieważ jest ładowany bezpośrednio z katalogu `public`.

## 8. Vue a panel administratora

### 8.1. Montowanie tylko przy obecności elementu docelowego

Plik: `resources/js/app.js`

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

## 9. Dostępność i interakcje

### 9.1. Opisy i stan elementów

Pliki: `resources/views/components/admin-layout.blade.php`, `resources/js/app.js`

Podczas tworzenia panelu uwzględniliśmy podstawowe zasady dostępności.

Przycisk zawiera atrybuty:

- `aria-controls="admin-sidebar"` — wskazuje element kontrolowany przez przycisk;
- `aria-expanded` — opisuje rozwinięty lub zwinięty stan nawigacji; `true` oznacza rozwinięcie, a `false` zwinięcie;
- `aria-label` — opisuje działanie przycisku.

W zależności od stanu panelu etykieta przycisku przyjmuje wartość „Zwiń panel nawigacyjny” albo „Rozwiń panel nawigacyjny”.

Atrybuty są aktualizowane przez JavaScript zarówno podczas inicjalizacji, jak i po kliknięciu.

W tym układzie zwinięcie ukrywa etykiety i zmniejsza szerokość paska, ale nie usuwa samych odnośników. Używamy `String(!isCollapsed)`, ponieważ stan rozwinięcia jest odwrotnością stanu zwinięcia.

### 9.2. Klawiatura i wygląd przycisku

Pliki: `resources/views/components/admin-layout.blade.php`, `resources/css/admin.css`

Dodatkowo:

- link do pulpitu ma `aria-label="Pulpit"`;
- dekoracyjna ikona SVG ma `aria-hidden="true"`;
- przycisk ma obszar 48 × 48 px;
- najechanie kursorem powoduje zmianę koloru tła;
- `:focus-visible` wyświetla obramowanie przy obsłudze klawiaturą.

W ostatniej sprawdzonej wersji CSS kolor obramowania wynosi `#ffffff`.

## 10. Sprawdzenie działania

### 10.1. Ręczne sprawdzenie w przeglądarce

Przetestowaliśmy działanie panelu w przeglądarce.

Potwierdziliśmy:

1. Poprawne zwijanie i rozwijanie paska.
2. Ukrywanie etykiet menu przy zachowaniu widoczności ikon.
3. Zapisywanie stanu w `localStorage`.
4. Przywracanie zapamiętanego stanu po odświeżeniu strony.
5. Brak widocznego migania paska podczas ładowania.
6. Aktualizowanie atrybutów `aria`.
7. Brak wcześniejszego ostrzeżenia Vue dotyczącego elementu `#app`.

### 10.2. Przegląd plików

Przeanalizowaliśmy także zmiany w plikach:

- `resources/css/admin.css`;
- `resources/js/app.js`;
- `resources/views/components/admin-layout.blade.php`;
- `public/js/admin-sidebar-init.js`.

## 11. Obecne ograniczenia i dalszy rozwój

### 11.1. Wynik i dalsze tematy

Podstawowy układ panelu administratora oraz mechanizm zapamiętywania stanu paska są gotowe.

Na zakończenie etapu układu nie zaimplementowaliśmy jeszcze:

- responsywnego zachowania paska dla urządzeń mobilnych;
- wielopoziomowego menu nawigacyjnego;
- zarządzania użytkownikami;
- ochrony panelu za pomocą logowania i uprawnień administratora;
- synchronizacji ustawień pomiędzy różnymi urządzeniami.

W przyszłości rozważymy przeniesienie bardziej zaawansowanych interakcji panelu do osobnego modułu JavaScript lub komponentów Vue.

Ochronę panelu wdrożono w późniejszym etapie opisanym w [[09-kontrola-dostępu-do-panelu-administratora]]. Pozostałe propozycje nie są potwierdzeniem ich wykonania.

## 12. Przycisk wylogowania na dole lewego paska

### 12.1. Położenie formularza

Po wdrożeniu mechanizmu opisanego w [[12-wylogowanie-uzytkownikow]] dostosowaliśmy położenie i wygląd istniejącego przycisku.

W `resources/css/admin.css` do reguły `.admin-sidebar` dodano:

```css
display: flex;
flex-direction: column;
```

Elementy paska są ułożone pionowo. Formularz wylogowania pozostaje po menu, wewnątrz `<aside>`. Dodano dla niego regułę:

```css
.admin-sidebar form {
    margin-top: auto;
    margin-bottom: 30px;
}
```

`margin-top: auto` wykorzystuje wolną przestrzeń nad formularzem i przesuwa go na dół paska. `margin-bottom: 30px` podnosi przycisk o dodatkowe 30 px; ten odstęp występuje oprócz istniejącego dolnego paddingu paska.

### 12.2. Ikona, etykieta i dostępność

W `resources/views/components/admin-layout.blade.php` zachowano formularz POST, nazwaną trasę `logout` oraz `@csrf`. Zmieniono jego przycisk na:

```blade
<button type="submit" aria-label="Wyloguj się"
    class="flex w-full cursor-pointer items-center gap-3 rounded-lg p-3 hover:bg-gray-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="1.5" aria-hidden="true" class="shrink-0">
        <path d="M9 5H5v14h4M9 12h12m-5-5 5 5-5 5" />
    </svg>
    <span class="admin-nav-label">Wyloguj się</span>
</button>
```

Ikona SVG przedstawia wyjście ze strzałką, a `shrink-0` zapobiega jej zmniejszaniu. Klasy `flex`, `items-center` i `gap-3` ustawiają ikonę i tekst obok siebie. Przycisk zajmuje całą dostępną szerokość formularza, ma odstęp wewnętrzny, zaokrąglenie i zmianę tła po najechaniu kursorem.

W rozwiniętym pasku widoczne są ikona i tekst „Wyloguj się”. Istniejąca reguła dla `.admin-nav-label` ukrywa tekst po zwinięciu, pozostawiając ikonę. Nie zmieniano mechanizmu zwijania ani zapamiętywania stanu.

`aria-label="Wyloguj się"` zachowuje dostępną nazwę przycisku także przy ukrytym tekście. Dekoracyjne SVG ma `aria-hidden="true"`. Klasy `focus-visible:*` dodają białą obwódkę przy obsłudze klawiaturą.

### 12.3. Ręczne sprawdzenie

Użytkownik wykonał zmiany i potwierdził działanie w przeglądarce:

| Sprawdzenie | Potwierdzony wynik |
| --- | --- |
| Położenie przycisku | Przycisk znajduje się na dole lewego paska, podniesiony o dodatkowe 30 px. |
| Pasek rozwinięty i zwinięty | Widoczne są odpowiednio ikona z tekstem oraz sama ikona. |
| Odświeżenie przy zwiniętym pasku | Pasek pozostaje zwinięty, a przycisk pokazuje samą ikonę. |
| Przejście klawiszem Tab | Przycisk otrzymuje widoczną obwódkę. |
| Aktywacja klawiszem Enter | Następuje wylogowanie i przekierowanie na stronę główną. |
| Ponowne wejście do panelu | Wyświetla się formularz logowania. |

Są to wyniki ręcznego sprawdzenia przez użytkownika. W ramach tej poprawki wyglądu nie dodawano ani nie uruchamiano testów automatycznych; nie wykonywano również osobnego sprawdzenia czytnikiem ekranu.
