---
tags:
  - sport
---

# 37. Model i tabela lig

## Cel i zakres

Druga część [issue #25](https://github.com/SebMagacy/sportowiada/issues/25): przygotowanie modelu i tabeli lig zgodnie z [zatwierdzonymi założeniami](36-zalozenia-katalogu-lig.md).

Planowane dane: wymagana nazwa do 255 znaków, opcjonalny opis, jedna dyscyplina i właściciel. Nazwa ma być unikalna łącznie z właścicielem i dyscypliną. Powiązania w bazie muszą wskazywać istniejące rekordy. Metody relacji Eloquent należą do kolejnej części, a obsługa uprawnień i formularze do późniejszych części.

## 1. Wygenerowanie plików

Polecenie dla użytkownika w terminalu projektu:

```bash
php artisan make:model League --migration --factory --seed --no-interaction
```

Składnię sprawdzono wcześniej przez Boost `search-docs` dla Laravel 13 i lokalne `php artisan make:model --help`. Polecenie przygotowuje cztery szkielety:

- `app/Models/League.php` — model Eloquent ligi;
- migrację `create_leagues_table` w `database/migrations/` — definicję przyszłej tabeli;
- `database/factories/LeagueFactory.php` — fabrykę danych testowych;
- `database/seeders/LeagueSeeder.php` — miejsce na ewentualne dane początkowe.

Samo wygenerowanie plików nie tworzy tabeli i nie zapisuje lig w bazie. Nie uruchamiamy jeszcze migracji ani seedera.

Użytkownik przekazał cztery komunikaty `created successfully`. Asystent odczytał i potwierdził wszystkie cztery pliki: model z `HasFactory`, pustą definicję fabryki, migrację `database/migrations/2026_11_16_125327_create_leagues_table.php` z `id()` i `timestamps()` oraz seeder bez danych początkowych. Kryterium wygenerowania szkieletów zostało spełnione. Nie potwierdzono wykonania migracji ani zapisu lig.

## 2. Wymagana nazwa ligi

Plik: `database/migrations/2026_11_16_125327_create_leagues_table.php`.

Między `id()` a `timestamps()` użytkownik dodał:

```php
$table->string('name');
```

Kolumna przechowa wymaganą nazwę do 255 znaków. Nie ma globalnego indeksu unikalności: docelowo ograniczenie obejmie łącznie właściciela, dyscyplinę i nazwę. Użytkownik potwierdził edycję odpowiedzią „ok. sukces”, a asystent niezależnie odczytał fragment w pliku. Potwierdzono kod, nie utworzenie kolumny w bazie ani działanie zapisu.

Początkowo podano i potwierdzono zapis z jawnym `255`. Po pytaniu użytkownika pominięto ten argument, zgodnie z migracjami sezonów i dyscyplin. Asystent potwierdził aktualny zapis `string('name')`; nie znaleziono zmiany domyślnej długości przez `defaultStringLength` w `app/` ani `bootstrap/`.

## 3. Opcjonalny opis ligi

W tej samej migracji, pod nazwą, użytkownik dodał:

```php
$table->text('description')->nullable();
```

`text()` definiuje kolumnę na dłuższą treść, a `nullable()` pozwala przechować brak opisu jako `NULL`, zgodnie z istniejącą migracją opisu dyscyplin. Asystent potwierdził kod odczytem pliku po odpowiedzi użytkownika „ok. dalej”. Nie uruchamiano migracji ani zapisu rekordu.

## 4. Powiązanie z dyscypliną

Pod opisem użytkownik dodał:

```php
$table->foreignId('discipline_id')
    ->constrained()
    ->restrictOnDelete();
```

`foreignId()` definiuje wymagany identyfikator, a `constrained()` klucz obcy do `disciplines.id`, zgodnie z nazwą kolumny. `restrictOnDelete()` blokuje usunięcie dyscypliny używanej przez ligę. Nie jest to jeszcze zabezpieczenie zmiany dyscypliny ligi mającej projekty — to późniejszy zakres.

Użytkownik potwierdził edycję, a asystent odczytał fragment w migracji. Przed podaniem kodu sprawdzono schemat dyscyplin przez Boost oraz dokumentację kluczy obcych Laravel 13. Potwierdzono definicję w pliku, nie działanie ograniczenia w bazie.

## 5. Powiązanie z właścicielem

Przed `timestamps()` użytkownik dodał:

```php
$table->foreignId('owner_id')
    ->constrained('users')
    ->restrictOnDelete();
```

Klucz jest wymagany i wskazuje `users.id`. Tabelę `users` podano jawnie, ponieważ nazwa `owner_id` sugerowałaby tabelę `owners`. `restrictOnDelete()` chroni przed usunięciem konta będącego właścicielem ligi i pozostawieniem ligi bez właściciela. Przed podaniem kodu sprawdzono schemat `users` przez Boost.

Użytkownik potwierdził edycję, a asystent odczytał kod w migracji. Nie sprawdzono jeszcze działania klucza w bazie. Automatyczne przypisanie twórcy pozostaje późniejszym krokiem; sama kolumna nie realizuje tego zachowania.

## 6. Unikalność nazwy dla właściciela i dyscypliny

Pod `timestamps()` dodano:

```php
$table->unique(['owner_id', 'discipline_id', 'name']);
```

Indeks unikalny obejmuje zestaw trzech wartości. Nie dopuszcza dwóch lig o tej samej nazwie dla tego samego właściciela i dyscypliny. Inny właściciel lub inna dyscyplina pozwalają na użycie tej samej nazwy. Asystent potwierdził zapis odczytem migracji po odpowiedzi użytkownika „ok. co dalej?”. Składnię indeksu złożonego sprawdzono przez Boost dla Laravel 13. Nie wykonano jeszcze próby zapisu duplikatu.

Wyjaśniono również możliwość późniejszej zmiany zasady przez nową migrację, dostosowanie walidacji i testów. Zaostrzenie unikalności wymaga uprzedniego rozwiązania ewentualnych konfliktów w istniejących danych; nie edytujemy wtedy historycznej, wdrożonej migracji.

## 7. Wykonanie migracji

Użytkownik wykonał `php artisan migrate --no-interaction` i przekazał wynik `2026_11_16_125327_create_leagues_table ... 68.67ms DONE`.

Asystent niezależnie odczytał schemat przez Boost i potwierdził tabelę `leagues`: `id`, wymagane `name` typu `varchar(255)`, opcjonalne `description` typu `text`, wymagane `discipline_id` i `owner_id` oraz znaczniki czasu. Istnieje unikalny indeks `leagues_owner_id_discipline_id_name_unique` na trzech kolumnach. Oba klucze obce wskazują odpowiednio `disciplines.id` i `users.id`, z `ON DELETE RESTRICT`.

Potwierdzono wykonanie migracji i strukturę bazy. Nie wykonano prób zapisu duplikatów, usuwania powiązanych rekordów ani rollbacku. Nie uruchamiano seedera ani testów lig.

## 8. Masowe przypisanie w modelu

Plik: `app/Models/League.php`.

Użytkownik dodał import `Illuminate\Database\Eloquent\Attributes\Fillable` oraz nad klasą:

```php
#[Fillable(['name', 'description', 'discipline_id'])]
```

Te pola można przekazywać do `fill()`, `create()` i `update()`. `owner_id` nie znajduje się na liście: właściciela przypiszemy osobno na podstawie zalogowanego twórcy. Samo `Fillable` nie waliduje danych ani nie sprawdza uprawnień, w tym przyszłej blokady zmiany dyscypliny. Składnię sprawdzono w dokumentacji Laravel 13 przez Boost, zgodnie z konwencją modeli dyscyplin i sezonów.

Użytkownik potwierdził krok odpowiedzią „Sukces”, a asystent odczytał import i atrybut w modelu. Nie wykonywano próby zapisu. Formatowanie pozostaje do wykonania.

## 9. Fabryka danych testowych

Plik: `database/factories/LeagueFactory.php`.

Użytkownik dodał importy `App\Models\Discipline` i `App\Models\User`, a w `definition()`:

```php
return [
    'name' => fake()->words(3, true),
    'description' => null,
    'discipline_id' => Discipline::factory(),
    'owner_id' => User::factory(),
];
```

Fabryka przygotowuje nazwę z trzech słów, brak opisu oraz powiązane rekordy dyscypliny i właściciela. W testach można nadpisywać wartości, aby sprawdzać wspólnego właściciela, dyscyplinę i konflikty nazw. Fabryki mogą przypisywać także pola niewymienione w `Fillable`; nie jest to mechanizm przypisywania właściciela w formularzu aplikacji. Składnię zagnieżdżonych fabryk sprawdzono przez Boost dla Laravel 13.

Użytkownik potwierdził edycję odpowiedzią „ok. sukces”, a asystent niezależnie odczytał i potwierdził importy oraz tablicę w fabryce. Samo zapisanie pliku nie potwierdza wykonania fabryki ani utworzenia lig w bazie. Nie uruchamiano seedera.

## 10. Formatowanie PHP

Użytkownik wykonał `vendor/bin/pint --dirty --format agent` i przekazał wynik `fixed`. Pint uporządkował importy fabryki, usunął nieużywany import seedera i zastąpił pełną nazwę `LeagueFactory` importem w modelu, wraz z uporządkowaniem importów. W lokalnie zmienionym `UserResource.php` poprawił formatowanie funkcji strzałkowej. Asystent odczytał model i diff zasobu: wcześniejsze dodanie `searchable()` przy nazwie i e-mailu pozostało zachowane. Zasób użytkowników nie należy do commita lig.

Wynik Pinta potwierdza formatowanie, nie działanie fabryki ani ograniczeń bazy. Następny krok to wygenerowanie pliku testów `LeaguePersistenceTest` według konwencji istniejących testów sezonów i dyscyplin.

## 11. Wygenerowanie pliku testów

Użytkownik wykonał `php artisan make:test --phpunit LeaguePersistenceTest --no-interaction` i przekazał komunikat o utworzeniu `tests/Feature/LeaguePersistenceTest.php`. Asystent odczytał plik: zawiera domyślny `test_example()` sprawdzający stronę główną, bez testów lig. Następny krok to zastąpienie przykładu testem konfliktu nazwy dla tego samego właściciela i dyscypliny.

Sprawdzono istniejące testy sezonów i dyscyplin oraz `phpunit.xml`: testy korzystają z SQLite w pamięci i nie powinny zmieniać lokalnej bazy MySQL. Przyjmujemy `LazilyRefreshDatabase`, zgodnie z konwencją projektu. Nie uruchamiano jeszcze testu lig.

## 12. Test konfliktu nazwy ligi

Użytkownik zastąpił wygenerowany przykład w `tests/Feature/LeaguePersistenceTest.php` testem `test_same_owner_cannot_repeat_a_league_name_in_the_same_discipline()`. Asystent odczytał i potwierdził kod:

```php
public function test_same_owner_cannot_repeat_a_league_name_in_the_same_discipline(): void
{
    $league = League::factory()->create([
        'name' => 'Liga Amatorska',
    ]);

    $this->expectException(UniqueConstraintViolationException::class);

    League::factory()->create([
        'name' => 'Liga Amatorska',
        'owner_id' => $league->owner_id,
        'discipline_id' => $league->discipline_id,
    ]);
}
```

Klasa używa `LazilyRefreshDatabase`. Pierwszy zapis tworzy ligę wraz z danymi fabryk; drugi wykorzystuje tę samą nazwę, właściciela i dyscyplinę. Oczekiwany `UniqueConstraintViolationException` sprawdza zakaz duplikatu. Test nie obejmuje jeszcze dozwolonych powtórzeń przy innym właścicielu lub dyscyplinie ani ochrony kluczy obcych. Potwierdzono zapis kodu, nie wynik uruchomienia. Następny krok to wykonanie tego pliku testów.

## 13. Wynik testu konfliktu nazwy

Użytkownik wykonał `php artisan test --compact tests/Feature/LeaguePersistenceTest.php`: **1 passed / 1 assertion**, czas 0,32 s. Wynik potwierdza w bazie testowej zapis pierwszej ligi oraz odrzucenie drugiej o tej samej nazwie, właścicielu i dyscyplinie oczekiwanym wyjątkiem. Fabryka utworzyła również powiązane dane dla pierwszego zapisu. Nie jest to sprawdzenie formularza, autoryzacji ani automatycznego przypisania zalogowanego twórcy.

Następny krok to test dozwolonego powtórzenia nazwy w tej samej dyscyplinie przez innego właściciela.

## 14. Ta sama nazwa u różnych właścicieli

Użytkownik dodał import `App\Models\User` i test `test_different_owners_can_use_the_same_league_name_in_the_same_discipline()`. Asystent odczytał i potwierdził kod: po zapisaniu pierwszej ligi test tworzy innego właściciela, zapisuje drugą ligę z tą samą nazwą i dyscypliną oraz sprawdza jej istnienie przez `assertModelExists($otherLeague)`.

Użytkownik uruchomił `php artisan test --compact tests/Feature/LeaguePersistenceTest.php`: **2 passed / 2 assertions**, czas 0,40 s. Wynik potwierdza oba scenariusze: odrzucenie duplikatu dla wspólnego właściciela i dyscypliny oraz dopuszczenie tej samej nazwy w tej samej dyscyplinie u różnych właścicieli. Nie potwierdza jeszcze wariantu zmiany samej dyscypliny ani ochrony kluczy obcych.

Następny krok to test tej samej nazwy i właściciela przy różnych dyscyplinach.

## 15. Samodzielne dokończenie testów części 2

Użytkownik wyraźnie zlecił asystentowi wykonanie wszystkich uzgodnionych testów bez jego udziału oraz zapisanie ich w dokumentacji. Zlecenie dotyczy testów bieżącej części; nie oznacza samodzielnej implementacji przyszłych relacji, formularzy, uprawnień ani projektów.

Asystent zachował dwa istniejące testy i uzupełnił `tests/Feature/LeaguePersistenceTest.php` do ośmiu scenariuszy:

| Test | Sprawdzana zasada |
| --- | --- |
| `test_same_owner_cannot_repeat_a_league_name_in_the_same_discipline` | Duplikat wszystkich trzech wartości powoduje `UniqueConstraintViolationException`. |
| `test_different_owners_can_use_the_same_league_name_in_the_same_discipline` | Inny właściciel pozwala zapisać tę samą nazwę w tej samej dyscyplinie. |
| `test_same_owner_can_use_the_same_league_name_in_different_disciplines` | Inna dyscyplina pozwala temu samemu właścicielowi ponownie użyć nazwy. |
| `test_league_can_be_saved_without_a_description` | Baza zapisuje opis jako `NULL`. |
| `test_league_cannot_reference_a_nonexistent_discipline` | Nieistniejąca dyscyplina blokuje zapis ligi; tabela lig pozostaje pusta. |
| `test_league_cannot_reference_a_nonexistent_owner` | Nieistniejący właściciel blokuje zapis ligi; tabela lig pozostaje pusta. |
| `test_discipline_used_by_a_league_cannot_be_deleted` | Nie można usunąć dyscypliny używanej przez ligę; oba rekordy pozostają zapisane. |
| `test_owner_of_a_league_cannot_be_deleted` | Nie można usunąć właściciela ligi; konto i liga pozostają zapisane. |

Testy używają fabryk i `LazilyRefreshDatabase`. Warianty z nieistniejącym powiązaniem wskazują identyfikator `0`, którego nie tworzą fabryki w świeżej bazie testowej. Oczekują `QueryException` z kodem naruszenia integralności `23000`. W czterech testach kluczy obcych blok `finally` sprawdza stan bazy również po odrzuconej operacji. Przykład ochrony właściciela:

```php
$this->expectException(QueryException::class);
$this->expectExceptionCode('23000');

try {
    $owner->delete();
} finally {
    $this->assertModelExists($owner);
    $this->assertModelExists($league);
}
```

Własne wyniki asystenta:

- `php artisan test --compact tests/Feature/LeaguePersistenceTest.php`: **8 passed / 18 assertions**, 448 ms.
- `vendor/bin/pint --dirty --format agent`: **passed**, bez dalszych poprawek.
- Po formatowaniu: `php artisan test --compact tests/Feature/LeaguePersistenceTest.php tests/Feature/SeasonPersistenceTest.php tests/Feature/DisciplinePersistenceTest.php`: **10 passed / 20 assertions**, 389 ms.
- `vendor/bin/phpstan analyse --memory-limit=1G --debug`: **passed / 0 errors**. Analiza obejmuje `app/` zgodnie z `phpstan.neon`; nie jest to osobna analiza statyczna pliku testów.

Testy działają na SQLite w pamięci według `phpunit.xml`. Nie zmieniają lokalnych danych MySQL i nie zastępują testów formularzy ani przyszłych zasad dostępu. Nie uruchamiano pełnej regresji aplikacji; wykonano testy ograniczeń lig oraz powiązanych katalogów sezonów i dyscyplin. Nie dodawano zależności, nie zmieniano logiki aplikacji ani nie wykonywano commita lub pushu.

## Stan weryfikacji

Potwierdzono wygenerowanie szkieletów, wykonanie migracji, schemat tabeli, listę pól masowego przypisania, kod fabryki oraz osiem zaplanowanych testów ograniczeń lig. Testy regresji zapisu sezonów i dyscyplin, Pint oraz PHPStan przeszły poprawnie. Przed drugim commitem pozostają przegląd zakresu zmian i przygotowanie indeksu przez użytkownika; commit i push nie są wykonane. Relacje Eloquent, uprawnienia, formularze i projekty należą do kolejnych części.
