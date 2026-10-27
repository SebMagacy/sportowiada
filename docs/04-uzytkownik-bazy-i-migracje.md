# 04. Osobny użytkownik bazy i pierwsze migracje

## Stan początkowy

Laravel łączył się z pustą bazą MySQL `sportowiada`, korzystając
z konta `redaktor`. Konto miało uprawnienia do dwóch baz:
`cms` oraz `sportowiada`.

Przed uruchomieniem migracji sprawdziliśmy zasadność prefiksu tabel
i przygotowaliśmy osobne konto dla aplikacji.

## Decyzja o braku prefiksu

W `config/database.php` połączenie MySQL zawiera:

```php
'prefix' => '',
'prefix_indexes' => true,
```

Baza `sportowiada` jest przeznaczona wyłącznie dla tej aplikacji,
dlatego pozostawiliśmy pusty prefiks.

Prefiks nie stanowi istotnego zabezpieczenia przed SQL injection
ani dostępem po przejęciu danych logowania. Rozdzielenie dostępu
zapewnia osobny użytkownik z uprawnieniami do właściwej bazy.

Opcja `prefix_indexes` dotyczy nazw indeksów i sama nie dodaje
prefiksu do tabel.

## Osobny użytkownik MySQL

Po zalogowaniu do MySQL jako dotychczasowy użytkownik sprawdziliśmy
jego uprawnienia:

```sql
SHOW GRANTS FOR CURRENT_USER;
```

Wynik potwierdził dostęp konta `redaktor@localhost` do baz `cms`
i `sportowiada`.

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

Hasło w przykładzie jest symbolem zastępczym. Rzeczywistego hasła
nie zapisujemy w dokumentacji ani w repozytorium Git.

Uprawnienia obejmują operacje potrzebne do uruchamiania migracji
w lokalnym środowisku deweloperskim. Nie zmienialiśmy uprawnień
dotychczasowego konta `redaktor`.

## Zmiana konfiguracji aplikacji

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

Po zapisaniu pliku wyczyściliśmy pamięć konfiguracji:

```bash
php artisan config:clear
```

Następnie sprawdziliśmy stan migracji:

```bash
php artisan migrate:status
```

Laravel zwrócił `Migration table not found`. Połączenie z bazą
działało, ale tabela migracji jeszcze nie istniała.

## Podgląd migracji

Wyświetliliśmy zapytania SQL:

```bash
php artisan migrate --pretend
```

Nazwy tabel i indeksów nie zawierały prefiksu.

W naszym przypadku polecenie utworzyło tabelę `migrations`
podczas przygotowania bazy, co potwierdził komunikat
`Creating migration table … DONE`. Zapytania z właściwych migracji
zostały tylko wyświetlone.

## Wykonanie migracji

Uruchomiliśmy:

```bash
php artisan migrate
```

Wszystkie trzy migracje zakończyły się komunikatem `DONE`:

- `0001_01_01_000000_create_users_table`
- `0001_01_01_000001_create_cache_table`
- `0001_01_01_000002_create_jobs_table`

Utworzyły tabele:

- `users`, `password_reset_tokens`, `sessions`;
- `cache`, `cache_locks`;
- `jobs`, `job_batches`, `failed_jobs`.

## Weryfikacja

Ponownie wykonaliśmy:

```bash
php artisan migrate:status
```

Wszystkie trzy migracje miały status `[1] Ran`, czyli zostały
wykonane w pierwszej partii migracji.

Aplikacja korzysta z bazy MySQL `sportowiada`, dedykowanego konta
`sportowiada` i tabel bez prefiksu.
