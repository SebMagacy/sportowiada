# Czyszczenie wyszukiwania użytkowników — przycisk X

## 1. Zgłoszenie i status

Etap 22 realizuje [issue #8 — Dodać przycisk wyczyszczenia wyszukiwania użytkowników](https://github.com/SebMagacy/sportowiada/issues/8).

**Status:** zaimplementowano przycisk X, komponent pola, moduł JavaScript i osobne zasoby panelu. Użytkownik potwierdził opisane niżej sprawdzenia ręczne. Na wyraźne zlecenie użytkownika asystent dodał i uruchomił testy automatyczne. Problem układu mobilnego przy rozwiniętym sidebarze pozostaje otwarty i powiązany z #12. Nie potwierdzono publikacji zmian ani zamknięcia #8. Sekcje 2–7 opisują punkt wyjścia i uzgodniony zakres, a sekcja 8 wykonane kroki oraz ich wyniki.

## 2. Punkt wyjścia

Lista użytkowników ma wyszukiwanie GET, sortowanie, filtr typu konta, paginację i rozwijany panel opisany w [[21-panel-filtrow-uzytkownikow]]. „Wyczyść filtry” przywraca wszystkie typy kont, ale zachowuje wyszukiwanie; przycisk X ma czyścić wyłącznie frazę.

Pole wyszukiwania znajduje się w `resources/views/admin/users/index.blade.php`. Kontroler przekazuje `$search`, `$accountType`, `$sort`, `$direction` i `$activeFiltersCount`. Panel typu konta jest w `resources/views/components/user-filters.blade.php`, a jego obsługa w `resources/js/user-filters.js`, importowanym przez `app.js`.

Przed przygotowaniem rozdziału Git był czysty: `main` zgodny z lokalnym `origin/main`, ostatni commit `423096a` zapisuje nowe reguły list. Wcześniejsze commity: `428d30c` — zapis publikacji, `818fa70` — dokumentacja commita panelu, `7d828dc` — panel filtrów. Nie zmieniano gałęzi ani nie tworzono worktree.

Wyniki poprzedniego etapu: 115 testów PHP / 571 asercji, 8 przypadków JavaScript, poprawne Pint, PHPStan i build. To wyniki historyczne, nie sprawdzenie nowej funkcji. Ponowne testy PHP wymagały osobnego `VIEW_COMPILED_PATH` w katalogu tymczasowym z powodu uprawnień istniejącego cache Blade. PHPStan działał z `--debug`, gdy środowisko blokowało otwarcie portu procesów równoległych.

## 3. Zakres issue #8

- X po prawej stronie wewnątrz pola wyszukiwania.
- Widoczność przy niepustej frazie.
- Usunięcie frazy z pola i adresu oraz lista bez wyszukiwania od pierwszej strony.
- Zachowanie zastosowanego typu konta i sortowania; reset numeru strony.
- Działanie przy zamkniętym panelu filtrów.
- Obsługa klawiaturą, widoczny fokus, dostępna etykieta „Wyczyść wyszukiwanie” i dekoracyjny X ukryty przed czytnikiem ekranu.
- Tłumaczenia PL/EN i testy.
- Ikona nie zasłania wpisanej frazy.

Przed implementacją ustalić na podstawie obecnego kodu zachowanie przy wpisanej, ale jeszcze niewysłanej frazie oraz relację własnego X do natywnego przycisku czyszczenia `input type="search"`. Nie zakładać, że samo wyczyszczenie wartości pola usuwa zastosowane wyszukiwanie z wyników i URL. Mechanizm nie został jeszcze wybrany.

## 4. Nowe zasady komponentów

Przed pracą przeczytać `AGENTS.md`, `.ai/rules/index.md` i wszystkie pasujące reguły, szczególnie `views.md`, `js.md`, `controllers.md`, `praca-z-issue.md` oraz `dokumentowanie-krokow.md`. Przeszukać `.ai/rules` pod kątem zadania.

Nowe i przebudowywane elementy list budować z małych komponentów Blade ze slotami, jawnymi props i attribute bag. Korzystać z istniejących komponentów. Wspólna oprawa nie powinna znać typów konkretnych list; zapytania i reguły parametrów pozostają poza Blade. Interakcje powinny pozwalać na wiele niezależnych instancji. W razie prac przy JavaScript uwzględnić oddzielenie panelu od publicznego Vue zgodnie z zakresem zadania, bez nieuzgodnionej przebudowy całej aplikacji.

Osobne [issue #13](https://github.com/SebMagacy/sportowiada/issues/13) obejmuje uporządkowanie istniejącego widoku listy zgodnie z tymi zasadami. Etap 22 nie realizuje automatycznie całej refaktoryzacji z #13. Nie instalujemy zależności ani Filamenta.

## 5. Ograniczenia i powiązania

- #11 pozostaje otwarte ze względu na kryterium mobilne.
- [#12](https://github.com/SebMagacy/sportowiada/issues/12): przy 375 px sidebar mocno ogranicza treść, wyszukiwarka jest wąska i przewija się cały dokument. Nie uznawać tego za naprawione; X nie może pogarszać układu.
- #4 dotyczy przewijania tabeli i dostępu do kolumn.

Integracja GitHub odczytuje zgłoszenia, ale zapis zwracał 403. Zgłoszenia #12 i #13 utworzono, a #8 doprecyzowano przez zalogowane Chrome. Dostępność przeglądarki i sesji należy sprawdzić ponownie, bez zakładania logowania.

## 6. Plan sprawdzeń

- Pusta i niepusta fraza, także wpisana przed wysłaniem formularza.
- Czyszczenie zastosowanego wyszukiwania z zachowaniem typu konta i sortowania, z drugiej strony na pierwszą.
- Prawidłowy licznik typu konta po wyczyszczeniu frazy.
- Zamknięty panel, Enter, klawiatura i fokus X.
- PL/EN, kodowanie znaków w frazie i URL.
- Układ pola i przycisku na dużym i wąskim ekranie.
- Dopasowanie testów `UserSearchTest`, `UserFilterPanelTest` i ewentualnych testów modułu do wybranego mechanizmu. Przed dodawaniem testów zwięźle przedstawić zakres i używać zestawów danych.

## 7. Sposób pracy i pierwszy krok

Po polsku, po jednym małym kroku: cel, mechanizm, konkretna zmiana i kryterium sukcesu, potem czekamy na wynik. Użytkownik wykonuje edycje aplikacji, polecenia i Git, chyba że wyraźnie zleci konkretną pracę. Zlecenie samodzielnego napisania testów etapu 21 nie oznacza stałej zgody na implementację nowego etapu.

Asystent aktualizuje istniejący rozdział po potwierdzonych krokach, dodając istotny kod i jego wyjaśnienie, bez osobnej prośby. Oddziela plany, wykonane zmiany i wyniki. Zachować przygotowane, niezacommitowane pliki dokumentacji.

Nowy czat ma najpierw odczytać aktualne issue, reguły i kod, sprawdzić Git oraz wersje, użyć właściwych skilli Laravel/Tailwind/testowania i dokumentacji API przez Boost. Następnie zaproponować pierwszy mały krok. Nie wdrażać przycisku przed tym krokiem i wynikiem użytkownika.

## 8. Potwierdzone kroki

### Tłumaczenia etykiety X

Użytkownik dodał do `lang/pl/users.php` i `lang/en/users.php` klucz `clear_search`:

```php
// PL
'clear_search' => 'Wyczyść wyszukiwanie',
// EN
'clear_search' => 'Clear search',
```

Etykieta będzie używana przez kontrolkę czyszczenia, aby jej działanie było zrozumiałe dla czytnika ekranu. Asystent potwierdził obecność obu wpisów przez odczyt plików i diff. Nie uruchamiano testów; sama kontrolka jeszcze nie istnieje.

### Uzgodniony mechanizm — do implementacji

Użytkownik zaakceptował własny X sterowany małym modułem JavaScript, widoczny również dla wpisanej, niewysłanej frazy. Aktywacja ma czyścić pole i przechodzić pod adres bez `search` oraz `page`, zachowując zastosowany typ konta i sortowanie. Niewysłana zmiana typu konta nie będzie stosowana. Zachowujemy `type="search"`, ukrywając natywny X przeglądarki. Pole wydzielimy do małego komponentu Blade. Są to uzgodnienia, a nie wykonana funkcjonalność.

### Wydzielenie pola wyszukiwania

Użytkownik utworzył `resources/views/components/search-input.blade.php` i zastąpił nim dotychczasowy kontener pola w `resources/views/admin/users/index.blade.php`:

```blade
<x-search-input id="users-search" name="search" :value="$search" :label="__('users.search_label')"
    :placeholder="__('users.search_placeholder')" class="flex-1 sm:max-w-xs" />
```

Komponent deklaruje jawne dane przez `@props(['id', 'name', 'value' => '', 'label', 'placeholder' => ''])`. `$attributes->class(['relative min-w-0'])` łączy klasy oprawy z klasami przekazanymi przez widok. Etykieta znajduje się teraz wewnątrz komponentu i wiąże się z polem przez przekazany `id`; poprzednią etykietę usunięto. Zachowano ikonę lupy, typ pola, nazwę parametru, wartość i klasy wyglądu.

Asystent sprawdził plik komponentu i diff widoku: zmiana odpowiada zaplanowanemu wydzieleniu. Użytkownik potwierdził ręcznie, że po odświeżeniu pole wygląda jak wcześniej, a wpisanie frazy i Enter filtruje listę. Nie uruchamiano testów automatycznych. Komponent nie ma jeszcze własnego X ani obsługi JavaScript.

### Adres czyszczenia wyszukiwania

W `app/Http/Controllers/Admin/UserController.php` użytkownik dodał do danych widoku:

```php
'clearSearchUrl' => route('admin.users.index', [
    'account_type' => $accountType,
    'sort' => $sort,
    'direction' => $direction,
]),
```

Adres powstaje ze znormalizowanych, zastosowanych parametrów. Pomija `search` i `page`, zachowując typ konta i sortowanie. Nie zależy od niewysłanych zmian formularza. Użytkownik potwierdził sukces kroku (dodanie wpisu i poprawne otwieranie listy); asystent potwierdził kod przez odczyt kontrolera. Nie sprawdzono jeszcze czyszczenia przez X, ponieważ kontrolka nie została dodana.

### Dodanie własnego X — wygląd

W komponencie `search-input.blade.php` użytkownik dodał wymagane props `clearUrl` i `clearLabel`, a w widoku listy przekazał `:clear-url="$clearSearchUrl"` oraz `:clear-label="__('users.clear_search')"`. Pole ma teraz `pr-10`, pozostawiające miejsce na przycisk.

Przycisk ma `type="button"`, `data-clear-search`, `data-clear-url="{{ $clearUrl }}"` i `aria-label="{{ $clearLabel }}"`. Dla pustej wartości Blade dodaje atrybut `hidden`. SVG krzyżyka jest dekoracyjne (`aria-hidden="true"`), a klasy `focus-visible` zapewniają styl fokusu klawiatury.

Użytkownik potwierdził sukces sprawdzenia wyglądu po wyszukaniu frazy. Asystent potwierdził zgodność kodu komponentu i wywołania w widoku. Nie potwierdzono jeszcze obsługi klawiaturą ani działania czyszczenia: przycisk nie ma obsługi JavaScript, a widoczność nie reaguje jeszcze na wpisywanie. Ukrycie natywnego X pozostaje kolejnym krokiem.

### Ukrycie natywnego X

Użytkownik dodał do klas pola w `search-input.blade.php` wariant Tailwind `[&::-webkit-search-cancel-button]:hidden`, ukrywający natywną kontrolkę czyszczenia w przeglądarkach obsługujących ten pseudoelement. Pozostawiono `type="search"`. Asystent potwierdził wpis w kodzie; użytkownik zaakceptował krok i poprosił o kontynuację, bez osobnego szczegółowego raportu z przeglądarki. Obsługa własnego X nadal oczekuje na JavaScript.

### Przygotowanie modułu JavaScript

Użytkownik dodał `data-search-input` do kontenera komponentu i utworzył `resources/js/search-input.js`, eksportujący `initializeSearchInputs(root = document)`. Asystent potwierdził obie zmiany przez odczyt kodu.

Funkcja wyszukuje wszystkie kontenery `[data-search-input]`, a w każdym lokalnie znajduje pole i przycisk. Pomija niekompletne kontenery. Ustawia `clearButton.hidden = input.value === ''` przy inicjalizacji oraz po zdarzeniu `input`. Obsługa `click` zeruje wartość, aktualizuje widoczność i wykonuje `window.location.assign(clearButton.dataset.clearUrl)`.

Moduł obsługuje wiele niezależnych pól i strony bez komponentu, bez stałych globalnych identyfikatorów. Użytkownik potwierdził zapisanie pliku. Funkcja nie jest jeszcze wywoływana przez plik wejściowy, więc jej działania w przeglądarce nie sprawdzono. Nie uruchamiano testów.

### Podłączenie zasobów panelu i pierwsze sprawdzenie działania

Użytkownik utworzył `resources/js/admin.js`:

```js
import './user-filters.js';
import { initializeSearchInputs } from './search-input.js';

initializeSearchInputs();
```

Do tego pliku przeniósł dotychczasową obsługę zwijania nawigacji z `app.js`. Publiczny `app.js` zachowuje importy Vue i montowanie aplikacji, a panel korzysta z osobnego wejścia bez importowania publicznego interfejsu Vue.

W `vite.config.js` dodano `resources/js/admin.js` do tablicy `input`. W `resources/views/components/admin-layout.blade.php` zasoby są ładowane przez `@vite(['resources/css/admin.css', 'resources/js/admin.js'])`. Asystent potwierdził zgodność tych plików przez odczyt kodu.

Użytkownik potwierdził sukces `npm run build` oraz sprawdzeń: panel filtrów i zwijanie nawigacji działają, X pojawia się podczas wpisywania, a kliknięcie usuwa wyszukiwanie z pola i adresu. To wynik zgłoszony przez użytkownika, bez osobnego uruchomienia buildu przez asystenta. Zachowanie typu konta, sortowania i reset strony w połączonym scenariuszu, klawiatura oraz testy automatyczne pozostają do sprawdzenia.

### Czyszczenie z zachowaniem typu konta i sortowania

Użytkownik potwierdził działanie przedstawionego scenariusza: wyszukiwanie z zastosowanym typem konta „Użytkownicy”, sortowaniem po imieniu malejąco i przejściem na drugą stronę, następnie kliknięcie X przy zamkniętym panelu filtrów. Wynik: puste pole, brak `search` i `page` w adresie, zachowany typ konta i sortowanie oraz licznik filtrów `(1)`. Jest to potwierdzenie ręczne użytkownika; asystent nie wykonywał tego scenariusza w przeglądarce. Klawiatura i testy automatyczne pozostają do sprawdzenia.

### Aktywacja klawiaturą i niewysłana fraza

Użytkownik potwierdził działanie Entera i spacji w przedstawionym scenariuszu: wpisanie frazy bez wysyłania formularza, przejście Tabem na X i aktywacja przycisku. Załączony zrzut pokazuje pole z niebieskim obramowaniem fokusu i rozwiniętymi podpowiedziami historii przeglądarki. Nie pokazuje jednoznacznie fokusu samego przycisku X, więc jego widocznego obramowania nie zapisujemy jeszcze jako osobno potwierdzonego. Testy automatyczne pozostają do wykonania.

Na kolejnym zrzucie przekazanym przez użytkownika asystent potwierdził widoczne niebieskie obramowanie wokół samego przycisku X. Tym samym potwierdzono wizualnie fokus kontrolki po przejściu klawiaturą.

### Pomijanie niewysłanej zmiany typu konta

Użytkownik potwierdził ręcznie scenariusz: zastosowany typ konta „Użytkownicy” i wyszukiwanie, następnie wybór „Administratorzy” w panelu bez kliknięcia „Zastosuj”, a potem aktywacja X. Fraza została usunięta, a zastosowany typ konta pozostał „Użytkownicy”. Przycisk korzysta z adresu przygotowanego przez kontroler i nie stosuje niewysłanej zmiany formularza.

### Widoczność podczas edycji pola

Użytkownik potwierdził ręcznie, bez wysyłania formularza: X pojawia się po wpisaniu tekstu, znika po zaznaczeniu całej frazy i usunięciu jej Backspace’em, a następnie wraca po wpisaniu jednego znaku. Potwierdza to aktualizowanie widoczności kontrolki w reakcji na zdarzenie `input`.

### Weryfikacja asystenta przy szerokości 375 px — problem układu

Na wyraźne zlecenie użytkownika asystent sprawdził istniejącą, zalogowaną sesję Chrome przy viewport 375 × 812 i wpisał długą frazę z polskimi znakami bez wysyłania formularza. Test układu nie przeszedł: kontener `[data-search-input]` miał szerokość 0 px, a pole 82 px przy paddingach po 40 px. Przycisk X znajdował się poza polem, po jego lewej stronie. Zmierzona szerokość dokumentu wynosiła 589 px przy viewport 375 px. Obserwacja nie oznacza naprawy znanego problemu #12 ani dowodu, że całość przewijania wprowadził ten etap.

Kliknięcie X zadziałało: pole zostało wyczyszczone, a adres zawierał tylko `account_type=all`, bez `search` i `page`. Po teście przywrócono domyślny viewport i pozostawiono listę bez wyszukiwania. Asystent nie zmieniał kodu aplikacji ani nie uruchamiał testów automatycznych. Dalszy krok wymaga poprawienia układu samego pola, aby nowy X mieścił się w nim także na wąskim ekranie.

### Układ pionowy paska i ponowny test — nadal problem przy rozwiniętym sidebarze

Użytkownik zastosował pionowy układ paska na małym ekranie: `flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between`, przycisk filtrów z `self-start sm:self-auto` i pole z `w-full sm:flex-1 sm:max-w-xs`. Zgłoszony wynik `npm run build` jest poprawny (Vite 8.3.5, 462 ms). Build zawiera ostrzeżenie o opcjonalnym pakiecie `fontaine`; nie instalowano zależności.

Asystent ponowił test w Chrome po przeładowaniu strony przy 375 × 812. Pole znalazło się pod przyciskiem filtrów, ale test układu nadal nie przeszedł. Przy rozwiniętej nawigacji (240 px) obszar `main` miał 120 px, a kontener pola tylko 22 px. Sam input miał 82 px, ponieważ jego paddingi i obramowanie przekraczały dostępną szerokość. X nadal nie mieścił się prawidłowo w polu. Kliknięcie nadal czyściło wyszukiwanie. Przywrócono domyślny viewport i puste pole. Sama zmiana paska jest niewystarczająca; problem pozostaje powiązany z otwartym issue #12. Nie oznaczono mobilnego kryterium jako spełnionego.

### Doprecyzowanie: zwinięta i rozwinięta nawigacja

Użytkownik potwierdził, że problem znika przy zwiniętym lewym pasku i występuje wyłącznie przy rozwiniętym. Na dostarczonym zrzucie asystent potwierdził prawidłowe położenie X wewnątrz pola, z tekstem po jego lewej stronie i pionowym układem paska narzędzi. Nie ustalono dokładnej szerokości viewportu tego zrzutu. Potwierdzenie dotyczy wyglądu przy zwiniętej nawigacji; nie oznacza naprawienia rozwiniętego wariantu ani zamknięcia #12.

### Testy automatyczne czyszczenia wyszukiwania

Na wyraźne zlecenie użytkownika asystent dodał dwa nowe pliki testowe i dostosował istniejący test nawigacji. Nie dodano zależności.

`tests/Feature/Http/Controllers/Admin/UserClearSearchTest.php` powstał przez `php artisan make:test --phpunit Http/Controllers/Admin/UserClearSearchTest --no-interaction`. Zawiera cztery metody i 11 przypadków z nazwanych zestawów danych:

- cztery warianty czyszczenia: wszyscy z imieniem rosnąco, użytkownicy z imieniem malejąco, administratorzy bez jawnego sortowania, użytkownicy z e-mailem rosnąco;
- dwa języki dostępnej etykiety, dekoracyjna ikona i położenie X poza zamkniętym panelem;
- trzy początkowe frazy: pusta, same spacje oraz niepusta;
- dwa warianty znaków specjalnych: polskie znaki i znaki URL oraz próba wstrzyknięcia HTML.

Test czyszczenia pobiera faktyczny `data-clear-url` z wygenerowanego HTML. Sprawdza brak `search` i `page`, a następnie otwiera ten adres i weryfikuje pustą frazę, zastosowany typ konta, sortowanie, licznik filtrów, pierwszą stronę, całkowitą liczbę wyników i dokładną kolejność identyfikatorów. Konta spoza poprzedniej frazy muszą wrócić do wyników, a konta innego typu pozostać wykluczone dla filtra typu. Nie jest to kliknięcie w przeglądarce — obsługę kliknięcia sprawdza moduł JS oraz wcześniejsze sprawdzenia ręczne.

`tests/Frontend/search-input.test.mjs` importuje rzeczywistą funkcję `initializeSearchInputs` i używa uproszczonego DOM, zgodnie z istniejącymi testami frontendowymi. Zawiera 10 przypadków obejmujących pięć uzgodnionych grup zachowania: inicjalizację widoczności (pusta, niepusta i same spacje), wpisywanie/usuwanie/ponowne wpisanie, kliknięcie z przejściem pod przygotowany adres po wyzerowaniu pola, niezależne instancje i bezpieczne pomijanie brakujących elementów. Stan zastępczego `window` jest przywracany po teście kliknięcia. Spacje wpisane w przeglądarce pozostawiają X widocznym do czasu normalizacji frazy przez backend.

W `tests/Frontend/navigation.test.mjs` zachowano dotychczasowe cztery przypadki. Środowisko wykonuje teraz również kod `admin.js`, ponieważ tam przeniesiono obsługę sidebara. Inicjalizator wyszukiwania jest w tym teście zastąpiony pustą funkcją — jego zachowanie sprawdza nowy dedykowany plik.

Wyniki własnych uruchomień asystenta:

- nowy test PHP: **11 przypadków / 91 asercji**;
- testy kontrolerów administracyjnych: **76 przypadków / 412 asercji**;
- pełny zestaw PHP po końcowej zmianie: **126 testów / 662 asercje**, poprawny;
- nowy JavaScript: **10 przypadków**, poprawny; nawigacja: **4**, panel filtrów: **4** — razem **18**;
- `vendor/bin/pint --dirty --format agent`: poprawny; formatter poprawił też formatowanie zmodyfikowanego kontrolera;
- `vendor/bin/phpstan analyse --debug --no-progress app tests/Feature/Http/Controllers/Admin/UserClearSearchTest.php`: brak błędów;
- `git diff --check`: poprawny.

Testy PHP uruchamiano z `VIEW_COMPILED_PATH` wskazującym świeży katalog `mktemp` w `/tmp`. Pierwsza analiza PHPStan zgłosiła `modelKeys()` na kolekcji paginatora; test poprawiono na `getCollection()->pluck('id')->all()` i ponownie uruchomiono pełny zestaw oraz analizę. Przy `node --test` raport tego środowiska pokazywał liczbę plików, dlatego liczbę przypadków potwierdzono także osobnym uruchomieniem `node tests/Frontend/<plik>.test.mjs` dla każdego z trzech plików.

Testy z uproszczonym DOM nie dowodzą poprawności wymiarów pola, fokusu w przeglądarce ani mobilnego układu. Wyniki nie zamykają problemu rozwiniętej nawigacji z #12. Nie wykonano commita, pusha ani zmian w GitHub.
