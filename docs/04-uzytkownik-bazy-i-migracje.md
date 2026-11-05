# Osobny użytkownik bazy i pierwsze migracje

Opis przygotowania dedykowanego konta MySQL i wykonania pierwszych migracji. Wyniki odnoszą się do tego etapu; nie wymagają ponownego tworzenia konta ani wykonywania zakończonych migracji.

## 1. Stan początkowy

### 1.1. Zakres prac

Laravel łączył się z pustą bazą MySQL `sportowiada`, korzystając
z konta `redaktor`. Konto miało uprawnienia do dwóch baz:
`cms` oraz `sportowiada`.

Przed uruchomieniem migracji sprawdziliśmy zasadność prefiksu tabel
i przygotowaliśmy osobne konto dla aplikacji.

## 2. Decyzja o braku prefiksu

### 2.1. Ustawienie połączenia MySQL

Plik: `config/database.php`

Połączenie MySQL zawiera:

```php
'prefix' => '',
'prefix_indexes' => true,
```

Baza `sportowiada` jest przeznaczona wyłącznie dla tej aplikacji,
dlatego pozostawiliśmy pusty prefiks.

Prefiks nie stanowi istotnego zabezpieczenia przed SQL injection
ani dostępem po przejęciu danych logowania. Zakres dostępu aplikacji
ograniczamy przez uprawnienia konta MySQL do właściwej bazy.

Opcja `prefix_indexes` dotyczy nazw indeksów i sama nie dodaje
prefiksu do tabel.

## 3. Osobny użytkownik MySQL

### 3.1. Sprawdzenie uprawnień dotychczasowego konta

Po zalogowaniu do MySQL jako dotychczasowy użytkownik sprawdziliśmy
jego uprawnienia:

```sql
SHOW GRANTS FOR CURRENT_USER;
```

Wynik potwierdził dostęp konta `redaktor@localhost` do baz `cms`
i `sportowiada`.

### 3.2. Utworzenie dedykowanego konta

Następnie zalogowaliśmy się jako administrator:

```bash
mysql -h 127.0.0.1 -u root -p sportowiada
```

W konsoli MySQL utworzyliśmy użytkownika i nadaliśmy mu uprawnienia
wyłącznie do bazy Sportowiady:

```sql
CREATE USER 'sportowiada'@'localhost' IDENTIFIED BY 'TU_WPISZ_NOWE_SILNE_HASLO';

GRANT ALL PRIVILEGES ON `sportowiada`.* TO 'sportowiada'@'localhost';
```

`CREATE USER` tworzy konto MySQL, a `GRANT` nadaje mu uprawnienia. Zapis `sportowiada.*` ogranicza zakres nadanych uprawnień do wszystkich tabel tej bazy. Konto bazy jest niezależne od kont w tabeli `users` aplikacji.

Hasło w przykładzie jest symbolem zastępczym. Rzeczywistego hasła
nie zapisujemy w dokumentacji ani w repozytorium Git.

Uprawnienia obejmują operacje potrzebne do uruchamiania migracji
w lokalnym środowisku deweloperskim. Nie zmienialiśmy uprawnień
dotychczasowego konta `redaktor`.

Nowe konto ogranicza zakres uprawnień używanych przez Sportowiadę, ale nie odbiera dostępu innym kontom. `redaktor` zachował wcześniej nadane uprawnienia do obu baz. Ten etap nie oznacza więc, że tylko konto `sportowiada` może otworzyć bazę Sportowiady.

## 4. Zmiana konfiguracji aplikacji

### 4.1. Dane dedykowanego konta

Plik: `.env` (lokalny, poza Git)

W lokalnym pliku `.env` ustawiliśmy nowe dane logowania:

```dotenv
DB_USERNAME=sportowiada
DB_PASSWORD="TU_WPISZ_NOWE_SILNE_HASLO"
```

Pozostałe ustawienia połączenia pozostały bez zmian:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sportowiada
```

### 4.2. Odświeżenie konfiguracji i sprawdzenie połączenia

Po zapisaniu pliku wyczyściliśmy pamięć konfiguracji:

```bash
php artisan config:clear
```

`config:clear` usuwa zapisaną pamięć konfiguracji, aby kolejne uruchomienie mogło odczytać zmienione ustawienia. Nie zmienia danych w tabelach.

Następnie sprawdziliśmy stan migracji:

```bash
php artisan migrate:status
```

Laravel zwrócił `Migration table not found`. Połączenie z bazą
działało, ale tabela migracji jeszcze nie istniała.

## 5. Podgląd migracji

### 5.1. Próba przed wykonaniem zmian

Wyświetliliśmy zapytania SQL:

```bash
php artisan migrate --pretend
```

Nazwy tabel i indeksów nie zawierały prefiksu.

W naszym przypadku polecenie utworzyło tabelę `migrations`
podczas przygotowania bazy, co potwierdził komunikat
`Creating migration table … DONE`. Zapytania z właściwych migracji
zostały tylko wyświetlone.

Dlatego w użytej wersji Laravel `migrate --pretend` nie oznacza braku jakichkolwiek zmian w pustej bazie: polecenie najpierw przygotowuje repozytorium migracji. Nie uruchamiamy go tu ponownie. Potwierdzono to także w zainstalowanym `MigrateCommand::prepareDatabase()`.

## 6. Wykonanie migracji

### 6.1. Wykonanie oczekujących migracji

Uruchomiliśmy:

```bash
php artisan migrate
```

`migrate` wykonuje oczekujące migracje i zapisuje ich wykonanie w tabeli `migrations`. Nie ogranicza się do jednego wskazanego wyżej pliku.

Wszystkie trzy migracje zakończyły się komunikatem `DONE`:

- `0001_01_01_000000_create_users_table`
- `0001_01_01_000001_create_cache_table`
- `0001_01_01_000002_create_jobs_table`

Utworzyły tabele:

- `users`, `password_reset_tokens`, `sessions`;
- `cache`, `cache_locks`;
- `jobs`, `job_batches`, `failed_jobs`.

## 7. Weryfikacja

### 7.1. Status migracji i wynik etapu

Ponownie wykonaliśmy:

```bash
php artisan migrate:status
```

Wszystkie trzy migracje miały status `[1] Ran`, czyli zostały
wykonane w pierwszej partii migracji.

Aplikacja korzysta z bazy MySQL `sportowiada`, dedykowanego konta
`sportowiada` i tabel bez prefiksu.
