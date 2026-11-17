---
tags:
  - sport
---

# 42. Katalog lig w Filamencie

## Cel i zgłoszenie

Realizacja [#28 — katalog lig w Filamencie: lista i formularze](https://github.com/SebMagacy/sportowiada/issues/28), wydzielonego z nadrzędnego [#25](https://github.com/SebMagacy/sportowiada/issues/25). Zakres obejmuje listę, tworzenie, edycję i usuwanie lig oraz walidację i sprawdzenie dostępu.

## Punkt startowy

Commit `15bf1cf` opublikowano na `main`: usunięto właściciela ligi, relacje właściciela oraz dane właściciela w fabryce i testach. Migrację wykonano w lokalnym MySQL, a odczyt Boost potwierdził brak `owner_id`, unikalność `(discipline_id, name)` i zachowany klucz obcy dyscypliny z `RESTRICT`. Szczegóły opisuje [rozdział 41](41-uprawnienia-lig.md).

Użytkownik potwierdził dla poprzedniej zmiany 129 testów / 605 asercji, Pint i PHPStan bez błędów. Były to wyniki wcześniejszego etapu, przed wdrożeniem formularzy opisanych w tym rozdziale.

## Ustalenia tego etapu

- Nazwa wymagana, do 255 znaków; dokładnie jedna istniejąca dyscyplina; opis opcjonalny.
- Nazwa unikalna w obrębie dyscypliny; przy edycji pomijamy własny rekord.
- Bez właściciela i bez przywracania `owner_id`.
- Użytkownik zatwierdził dostęp do katalogu na tym etapie wyłącznie dla `super_admin`. Sama rola `panel_user` nie wystarcza. Kontrola ma działać po stronie serwera przez politykę, zgodnie z obecnym systemem Shield; implementację i jej sprawdzenie opisano poniżej.
- Delegowanie zarządzania organizatorom i redaktorom pozostaje odłożone. Projekty i ochrona historii są dalszym zakresem #25.

## Przebieg implementacji i wyniki kolejnych kroków

Poniższe wpisy opisują stan w chwili wykonywania kroku. Zapowiedzi sprawdzeń zostały zrealizowane w dalszych wpisach; aktualny stan i końcowe wyniki znajdują się na końcu rozdziału.

## Pierwszy krok — stan pliku polityki

Przekazano polecenie `php artisan make:policy LeaguePolicy --model=League --no-interaction`. Podczas przygotowania nowego czatu asystent potwierdził obecność nieśledzonego pliku `app/Policies/LeaguePolicy.php` i odczytał jego zawartość. To wygenerowany szkielet, nie gotowa autoryzacja. Nie otrzymano jeszcze od użytkownika wyniku polecenia; obecność pliku potwierdzono niezależnie.

## Ograniczenie dostępu do listy lig

Użytkownik potwierdził zapisanie zmiany, a asystent sprawdził kod `app/Policies/LeaguePolicy.php`. Metoda `viewAny(User $user): bool` zwraca teraz:

```php
return $user->hasRole('super_admin');
```

Celem jest dopuszczenie do listy lig wyłącznie użytkownika z rolą `super_admin` z istniejącego systemu Shield/Spatie. Sama rola `panel_user` nie spełnia tego warunku. Konfiguracja Shield ma `define_via_gate=false`; kontrola nie zakłada globalnego obejścia Gate. Wzorcem sprawdzania roli jest istniejąca metoda `UserPolicy::assignRoles()`.

Po tym kroku pozostałe metody polityki zwracały `false`. Potwierdzono wyłącznie zawartość kodu; testy autoryzacji i sprawdzenie działania w zasobie Filament pozostają do wykonania.

## Ograniczenie tworzenia lig

Użytkownik potwierdził kolejną zmianę, a asystent sprawdził kod: metoda `create(User $user): bool` w `app/Policies/LeaguePolicy.php` również zwraca `$user->hasRole('super_admin')`. Dostęp do listy i prawo tworzenia są osobnymi decyzjami polityki; oba wymagają teraz tej roli. Pozostałe metody nadal zwracają `false`. Działanie tworzenia nie zostało jeszcze sprawdzone testem ani w formularzu.

## Ograniczenie edycji lig

Użytkownik potwierdził zapisanie zmiany, a asystent sprawdził metodę `update(User $user, League $league): bool` w `app/Policies/LeaguePolicy.php`. Zwraca ona teraz `$user->hasRole('super_admin')`, ograniczając zgodę na edycję konkretnej ligi do tej roli. Parametr `$league` pozostaje w sygnaturze, choć obecna decyzja zależy wyłącznie od roli użytkownika. Potwierdzono kod; testy autoryzacji i działanie formularza edycji pozostają do sprawdzenia.

## Ograniczenie usuwania lig

Użytkownik potwierdził zmianę, a asystent odczytał aktualny kod `app/Policies/LeaguePolicy.php`: metoda `delete(User $user, League $league): bool` zwraca `$user->hasRole('super_admin')`. Prawo usunięcia konkretnej ligi wymaga więc tej roli. Potwierdzenie usuwania w akcji Filament oraz testy pozostają do wykonania. Metody `view()`, `restore()` i `forceDelete()` nadal zwracają `false`.

## Ograniczenie podglądu pojedynczej ligi

Użytkownik potwierdził zapisanie zmiany, a asystent sprawdził kod: metoda `view(User $user, League $league): bool` w `app/Policies/LeaguePolicy.php` zwraca `$user->hasRole('super_admin')`. Lista i podgląd pojedynczego rekordu mają osobne decyzje polityki, obecnie z takim samym warunkiem roli. Nie utworzono jeszcze strony podglądu. Metody `restore()` i `forceDelete()` nadal zwracają `false`. Potwierdzono zawartość kodu; zachowanie autoryzacji pozostaje do sprawdzenia testami.

## Usunięcie nieużywanego importu

Użytkownik potwierdził usunięcie `use Illuminate\Auth\Access\Response;` z `app/Policies/LeaguePolicy.php`. Asystent sprawdził importy: pozostały `League` i `User`. Metody polityki zwracają `bool`, dlatego import `Response` był zbędny. Formatowanie i testy pozostają do wykonania.

## Formatowanie polityki

Użytkownik wykonał `vendor/bin/pint --dirty --format agent` i przekazał wynik `{"tool":"pint","result":"passed"}`. Formatowanie zakończyło się poprawnie. Ten wynik nie potwierdza działania autoryzacji; testy polityki pozostają do wykonania.

## Utworzenie szkieletu zasobu Filament

Użytkownik wykonał `php artisan make:filament-resource League --panel=panel --record-title-attribute=name --embed-schemas --embed-table --no-interaction` i przekazał komunikat o poprawnym utworzeniu zasobu. Asystent potwierdził obecność `app/Filament/Resources/Leagues/LeagueResource.php` oraz stron `Pages/ListLeagues.php`, `Pages/CreateLeague.php` i `Pages/EditLeague.php`.

Generator umieścił formularz i tabelę w klasie zasobu, zgodnie z układem istniejącego `DisciplineResource`. Wygenerowany formularz zawiera tylko wymaganą nazwę do 255 znaków, a tabela kolumnę nazwy i domyślne akcje. Brakuje jeszcze m.in. dyscypliny, opisu, unikalności nazwy w obrębie dyscypliny i grupy nawigacji Sport. Nie potwierdzono działania katalogu ani jego autoryzacji w interfejsie. Domyślna akcja usuwania zbiorczego wymaga przeglądu przed ukończeniem zasobu.

## Grupa nawigacji Sport

Użytkownik dodał do `LeagueResource` import `UnitEnum` i właściwość `protected static string|UnitEnum|null $navigationGroup = 'Sport';`. Asystent potwierdził zapis kodu, zgodny z katalogami dyscyplin i sezonów.

## Polskie tłumaczenia katalogu lig

Użytkownik potwierdził utworzenie `lang/pl/leagues.php`, a asystent odczytał plik. Zawiera klucze `title` (Ligi), `singular` (liga), `name` (Nazwa), `discipline` (Dyscyplina) i `description` (Opis). Przygotowano nazwy do wykorzystania przez zasób i formularz; samo utworzenie pliku nie zmienia jeszcze etykiet w interfejsie.

Następnie użytkownik potwierdził dodanie metod `getPluralModelLabel(): string` i `getModelLabel(): string` w `LeagueResource`. Asystent sprawdził kod: metody zwracają odpowiednio `__('leagues.title')` i `__('leagues.singular')`. Podłączono w ten sposób nazwy zasobu „Ligi” i „liga”; wyświetlenie w interfejsie pozostaje do sprawdzenia.

## Etykieta nazwy w formularzu

Asystent potwierdził w kodzie `LeagueResource::form()` dodanie `->label(__('leagues.name'))` do pola `TextInput::make('name')`, z zachowaniem `required()` i `maxLength(255)`. Po zgłoszeniu widocznej etykiety „Name” ustawiono tę samą etykietę osobno na kolumnie `TextColumn::make('name')` w `table()`. Asystent potwierdził obie deklaracje w kodzie, a użytkownik potwierdził, że zarówno formularz, jak i nagłówek tabeli wyświetlają „Nazwa”. To sprawdzenie etykiet nie potwierdza zapisu formularza ani autoryzacji.

## Wybór dyscypliny w formularzu

Asystent potwierdził import `Filament\Forms\Components\Select` oraz pole `Select::make('discipline_id')` w `LeagueResource::form()`. Pole korzysta z etykiety `__('leagues.discipline')`, relacji `relationship('discipline', 'name')` i metod `required()`, `searchable()` oraz `preload()`. Pozwala wybrać jedną dyscyplinę po nazwie i przypisać jej identyfikator do ligi.

Użytkownik potwierdził działanie wyboru dyscypliny w formularzu. Polecono na tym kroku nie zapisywać ligi; wynik dotyczy wyboru, nie poprawności zapisu ani pełnej walidacji.

## Opcjonalny opis ligi

Użytkownik potwierdził wykonanie kroku dodającego opis, a asystent sprawdził kod `LeagueResource`: import `Filament\Forms\Components\Textarea` i pole `Textarea::make('description')->label(__('leagues.description'))->rows(5)` po wyborze dyscypliny. Pole nie ma `required()`, więc opis jest opcjonalny. Użytkownik potwierdził sprawdzenie pola zgodnie z poleceniem; nie potwierdzono jeszcze zapisu ligi ani testów formularza.

## Kolumna dyscypliny na liście lig

Użytkownik potwierdził dodanie kolumny, a asystent sprawdził w `LeagueResource::table()` deklarację `TextColumn::make('discipline.name')->label(__('leagues.discipline'))->searchable()`. Kolumna pobiera nazwę przez relację `discipline`. Potwierdzono kod; wyświetlanie danych i wyszukiwanie pozostają do sprawdzenia po utworzeniu ligi.

## Unikalność nazwy w obrębie dyscypliny

Użytkownik potwierdził zmianę, a asystent sprawdził pole nazwy w `LeagueResource::form()`. Dodano importy `Filament\Schemas\Components\Utilities\Get` i `Illuminate\Validation\Rules\Unique` oraz regułę `unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where('discipline_id', $get('discipline_id')))`. Warunek ogranicza sprawdzanie nazwy do wybranej dyscypliny, a `ignoreRecord: true` pomija edytowany rekord. Nadal obowiązują `required()` i `maxLength(255)`.

Potwierdzono zawartość kodu; zapis, odrzucenie duplikatu, ta sama nazwa w innej dyscyplinie i edycja bez konfliktu z własnym rekordem pozostają do sprawdzenia. Formatowanie zasobu po kolejnych zmianach również pozostaje do wykonania.

## Pierwsze sprawdzenie ręczne tworzenia ligi

Po poleceniu utworzenia ligi z pustym opisem użytkownik przekazał zrzut listy lig. Zrzut potwierdza obecność rekordu „Liga testowa” z dyscypliną „Futsal” oraz polskie nagłówki „Nazwa” i „Dyscyplina”. Sam zrzut nie pokazuje wartości opisu ani roli zalogowanego użytkownika. Nie potwierdza też odrzucenia błędnych danych, edycji lub ograniczeń dostępu.

Użytkownik uzgodnił wykonanie kilku sprawdzeń ręcznych i automatycznych. Rozpoczęcie testów automatycznych przyspieszono względem wcześniejszego planu odkładania ich do końca przygotowania widoków.

## Przygotowanie pliku testów katalogu

Użytkownik wykonał `php artisan make:test --phpunit FilamentLeagueTest --no-interaction` i przekazał komunikat powodzenia. Asystent potwierdził obecność `tests/Feature/FilamentLeagueTest.php` z domyślnym `test_example()`. Jest to szkielet, który nie sprawdza jeszcze katalogu lig. Pierwszy właściwy test i jego uruchomienie pozostają do wykonania.

## Automatyczny test tworzenia ligi bez opisu

Szkielet zastąpiono testem `test_super_admin_can_create_league_without_description()`; asystent potwierdził aktualny kod. Test korzysta z `LazilyRefreshDatabase`, ustawia panel `panel`, loguje użytkownika z `createPanelAdministrator()` i tworzy dyscyplinę fabryką. Następnie przez `Livewire::test(CreateLeague::class)` wypełnia formularz nazwą „Liga testowa”, identyfikatorem dyscypliny i opisem `null`, wywołuje `create` oraz sprawdza brak błędów formularza. Asercje bazy potwierdzają wartości zapisanego rekordu i dokładnie jedną ligę.

Użytkownik wykonał `php artisan test --compact tests/Feature/FilamentLeagueTest.php`: **1 test przeszedł, 5 asercji**, czas 0,79 s. Konfiguracja `phpunit.xml` używa SQLite `:memory:`. Wynik potwierdza tworzenie ligi bez opisu przez superadministratora; nie potwierdza jeszcze odrzucania duplikatów, innych błędnych danych ani odmowy dostępu dla innych ról. Formatowanie nowego testu pozostaje do wykonania.

## Ręczne sprawdzenie duplikatu nazwy

Użytkownik przekazał zrzut formularza po próbie ponownego utworzenia „Ligi testowej” z dyscypliną „Futsal” i pustym opisem. Widoczny komunikat przy nazwie: „Taki nazwa już występuje.” Formularz pozostał na stronie tworzenia z błędem pola. Potwierdzono wyświetlenie błędu walidacji duplikatu; brak dodatkowego rekordu w bazie zostanie sprawdzony testem automatycznym. Brzmienie komunikatu przytoczono zgodnie ze zrzutem.

## Automatyczny test odrzucenia duplikatu

Asystent sprawdził dodany przez użytkownika test `test_duplicate_name_in_same_discipline_is_rejected()` w `tests/Feature/FilamentLeagueTest.php`. Test loguje superadministratora, tworzy ligę fabryką i próbuje przez formularz `CreateLeague` utworzyć drugą o tej samej nazwie i `discipline_id`. Sprawdza błąd `unique` przy nazwie, brak przekierowania i pozostawienie dokładnie jednego rekordu w tabeli `leagues`.

Użytkownik wykonał `php artisan test --compact tests/Feature/FilamentLeagueTest.php`: **2 testy przeszły, 12 asercji**, czas 0,94 s. Wynik potwierdza również, że próba duplikatu nie dodała drugiego rekordu w bazie testowej. Ta sama nazwa w innej dyscyplinie pozostaje do sprawdzenia.

## Ręczne sprawdzenie tej samej nazwy w różnych dyscyplinach

Użytkownik przekazał zrzut listy po utworzeniu kolejnej ligi. Widoczne są dwa rekordy „Liga testowa”: jeden z dyscypliną „Futsal”, drugi z „Koszykówką”. Potwierdza to ręcznie możliwość utworzenia lig o tej samej nazwie w różnych dyscyplinach i wyświetlanie przypisanych dyscyplin w tabeli. Automatyczny test tego przypadku pozostaje do dodania i uruchomienia.

## Automatyczny test tej samej nazwy w różnych dyscyplinach

Asystent sprawdził dodaną metodę `test_same_name_is_allowed_in_different_disciplines()` w `FilamentLeagueTest`. Test tworzy istniejącą ligę i odrębną dyscyplinę, a następnie jako superadministrator zapisuje przez `CreateLeague` tę samą nazwę z identyfikatorem nowej dyscypliny. Sprawdza brak błędów formularza, zachowanie istniejącego rekordu, zapis nowego przypisania i dokładnie dwie ligi w bazie testowej.

Użytkownik wykonał `php artisan test --compact tests/Feature/FilamentLeagueTest.php`: **3 testy przeszły, 18 asercji**, czas 1,23 s. Potwierdzono automatycznie dozwoloną powtarzalność nazwy między dyscyplinami. Edycja, pozostała walidacja, usuwanie i odmowy dostępu pozostają do sprawdzenia.

## Ręczne sprawdzenie edycji bez zmiany nazwy

Użytkownik potwierdził sukces po edycji „Ligi testowej” z dyscypliną „Futsal”: pozostawieniu nazwy i dyscypliny bez zmian, wpisaniu opisu „Opis testowy ligi futsalowej”, zapisaniu i ponownym otwarciu edycji. Zgodnie z przekazanym wynikiem zapis nie zgłosił konfliktu nazwy z własnym rekordem, a opis pozostał zachowany. Test automatyczny tego przypadku pozostaje do dodania i uruchomienia.

## Automatyczny test edycji bez zmiany nazwy

Asystent sprawdził metodę `test_edit_allows_unchanged_name_and_saves_description()` w `FilamentLeagueTest`. Test jako superadministrator otwiera `EditLeague` dla ligi utworzonej fabryką, sprawdza początkową nazwę i dyscyplinę, zmienia opis, wywołuje `save` i sprawdza brak błędów formularza. Asercje bazy potwierdzają zapis opisu, zachowanie identyfikatora, nazwy i dyscypliny oraz dokładnie jeden rekord.

Użytkownik wykonał `php artisan test --compact tests/Feature/FilamentLeagueTest.php`: **4 testy przeszły, 26 asercji**, czas 1,57 s. Potwierdzono zapis edycji bez konfliktu z własną nazwą. Konflikt z inną ligą podczas edycji pozostaje do sprawdzenia.

## Ręczne sprawdzenie konfliktu po zmianie dyscypliny

Po poleceniu zmiany dyscypliny „Ligi testowej” z Koszykówki na Futsal użytkownik przekazał zrzut strony edycji. Widoczne są nazwa „Liga testowa”, wybrany Futsal i błąd zajętej nazwy. Potwierdzono wyświetlenie walidacji podczas edycji. Zrzut nie pokazuje listy ani stanu bazy po odrzuconym zapisie; zachowanie pierwotnej dyscypliny zostanie sprawdzone automatycznie.

## Automatyczny test konfliktu po zmianie dyscypliny

Asystent sprawdził dodany test `test_edit_rejects_duplicate_after_changing_discipline()`. Test tworzy fabryką dwie ligi o tej samej nazwie z odrębnymi dyscyplinami. Jako superadministrator próbuje przypisać edytowaną ligę do dyscypliny istniejącej ligi, wywołuje `save` i sprawdza błąd `unique`. Asercje bazy potwierdzają zachowanie pierwotnej nazwy i dyscypliny edytowanego rekordu oraz dokładnie dwie ligi.

Użytkownik wykonał `php artisan test --compact tests/Feature/FilamentLeagueTest.php`: **5 testów przeszło, 33 asercje**, czas 1,84 s. Potwierdzono odrzucenie konfliktu przy zmianie dyscypliny i zachowanie danych w bazie testowej. Pozostała walidacja, usuwanie i odmowy dostępu pozostają do sprawdzenia.

## Ręczne sprawdzenie wymaganych pól

Użytkownik przekazał dwa zrzuty formularza tworzenia. Przy pustej nazwie przeglądarka pokazuje „Proszę wypełnić to pole”. Po wpisaniu nazwy „asdf” i pozostawieniu dyscypliny pustej formularz pokazuje „Pole dyscyplina jest wymagane.” Potwierdzono ręcznie blokadę pustej nazwy w przeglądarce oraz komunikat braku dyscypliny w formularzu. Walidację obu wymaganych pól po stronie serwera i brak zapisu sprawdzimy automatycznie.

## Automatyczny test wymaganych pól

Asystent sprawdził metodę `test_name_and_discipline_are_required()` w `FilamentLeagueTest`. Test jako superadministrator wysyła przez `CreateLeague` pustą nazwę i `discipline_id=null`. Sprawdza błędy `required` obu pól, brak przekierowania i brak rekordów w tabeli `leagues`.

Użytkownik wykonał `php artisan test --compact tests/Feature/FilamentLeagueTest.php`: **6 testów przeszło, 42 asercje**, czas 2,15 s. Potwierdzono wymaganie nazwy i dyscypliny po stronie serwera oraz brak zapisu przy tych błędach.

## Uzupełnienie testów automatycznych na zlecenie użytkownika

Użytkownik wyraźnie zlecił asystentowi przygotowanie wszystkich pozostałych testów. Asystent rozszerzył istniejący `tests/Feature/FilamentLeagueTest.php`, zachowując dotychczasowe sześć testów. Dodane sprawdzenia obejmują:

- zapis nazwy o długości 255 znaków (także polskich) i wielowierszowego opisu oraz odczyt formularza edycji;
- odrzucenie 256 znaków i nieistniejącej dyscypliny przy tworzeniu;
- odrzucenie pustej nazwy, zbyt długiej nazwy, pustej i nieistniejącej dyscypliny przy edycji, z zachowaniem zapisanych danych;
- poprawną zmianę nazwy i dyscypliny oraz wyczyszczenie opisu;
- odrzucenie zmiany nazwy na zajętą w tej samej dyscyplinie;
- wyświetlanie nazw lig i dyscyplin oraz wyszukiwanie według obu kolumn;
- oczekiwanie usuwania na potwierdzenie, anulowanie bez usunięcia oraz zatwierdzone usunięcie ligi z zachowaniem dyscypliny;
- przekierowanie gościa do logowania, odmowę dostępu do listy/tworzenia/edycji użytkownikowi bez roli i `panel_user`, dostęp superadministratora;
- macierz decyzji Gate dla `viewAny`, `view`, `create`, `update`, `delete` oraz odmowę `restore` i `forceDelete`;
- odmowę operacji użytkownikowi `panel_user` nawet z jawnymi uprawnieniami `ViewAny:League`, `View:League`, `Create:League`, `Update:League` i `Delete:League`.

Własne uruchomienie asystenta przed końcowym uporządkowaniem dostawcy danych: **26 przypadków przeszło, 168 asercji**, około 6,84 s. Nie ujawniono potrzeby zmiany implementacji aplikacji. Dostawca danych został następnie uproszczony, a ponowna weryfikacja odbywa się w pełnym zestawie testów.

Asystent wykonał `vendor/bin/pint --dirty --format agent` (uporządkowano importy testu; kolejne uruchomienie `passed`), `vendor/bin/phpstan analyse --no-progress` (**passed, 0 błędów**, konfiguracja `app`, poziom 5) i `git diff --check` (bez błędów). PHPStan w tej konfiguracji nie analizuje katalogu `tests`.

Po ostatniej zmianie testów asystent wykonał `php artisan test --compact`: **155 testów przeszło, 773 asercje**, około 27,45 s. Pełne uruchomienie obejmuje także wszystkie 26 przypadków katalogu lig i potwierdza je po uproszczeniu dostawcy danych.

Sprawdzenia automatyczne nie zastępują pozostałych sprawdzeń ręcznych okna usuwania i dostępu. Nie wykonano commita, push ani zamknięcia #28.

## Ręczne sprawdzenie anulowania usuwania

Użytkownik potwierdził sukces po otwarciu edycji „Ligi testowej” z dyscypliną „Koszykówka”, kliknięciu „Usuń”, anulowaniu w oknie potwierdzenia i powrocie do listy. Potwierdzono pojawienie się okna potwierdzenia oraz zachowanie ligi po anulowaniu. Ręczne zatwierdzenie usunięcia i sprawdzenie dostępu innych użytkowników pozostają do wykonania.

## Ręczne sprawdzenie zatwierdzonego usunięcia

Użytkownik potwierdził sukces po ponownym otwarciu edycji ligi koszykarskiej i zatwierdzeniu usunięcia w oknie dialogowym. Zgodnie z wynikiem liga przypisana do Koszykówki zniknęła z listy, a „Liga testowa” dla Futsalu pozostała. Potwierdzono ręcznie zatwierdzony przebieg usuwania; sprawdzenie dostępu innych użytkowników pozostaje do wykonania.

## Ręczne sprawdzenie dostępu panel_user

Użytkownik potwierdził sukces sprawdzenia konta z rolą wyłącznie `panel_user` w osobnym oknie prywatnym. Zgodnie z kryterium przekazanego kroku katalog „Ligi” nie był widoczny w menu, a wejście bezpośrednio na `/admin/leagues` zwróciło odmowę 403. Potwierdza to ręcznie ograniczenie dostępu do katalogu mimo możliwości wejścia do panelu.

## Uwagi użytkownika przed końcową weryfikacją

Użytkownik potwierdził wykonanie tłumaczenia nagłówka katalogu i kolumny nazwy. Przed końcowym formatowaniem i publikacją zgłosił dodatkowo do wykonania: zmianę ikony lig, angielskie nazwy katalogu i pól, przekierowanie po zapisie edycji na listę oraz poprawę komunikatu „Taki nazwa już występuje.”

Odczyt asystenta potwierdził ikonę `Heroicon::OutlinedRectangleStack`, pusty plik `lang/en/leagues.php`, brak własnego `getRedirectUrl()` w `EditLeague` oraz ogólny komunikat `unique` w `lang/pl/validation.php` o treści `Taki :attribute już występuje.`. Są to ustalenia do poprawek, nie wykonane zmiany.

## Zmiana ikony katalogu lig

Użytkownik potwierdził wykonanie kroku i sprawdzenie ikony w panelu. Asystent odczytał `LeagueResource`: właściwość `$navigationIcon` ma teraz wartość `Heroicon::OutlinedTrophy`. Katalog lig korzysta z ikony pucharu zamiast domyślnego stosu prostokątów.

## Polskie nazwy, tytuły i przycisk tworzenia

Po omówieniu odmiany użytkownik ustawił `singular => liga`, a pełne polecenia wydzielił do `create => Dodaj ligę` i `edit => Edytuj ligę`. Przywrócił również przypadkowo pominięty wpis `title => Ligi`. Asystent potwierdził kod i komplet kluczy.

W `Pages/CreateLeague.php` metoda `getTitle(): string` zwraca `__('leagues.create')`, a w `Pages/EditLeague.php` analogiczna metoda zwraca `__('leagues.edit')`. W `Pages/ListLeagues.php` akcja tworzenia ma `->label(__('leagues.create'))`. Użytkownik potwierdził ręcznie tytuły „Dodaj ligę” i „Edytuj ligę” oraz działanie przycisku „Dodaj ligę”. Pełne polecenia zapobiegają automatycznemu łączeniu czasownika z nieodmienioną nazwą modelu.

## Angielskie tłumaczenia katalogu lig

Asystent potwierdził uzupełniony przez użytkownika `lang/en/leagues.php`: `title => Leagues`, `singular => league`, `create => Add league`, `edit => Edit league`, `name => Name`, `discipline => Discipline`, `description => Description`. Zestaw zawiera odpowiedniki wszystkich kluczy polskich. Nie wykonano ręcznego sprawdzenia całego interfejsu po przełączeniu języka na angielski.

## Przekierowanie po zapisie edycji

Użytkownik dodał do `EditLeague`:

```php
protected function getRedirectUrl(): string
{
    return $this->getResource()::getUrl('index');
}
```

Asystent sprawdził kod, a użytkownik potwierdził ręcznie powrót do listy po zmianie opisu i zapisaniu. Przekierowanie dotyczy udanego zapisu. Test `test_edit_allows_unchanged_name_and_saves_description()` rozszerzono o `assertRedirect(LeagueResource::getUrl('index'))` po `assertHasNoFormErrors()`. Po poprawieniu średnika pomiędzy wywołaniami użytkownik uruchomił testy katalogu: **26 przypadków przeszło, 170 asercji**, czas 6,44 s. Asystent potwierdził asercję w kodzie.

## Poprawa polskiego komunikatu unikalności

Użytkownik wybrał i zapisał w `lang/pl/validation.php`:

```php
'unique' => 'W bazie istnieje już taki wpis.',
```

Asystent potwierdził kod. Komunikat zastępuje niepoprawne gramatycznie „Taki nazwa już występuje.” i dotyczy ogólnego polskiego tłumaczenia reguły `unique`, także poza katalogiem lig. Użytkownik potwierdził ręcznie, że próba ponownego utworzenia „Ligi testowej” dla Futsalu pokazuje nowe brzmienie i blokuje zapis.

## Końcowa weryfikacja po poprawkach interfejsu

Wyniki przekazane przez użytkownika po ostatnich zmianach:

| Polecenie | Potwierdzony wynik |
| --- | --- |
| `vendor/bin/pint --dirty --format agent` | `fixed`: usunięto odstępy w pustej linii testu (`no_whitespace_in_blank_line`), bez błędów |
| `vendor/bin/phpstan analyse --no-progress` | `[OK] No errors`; konfiguracja `app`, poziom 5 |
| `php artisan test --compact` | **155 testów przeszło, 775 asercji**, czas 26,30 s |

Asystent wykonał dodatkowo `git diff --check`: bez błędów. PHPStan w obecnej konfiguracji nie analizuje katalogu `tests`; testy zostały wykonane przez PHPUnit.

## Stan końcowy etapu przed publikacją

- Katalog korzysta z modelu `League`, grupy nawigacji `Sport`, ikony pucharu i kolumn nazwy oraz dyscypliny.
- Formularz zawiera wymaganą nazwę do 255 znaków, jedną istniejącą dyscyplinę i opcjonalny opis. Nie ma pola właściciela ani automatycznego przypisywania użytkownika.
- Unikalność obowiązuje w obrębie dyscypliny, a edycja pomija własny rekord. Testy obejmują także nieistniejącą dyscyplinę i zachowanie danych po błędzie.
- Dostęp pozostaje wyłącznie dla `super_admin`. Sprawdzono automatycznie operacje i strony; użytkownik potwierdził ręcznie niewidoczność katalogu i odmowę 403 dla `panel_user`.
- Usuwanie pojedynczej ligi wymaga potwierdzenia. Użytkownik sprawdził anulowanie i zatwierdzenie; testy potwierdzają również zachowanie dyscypliny po usunięciu ligi.
- Generator pozostawił deklarację `DeleteBulkAction`; polityka nie przyznaje `deleteAny`. Ten etap potwierdza usuwanie pojedyncze, nie udostępnia usuwania zbiorczego.
- Polski interfejs i formularze sprawdzono ręcznie w opisanych scenariuszach. Angielskie tłumaczenia przygotowano i odczytano; przełączenia całego panelu na angielski nie potwierdzono.
- Projekty, ochrona lig powiązanych z projektami i delegowanie zarządzania organizatorom/redaktorom pozostają poza zakresem #28, w dalszym etapie #25.

Pozostają przygotowanie commita, publikacja i aktualizacja zgłoszenia #28 po potwierdzeniu publikacji. Nie wykonano ich na podstawie samych wyników testów. Ten rozdział nadal jest nieśledzonym plikiem przed dodaniem do Git.

Prowadzimy po polsku, po jednym małym kroku. Użytkownik wykonuje kod, polecenia i Git; wyjątkiem było wyraźne zlecenie asystentowi rozszerzenia testów. Asystent aktualizuje istniejącą dokumentację po potwierdzonych krokach. Nie utworzono nowej gałęzi ani worktree i nie zmieniono zależności.
