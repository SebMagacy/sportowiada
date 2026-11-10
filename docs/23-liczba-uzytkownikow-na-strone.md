# Wybór liczby użytkowników na stronę

## 1. Zgłoszenie i status

Etap 23 dotyczy [issue #7 — Dodać wybór liczby użytkowników na stronę](https://github.com/SebMagacy/sportowiada/issues/7).

**Status:** implementacja i lokalna weryfikacja zakończone. Użytkownik potwierdził opisane poniżej scenariusze ręczne; na jego polecenie „Przygotuj wszystko” asystent przygotował testy i wykonał kontrole z sekcji 31. Commit, push i zamknięcie issue nie zostały jeszcze wykonane.

## 2. Zakres

Aktualny opis issue określa:

- wybór 10, 25 lub 50 wyników, domyślnie 10;
- akceptowanie przez serwer wyłącznie dozwolonych wartości;
- zachowanie wyboru przy paginacji i wyszukiwaniu;
- powrót na pierwszą stronę po zmianie limitu;
- dostępną etykietę i tłumaczenia PL/EN;
- testy liczby wyników i nieprawidłowych parametrów.

W kontekście obecnej listy wybór powinien być zachowany również przy sortowaniu, stosowaniu i czyszczeniu typu konta oraz czyszczeniu wyszukiwania przez X. Sama zmiana limitu powinna zachować zastosowaną frazę, typ konta i sortowanie.

## 3. Uzgodniony interfejs

Użytkownik wybrał kontrolkę wyśrodkowaną w stopce, według przesłanego wzoru: informacja o wynikach po lewej, etykieta „Na stronie” i select we wspólnej ramce pośrodku, paginacja po prawej. Potwierdził automatyczne odświeżanie listy po wyborze, bez dodatkowego przycisku. Dostępne wartości pozostają zgodne z issue: 10, 25 i 50.

Trzeba uwzględnić dostępność kontrolki przy pustych wynikach — obecny dolny blok widoku jest renderowany tylko wtedy, gdy bieżąca strona zawiera użytkowników.

## 4. Obecny kod i miejsca integracji

- `app/Http/Controllers/Admin/UserController.php`: `paginate((int) $perPage)` po normalizacji limitu; do rozszerzenia pozostają jawne parametry `appends`, przekierowanie spoza zakresu stron i adres `clearSearchUrl`.
- `resources/views/admin/users/index.blade.php`: formularz wyszukiwania i filtrów, konfiguracja linków sortowania oraz dolna informacja o wynikach i paginacja.
- `resources/views/components/user-filters.blade.php`: formularz typu konta i adres „Wyczyść filtry”.
- `resources/views/components/sortable-column.blade.php`: parametry przekazane do linków sortowania.
- `resources/views/components/search-input.blade.php` i `resources/js/search-input.js`: X korzysta z adresu przygotowanego przez kontroler.
- `resources/js/admin.js`: osobny plik wejściowy panelu; `resources/js/app.js` zawiera publiczne Vue. Vite i layout panelu są już rozdzielone.
- `lang/pl/users.php`, `lang/en/users.php`: tłumaczenia listy.

Parametr limitu nazywa się `per_page`; normalizacja jest opisana w sekcji 8. Struktura nowej kontrolki pozostaje do wdrożenia. Stosować małe komponenty Blade z jawnymi props i attribute bag; zapytania oraz normalizacja parametrów pozostają poza Blade. Nie realizować całej refaktoryzacji #13.

## 5. Stan poprzedniego etapu i Git

Etap 22 opisuje [[22-czyszczenie-wyszukiwania-uzytkownikow]]. Opublikowane na `main` commity:

- `4ddcec4` — czyszczenie wyszukiwania, testy i dokumentacja (`Refs #8`);
- `a8c1d0f` — dokumentacja zamknięcia #6;
- `18bd875` — pojęcia do słownika.

Potwierdzono push `423096a..18bd875`. Po nim Git był czysty, `HEAD` i lokalny `origin/main` zgodne. Przed utworzeniem tego rozdziału jedyną lokalną zmianą było późniejsze uzupełnienie `docs/22-czyszczenie-wyszukiwania-uzytkownikow.md` o publikację. Zachować je wraz z niniejszym rozdziałem i wpisem do `docs/README.md`. Nie tworzyć worktree ani nie zmieniać gałęzi.

Historyczne wyniki etapu 22: 126 testów PHP / 662 asercje, 18 przypadków JavaScript, poprawne Pint i PHPStan; build Vite 8.3.5 zgłoszony przez użytkownika poprawny. To nie są wyniki nowej funkcji. PHP wymagał `VIEW_COMPILED_PATH` wskazującego katalog `mktemp` w `/tmp`; PHPStan uruchamiano z `--debug`. Dla pełnej liczby przypadków JS uruchamiano osobno `node tests/Frontend/<plik>.test.mjs`, ponieważ raport `node --test` w tym środowisku pokazywał liczbę plików.

## 6. Odłożony układ mobilny

Użytkownik zdecydował odłożyć prace nad układem mobilnym do czasu dodania wszystkich funkcji listy. #8, #11 i #12 pozostają otwarte. Przy zwiniętym sidebarze pole wyszukiwania i X mają potwierdzony poprawny wygląd; przy rozwiniętym pasku na 375 px sidebar ogranicza treść, a pole jest zbyt wąskie. Nie uznawać tego za naprawione ani automatycznie zamykać zgłoszeń. #4 dotyczy przewijania tabeli.

## 7. Sposób pracy i plan sprawdzeń

Po polsku, jeden mały krok naraz: cel, mechanizm, konkretna zmiana i kryterium sukcesu, następnie czekamy na wynik. Użytkownik wykonuje edycje aplikacji, polecenia i Git, chyba że wyraźnie zleci daną pracę asystentowi. Zlecenia samodzielnych testów i publikacji etapu 22 nie oznaczają stałej zgody na samodzielną implementację etapu 23. Asystent uzupełnia ten istniejący rozdział po potwierdzonych krokach, z kodem i wyjaśnieniem, bez dodatkowej prośby.

Najpierw przeczytać `AGENTS.md`, `.ai/rules/index.md`, pasujące reguły i przeszukać reguły słowami związanymi z zadaniem. Użyć właściwych skilli Laravel/Tailwind/testowania, sprawdzić aktualny kod, Git, wersje i dokumentację API przez Boost.

Przed pisaniem testów przedstawić zwięzły zakres i stosować zestawy danych. Plan obejmuje domyślny limit, 10/25/50, nieprawidłowe parametry (w tym tablice), rzeczywistą liczbę wyników, reset strony, zachowanie wszystkich zastosowanych parametrów w akcjach listy, dostępność i PL/EN. Uwzględnić już istniejące testy paginacji, wyszukiwania, sortowania, filtrów i X, bez zbędnego powielania.

## 8. Wykonany krok — normalizacja i limit zapytania

Użytkownik potwierdził zapis zmian w `app/Http/Controllers/Admin/UserController.php`. Asystent sprawdził diff i potwierdził dodanie:

```php
$perPage = $request->query('per_page', '10');

if (! in_array($perPage, ['10', '25', '50'], true)) {
    $perPage = '10';
}
```

Brak parametru oznacza limit 10. Ścisłe porównanie dopuszcza wyłącznie trzy wskazane ciągi; inne wartości, także tablice, zastępuje domyślnym limitem. Dopiero po normalizacji wartość jest rzutowana na liczbę dla zapytania:

```php
$users = $query->paginate((int) $perPage)
```

To potwierdzenie kodu, bez uruchomienia testów lub ręcznego sprawdzenia działania. Zachowanie limitu w pozostałych akcjach listy i formularzach oraz kontrolka nie są jeszcze wdrożone. Diff zawiera także zmiany odstępów w istniejącym PHP; formatowanie Pint pozostaje do wykonania przed zakończeniem etapu.

## 9. Wykonany krok — limit w linkach paginacji

Po potwierdzeniu zapisu przez użytkownika asystent sprawdził kod kontrolera. Tablica `appends()` zawiera teraz:

```php
'per_page' => $perPage,
```

Paginacja otrzymuje znormalizowany limit jako parametr swoich linków, obok wyszukiwania, typu konta i sortowania. Zapis kodu został potwierdzony odczytem; działanie nie było jeszcze sprawdzane testami ani w przeglądarce.

## 10. Wykonany krok — limit w przekierowaniu spoza zakresu

Użytkownik dodał w kontrolerze do parametrów `redirect()->route()` w warunku `currentPage() > lastPage()`:

```php
'per_page' => $perPage,
```

Asystent potwierdził zapis odczytem kodu. Przekierowanie na ostatnią dostępną stronę otrzymuje teraz również znormalizowany limit, aby zachować go w docelowym adresie. Nie wykonano jeszcze sprawdzenia tego zachowania testem ani w przeglądarce. Ten mechanizm dotyczy adresu ze stroną spoza zakresu; reset do pierwszej strony po zmianie limitu pozostaje do wdrożenia w kontrolce.

## 11. Wykonany krok — limit w adresie czyszczenia wyszukiwania

Użytkownik potwierdził zapis, a asystent sprawdził w kontrolerze następującą konfigurację:

```php
'clearSearchUrl' => route('admin.users.index', [
    'account_type' => $accountType,
    'sort' => $sort,
    'direction' => $direction,
    'per_page' => $perPage,
]),
```

Adres używany przez X zawiera znormalizowany limit, typ konta i sortowanie. Pomija frazę wyszukiwania i numer strony, aby wyczyścić wyszukiwanie i wrócić na pierwszą stronę. Potwierdzono kod; nie wykonano jeszcze testu ani sprawdzenia w przeglądarce.

## 12. Wykonany krok — przekazanie limitu do widoku

Użytkownik dodał do tablicy danych `return view('admin.users.index', [...])`:

```php
'perPage' => $perPage,
```

Asystent potwierdził zapis odczytem kontrolera. Widok otrzymuje znormalizowaną wartość, przeznaczoną do wykorzystania w formularzach, linkach i zaznaczeniu aktualnej opcji nowej kontrolki. Nie dodano jeszcze jej obsługi w widoku i nie wykonano testów tego kroku.

## 13. Wykonany krok — limit w formularzu wyszukiwania i filtrów

Użytkownik dodał w `resources/views/admin/users/index.blade.php`, pod ukrytymi polami sortowania:

```blade
<input type="hidden" name="per_page" value="{{ $perPage }}">
```

Asystent potwierdził zapis odczytem widoku. Wspólny formularz wyszukiwania i typu konta otrzymał pole wysyłające aktualny limit. Brak pola `page` pozwala wrócić na pierwszą stronę po wysłaniu formularza. Nie wykonano jeszcze testów ani sprawdzenia w przeglądarce; zachowanie limitu w linkach sortowania i czyszczenia filtrów pozostaje do wdrożenia.

## 14. Wykonany krok — limit w linkach sortowania

Użytkownik rozszerzył prop `filters` komponentu `x-sortable-column` w widoku listy:

```blade
:filters="[
    'search' => $search,
    'account_type' => $accountType,
    'per_page' => $perPage,
]"
```

Asystent potwierdził zapis odczytem kodu. Istniejący komponent wykorzystuje tę tablicę do budowy linków zmiany i wyłączenia sortowania, więc otrzymuje teraz również znormalizowany limit. Nie zmieniano samego komponentu sortowania. Działanie pozostaje do sprawdzenia testami i w przeglądarce; link czyszczenia filtrów nadal wymaga rozszerzenia.

## 15. Wykonany krok — limit przy czyszczeniu filtrów

Asystent potwierdził odczytem obu widoków, że użytkownik przekazał `:per-page="$perPage"` do `x-user-filters`, dodał `perPage` do jawnych props komponentu i rozszerzył parametry linku „Wyczyść filtry” o:

```php
'per_page' => $perPage,
```

Link zachowuje wyszukiwanie, sortowanie i limit, pomijając typ konta i stronę. Nie wykonano jeszcze sprawdzenia działania w przeglądarce ani testami. Użytkownik zmienił również zapis klasy szerokości z `sm:max-w-[17.5rem]` na `sm:max-w-70` zgodnie z podpowiedzią edytora; nie oznacza to realizacji odłożonych prac nad układem mobilnym.

## 16. Wykonany krok — tłumaczenia kontrolki

Użytkownik potwierdził zapis tłumaczeń w `lang/pl/users.php` i `lang/en/users.php`; asystent sprawdził oba pliki. Dodano klucze:

```php
// lang/pl/users.php
'per_page' => 'Na stronie',
'per_page_hint' => 'Zmiana liczby wyników automatycznie odświeża listę.',

// lang/en/users.php
'per_page' => 'Per page',
'per_page_hint' => 'Changing the number of results automatically refreshes the list.',
```

Etykieta i opis automatycznego odświeżania są przygotowane do użycia w komponencie. Nie potwierdzono jeszcze ich wyświetlania w interfejsie; komponent pozostaje do utworzenia.

## 17. Wykonany krok — utworzenie komponentu Blade

Użytkownik uruchomił:

```bash
php artisan make:component per-page-select --view --no-interaction
```

Artisan potwierdził utworzenie `resources/views/components/per-page-select.blade.php`. Jest to anonimowy komponent Blade, bez osobnej klasy PHP. Na tym kroku utworzono szablon; zawartość kontrolki, osadzenie w stopce i automatyczne wysyłanie pozostają do wdrożenia.

## 18. Wykonany krok — struktura kontrolki

Użytkownik zapisał zawartość `per-page-select.blade.php`; asystent sprawdził plik. Komponent przyjmuje jawne props `id`, `action`, `value`, `options`, `label`, `hint` i `parameters` (domyślnie pusta tablica). Attribute bag umożliwia dodanie klas i atrybutów formularza.

Formularz GET z atrybutem `data-per-page-select` renderuje przekazane parametry jako ukryte pola. Etykieta i select `name="per_page"` znajdują się we wspólnej ramce. Opcje pochodzą z przekazanej konfiguracji; aktualna jest zaznaczana przez `@selected`. Powiązanie `for`/`id` zapewnia etykietę, a `aria-describedby` wskazuje opis automatycznego odświeżania ukryty wizualnie przez `sr-only`. Identyfikatory zależą od przekazanego `id`, co umożliwia użycie wielu instancji.

To potwierdzenie struktury kodu. Komponent nie został jeszcze osadzony w stopce, nie ma obsługi automatycznego wysyłania i nie był sprawdzany w przeglądarce ani testami.

## 19. Wykonany krok — kontrolka w stopce listy

Użytkownik potwierdził zapis stopki w `resources/views/admin/users/index.blade.php`. Asystent sprawdził kod. Zewnętrzny warunek `count() > 0` został zastąpiony stale renderowaną siatką trzech równych kolumn (`grid grid-cols-3`). Informacja o wynikach jest po lewej i pozostaje warunkowa, komponent ma `justify-self-center`, a paginacja znajduje się po prawej (`justify-self-end`) i nadal zależy od `hasPages()`.

Komponent otrzymuje identyfikator `users-per-page`, adres nazwanej trasy listy, aktualny limit, opcje `[10, 25, 50]`, etykietę i opis z tłumaczeń oraz parametry `search`, `account_type`, `sort`, `direction`. Brak `page` w osobnym formularzu ma zapewnić powrót na pierwszą stronę po jego wysłaniu. Kontrolka nie zależy od obecności wyników.

Potwierdzono kod, bez sprawdzenia wyglądu w przeglądarce ani testów. Automatyczne wysyłanie formularza pozostaje do dodania. Ten krok nie stanowi potwierdzenia poprawności odłożonego układu mobilnego.

## 20. Wykonany krok — moduł automatycznego wysyłania

Użytkownik utworzył `resources/js/per-page-select.js`. Asystent sprawdził zawartość:

```js
export function initializePerPageSelects(root = document) {
    root.querySelectorAll('[data-per-page-select]').forEach((form) => {
        const select = form.querySelector('select[name="per_page"]');

        if (!select) {
            return;
        }

        select.addEventListener('change', () => {
            form.requestSubmit();
        });
    });
}
```

Inicjalizacja działa względem przekazanego kontenera i obsługuje każdy znaleziony formularz osobno. Zmiana selecta wywołuje `requestSubmit()` jego formularza; brak selecta pomija daną instancję. Nie używa globalnego identyfikatora kontrolki. Moduł pozostaje do podłączenia w `admin.js`; nie wykonano testów ani sprawdzenia automatycznego odświeżania w przeglądarce.

## 21. Wykonany krok — podłączenie modułu w panelu

Użytkownik zapisał zmiany w `resources/js/admin.js`; asystent potwierdził odczytem import:

```js
import { initializePerPageSelects } from './per-page-select.js';
```

oraz wywołanie obok inicjalizacji wyszukiwania:

```js
initializePerPageSelects();
```

Moduł jest podłączony do istniejącego punktu wejścia panelu. Nie zmieniano publicznego `app.js` ani zależności. Nie potwierdzono jeszcze działania w przeglądarce, kompilacji ani testów nowej funkcji.

## 22. Potwierdzone sprawdzenie — wybór 25 wyników

Użytkownik potwierdził działanie po wyborze 25 i przesłał zrzut przeglądarki. Na zrzucie widać parametr `per_page=25`, zaznaczone 25 w kontrolce pośrodku stopki oraz informację „Wyświetlono 1–25 z 27 wyników”. Paginacja pokazuje dwie strony z aktywną pierwszą. Użytkownik potwierdził działanie automatycznego odświeżenia w ramach zadanego sprawdzenia.

To sprawdzenie ręczne użytkownika, poparte zrzutem interfejsu; nie jest wynikiem testów automatycznych. Zachowanie limitu po przejściu na drugą stronę, reset strony po zmianie limitu, pozostałe akcje listy, puste wyniki, dostępność i wersja EN pozostają do sprawdzenia.

## 23. Potwierdzone sprawdzenie — paginacja i reset strony

Użytkownik potwierdził oba scenariusze i przesłał dwa zrzuty:

- Po przejściu na drugą stronę adres zawiera `per_page=25&page=2`, kontrolka zachowuje 25, a tabela pokazuje dwa pozostałe wyniki i informację „Wyświetlono 26–27 z 27 wyników”.
- Po zmianie limitu na 10 na drugiej stronie lista wraca na pierwszą. Adres zawiera `per_page=10` bez `page`, kontrolka wskazuje 10, a stopka pokazuje „Wyświetlono 1–10 z 27 wyników” i aktywną pierwszą stronę.

Są to sprawdzenia ręczne użytkownika poparte zrzutami, bez uruchomienia testów automatycznych. Pozostałe akcje listy, limit 50, nieprawidłowe parametry, puste wyniki, dostępność i EN pozostają do sprawdzenia.

## 24. Potwierdzone sprawdzenie — limit 50, wyszukiwanie i puste wyniki

Użytkownik potwierdził poprawny wynik wszystkich czterech zleconych scenariuszy:

- Wybór 50 wyświetla wszystkie 27 wyników bez paginacji.
- Wyszukanie `example-user` zachowuje limit 50.
- Wyszukanie `zzzzbrakwynikow` daje pustą listę, ale kontrolka pozostaje widoczna z wartością 50.
- Kliknięcie X przywraca wszystkie 27 wyników, zachowując limit 50.

Wyniki pochodzą z ręcznego sprawdzenia użytkownika. Nie uruchomiono jeszcze testów automatycznych nowej funkcji. Typ konta, sortowanie, zachowanie zastosowanych parametrów przy zmianie limitu, nieprawidłowe parametry, dostępność i EN pozostają do sprawdzenia.

## 25. Potwierdzone sprawdzenie — typ konta i sortowanie

Użytkownik potwierdził poprawne działanie wszystkich czterech scenariuszy:

- Zastosowanie typu konta „Użytkownicy” zachowuje limit 50.
- Sortowanie przez nagłówek „Imię” zachowuje typ konta i limit 50.
- Zmiana limitu na 10 zachowuje zastosowany typ konta i sortowanie.
- „Wyczyść filtry” przywraca wszystkie typy kont, zachowując sortowanie i limit 10.

To ręczne sprawdzenia użytkownika, bez testów automatycznych. Zachowanie zastosowanej frazy przy zmianie limitu, nieprawidłowe parametry, przekierowanie spoza zakresu, dostępność i wersja EN pozostają do sprawdzenia.

## 26. Potwierdzone sprawdzenie — fraza przy zmianie limitu

Użytkownik potwierdził, że po wyszukaniu `example-user` i zmianie limitu z 10 na 25 fraza pozostaje w polu, lista nadal zawiera wyłącznie pasujące konta, kontrolka wskazuje 25 i aktywna jest pierwsza strona. Jest to wynik ręcznego sprawdzenia użytkownika. Testy automatyczne, nieprawidłowe parametry, przekierowanie spoza zakresu, dostępność i EN pozostają do weryfikacji.

## 27. Potwierdzone sprawdzenie — niedozwolony limit 999

Użytkownik potwierdził działanie i przesłał zrzut po ręcznej zmianie parametru na `per_page=999`. Adres zachowuje wpisane 999, ale kontrolka wskazuje 10, a stopka pokazuje „Wyświetlono 1–10 z 27 wyników”. Strona wyświetla się bez błędu. To potwierdza ręcznie zastosowanie domyślnego limitu dla tej niedozwolonej wartości; nie stanowi sprawdzenia wszystkich nieprawidłowych parametrów. Tablica w parametrze, przekierowanie spoza zakresu, dostępność, EN i testy automatyczne pozostają do weryfikacji.

## 28. Potwierdzone sprawdzenie — tablica zamiast limitu

Użytkownik przesłał zrzut po wpisaniu `per_page[]=25`. Strona wyświetla się bez błędu, select wskazuje 10, a informacja o wynikach brzmi „Wyświetlono 1–10 z 27 wyników”. Zrzut potwierdza zastosowanie domyślnego limitu dla tablicy zawierającej dozwoloną liczbę. To sprawdzenie ręczne, bez testów automatycznych. Przekierowanie spoza zakresu, dostępność i EN pozostają do weryfikacji.

## 29. Potwierdzone sprawdzenie — strona spoza zakresu

Po zadanym sprawdzeniu `per_page=25&page=99` użytkownik przesłał zrzut z docelowym adresem zawierającym `page=2`, `per_page=25`, `account_type=all`, `sort=id` i `direction=asc`. Select wskazuje 25, aktywna jest druga strona, a stopka pokazuje „Wyświetlono 26–27 z 27 wyników”. Zrzut potwierdza ręcznie przekierowanie na ostatnią dostępną stronę z zachowaniem limitu i sortowania. Dostępność, EN i testy automatyczne pozostają do weryfikacji.

## 30. Potwierdzone sprawdzenie — klawiatura i fokus

Użytkownik potwierdził działanie zadanego scenariusza: przejście Tabem do selecta, otwarcie przez Alt + strzałka w dół, wybór 10 i zatwierdzenie Enterem, skutkujące zmianą limitu i powrotem na pierwszą stronę. Przesłany zrzut pokazuje widoczne niebieskie obramowanie fokusu na selectcie z wartością 25 przed zmianą; końcowy wynik zmiany pochodzi z potwierdzenia użytkownika.

To sprawdzenie obsługi klawiaturą i widoczności fokusu, nie pełny audyt dostępności ani sprawdzenie czytnikiem ekranu. Wersja EN oraz testy automatyczne pozostają do weryfikacji.

## 31. Testy automatyczne i końcowa weryfikacja lokalna

Na wyraźne polecenie użytkownika „Przygotuj wszystko” asystent przygotował testy i uruchomił lokalne kontrole. Nie wykonywał commita ani push.

### Zakres testów PHP

Istniejący szablon `tests/Feature/UserPerPageTest.php` zastąpiono 17 przypadkami uruchomieniowymi. Testy korzystają z `LazilyRefreshDatabase`, fabryk użytkowników i zestawów danych:

- Domyślny limit oraz 10/25/50: 51 zapisanych kont pozwala sprawdzić rzeczywistą liczbę zwróconych rekordów i wierszy tabeli, limit paginatora oraz zaznaczenie selecta.
- Nieprawidłowe wartości: 999, zero, liczba ujemna, tekst, pusty parametr, zapis dziesiętny, początkowe zero, tablica i tablica zagnieżdżona — oczekiwany limit 10.
- Linki paginacji, sortowania i obu akcji czyszczenia: odczyt rzeczywistych adresów z HTML i wykonanie żądań pod te adresy.
- Formularze wyszukiwania i limitu: odczyt wysyłanych pól z HTML, zachowanie frazy, typu konta, sortowania i limitu oraz powrót na pierwszą stronę po zmianie limitu.
- Przekierowanie ze strony spoza zakresu z zachowaniem zastosowanych parametrów.
- Pusta lista w PL i EN: obecność kontrolki, etykieta, powiązanie opisu `aria-describedby`, przetłumaczony opis, zaznaczenie 50 i brak zagnieżdżonych formularzy.

W istniejących testach `UserAccountFilterTest`, `UserClearSearchTest`, `UserControllerTest`, `UserFilterPanelTest`, `UserSearchTest` i `UserSortingTest` zaktualizowano oczekiwane adresy o nowy parametr `per_page=10`. Zachowano dotychczasowe scenariusze i asercje zachowania; nie usuwano testów.

### Zakres testów JavaScript

Dodano `tests/Frontend/per-page-select.test.mjs` z czterema przypadkami: brak wysłania podczas inicjalizacji i pojedyncze wysłanie na zmianę, niezależne działanie wielu instancji, pomijanie formularza bez selecta oraz strona bez kontrolek. Testy wykonują rzeczywisty moduł na uproszczonym DOM; nie zastępują przeglądarkowej weryfikacji klawiatury lub CSS.

W środowisku testu `navigation.test.mjs` dodano zastępnik `initializePerPageSelects`, analogicznie do istniejącego zastępnika inicjalizacji wyszukiwania. Pozwala to nadal niezależnie sprawdzać nawigację po rozszerzeniu punktu wejścia panelu.

### Wyniki potwierdzone przez asystenta

- Pełny zestaw PHP: **143 testy, 778 asercji — poprawnie**. Uruchomiono `php artisan test --compact` z `VIEW_COMPILED_PATH` wskazującym osobny katalog z `mktemp` w `/tmp`.
- JavaScript: **22 przypadki — poprawnie**: navigation 4, per-page-select 4, search-input 10, user-filters 4. Każdy plik uruchomiono osobno przez `node tests/Frontend/<plik>.test.mjs`.
- `vendor/bin/pint --dirty --format agent` — poprawnie; uporządkowano formatowanie zmienionego PHP.
- `vendor/bin/phpstan analyse app tests/Feature/UserPerPageTest.php --debug --no-progress` — **0 błędów**. To analiza aplikacji i nowego testu, nie wszystkich istniejących testów.
- `npm run build` — poprawnie, Vite 8.3.5. Build zgłosił informację o opcjonalnym `fontaine` dla optymalizacji zastępczych fontów; zależności nie zmieniano.
- `git diff --check` — poprawnie.

Wszystkie kryteria funkcjonalne issue mają pokrycie w lokalnej implementacji i sprawdzeniach. Wersję EN potwierdzono przez renderowanie odpowiedzi w testach, bez ręcznego przełączania języka w przeglądarce. Nie wykonywano audytu czytnikiem ekranu ani odłożonych prac mobilnych. Zachowano wcześniejsze zmiany dokumentacji etapu 22 i wpis w `docs/README.md`. Publikacja i zamknięcie issue pozostają osobnym krokiem.
