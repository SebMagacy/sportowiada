---
tags:
  - filament
  - dyscypliny
  - usuwanie
---

# 47. Ochrona usuwania dyscyplin powiązanych z ligami

## Cel i status

Rozdział utworzono 20.11.2026 na prośbę użytkownika dla [issue #38](https://github.com/SebMagacy/sportowiada/issues/38).

Cel: odmowa usunięcia dyscypliny używanej przez ligi ma wyświetlać zrozumiały komunikat w panelu, bez błędu 500. Zachowujemy dyscyplinę, ligi, media oraz klucz obcy RESTRICT.

**Status: implementacja i uzgodniona weryfikacja zakończone. Potwierdzono ręcznie odmowę pojedynczą i zbiorczą w PL oraz automatycznie komunikaty PL/EN, zachowanie danych i mediów, uprawnienia i przekazywanie innych błędów. Oba testy współbieżności MySQL przeszły na osobnej bazie. Commit publikujący zmiany i zakończenie etapu identyfikuje historia Git oraz podsumowanie w issue #38.**

## Uzgodnione zachowanie

- Usuwanie pojedyncze: dyscyplina powiązana z ligami pozostaje, panel wyjaśnia przyczynę odmowy.
- Usuwanie zbiorcze: jeśli choć jedna zaznaczona dyscyplina ma ligi, blokujemy całą operację. Nie usuwamy żadnego zaznaczonego rekordu.
- Ochrona ma działać po stronie serwera, z zachowaniem Policies/Shield i potwierdzenia operacji.
- Trzeba obsłużyć równoczesne zmiany powiązań, bez ukrywania niezwiązanych błędów bazy.

## Punkt wyjścia — odczyt asystenta

Pojedyncza akcja `DeleteAction` znajduje się w `app/Filament/Resources/Disciplines/Pages/EditDiscipline.php`, a `DeleteBulkAction` w `app/Filament/Resources/Disciplines/DisciplineResource.php`. `DisciplinePolicy` sprawdza uprawnienia, lecz nie powiązania z ligami. Model ma relację `leagues()`.

Model korzysta z biblioteki mediów, która reaguje na zdarzenie `deleting`. Samo wycofanie transakcji bazy nie przywraca usuniętych plików. Sprawdzenie całego zaznaczenia musi poprzedzać rozpoczęcie usuwania, a rozwiązanie musi uwzględniać równoczesne operacje.

Potwierdzone na początku pracy wersje: Laravel 13.42.0, Filament 5.11.3. Aplikacja używa MySQL, testy korzystają z SQLite w pamięci.

## Krok 1 — komunikaty PL/EN (wykonany)

Użytkownik dodał do `lang/pl/disciplines.php` i `lang/en/disciplines.php` cztery klucze:

- `delete_blocked_title` — tytuł odmowy pojedynczej;
- `delete_blocked_body` — wyjaśnienie powiązania z ligami;
- `bulk_delete_blocked_title` — tytuł odmowy zbiorczej;
- `bulk_delete_blocked_body` — wyjaśnienie, że powiązanie przynajmniej jednej dyscypliny zatrzymało całą operację i niczego nie usunięto.

Polska treść odmowy zbiorczej: „Co najmniej jedna zaznaczona dyscyplina jest używana przez ligi. Nie usunięto żadnej dyscypliny.”

Asystent potwierdził treść obu plików odczytem i przeglądem różnic. Nie potwierdzono jeszcze wyświetlania komunikatów: akcje nie korzystają z nowych kluczy. Nie uruchomiono testów ani formatowania tego kroku.

## Krok 2 — utworzenie klasy wyjątku (wykonany)

Użytkownik wykonał `php artisan make:exception DisciplineHasLeaguesException --no-interaction` i przekazał komunikat sukcesu. Asystent potwierdził odczytem utworzenie `app/Exceptions/DisciplineHasLeaguesException.php`: klasa dziedziczy po `Exception` i nie zawiera jeszcze własnego zachowania.

Klasa będzie rozróżniać oczekiwaną odmowę wynikającą z powiązań od innych błędów. Na tym etapie nie jest jeszcze zgłaszana ani przechwytywana przez aplikację i nie chroni usuwania. Nie wykonano testów tego mechanizmu.

## Krok 3 — oznaczenie oczekiwanej odmowy (wykonany)

Użytkownik dodał import `Illuminate\Contracts\Debug\ShouldntReport` i `implements ShouldntReport` do `DisciplineHasLeaguesException`. Asystent potwierdził zapis odczytem pliku.

Interfejs wyłącza domyślne raportowanie tego konkretnego wyjątku przez Laravel. Nie wyświetla komunikatu, nie blokuje usuwania i nie zmienia raportowania pozostałych błędów. Przechwycenie odmowy w akcjach panelu pozostaje do wdrożenia. Testy zachowania i formatowanie zmienionego PHP pozostają do wykonania.

## Krok 4 — utworzenie wspólnej klasy operacji (wykonany)

Użytkownik wykonał `php artisan make:class Actions/DeleteDisciplines --no-interaction` i przekazał komunikat sukcesu. Asystent potwierdził odczytem plik `app/Actions/DeleteDisciplines.php` w nowym katalogu `app/Actions`. Wygenerowana klasa ma jedynie pusty konstruktor; logika operacji nie została jeszcze dodana.

Klasa ma służyć obu akcjom panelu: sprawdzić uprawnienia i wszystkie wybrane dyscypliny przed rozpoczęciem usuwania. Obsługa blokad i weryfikacja zachowania pozostają do wykonania.

## Krok 5 — logika wspólnego usuwania (zapisana, nieweryfikowana testami)

Użytkownik uzupełnił `DeleteDisciplines::handle(array $disciplineIds): int`. Asystent potwierdził odczytem: transakcję, pobranie dyscyplin w kolejności ID z `lockForUpdate()`, sprawdzenie `Gate::authorize('delete', ...)` dla każdej dyscypliny oraz odczyt powiązanej ligi z blokadą przed pętlą usuwania. Znalezienie ligi zgłasza `DisciplineHasLeaguesException` przed wywołaniem `delete()`. Niepowodzenie `delete()` zgłasza `RuntimeException`; inne wyjątki nie są przechwytywane. Metoda zwraca liczbę pobranych i usuniętych dyscyplin.

Klasa nie jest jeszcze podłączona do panelu. Testy, analiza statyczna i formatowanie pozostają do wykonania. Kontrola odczytu nie potwierdza zachowania przy równoczesnych żądaniach ani bezpieczeństwa plików przy niezwiązanych awariach; transakcja bazy nie przywraca plików.

## Krok 6 — podłączenie pojedynczego usuwania (zapisane)

Użytkownik dodał w `EditDiscipline.php` importy wspólnej operacji, wyjątku, modelu i powiadomienia. `DeleteAction::using()` wywołuje `DeleteDisciplines::handle()` dla ID edytowanej dyscypliny. Przechwytuje wyłącznie `DisciplineHasLeaguesException`, wysyła powiadomienie `danger` z kluczy `delete_blocked_title` i `delete_blocked_body`, a następnie wywołuje `halt()`.

Edytor zgłosił brak zwracanej wartości w gałęzi odmowy: metoda Filament `halt()` ma typ `void`, choć jej implementacja rzuca wyjątek. Użytkownik dopisał `return false;` po `halt()` i potwierdził zniknięcie ostrzeżeń. Asystent potwierdził zapis odczytem pliku. Linia po `halt()` nie jest wykonywana w obecnej implementacji Filament.

Potwierdzenie dotyczy kodu i komunikatów edytora; nie działania w panelu. Ręczna kontrola odmowy, testy, formatowanie i analiza statyczna pozostają do wykonania. Akcja zbiorcza nadal korzysta z dotychczasowej implementacji.

## Krok 7 — ręczna odmowa pojedynczego usunięcia (potwierdzona)

Użytkownik przekazał zrzut edycji dyscypliny „Liga testowa” po potwierdzeniu usuwania. Widoczne jest powiadomienie „Nie można usunąć dyscypliny” / „Dyscyplina jest używana przez ligi.”, bez strony błędu 500. Okno potwierdzenia pozostaje otwarte po `halt()`.

Asystent odczytał bazę: dyscyplina ID 16 nadal istnieje i w chwili sprawdzenia ma dwie powiązane ligi. Zrzut przedstawia puste pole grafiki, więc nie potwierdza zachowania istniejącego pliku mediów. Wcześniejsza zgłoszona próba usunięcia dotyczyła ligi, co potwierdził odczyt zapytań Debugbar; nie była wynikiem testu odmowy usunięcia dyscypliny.

Nie potwierdzono jeszcze odmowy zbiorczej, komunikatów EN, testów automatycznych ani zachowania przy równoczesnych operacjach.

## Krok 8 — podłączenie usuwania zbiorczego (zapisane)

Użytkownik uzupełnił `DisciplineResource.php`: `DeleteBulkAction::using()` przekazuje klucze zaznaczonych rekordów do `DeleteDisciplines::handle()`, a zwróconą liczbę zgłasza przez `reportBulkProcessingSuccessfulRecordsCount()`. Przy `DisciplineHasLeaguesException` wysyła powiadomienie z kluczy `bulk_delete_blocked_title` i `bulk_delete_blocked_body`, po czym zatrzymuje akcję przez `halt()`.

Asystent potwierdził zapis odczytem kodu. Przed kontrolą mieszanego zaznaczenia odczytał bazę: „Liga testowa” (ID 16) ma dwie ligi, a „Piłka nożna 3” (ID 18) nie ma lig. Jest to stan przed próbą, nie potwierdzenie działania akcji zbiorczej. Testy i wymagane kontrole pozostają do wykonania.

## Krok 9 — ręczna odmowa mieszanego zaznaczenia (potwierdzona)

Użytkownik przekazał zrzut po próbie usunięcia mieszanego zaznaczenia. Widać powiadomienie „Nie można usunąć zaznaczonych dyscyplin” oraz informację o powiązaniu przynajmniej jednej dyscypliny z ligami i nieusunięciu żadnej dyscypliny. Oba rekordy pozostają widoczne na liście, bez strony błędu 500; okno potwierdzenia pozostaje otwarte.

Asystent potwierdził odczytem bazy zachowanie obu rekordów: ID 16 nadal ma dwie ligi, ID 18 nadal nie ma lig. Kontrola nie obejmowała grafik ani języka angielskiego. Testy automatyczne pozostają do dodania.

## Krok 10 — test odmowy pojedynczej z grafiką (potwierdzony przez użytkownika)

Użytkownik dodał do `FilamentDisciplineTest` test `test_delete_preserves_discipline_with_league_and_media`. Test tworzy dyscyplinę z ligą i grafiką na pozorowanym dysku `public`, wykonuje akcję usuwania na stronie edycji i sprawdza polskie powiadomienie odmowy oraz zachowanie dyscypliny, ligi, rekordu mediów i pliku.

Użytkownik uruchomił `php artisan test --compact --filter=test_delete_preserves_discipline_with_league_and_media` i przekazał wynik: **1 test przeszedł, 10 asercji, 0,94 s**. Wynik nie potwierdza operacji zbiorczej ani blokad współbieżnych w MySQL; testy projektu używają SQLite.

## Krok 11 — pozostałe testy i kontrole (wykonane przez asystenta w uzgodnionym zakresie)

Użytkownik zlecił asystentowi dodanie wszystkich pozostałych testów. Asystent rozszerzył `tests/Feature/FilamentDisciplineTest.php` o:

- odmowę mieszanego zaznaczenia w PL i EN, z zachowaniem obu dyscyplin, ligi, obu rekordów mediów i obu plików; niepowiązana dyscyplina ma niższe ID, więc test wykrywa przedwczesne usuwanie;
- odmowę pojedynczą po angielsku, bez powiadomienia sukcesu;
- brak możliwości wywołania pojedynczej akcji przez użytkownika z dostępem do edycji, lecz bez `Delete:Discipline`;
- odmowę zbiorczą dla użytkownika mającego `DeleteAny:Discipline`, lecz bez `Delete:Discipline`, przed usunięciem jakiegokolwiek rekordu;
- poprawne zbiorcze usunięcie niepowiązanej dyscypliny wraz z mediami i plikiem oraz powiadomienie sukcesu;
- propagowanie niezwiązanego `QueryException` w obu akcjach, bez zamiany na komunikat o ligach. Awaria jest wstrzykiwana przed zapytaniem DELETE w testowej bazie SQLite; test sprawdza zachowanie rekordu i brak powiadomienia.

Dotychczasowe testy poprawnego usuwania pojedynczego, usuwania tylko zaznaczonych rekordów, braku uprawnień zbiorczych i poprawnego użycia uprawnień pozostały zachowane. Nie zmieniano zależności ani konfiguracji aplikacji.

Asystent wykonał wymagane `vendor/bin/pint --dirty --format agent`. Formatter uporządkował importy, białe znaki i koniec pliku wspólnej operacji; ponowna kontrola formatowania przeszła. Przegląd różnic i `git diff --check` nie wykazały problemów z formatem.

Własne wyniki asystenta:

- testy `FilamentDisciplineTest`: **38 zaliczonych, 282 asercje**;
- pełny zestaw: **200 zaliczonych, 2 pominięte, 1103 asercje**, około 41,5 s;
- PHPStan: **0 błędów**. Po formatowaniu standardowe uruchomienie nie mogło otworzyć lokalnego portu używanego do pracy równoległej; uruchomienie z `--debug` bez tego mechanizmu zakończyło się poprawnie.

### Testy współbieżności MySQL — dodane, jeszcze niewykonane

`tests/Feature/DeleteDisciplinesConcurrencyTest.php` zawiera dwa testy używające niezależnych połączeń do MySQL:

1. Próba przypisania ligi po zablokowaniu dyscypliny musi czekać na blokadę; po usunięciu dyscypliny klucz obcy nie pozwala na takie przypisanie.
2. Liga zatwierdzona przez drugie połączenie po utworzeniu wcześniejszego obrazu danych w transakcji musi zostać wykryta przez odczyt z blokadą. Odmowa zachowuje dyscyplinę i grafikę.

Testy uruchamiają migracje wyłącznie po sprawdzeniu połączenia MySQL i nazwy bazy pasującej do `sportowiada_test_<hex>`. W zwykłym zestawie SQLite są jawnie pomijane. Celowe oczekiwanie na blokadę ma limit jednej sekundy.

Asystent spróbował utworzyć osobną bazę o losowej nazwie, aby nie dotykać danych aplikacji. Konto MySQL odmówiło operacji: kod **1044**, brak uprawnienia do utworzenia bazy. Baza testowa nie powstała; nie zmieniono uprawnień ani konfiguracji. Nie należy uznawać dwóch pominiętych testów za zaliczone.

Po przygotowaniu oddzielnej bazy o dozwolonej nazwie i dostępu do niej można uruchomić:

```bash
DB_CONNECTION=mysql DB_DATABASE=sportowiada_test_a1b2 php artisan test --compact tests/Feature/DeleteDisciplinesConcurrencyTest.php
```

To polecenie usuwa i odtwarza tabele w wskazanej bazie testowej, dlatego nie należy wskazywać bazy z potrzebnymi danymi. Nazwa powyżej jest przykładem, a nie utworzoną bazą. Domyślny zestaw pozostaje na SQLite.

## Krok 12 — osobna baza MySQL do testów (utworzona przez użytkownika)

Użytkownik zalogował się do MySQL przez `mysql -u root -p` i wykonał:

```sql
CREATE DATABASE sportowiada_test_a1b2
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

GRANT ALL PRIVILEGES ON sportowiada_test_a1b2.*
    TO 'sportowiada'@'localhost';
```

Oba polecenia zwróciły `Query OK`. Uprawnienia przyznano istniejącemu kontu wyłącznie dla bazy testowej; nie nadano globalnego prawa tworzenia baz. Użytkownik przekazał również wersję serwera: MySQL 8.4.13. Próba `sudo mysql` bez hasła MySQL wcześniej została odrzucona; logowanie z hasłem powiodło się.

To potwierdza utworzenie bazy i nadanie uprawnień, ale jeszcze nie wykonanie testów. Nie zmieniano `.env`; połączenie i nazwę bazy wskażemy jednorazowo w poleceniu testowym.

## Krok 13 — testy współbieżności MySQL (potwierdzone przez użytkownika)

Użytkownik wykonał:

```bash
DB_CONNECTION=mysql DB_DATABASE=sportowiada_test_a1b2 php artisan test --compact tests/Feature/DeleteDisciplinesConcurrencyTest.php
```

Przekazany wynik: **2 testy zaliczone, 13 asercji, 3,40 s**, bez pominięć. Potwierdza to oba opisane scenariusze na MySQL: blokadę przypisania ligi podczas usuwania i wykrycie ligi zatwierdzonej po wcześniejszym odczycie w transakcji, z zachowaniem grafiki przy odmowie. Nie jest to pełny zestaw aplikacji na MySQL; pełny zestaw był uruchomiony na SQLite, zgodnie z domyślną konfiguracją testów.

## Publikacja etapu

Użytkownik zlecił wykonanie commita, push i zakończenie #38 po zaliczeniu weryfikacji. Zakres publikacji obejmuje kod operacji i obu akcji, wyjątek, tłumaczenia, testy oraz ten rozdział ze spisem treści. Lokalne reguły rozpoczynania czatu pozostają osobnym zakresem.

Komunikaty EN są potwierdzone automatycznie; ręczne zrzuty dotyczą PL. Numer commita i wynik push są potwierdzane po wykonaniu operacji i zapisywane w podsumowaniu issue, aby nie deklarować publikacji przed jej wykonaniem.
