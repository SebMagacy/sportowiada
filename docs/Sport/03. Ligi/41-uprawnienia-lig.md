---
tags:
  - sport
---

# 41. Uprawnienia lig

> Aktualizacja 17.11.2026: użytkownik odłożył uprawnienia i zatwierdził zmianę struktury `leagues`: usunięcie `owner_id` i unikalność `(discipline_id, name)`. Poniższe wcześniejsze założenia własności oraz plan `LeaguePolicy` zachowują kontekst, ale nie stanowią aktualnego polecenia implementacji. Nowe ustalenia i potwierdzone kroki zapisano na końcu rozdziału.

> Stan wykonania: migracja zmieniająca została wykonana w lokalnym MySQL i sprawdzona przez Boost. Testy: 129 zaliczonych, 605 asercji; Pint: `fixed`; PHPStan: bez błędów. Uprawnienia pozostają odłożone. Commit i push tej zmiany nie zostały jeszcze potwierdzone. Wcześniejsze wymagania własności w issue #25 i poprzednich rozdziałach wymagają osobnego uzgodnienia aktualizacji; nie opisują nowej struktury.

## Cel i zgłoszenie

Czwarta część [issue #25 — Sport: powiązać ligi z dyscyplinami i projekty z ligami oraz sezonami](https://github.com/SebMagacy/sportowiada/issues/25) obejmuje nadawanie prawa do tworzenia lig oraz ograniczenie zarządzania konkretnymi ligami zgodnie z własnością. Model i tabelę opisuje [rozdział 37](37-model-i-tabela-lig.md), a relacje [rozdział 38](38-relacje-ligi.md).

## Zatwierdzone zasady

- Samo zalogowanie nie pozwala tworzyć lig. Użytkownik musi otrzymać uprawnienie nadane przez administratora.
- System przypisuje twórcę jako właściciela ligi; użytkownik nie wybiera dowolnego właściciela w formularzu.
- Właściciel zarządza swoimi ligami, administrator wszystkimi.
- Ogólne uprawnienie do czynności i własność konkretnego rekordu są osobnymi warunkami. Sama rola organizatora nie daje dostępu do cudzych lig.
- Wykorzystujemy istniejący mechanizm Laravel Policies, Spatie Permission i Filament Shield. Nie przywracamy `users.is_admin`, Gate `access-admin` ani usuniętego panelu legacy-admin.

## Przegląd przed implementacją — 17.11.2026

Asystent wykonał przegląd tylko do odczytu: instrukcji projektu, opisu issue #25, Git, kodu, dokumentacji, zależności przez `composer show --direct`, informacji aplikacji i schematu przez Laravel Boost. Sprawdził także dokumentację tworzenia i automatycznego wykrywania polityk przez Boost `search-docs`.

Potwierdzono:

- Gałąź `main`, czysty katalog roboczy przed utworzeniem tego rozdziału; ostatni lokalny commit `e29cbdc`.
- Laravel `13.42.0`, Filament `5.11.3`, Filament Shield `4.3.1` i Spatie Permission `8.5.0`.
- Istnieją model `League` i relacje z dyscypliną oraz właścicielem, a także relacje odwrotne.
- Tabela `leagues` ma unikalność `owner_id + discipline_id + name` oraz klucze obce do dyscypliny i właściciela z blokadą usuwania powiązanych rekordów.
- `owner_id` pozostaje poza listą `Fillable`; automatyczne przypisanie twórcy nie zostało jeszcze zaimplementowane.
- Istnieją polityki użytkowników, ról, dyscyplin i sezonów. `SeasonPolicy` sprawdza uprawnienia w formacie np. `Create:Season`.
- Shield używa separatora `:` i formatu `pascal`. `super_admin.define_via_gate` ma wartość `false`, więc administrator korzysta z jawnie przypisanych uprawnień.
- Nie ma jeszcze `LeaguePolicy` ani zasobu Filamentu dla lig. Issue #25 pozostaje otwarte.

Przegląd nie obejmował uruchomienia testów ani ręcznego sprawdzenia uprawnień lig. Historyczne wyniki testów wspólnego systemu uprawnień nie potwierdzają tej nowej funkcji.

## Pierwszy krok — oczekuje na wykonanie

Cel: przygotować szkielet polityki dla modelu `League`.

Polecenie przekazane użytkownikowi:

```bash
php artisan make:policy LeaguePolicy --model=League --no-interaction
```

Laravel rozpoznaje konwencyjne powiązanie `App\Models\League` z `App\Policies\LeaguePolicy`. Wygenerowany szkielet sam nie nadaje uprawnień. Kryterium sukcesu tego kroku to komunikat o utworzeniu `app/Policies/LeaguePolicy.php`.

Użytkownik nie przekazał jeszcze wyniku polecenia. Nie zapisujemy wygenerowania polityki ani obsługi `Create:League` jako wykonanych zmian.

## Dalszy zakres — plan

Po potwierdzeniu pierwszego kroku przygotujemy sprawdzanie `Create:League`, nadawanie uprawnień zgodne z aktualnym Shield oraz reguły dostępu do własnych i cudzych rekordów. Zmiany wymagają testów uprawnień. Formularze i automatyczne przypisanie właściciela będą wdrażane przy obsłudze katalogu lig; ochrona lig powiązanych z projektami wymaga późniejszego etapu projektów.

## Sposób pracy

Po polsku, jeden mały krok naraz: cel, mechanizm, konkretna edycja lub polecenie, kryterium sukcesu, następnie oczekiwanie na wynik. Użytkownik wykonuje edycje aplikacji, polecenia i Git. Asystent uzupełnia ten rozdział po potwierdzonych krokach, zachowując rozróżnienie między planem, kodem i wynikiem weryfikacji. Bez nowych gałęzi, worktree i zmian zależności.

Rozdział utworzono na wyraźne zlecenie użytkownika. W tym kroku zmieniono wyłącznie dokumentację; commit i push nie zostały wykonane.

## Zmiana podejścia — katalog rozgrywek bez właściciela

Użytkownik zatwierdził zachowanie nazwy `leagues` i powiązania `discipline_id`, usunięcie `owner_id` oraz zastąpienie unikalności `(owner_id, discipline_id, name)` unikalnością `(discipline_id, name)`. Ta sama nazwa będzie dozwolona w różnych dyscyplinach. Uprawnienia pozostają odłożone. Jest to decyzja dla Sportowiady, nie potwierdzenie zgodności z kodem JoomLeague 6.2, którego archiwum nie było dostępne podczas przeglądu.

### Utworzenie migracji zmieniającej

Użytkownik wykonał:

```bash
php artisan make:migration remove_owner_from_leagues_table --table=leagues --no-interaction
```

Przekazał komunikat o utworzeniu `database/migrations/2026_11_17_164034_remove_owner_from_leagues_table.php`. Asystent odczytał plik i potwierdził szkielet z pustymi metodami `up()` oraz `down()`, zawierającymi `Schema::table('leagues', ...)`. Wcześniejsza migracja tworząca tabelę pozostaje bez zmian.

Odczyt schematu przez Boost potwierdził, że baza nadal zawiera `owner_id`, jego klucz obcy i dotychczasową unikalność. Zapytanie tylko do odczytu grupujące ligi według `discipline_id, name` nie wykazało duplikatów w obecnej bazie. Nie uruchamiano migracji ani testów tej zmiany.

Następny krok: uzupełnić `up()`. Przed wykonaniem migracji trzeba też przygotować `down()`, rozstrzygnąć ograniczenie odtwarzania usuniętych wartości właścicieli oraz dostosować zależne modele, fabryki i testy. Utworzenie szkieletu nie potwierdza jeszcze działania nowego modelu danych.

### Uzupełnienie metody `up()`

Użytkownik potwierdził zapisanie kodu odpowiedzią „ok. sukces”. Asystent odczytał migrację i potwierdził, że `up()` kolejno usuwa klucz obcy `owner_id`, indeks unikalny `(owner_id, discipline_id, name)`, kolumnę `owner_id` i dodaje unikalność `(discipline_id, name)`. Zmiana zachowuje pozostałe pola i powiązanie dyscypliny.

Potwierdzono treść pliku, nie wykonanie migracji. `down()` nadal zawiera pusty szkielet. Usunięcie kolumny oznacza utratę zapisanych wartości właścicieli; samo ponowne dodanie kolumny nie odtworzy tych danych. Następny proponowany krok to jawna blokada automatycznego rollbacku z czytelnym komunikatem. Kod tej blokady nie został jeszcze dodany. Migracja, testy i formatowanie pozostają do wykonania po przygotowaniu całości zmiany.

### Jawna blokada automatycznego rollbacku

Po potwierdzeniu użytkownika „ok. sukces” asystent odczytał migrację. Metoda `down()` zawiera teraz `throw new \RuntimeException('Nie można automatycznie odtworzyć usuniętych właścicieli lig.');`. Blokuje cofnięcie tej migracji zamiast pozorować odzyskanie utraconych danych. Potwierdzono kod; nie wykonywano rollbacku ani migracji.

Przegląd zależności wykazał nadal obecne `League::owner()`, `User::leagues()`, `owner_id` w `LeagueFactory` oraz testy dotychczasowej własności. Wymagają dostosowania przed uruchomieniem i sprawdzeniem całości zmiany. Następny mały krok to usunięcie relacji `owner()` z modelu `League`.

### Usunięcie relacji właściciela z ligi

Użytkownik potwierdził zmianę odpowiedzią „ok. sukces”. Asystent odczytał `app/Models/League.php` i potwierdził usunięcie metody `owner()` wraz z PHPDoc. Relacja `discipline()` i potrzebny jej import `BelongsTo` pozostały zachowane. Lista `Fillable` nadal obejmuje `name`, `description` i `discipline_id`.

Potwierdzono kod, nie działanie po migracji. W `app/Models/User.php` nadal istnieje odwrotna relacja `leagues()` wykorzystująca `owner_id`; jej usunięcie wraz z niepotrzebnym importem `HasMany` jest następnym krokiem. Migracja, dostosowanie fabryki i testów oraz kontrole całości pozostają do wykonania.

### Usunięcie odwrotnej relacji z użytkownika

Po odpowiedzi użytkownika „ok. sukces” asystent odczytał `app/Models/User.php` i potwierdził usunięcie metody `leagues()` wraz z PHPDoc oraz importu `HasMany`. Mechanizm ról `HasRoles` i metoda `canAccessPanel()` pozostały zachowane.

Potwierdzono treść pliku, nie wynik testów. Następny krok to usunięcie `owner_id => User::factory()` oraz importu `App\Models\User` z `database/factories/LeagueFactory.php`, gdzie nadal występują. Migracji jeszcze nie uruchamiano w ramach tej pracy; fabryka, testy i kontrole całości wymagają dokończenia.

### Dostosowanie fabryki ligi

Użytkownik potwierdził zmianę odpowiedzią „ok. sukces”. Asystent odczytał `database/factories/LeagueFactory.php`: usunięto import `App\Models\User` i wpis `owner_id => User::factory()`. Fabryka definiuje teraz wyłącznie `name`, `description` oraz `discipline_id => Discipline::factory()`.

Potwierdzono kod fabryki, nie zapis rekordu. Przegląd `LeagueRelationshipsTest` i `LeaguePersistenceTest` wykazał testy usuniętych relacji i dotychczasowej unikalności zależnej od właściciela. Następny krok to dostosowanie testów relacji do dwóch zachowanych kierunków: liga → dyscyplina oraz dyscyplina → ligi. Testy i migracja nie zostały jeszcze uruchomione.

### Dostosowanie i sprawdzenie testów relacji

Użytkownik uruchomił `php artisan test --compact tests/Feature/LeagueRelationshipsTest.php` i przekazał wynik `2 passed (2 assertions)`, czas `0.32s`. Asystent odczytał plik: usunięto dwa testy relacji właściciela i dane `owner_id`; nieużywany import `User` nadal pozostał do usunięcia. Pozostały test relacji ligi do dyscypliny oraz test pobierania obu lig dyscypliny z pominięciem ligi innej dyscypliny.

Wynik pochodzi od użytkownika i potwierdza te dwie relacje w środowisku testowym. Nie potwierdza jeszcze reguł nowej unikalności, pełnego zestawu testów lig ani aktualizacji lokalnej bazy MySQL. Następny krok obejmuje testy unikalności w `LeaguePersistenceTest`.

### Testy nowej unikalności nazw

Użytkownik wykonał `php artisan test --compact tests/Feature/LeaguePersistenceTest.php --filter=test_league_name` i przekazał wynik `2 passed (2 assertions)`, czas `0.32s`. Asystent odczytał plik i potwierdził zastąpienie trzech testów unikalności zależnej od właściciela dwoma testami: blokady powtórzenia nazwy w tej samej dyscyplinie i dopuszczenia tej samej nazwy w różnych dyscyplinach. Potwierdzono również usunięcie nieużywanego importu `User` z `LeagueRelationshipsTest.php`.

Wynik testów pochodzi od użytkownika i dotyczy nowej unikalności w bazie testowej. W `LeaguePersistenceTest` nadal pozostały dwa nieaktualne testy właściciela i dane właściciela w teście nieistniejącej dyscypliny; ich dostosowanie jest następnym krokiem. Nie potwierdzono jeszcze całego zestawu testów lig, formatowania, analizy statycznej ani migracji lokalnej bazy MySQL.

### Pełny zestaw dotychczasowych testów lig po zmianie

Użytkownik uruchomił `php artisan test --compact tests/Feature/LeaguePersistenceTest.php tests/Feature/LeagueRelationshipsTest.php` i przekazał wynik `7 passed (12 assertions)`, czas `0.39s`. Asystent przejrzał diff testów i potwierdził usunięcie dwóch testów właściciela, importu `User` oraz danych właściciela z testu nieistniejącej dyscypliny. Zachowano test opcjonalnego opisu, ochronę klucza obcego dyscypliny i blokadę usunięcia używanej dyscypliny.

Wyszukanie w `app`, fabrykach, seederach i testach nie wykazało pozostałych odwołań do `owner_id` ani relacji `owner`; jedyne dopasowanie `->leagues` dotyczy prawidłowej relacji dyscypliny. Wynik testów pochodzi od użytkownika i dotyczy bazy testowej. Nie potwierdza jeszcze migracji lokalnej bazy MySQL ani całego zestawu testów aplikacji. Następny krok to formatowanie zmienionych plików PHP; analiza statyczna i końcowa weryfikacja pozostają do wykonania.

### Formatowanie zmienionych plików PHP

Użytkownik wykonał `vendor/bin/pint --dirty --format agent` i przekazał wynik `fixed`. Pint poprawił migrację (`class_definition`, `fully_qualified_strict_types`, `braces_position`) i `LeaguePersistenceTest.php` (`no_whitespace_in_blank_line`). Asystent odczytał migrację po formatowaniu: operacje `up()` i blokada `down()` pozostały zachowane; `RuntimeException` zapisano bez początkowego ukośnika w globalnej przestrzeni nazw. Własna kontrola `git diff --check` zakończyła się bez błędów.

Formatowanie nie jest dodatkowym testem zachowania migracji. Następny krok to analiza statyczna zgodnie z `phpstan.neon`. Wykonanie migracji lokalnej bazy MySQL i końcowe sprawdzenia nadal pozostają do wykonania.

### Analiza statyczna

Użytkownik uruchomił `vendor/bin/phpstan analyse --memory-limit=1G` i przekazał wynik `[OK] No errors` dla 25/25 plików. Polecenie użyło projektowego `phpstan.neon`, który obejmuje katalog `app` na poziomie 5. Wynik pochodzi od użytkownika; nie oznacza analizy migracji i testów, które są poza skonfigurowanym zakresem.

Następny krok to pełny zestaw testów aplikacji, ponieważ usunięto również relację z modelu `User`. Migracja lokalnej bazy MySQL nadal nie została potwierdzona.

### Pełny zestaw testów i sprawdzenie danych przed migracją

Użytkownik wykonał `php artisan test --compact` i przekazał wynik `129 passed (605 assertions)`, czas `20.06s`. Wynik dotyczy pełnego zestawu testów w środowisku testowym; nie potwierdza wykonania migracji lokalnego MySQL.

Asystent przed zaproponowaniem migracji ponownie odczytał jej kod oraz wykonał zapytania tylko do odczytu przez Boost. Tabela `leagues` zawierała 0 rekordów; zapytanie grupujące po `discipline_id, name` nie wykazało duplikatów. W tym stanie nie ma zapisanych właścicieli lig do utracenia ani danych kolidujących z nową unikalnością.

Następny krok to wykonanie przez użytkownika wyłącznie migracji `database/migrations/2026_11_17_164034_remove_owner_from_leagues_table.php` w lokalnej bazie. Wykonanie i wynik kontroli schematu pozostają do potwierdzenia. Automatyczny rollback tej migracji jest celowo zablokowany przez `down()`.

### Wykonanie migracji lokalnego MySQL i kontrola schematu

Użytkownik wykonał:

```bash
php artisan migrate --path=database/migrations/2026_11_17_164034_remove_owner_from_leagues_table.php --no-interaction
```

Przekazał wynik `DONE` dla wskazanej migracji, czas `34.87ms`. Asystent następnie niezależnie odczytał schemat przez Boost i potwierdził:

- brak kolumny `owner_id` i jej klucza obcego;
- unikalny indeks `leagues_discipline_id_name_unique` na `(discipline_id, name)`;
- zachowany klucz obcy `discipline_id` do `disciplines.id` z `ON DELETE RESTRICT`;
- zachowane pola `id`, `name`, `description`, `discipline_id`, `created_at` i `updated_at`.

Własna kontrola `git diff --check` przeszła bez błędów. Potwierdzono aktualizację struktury lokalnej bazy MySQL; nie sprawdzano formularzy lig, które nie zostały jeszcze wdrożone. Zmiana nie kończy całego issue #25. Zapis zmian w Git i ich publikacja pozostają do wykonania przez użytkownika.
