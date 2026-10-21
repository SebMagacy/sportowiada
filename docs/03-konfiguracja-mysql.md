# 03. Konfiguracja połączenia z MySQL

## Stan początkowy

Sportowiada korzystała z SQLite. Przed rozpoczęciem pracy Laravel Boost
wskazywał SQLite jako bieżący silnik bazy danych, a w lokalnym pliku
`.env` ustawione było `DB_CONNECTION=sqlite`.

Celem tego kroku było połączenie aplikacji z lokalną bazą MySQL
`sportowiada` bez prefiksu tabel. Hasło używane przez aplikację
zapisaliśmy w lokalnym pliku `.env`, poza dokumentacją i repozytorium Git.

## Sprawdzenie MySQL i PHP

Sprawdziliśmy wersję klienta MySQL i stan usługi:

```bash
mysql --version
systemctl is-active mysql
```

Klient zwrócił wersję `8.4.11-0ubuntu0.26.04.1`, a usługa była
aktywna (`active`).

Sprawdziliśmy sterownik MySQL dla PHP:

```bash
php -m | grep -i '^pdo_mysql$'
```

Wynik `pdo_mysql` potwierdził dostępność sterownika.

## Przygotowanie bazy `sportowiada`

Próby połączenia z MySQL bez hasła zakończyły się błędem `ERROR 1045`.
Dostęp administracyjny potwierdziliśmy, podając hasło konta MySQL
`root` w terminalu:

```bash
mysql -u root -p -e 'SELECT CURRENT_USER();'
```

Wynik wskazał `root@localhost`. Hasła nie umieszczaliśmy w poleceniu.

Sprawdziliśmy, że baza `sportowiada` już istniała:

```bash
mysql -u root -p -e "SHOW DATABASES LIKE 'sportowiada';"
mysql -u root -p -e 'SHOW TABLES FROM sportowiada;'
```

Zawierała liczne tabele z prefiksem `aej2m_`, w tym tabele użytkowników
i danych sportowych. Ustaliliśmy, że te stare dane nie są potrzebne.
Po podjęciu tej decyzji usunęliśmy lokalną bazę bez wykonywania kopii:

```bash
mysql -u root -p -e 'DROP DATABASE sportowiada;'
```

To polecenie trwale usunęło jej tabele i dane. Był to jednorazowy krok
porządkowania naszego lokalnego środowiska; nie należy go wykonywać
przy odtwarzaniu projektu, jeśli baza zawiera potrzebne dane.

Utworzyliśmy nową, pustą bazę:

```bash
mysql -u root -p -e 'CREATE DATABASE sportowiada CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'
mysql -u root -p -e 'SHOW TABLES FROM sportowiada;'
```

Drugie polecenie nie zwróciło żadnych tabel.

## Konto MySQL dla aplikacji

Sprawdziliśmy, że nie było konta MySQL o nazwie `sportowiada`:

```bash
mysql -u root -p -e "SELECT User, Host FROM mysql.user WHERE User = 'sportowiada';"
```

Wynik był pusty. Zdecydowaliśmy się tymczasowo współdzielić istniejące
konto `redaktor` z lokalnym projektem CMS. Sprawdziliśmy konto i jego
dotychczasowe uprawnienia:

```bash
mysql -u root -p -e "SELECT User, Host FROM mysql.user WHERE User = 'redaktor';"
mysql -u root -p -e "SHOW GRANTS FOR 'redaktor'@'localhost';"
```

Konto istniało jako `redaktor@localhost` i miało pełne uprawnienia
wyłącznie do bazy `cms`.

Potwierdziliśmy logowanie przez połączenie TCP z adresem `127.0.0.1`:

```bash
mysql --protocol=TCP -h 127.0.0.1 -u redaktor -p -e 'SELECT CURRENT_USER();'
```

MySQL zwrócił `redaktor@localhost`. Następnie nadaliśmy temu kontu
uprawnienia do bazy Sportowiady:

```bash
mysql -u root -p -e "GRANT ALL PRIVILEGES ON sportowiada.* TO 'redaktor'@'localhost';"
mysql -u root -p -e "SHOW GRANTS FOR 'redaktor'@'localhost';"
```

Końcowy wynik zawierał osobne uprawnienia do `cms` i `sportowiada`.
To świadoma, tymczasowa decyzja dla środowiska nieprodukcyjnego:
jedno konto ma dostęp do obu baz. Hasła konta nie zapisujemy w Git
ani w dokumentacji.

## Konfiguracja Laravela

W lokalnym pliku `.env` zastąpiliśmy `DB_CONNECTION=sqlite`
ustawieniami połączenia z MySQL:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sportowiada
DB_USERNAME=redaktor
DB_PASSWORD="..."
```

W rzeczywistym `.env` zamiast `...` wpisaliśmy hasło konta `redaktor`.
Nie przenieśliśmy z CMS ustawienia `DB_PREFIX=sr75_`.

## Sprawdzenie połączenia

Potwierdziliśmy domyślny sterownik odczytany przez Laravel:

```bash
php artisan config:show database.default --no-interaction
```

Wynik: `mysql`. Następnie sprawdziliśmy połączenie:

```bash
php artisan db:show --no-interaction
```

Laravel pokazał MySQL `8.4.11-0ubuntu0.26.04.1`, bazę
`sportowiada`, host `127.0.0.1`, port `3306` i konto `redaktor`.
Na liście tabel polecenie pokazało również tabele `cms/sr75_...`,
widoczne dla współdzielonego konta. Dlatego zawartość właściwej
bazy potwierdziliśmy bezpośrednio:

```bash
mysql --protocol=TCP -h 127.0.0.1 -u redaktor -p -D sportowiada -e 'SELECT DATABASE(); SHOW TABLES;'
```

Wynik `DATABASE()` wskazał `sportowiada`, a lista tabel była pusta.

Sprawdziliśmy stan migracji oraz prefiks tabel:

```bash
php artisan migrate:status --no-interaction
php artisan config:show database.connections.mysql.prefix --no-interaction
```

Laravel zgłosił `Migration table not found`, co jest oczekiwane
w pustej bazie. Wartość prefiksu była pusta. Migracji jeszcze
nie uruchamialiśmy.

Na końcu sprawdziliśmy stan Git:

```bash
git status -sb
```

Przed utworzeniem tego dokumentu wynik `## main...origin/main`
potwierdził brak zmian w śledzonych plikach. Lokalne dane połączenia
w `.env` nie trafiły do Git.
