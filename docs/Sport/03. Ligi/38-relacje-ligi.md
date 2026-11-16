---
tags:
  - sport
---

# 38. Relacje ligi

## Cel i zakres

Trzecia część [issue #25](https://github.com/SebMagacy/sportowiada/issues/25) obejmuje relacje Eloquent ligi z dyscypliną i właścicielem oraz relacje odwrotne. Tabela i klucze obce zostały przygotowane w [części 2](37-model-i-tabela-lig.md), opublikowanej w commicie `d4e0109`.

Klucz obcy chroni spójność w bazie, a metoda relacji pozwala pobierać powiązany model w kodzie aplikacji. Dodanie metod relacji nie wymaga nowej migracji.

## 1. Dyscyplina ligi

Plik: `app/Models/League.php`.

Dodany import:

```php
use Illuminate\Database\Eloquent\Relations\BelongsTo;
```

Dodana metoda w klasie:

```php
/** @return BelongsTo<Discipline, $this> */
public function discipline(): BelongsTo
{
    return $this->belongsTo(Discipline::class);
}
```

Nazwa metody wskazuje konwencyjny klucz `discipline_id`. Po dodaniu relacji `$league->discipline` pozwoli pobrać dyscyplinę ligi. PHPDoc opisuje typ powiązanego modelu dla analizy statycznej. Składnię sprawdzono przez Boost dla Laravel 13.

Użytkownik potwierdził edycję odpowiedzią „ok. sukces”. Asystent niezależnie odczytał i potwierdził import, PHPDoc oraz metodę `discipline()` w modelu. Potwierdzono kod, nie pobranie dyscypliny z bazy. Testy relacji oraz kontrole części 3 pozostają do wykonania.

## 2. Właściciel ligi

Plik: `app/Models/League.php`.

Pod relacją dyscypliny użytkownik dodał:

```php
/** @return BelongsTo<User, $this> */
public function owner(): BelongsTo
{
    return $this->belongsTo(User::class);
}
```

Nazwa `owner()` wskazuje konwencyjny klucz `owner_id`, a `User::class` model konta. Relacja umożliwi pobranie właściciela przez `$league->owner`. Nie wymaga nowego importu `BelongsTo` ani migracji. Nie nadaje uprawnień i nie przypisuje automatycznie twórcy.

Użytkownik potwierdził edycję odpowiedzią „ok. sukces”. Asystent odczytał i potwierdził metodę oraz PHPDoc w modelu. Nie testowano jeszcze pobrania właściciela. Następny krok to odwrotna relacja listy lig w modelu dyscypliny.

## 3. Ligi dyscypliny

Plik: `app/Models/Discipline.php`.

Dodano import `Illuminate\Database\Eloquent\Relations\HasMany` oraz metodę:

```php
/** @return HasMany<League, $this> */
public function leagues(): HasMany
{
    return $this->hasMany(League::class);
}
```

Relacja umożliwia pobranie lig dyscypliny przez `$discipline->leagues`. Eloquent wyznacza klucz `discipline_id` na podstawie modelu `Discipline`. Składnię sprawdzono przez Boost dla Laravel 13. Asystent odczytał i potwierdził import oraz metodę po odpowiedzi użytkownika „ok. co dalej?”. Istniejąca obsługa kolekcji mediów pozostała zachowana. Potwierdzono kod, nie wynik zapytania relacji.

Wyjaśniono, że blokada usunięcia dyscypliny używanej przez ligę wynika z klucza obcego `restrictOnDelete()` z części 2, objętego tam testem. Metoda `leagues()` umożliwia odczyt powiązań i sama nie ustanawia blokady usuwania. Czytelna obsługa odmowy w panelu pozostaje późniejszym zakresem.

Następny krok to relacja listy lig właściciela w modelu `User`.

## 4. Ligi właściciela

Plik: `app/Models/User.php`.

Użytkownik dodał import `Illuminate\Database\Eloquent\Relations\HasMany` i metodę:

```php
/** @return HasMany<League, $this> */
public function leagues(): HasMany
{
    return $this->hasMany(League::class, 'owner_id');
}
```

`owner_id` wskazano jawnie, ponieważ dla modelu `User` domyślnym kluczem byłby `user_id`. Relacja umożliwia pobranie lig właściciela przez `$user->leagues`. Nie zmienia dostępu do panelu ani nie nadaje uprawnienia do tworzenia lig.

Użytkownik przekazał kod modelu, a asystent niezależnie odczytał i potwierdził import oraz metodę w pliku. Potwierdzono kod, nie wynik pobrania lig. Formatowanie i testy relacji pozostają do wykonania. Następny krok to Pint dla zmienionych plików PHP.

## 5. Formatowanie modeli

Użytkownik wykonał `vendor/bin/pint --dirty --format agent` i przekazał wynik `fixed`. Pint uporządkował importy (`ordered_imports`) w `app/Models/Discipline.php` i `app/Models/User.php`. Asystent przejrzał diff trzech modeli: relacje pozostały zachowane. Wynik formatowania nie potwierdza jeszcze działania zapytań relacji.

Następny krok to wygenerowanie `LeagueRelationshipsTest` i sprawdzenie czterech kierunków powiązań: ligi do dyscypliny i właściciela oraz dyscypliny i właściciela do ich lig. Testy powinny rozróżniać rekordy należące do innych dyscyplin i właścicieli. Nie uruchamiano jeszcze testów ani analizy statycznej części 3. Commit i push tej części pozostają do wykonania.

## 6. Wygenerowanie pliku testów relacji

Użytkownik wykonał `php artisan make:test --phpunit LeagueRelationshipsTest --no-interaction` i przekazał komunikat o utworzeniu `tests/Feature/LeagueRelationshipsTest.php`. Asystent odczytał plik: zawiera domyślny test strony głównej, bez testów relacji. Następny krok to zastąpienie przykładu testem pobierania dyscypliny ligi. Samo utworzenie pliku nie oznacza przetestowania relacji.

## 7. Test pobierania dyscypliny

W `tests/Feature/LeagueRelationshipsTest.php` wygenerowany przykład zastąpiono testem `test_league_returns_its_discipline()`, z `LazilyRefreshDatabase`. Test tworzy dyscyplinę i ligę z jej `discipline_id`, a następnie porównuje identyfikator dyscypliny z `$league->discipline?->id` przez `assertSame()`. Operator `?->` pozwala porównać z `NULL`, gdyby relacja nie zwróciła modelu — taki wynik oznacza niepowodzenie testu.

Użytkownik wykonał `php artisan test --compact tests/Feature/LeagueRelationshipsTest.php`: **1 passed / 1 assertion**, czas 0,29 s. Asystent odczytał kod testu. Wynik potwierdza pobranie właściwej dyscypliny przez relację w bazie testowej. Nie potwierdza jeszcze relacji właściciela ani relacji odwrotnych. Następny krok to test pobierania właściciela ligi.

## 8. Samodzielne dokończenie czterech testów relacji

Użytkownik wyraźnie zlecił asystentowi wykonanie wszystkich czterech uzgodnionych testów części 3 bez jego udziału. Asystent zachował pierwszy test i uzupełnił `tests/Feature/LeagueRelationshipsTest.php` o import `User` oraz trzy pozostałe scenariusze:

| Test | Sprawdzana zasada |
| --- | --- |
| `test_league_returns_its_discipline` | Liga pobiera dyscyplinę wskazaną przez `discipline_id`. |
| `test_league_returns_its_owner` | Liga pobiera konto wskazane przez `owner_id`. |
| `test_discipline_returns_all_its_leagues_and_excludes_other_disciplines` | Dyscyplina zwraca dwie swoje ligi, również u różnych właścicieli, i pomija ligę innej dyscypliny. |
| `test_owner_returns_all_its_leagues_and_excludes_other_owners` | Właściciel zwraca dwie swoje ligi w różnych dyscyplinach i pomija ligę innego właściciela. |

Testy odwrotne porównują pełną listę identyfikatorów z oczekiwaną. Sortowanie odbywa się w teście, ponieważ relacje nie obiecują kolejności wyników:

```php
$leagueIds = $owner->leagues->sortBy('id')->values()->modelKeys();

$this->assertSame([$firstLeague->id, $secondLeague->id], $leagueIds);
```

Dane dodatkowej ligi dobrano tak, aby współdzieliła z jedną z oczekiwanych lig drugie powiązanie: w teście dyscypliny ma wspólnego właściciela, a w teście właściciela wspólną dyscyplinę. Dzięki temu testy rozróżniają dwa kierunki powiązań. Nie są to testy autoryzacji użytkowników.

Własne wyniki asystenta:

- `php artisan test --compact tests/Feature/LeagueRelationshipsTest.php`: **4 passed / 4 assertions**, 296 ms.
- `vendor/bin/pint --dirty --format agent`: **fixed**, wyłącznie odstęp między metodami w `LeagueRelationshipsTest.php` (`class_attributes_separation`).
- Po formatowaniu: `php artisan test --compact tests/Feature/LeagueRelationshipsTest.php tests/Feature/LeaguePersistenceTest.php`: **12 passed / 22 assertions**, 427 ms.
- `vendor/bin/phpstan analyse --memory-limit=1G --debug`: **passed / 0 errors**, w zakresie `app/` określonym przez `phpstan.neon`.
- `git diff --check`: bez błędów.

Testy korzystają z SQLite w pamięci zgodnie z `phpunit.xml`; nie zmieniają danych lokalnej bazy MySQL. Asystent nie zmieniał w tym kroku logiki modeli, nie dodawał zależności i nie wykonywał operacji zapisu Git. Nie uruchamiano pełnej regresji aplikacji. Wszystkie cztery relacje oraz osiem wcześniejszych scenariuszy ograniczeń lig przeszły sprawdzenie.

## 9. Lokalny commit trzeciej części

Użytkownik przejrzał zestawienie pięciu plików i wykonał `git commit -m "Dodaj relacje ligi z dyscypliną i właścicielem — Refs #25"`. Git potwierdził commit `079a46e`: 5 plików, 214 dodanych i 4 usunięte linie. PHPStan uruchomiony przez GrumPHP w hooku przeszedł poprawnie. Asystent niezależnie potwierdził identyfikator i tytuł odczytem historii Git. Push pozostaje niepotwierdzony. Ten wpis dopisano po commicie i pozostaje lokalną zmianą dokumentacji do kolejnego zapisu.

## 10. Potwierdzona publikacja

Użytkownik przekazał udany wynik `git push origin main`: `388c44f..079a46e main -> main`. Potwierdza to publikację commita `079a46e` na GitHubie. Wpisy o commicie i publikacji dopisano po commicie; pozostają lokalnymi zmianami dokumentacji do kolejnego zapisu.

## Stan części 3

Relacje i ich cztery testy są przygotowane. Formatowanie, testy relacji wraz z regresją zapisu lig oraz analiza statyczna przeszły poprawnie. Użytkownik zapisał i opublikował część 3 w commicie `079a46e`. Uprawnienia i formularze pozostają kolejnym zakresem.
