# Kontrola dostępu do panelu administratora

Reguła biznesowa: **dostęp do panelu ma wyłącznie zalogowany użytkownik z uprawnieniem administratora**.

## 1. Rozróżnienie użytkownika i administratora

Do rozróżnienia zwykłego użytkownika i administratora wykorzystujemy kolumnę `is_admin` w tabeli `users`.

### 1.1. Dodanie kolumny w bazie danych

Plik: `database/migrations/2026_10_30_183957_add_is_admin_to_users_table.php`

Poniższy opis odtwarza przygotowanie istniejącej migracji. Nie wymaga ponownego tworzenia jej pliku ani wykonywania migracji.

Plik migracji tworzymy poleceniem:

```bash
php artisan make:migration add_is_admin_to_users_table --table=users
```

Znaczenie poszczególnych części polecenia:

- `php` — uruchamia interpreter PHP.
- `artisan` — plik uruchamiający narzędzie konsolowe Laravel; polecenie wykonujemy w głównym katalogu projektu.
- `make:migration` — polecenie Artisan tworzące plik migracji.
- `add_is_admin_to_users_table` — nadana przez nas nazwa migracji, opisująca zamiar dodania `is_admin` do tabeli `users`. Sama nazwa nie definiuje kolumny.
- `--table=users` — opcja wskazująca istniejącą tabelę, którą chcemy zmodyfikować. Dzięki niej wygenerowany szkielet zawiera `Schema::table('users', ...)`.

Laravel dodaje do nazwy pliku znacznik czasu, który określa kolejność wykonywania migracji. Plik trafia do katalogu `database/migrations`. Utworzenie pliku nie powoduje wykonania zmian w bazie danych.

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
```

Poszczególne elementy mają następujące zadania:

- `use` — udostępnia klasy potrzebne w migracji pod krótkimi nazwami.
- `return new class extends Migration` — tworzy anonimową klasę migracji.
- `up()` — tutaj opisujemy zmianę wykonywaną podczas migracji.
- `down()` — tutaj opisujemy sposób cofnięcia tej zmiany.
- `Schema::table('users', ...)` — wskazuje, że modyfikujemy istniejącą tabelę `users`.
- `$table` typu `Blueprint` — pozwala opisać zmiany kolumn i indeksów.
- `//` — jest miejscem na nasz kod; samo niczego nie wykonuje.

Laravel zna nazwę tabeli dzięki `--table=users`, ale **nie dopisuje automatycznie kolumny `is_admin`**.

Wewnątrz `Schema::table()` w metodzie `up()`, zamiast `//`, dodajemy:

```php
$table->boolean('is_admin')->default(false);
```

- `boolean('is_admin')` — dodaje kolumnę przechowującą informację: użytkownik jest administratorem (`true`) albo nim nie jest (`false`).
- `default(false)` — ustawia domyślną wartość w bazie: jeśli przy tworzeniu rekordu pominiemy `is_admin`, konto nie otrzyma uprawnienia administratora.

Wewnątrz `Schema::table()` w metodzie `down()`, zamiast `//`, dodajemy:

```php
$table->dropColumn('is_admin');
```

Ta linia usuwa kolumnę podczas cofania migracji. Usunięcie kolumny oznacza również utratę zapisanych w niej wartości.

Po przygotowaniu pliku zmiany w bazie wykonujemy poleceniem:

```bash
php artisan migrate
```

Laravel sprawdza w tabeli `migrations`, które migracje zostały już wykonane. Następnie uruchamia metodę `up()` każdej oczekującej migracji i zapisuje informację o jej wykonaniu.

Polecenie wykonuje wszystkie oczekujące migracje, nie tylko ostatnio utworzony plik.

### 1.2. Rzutowanie atrybutu w modelu

Plik: `app/Models/User.php`

W tablicy zwracanej przez metodę `casts()` dodajemy wpis:

```php
'is_admin' => 'boolean',
```

Jest to rzutowanie atrybutu: podczas odczytu `is_admin` przez model Eloquent otrzymujemy w PHP wartość logiczną `true` albo `false`.

Nie zmienia to struktury tabeli — tę definiuje migracja.

## 2. Reguła dostępu do panelu

### 2.1. Zdefiniowanie reguły autoryzacji

Plik: `app/Providers/AppServiceProvider.php`

Na początku pliku, po deklaracji `namespace`, obok istniejących importów dodajemy:

```php
use App\Models\User;
use Illuminate\Support\Facades\Gate;
```

- `User` — model użytkownika, którego obiekt otrzymuje reguła autoryzacji.
- `Gate` — fasada Laravel udostępniająca mechanizm definiowania i sprawdzania reguł autoryzacji.

Importy pozwalają korzystać z krótkich nazw tych klas w dalszej części pliku. Zachowujemy istniejący import `ServiceProvider`.

W metodzie `boot()` dodajemy definicję reguły autoryzacji:

```php
Gate::define('access-admin', fn (User $user): bool => $user->is_admin);
```

Gate to mechanizm autoryzacji Laravel. Reguła o nazwie `access-admin` określa, czy użytkownik ma uprawnienie dostępu do panelu administratora.

Poszczególne elementy zapisu oznaczają:

- `Gate::define()` — definiuje regułę autoryzacji.
- `'access-admin'` — nazwa, przez którą odwołujemy się do reguły.
- `fn (User $user): bool => $user->is_admin` — funkcja strzałkowa, która otrzymuje obiekt użytkownika i zwraca jego atrybut `is_admin`.
- `User $user` — parametr wymagający obiektu klasy `User`.
- `: bool` — deklaracja, że funkcja zwraca wartość logiczną: `true` oznacza zgodę, a `false` — odmowę dostępu.

Definiując regułę w jednym miejscu, centralizujemy decyzję o dostępie do panelu. W miejscach wymagających ochrony możemy odwoływać się do nazwy `access-admin`.

Samo zdefiniowanie reguły nie chroni jeszcze panelu — należy ją zastosować przy obsłudze dostępu do jego tras.

### 2.2. Zastosowanie reguły do trasy panelu

Plik: `routes/web.php`

Do definicji trasy panelu dodajemy middleware:

```php
Route::view('/admin', 'admin.dashboard')
    ->name('admin.dashboard')
    ->middleware(['auth', 'can:access-admin']);
```

Middleware to mechanizm wykonywany podczas obsługi żądania, który może sprawdzić warunki dostępu przed wyświetleniem strony.

- `auth` — wymaga zalogowania. Gość zostaje przekierowany do logowania.
- `can:access-admin` — sprawdza regułę Gate `access-admin`.
  Zalogowany użytkownik bez uprawnienia administratora otrzymuje odpowiedź HTTP 403, oznaczającą odmowę dostępu.

Administrator spełnia oba warunki i może zobaczyć panel. Samo zalogowanie nie daje zwykłemu użytkownikowi dostępu do panelu.

## 3. Weryfikacja działania kontroli dostępu

### 3.1. Próba wejścia do panelu jako gość

Cel: sprawdzić, czy middleware `auth` zatrzymuje niezalogowanego użytkownika przed wyświetleniem panelu.

Warunek wstępny: przygotowana trasa `login` oraz widok formularza, opisane w [[10-logowanie-uzytkownikow]].

Przebieg sprawdzenia:

1. Otwieramy nowe okno prywatne przeglądarki, w którym nie jesteśmy zalogowani do aplikacji.
2. Przechodzimy do ścieżki `/admin` w lokalnej Sportowiadzie.
3. Sprawdzamy końcową ścieżkę w pasku adresu oraz wyświetloną stronę.

Oczekiwany wynik: gość zostaje przekierowany do `/login` i widzi formularz logowania zamiast panelu administratora.

To sprawdzenie dotyczy wymagania zalogowania przez middleware `auth`. Sam scenariusz gościa nie sprawdza reguły `access-admin` dla zalogowanych użytkowników; ich dostęp zweryfikowano osobno poniżej.

Wynik potwierdzony przez użytkownika: wejście na `/admin` jako gość przekierowało do `/login`. Wyświetlił się formularz z nagłówkiem „Logowanie”, polami adresu e-mail i hasła oraz przyciskiem „Zaloguj się”. Zrzut ekranu potwierdził widok formularza i końcową ścieżkę `/login`.

Status: **scenariusz gościa potwierdzony w przeglądarce**.

### 3.2. Dostęp administratora do panelu

Użytkownik zalogował się poprawnymi danymi konta administratora i potwierdził przekierowanie do `/admin` oraz dostęp do panelu.

W tym scenariuszu konto spełnia oba warunki ochrony trasy: jest zalogowane (`auth`) i ma uprawnienie administratora sprawdzane przez `can:access-admin`.

Status: **dostęp administratora do panelu potwierdzony przez użytkownika w przeglądarce**.

### 3.3. Odmowa dostępu zwykłemu użytkownikowi

Użytkownik zalogował się na zwykłe konto i potwierdził przekierowanie na stronę główną. Następnie, pozostając zalogowanym na tym koncie, otworzył ręcznie ścieżkę `/admin`.

Wynik: odpowiedź **HTTP 403** z komunikatem **„This action is unauthorized.”** Panel nie został udostępniony.

Zwykły użytkownik spełnia warunek zalogowania (`auth`), ale nie spełnia reguły `access-admin`. Odmowa wynika z autoryzacji przez `can:access-admin`. Sam wybór strony docelowej po logowaniu nie stanowi ochrony panelu — potwierdza ją dopiero próba bezpośredniego wejścia na chronioną trasę.

Status: **odmowa dostępu zwykłemu użytkownikowi potwierdzona przez użytkownika w przeglądarce**.

### 3.4. Zestawienie sprawdzonych scenariuszy

| Rodzaj użytkownika | Działanie | Potwierdzony wynik |
| --- | --- | --- |
| Gość | Wejście na `/admin` | Przekierowanie do `/login` i formularz logowania |
| Administrator | Poprawne logowanie | Przekierowanie do `/admin` i dostęp do panelu |
| Zwykły użytkownik | Poprawne logowanie | Przekierowanie na `/` |
| Zwykły użytkownik | Bezpośrednie wejście na `/admin` po zalogowaniu | HTTP 403 i odmowa dostępu |

Są to wyniki ręcznych sprawdzeń wykonanych przez użytkownika. Nie oznaczają utworzenia ani uruchomienia automatycznych testów kontroli dostępu. Szczegóły logowania opisano w [[10-logowanie-uzytkownikow]].
