---
tags:
  - zaznaczanie-uzytkownikow
---

# Zaznaczanie użytkowników na liście

## Zgłoszenie i cel

Etap 27 dotyczy [issue #14 — Dodać zaznaczanie użytkowników na liście jako podstawę edycji zbiorczej](https://github.com/SebMagacy/sportowiada/issues/14).

Na wyraźną prośbę użytkownika przygotowano osobny rozdział i przekazanie pracy do nowego czatu. Celem jest wybór kont jako podstawa przyszłej hurtowej zmiany danych. Sam wybór nie zapisuje zmian w kontach.

## Uzgodnione zachowanie

- Checkbox w pierwszej kolumnie każdego wiersza użytkownika.
- Checkbox w nagłówku zaznacza lub odznacza wszystkie rekordy wyłącznie bieżącej strony.
- Nagłówek odzwierciedla brak, częściowy i pełny wybór.
- Widoczny licznik zaznaczonych rekordów i przycisk wyczyszczenia wyboru.
- Pasek z licznikiem po lewej i czyszczeniem po prawej pojawia się dopiero po zaznaczeniu co najmniej jednego konta; przy zerowym wyborze jest ukryty. Użytkownik zaakceptował ten układ na podstawie przesłanego zrzutu. Pasek został zaimplementowany; wynik ręcznego sprawdzenia opisano poniżej.
- Wybór zeruje się po zmianie strony, wyszukiwania, filtrów, sortowania lub liczby wyników na stronę.
- Tłumaczenia PL/EN, jednoznaczne etykiety, klawiatura i widoczny fokus.
- Poprawne zachowanie przy pustych wynikach i zachowanie istniejących akcji listy.

## Stan wykonania

Użytkownik zaakceptował powyższe zachowanie. Dodano tłumaczenia, strukturę kolumny wyboru i obsługę zaznaczania bieżącej strony w JavaScript. Dodano także licznik, czyszczenie i warunkową widoczność paska. Pozostają dalsze sprawdzenia i testy etapu 27.

### Przygotowane etykiety PL/EN

Użytkownik potwierdził dodanie kluczy `select_page` i `select_user` w `lang/pl/users.php` oraz `lang/en/users.php`. Asystent potwierdził ich obecność i zgodną treść przez odczyt diffu. Pierwszy klucz opisuje wybór wszystkich użytkowników bieżącej strony, drugi zawiera miejsca `:name` i `:id`, aby jednoznacznie nazwać checkbox konkretnego konta. Same tłumaczenia nie zmieniają jeszcze widoku ani danych.

### Tłumaczenia licznika i czyszczenia wyboru

Użytkownik dodał w `lang/pl/users.php` i `lang/en/users.php` klucze `selected_count` (odpowiednio „Zaznaczono:” i „Selected:”) oraz `clear_selection` („Wyczyść zaznaczenie” i „Clear selection”). Potwierdził wykonanie kroku, a asystent sprawdził treść w diffie. Liczba będzie wyświetlana osobno, bez konieczności odmiany rzeczownika. Wszystkie cztery klucze dotyczące wyboru znajdują się obecnie w sekcji INDEX. Samo dodanie tłumaczeń nie wyświetla jeszcze licznika ani przycisku.

### Struktura kolumny wyboru

W `resources/views/admin/users/index.blade.php` użytkownik dodał pierwszą kolumnę z checkboxem nagłówka i checkboxami poszczególnych kont. Etykiety korzystają z tłumaczeń, a checkboxy wierszy zawierają ID konta w `value`. Atrybuty `data-select-page` i `data-select-user` przygotowują elementy do obsługi przez JavaScript. Checkbox nagłówka jest na tym etapie celowo wyłączony (`disabled`). Kolumna pozostaje poza konfiguracją kolumn sortowania w kontrolerze, dlatego pusty stan ma `colspan="{{ count($columns) + 1 }}"`.

Asystent potwierdził powyższe zmiany w diffie. Użytkownik potwierdził sukces sprawdzenia ręcznego: działają pojedyncze checkboxy i zachowane jest wyrównanie kolumn. Wybór całej strony jeszcze nie działa, zgodnie ze stanem implementacji. Pusty stan, język EN i obsługa klawiatury wymagają osobnej weryfikacji.

### Wybór bieżącej strony w JavaScript

W `resources/js/row-selection.js` dodano eksportowaną funkcję `initializeRowSelections`, importowaną i wywoływaną w `resources/js/admin.js`. Początkowo tabela miała atrybut `data-row-selection`; podczas dodawania paska przeniesiono go na zewnętrzną kartę listy. Każdy kontener inicjalizowany jest osobno, a strona bez takiego kontenera nie wymaga dodatkowej obsługi.

Zmiana checkboxa nagłówka ustawia `checked` wszystkich checkboxów wierszy tej tabeli. Zmiana pojedynczego checkboxa przelicza wybór: nagłówek jest zaznaczony przy pełnym wyborze, ma `indeterminate` przy częściowym i jest odznaczony przy braku wyboru. Przy braku wierszy pozostaje wyłączony. Kod nie wysyła żądań ani nie zapisuje danych kont.

Asystent potwierdził implementację przez odczyt modułu i diffu integracji. Użytkownik potwierdził, że działa po wykonaniu zaproponowanego sprawdzenia zaznaczania i odznaczania całej strony oraz stanów nagłówka. Nie uruchomiono jeszcze testów automatycznych. Reset po zmianie kryteriów listy, pusty stan, EN i klawiatura wymagają dalszej weryfikacji.

### Pasek, licznik i czyszczenie — implementacja

Asystent potwierdził odczytem kodu dodanie w widoku `data-selection-toolbar` z początkowym `hidden`, licznikiem `data-selection-count` i przyciskiem `data-clear-selection`. Pasek znajduje się wewnątrz kontenera listy, przed kontenerem przewijania tabeli. Korzysta z tłumaczeń PL/EN; licznik ma `role="status"` i `aria-atomic="true"`.

W `row-selection.js` funkcja `updateSelection` aktualizuje tekst licznika i ukrywa pasek przy zerowym wyborze. Przycisk czyszczenia odznacza wszystkie wiersze, przenosi fokus na checkbox nagłówka i synchronizuje stan. Odczyt kodu potwierdza implementację. Użytkownik potwierdził sukces sprawdzenia w przeglądarce: zaznaczenie dwóch kont pokazuje „Zaznaczono: 2”, a użycie czyszczenia odznacza konta i ukrywa pasek. `git diff --check` wykonany przez asystenta zakończył się bez uwag.

## Plan

Przygotowano kontener do dodania paska: `data-row-selection` przeniesiono z tabeli na zewnętrzną kartę listy, zachowując osobny `div.overflow-x-auto` wokół tabeli. Po korekcie zduplikowanego kontenera asystent potwierdził odczytem kodu pojedynczy atrybut inicjalizacji i poprawne zamknięcia tych kontenerów. Był to krok przygotowawczy przed dodaniem paska opisanego powyżej.

Po przeglądzie bieżącego kodu, reguł i wersji prowadzić implementację po jednym małym kroku: struktura kolumny i wierszy, tłumaczenia, obsługa wyboru w osobnym module JS, licznik i czyszczenie, następnie weryfikacja ręczna oraz odpowiednie testy. Uwzględnić dodatkową kolumnę w pustym stanie tabeli. Nie pozostawiać pośredniego przesunięcia kolumn jako końcowego wyniku.

## Poza zakresem

Pola, formularz i zapis edycji zbiorczej wymagają osobnego uzgodnienia. Nie zakładamy zbiorczej zmiany ról, haseł ani usuwania kont. Bez nowych zależności i pełnej refaktoryzacji listy z issue #13. Problemy mobilne panelu, w tym odłożony problem nagłówka z issue #18, pozostają osobnym zadaniem.

## Weryfikacja

- Użytkownik potwierdził reset wyboru po przejściu przez paginację: po zaznaczeniu dwóch kont i otwarciu drugiej strony wszystkie checkboxy były odznaczone, a pasek zaznaczenia ukryty.
- Użytkownik potwierdził reset po wyszukiwaniu: po zaznaczeniu dwóch kont i zatwierdzeniu zapytania wyniki odpowiadały wyszukiwaniu, checkboxy były odznaczone, a pasek ukryty.
- Użytkownik potwierdził reset po zastosowaniu filtra typu konta: lista zawierała wyłącznie administratorów, checkboxy były odznaczone, a pasek zaznaczenia ukryty.
- Użytkownik potwierdził reset po zmianie sortowania przez nagłówek „Imię”: stan sortowania zmienił się, checkboxy były odznaczone, a pasek zaznaczenia zniknął.
- Użytkownik potwierdził reset po zmianach limitu z 10 na 25 i następnie na 50, każdorazowo po zaznaczeniu konta: checkboxy były odznaczone, a pasek zaznaczenia ukryty.

- Użytkownik potwierdził poprawne puste wyniki po wyszukaniu nieistniejącego tekstu: komunikat obejmował całą szerokość tabeli, checkbox nagłówka był wyłączony, a pasek zaznaczenia ukryty.

- Użytkownik potwierdził obsługę klawiaturą: Tab dawał widoczny fokus na checkboxie nagłówka, Spacja zaznaczała konta i pokazywała pasek, a Shift+Tab i Enter na przycisku czyszczenia usuwały wybór. Pasek znikał, a widoczny fokus wracał na checkbox nagłówka.

- Użytkownik potwierdził angielską wersję paska: po zaznaczeniu dwóch kont pojawiały się „Selected: 2” i „Clear selection”, a czyszczenie działało. Zrzut wcześniej potwierdził angielski interfejs przy braku wyboru. Następnie użytkownik przywrócił język polski; asystent potwierdził odczytem `.env` wartość `APP_LOCALE=pl`.

Użytkownik uruchomił `node --test tests/Frontend/*.test.mjs`: 22 testy, 18 poprawnych i 4 nieudane. Wszystkie błędy pochodziły z `navigation.test.mjs` i miały przyczynę `ReferenceError: initializeRowSelections is not defined`. Asystent sprawdził test: usuwa on importy z kodu `admin.js`, a inicjalizatory udostępnia jako zastępcze funkcje w kontekście VM. Brakuje tam nowego inicjalizatora wyboru wierszy. Wymaga to aktualizacji środowiska testu nawigacji; nie potwierdza usterki checkboxów w przeglądarce. Osobne testy modułu wyboru pozostają do dodania.

Po dodaniu zastępczej funkcji `initializeRowSelections` do kontekstu VM w `navigation.test.mjs` użytkownik ponownie uruchomił zestaw frontendowy: 22 testy poprawne, 0 błędów. Asystent potwierdził obecność poprawki w kodzie. Wynik obejmuje dotychczasowe interakcje, bez osobnych testów modułu wyboru.

Wyniki etapu 26 (205 testów PHP, 1127 asercji) dotyczą wcześniejszej funkcjonalności, nie zaznaczania użytkowników.

### Testy dodane i wykonane przez asystenta

Na wyraźne zlecenie użytkownika asystent przygotował testy i sam wykonał weryfikację:

- `tests/Frontend/row-selection.test.mjs`: 9 testów rzeczywistego modułu z uproszczonym DOM. Obejmują stan początkowy, zaznaczanie i odznaczanie strony, częściowy/pełny/pusty wybór, licznik, widoczność paska, czyszczenie i powrót fokusu, puste wyniki, niezależne kontenery, brak checkboxa nagłówka, brak opcjonalnego paska i stronę bez kontenerów.
- `tests/Feature/UserSelectionTest.php`: 4 przypadki, 35 asercji. Żądania HTTP sprawdzają etykiety PL/EN, bezpieczne osadzanie nazwy zawierającej HTML w etykiecie checkboxa, początkowo ukryty pasek i brak wyboru, liczbę kolumn, checkboxy wyłącznie bieżącej strony oraz puste wyniki.
- Pełny zestaw PHP początkowo wykrył stary selektor `//th[1]/a` w `UserPerPageTest.php`. Pierwsza kolumna zawiera teraz checkbox. Asystent zmienił selektor na `(//thead//th/a)[1]`, zachowując sprawdzenie parametrów pierwszego linku sortowania. Po poprawce testy tego pliku: 17/17, 116 asercji.
- Pełny zestaw PHP: **209 testów, 1162 asercje, bez błędów**.
- Wszystkie pliki testów frontendowych uruchomione bezpośrednio przez Node: **31 przypadków, bez błędów**, w tym 9 nowych. Uruchomienie przez `node --test` w środowisku asystenta raportowało tylko wyniki 5 plików, dlatego każdy plik uruchomiono również bezpośrednio, uzyskując wyniki poszczególnych przypadków.
- Pint `--dirty --format agent`: poprawnie. PHPStan aplikacji i osobno zmienionych testów PHP: bez błędów. Dla testów użyto `--debug`, aby ominąć ograniczenie lokalnego gniazda procesu równoległego w środowisku wykonawczym.
- `npm run build`: poprawnie; komunikat o opcjonalnym pakiecie `fontaine` nie zatrzymał budowania. Nie zmieniano zależności.
- `git diff --check`: poprawnie.

Testy JS nie uruchamiają prawdziwej przeglądarki. Wyniki ręcznych sprawdzeń użytkownika pozostają osobnym potwierdzeniem interakcji, klawiatury i wyglądu. Asystent nie wykonał commita ani push.
