# Przykładowi użytkownicy

## 1. Punkt wyjścia

Lista użytkowników i paginacja są opisane w [[14-lista-uzytkownikow]], a testy aplikacji w [[16-testy-automatyczne-aplikacji]]. Testy i dokumentację zapisano w commicie `e1984af`; użytkownik potwierdził jego push na `origin/main`.

Przed utworzeniem tego rozdziału odczyt Git wskazywał czysty katalog roboczy na `main`, zgodny z lokalnym refem `origin/main`. Sprawdzono pliki fabryki i głównego seedera. Na tym początkowym etapie nie odczytywano jeszcze zawartości bazy; późniejszy odczyt liczby kont opisano poniżej.

## 2. Cel etapu

Dodać do lokalnej bazy przykładowe konta, aby wygodnie oglądać listę i sprawdzać paginację na kilku stronach. Uzgodniono **stały zestaw 25 zwykłych kont**, przy zachowaniu limitu 10 rekordów na stronie. Ponowne uruchomienie seedera ma dodawać tylko brakujące konta zestawu, bez zmiany istniejących użytkowników.

Istniejące konta, w tym administrator, mają pozostać zachowane. Nie planujemy czyszczenia bazy ani uruchamiania `migrate:fresh`. Przykładowe dane lokalnej aplikacji są osobne od danych tworzonych w bazie testowej SQLite w pamięci.

## 3. Obecne narzędzia

### 3.1. Fabryka użytkowników

Plik: `database/factories/UserFactory.php`.

Fabryka określa sposób przygotowania przykładowych atrybutów modelu. Obecnie generuje imię, adres e-mail, datę weryfikacji, zahaszowane hasło i token zapamiętania. Korzysta z Faker, m.in. `fake()->name()` i `fake()->unique()->safeEmail()`.

Fabryka nie ustawia obecnie `is_admin`. Przygotowany seeder przekazuje jawnie `is_admin => false`. Obecność `unique()` w generatorze nie oznacza sprawdzenia adresów już istniejących w bazie.

### 3.2. Główny seeder

Plik: `database/seeders/DatabaseSeeder.php`.

Seeder to klasa wykonująca przygotowany kod zasilania bazy danymi. Aktualny `DatabaseSeeder` ma zakomentowane tworzenie 10 kont oraz aktywne utworzenie jednego konta z ustalonym adresem `test@example.com`. Nie uruchamiano go w tym etapie.

Do zasilenia bazy użyto polecenia z `--class=ExampleUsersSeeder`, uruchamiającego wyłącznie przygotowany seeder. Nie zmieniano ani nie uruchamiano głównego seedera.

### 3.3. Wykonane przygotowanie

- Potwierdzono zainstalowaną wersję Laravel: **13.38.0**. Sprawdzono instrukcje projektu, skill `laravel-best-practices` i dokumentację fabryk oraz seederów przez Boost `search-docs`. Katalog `.ai/rules` nie istnieje.
- Przed utworzeniem seedera katalog roboczy był czysty. Commit dokumentacji `6ab0056` znajdował się na `main`, zgodnym z lokalnym refem `origin/main`; nie odświeżano zdalnych referencji.
- Odczytano przez Boost `database-query` wyłącznie liczby kont: **2 łącznie, w tym 1 administrator i 1 zwykły użytkownik**. To punkt odniesienia przed zasileniem bazy; nie odczytywano danych poszczególnych kont.
- Użytkownik wykonał polecenie:

```bash
php artisan make:seeder ExampleUsersSeeder --no-interaction
```

Artisan potwierdził utworzenie `database/seeders/ExampleUsersSeeder.php`. Następnie użytkownik zapisał w nim poniższą logikę; obecność kodu potwierdzono odczytem pliku.

### 3.4. Kod i mechanizm seedera

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class ExampleUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($number = 1; $number <= 25; $number++) {
            $email = "example-user-{$number}@example.com";

            if (User::where('email', $email)->exists()) {
                continue;
            }

            User::factory()->create([
                'email' => $email,
                'is_admin' => false,
            ]);
        }
    }
}
```

- `namespace Database\Seeders` określa przestrzeń nazw klasy. Importy `use` pozwalają używać krótkich nazw modelu `User` i bazowej klasy `Seeder`.
- `extends Seeder` oznacza, że klasa jest seederem Laravel. Metoda `run(): void` zawiera wykonywane instrukcje i nie zwraca wartości. Sam zapis pliku nie uruchamia tej metody.
- `for` zaczyna od `$number = 1`, wykonuje kod, dopóki `$number <= 25`, i po każdym obrocie zwiększa numer o jeden przez `$number++`. Powstaje 25 iteracji.
- `"example-user-{$number}@example.com"` wstawia aktualny numer do tekstu. Daje stałe adresy od `example-user-1@example.com` do `example-user-25@example.com`. Stały jest zestaw adresów; pozostałe dane nowych kont generuje fabryka.
- `User::where('email', $email)->exists()` sprawdza w bazie, czy istnieje użytkownik o danym adresie. `exists()` zwraca wartość logiczną i nie pobiera całego konta.
- Jeśli adres istnieje, `continue` przechodzi do następnego obrotu pętli. Istniejące konto nie jest aktualizowane — zachowuje także swoje hasło i uprawnienia. Dotyczy to również sytuacji, gdy dany adres należał wcześniej do innego konta; seeder je pomija, a nie przejmuje.
- `User::factory()->create([...])` przygotowuje atrybuty przy użyciu istniejącej fabryki i zapisuje nowe konto w bazie. Przekazana tablica nadpisuje adres e-mail oraz jawnie ustawia `is_admin` na `false`. Pozostałe atrybuty pochodzą z `UserFactory`.

Kod przy kolejnych, kolejno wykonywanych uruchomieniach pomija istniejące adresy i uzupełnia brakujące. Potwierdzono pomijanie istniejących kont testami oraz ponownym uruchomieniem w lokalnej aplikacji. Sprawdzenie `exists()` i utworzenie konta to osobne operacje; kod nie jest mechanizmem koordynowania równoczesnych uruchomień.

## 4. Testy i weryfikacja lokalna

### 4.1. Testy seedera

Utworzono `tests/Feature/ExampleUsersSeederTest.php` poleceniem:

```bash
php artisan make:test --phpunit ExampleUsersSeederTest --no-interaction
```

Klasa używa `LazilyRefreshDatabase`. Konfiguracja PHPUnit wskazuje SQLite w pamięci, oddzielone od lokalnej bazy aplikacji. Każdy test wywołuje konkretny seeder przez `$this->seed(ExampleUsersSeeder::class)`.

Testy obejmują:

- `test_creates_25_regular_users` — po uruchomieniu w pustej bazie istnieje 25 kont i żadne nie jest administratorem.
- `test_running_again_does_not_duplicate_users` — po dwóch uruchomieniach nadal istnieje 25 kont.
- `test_preserves_existing_users_and_administrator` — przygotowuje zwykłe konto i administratora zajmującego adres `example-user-1@example.com`. Seeder dodaje 24 brakujące konta, co daje łącznie 26. Asercje sprawdzają zachowanie wszystkich zapisanych atrybutów obu istniejących kont, w tym haseł i uprawnień.

Przy pierwszym uruchomieniu testu brak importu `Database\Seeders\ExampleUsersSeeder` spowodował błąd wyszukiwania klasy w przestrzeni `Tests\Feature`. Poprawiono import oraz dodano użycie `LazilyRefreshDatabase` wewnątrz klasy testowej.

Użytkownik uruchomił cały plik:

```bash
php artisan test --compact tests/Feature/ExampleUsersSeederTest.php
```

Potwierdzony wynik: **3 zaliczone testy, 6 asercji**. W tym etapie nie przedstawiono wyniku ponownego uruchomienia całego zestawu testów aplikacji.

Następnie wykonano:

```bash
vendor/bin/pint --dirty --format agent
```

Pint poprawił wyłącznie kolejność importów (`ordered_imports`) w pliku testu. Po tej zmianie formatowania nie przedstawiono kolejnego wyniku testów.

### 4.2. Pierwsze zasilenie lokalnej bazy

Użytkownik wykonał:

```bash
php artisan db:seed --class=ExampleUsersSeeder --no-interaction
```

Polecenie zakończyło się statusem `DONE` (358 ms). Późniejszy odczyt samych liczników przez Boost `database-query` wykazał **27 kont: 1 administratora i 26 zwykłych użytkowników**. Względem wyjściowych 2 kont przybyło 25 zwykłych kont.

### 4.3. Paginacja i ponowne uruchomienie

Użytkownik potwierdził w przeglądarce **27 wyników** oraz **7 kont na trzeciej stronie**. Przy limicie 10 rekordów odpowiada to oczekiwanemu podziałowi 10 + 10 + 7. Osobnej liczby wierszy na pierwszej i drugiej stronie nie zgłoszono.

Następnie ponownie wykonano to samo polecenie z `--class=ExampleUsersSeeder`. Zakończyło się statusem `DONE` (21 ms). Użytkownik potwierdził, że nie dodano żadnych nowych rekordów.

## 5. Kryteria sukcesu i status

- W lokalnej bazie znajdują się uzgodnione przykładowe konta zwykłych użytkowników.
- Istniejące konta i uprawnienia administratora pozostają zachowane.
- Lista pozwala przechodzić między stronami i pokazuje właściwe zakresy wyników.
- Zachowanie ponownego uruchomienia seedera jest określone i sprawdzone.

**Status:** przygotowano seeder i trzy testy, dodano 25 zwykłych kont do lokalnej bazy, sprawdzono liczbę wyników i trzecią stronę paginacji oraz potwierdzono brak nowych rekordów po ponownym uruchomieniu. Zachowanie istniejących kont i uprawnień pokrywa test seedera. Nie czyszczono lokalnej bazy ani nie zmieniano zależności.

Ostatni przedstawiony `git status --short` wskazywał zmodyfikowany rozdział 17 oraz nieśledzone pliki seedera i jego testu. Do wykonania pozostaje przegląd zmian i zapis w Git przez użytkownika. Nie potwierdzono commitu ani pushu tych zmian.

Pracujemy po jednym małym kroku: cel, mechanizm, kryterium sukcesu, następnie wynik użytkownika. Użytkownik wykonuje polecenia, edycje kodu i operacje Git; asystent nie wykonuje ich za niego bez wyraźnej prośby. Dokumentację aktualizujemy na wyraźną prośbę.
