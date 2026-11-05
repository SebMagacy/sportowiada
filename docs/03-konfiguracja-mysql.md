# Konfiguracja połączenia z MySQL

Opis historycznego przejścia z SQLite na MySQL. Współdzielone konto `redaktor` było rozwiązaniem przejściowym, zastąpionym dedykowanym kontem w [[04-uzytkownik-bazy-i-migracje]].

## 1. Stan początkowy

### 1.1. Cel zmiany silnika

Sportowiada korzystała z SQLite. Przed rozpoczęciem pracy Laravel Boost
wskazywał SQLite jako bieżący silnik bazy danych, a w lokalnym pliku
`.env` ustawione było `DB_CONNECTION=sqlite`.

Celem tego kroku było połączenie aplikacji z lokalną bazą MySQL
`sportowiada` bez prefiksu tabel. Hasło używane przez aplikację
zapisaliśmy w lokalnym pliku `.env`, poza dokumentacją i repozytorium Git.

## 2. Sprawdzenie MySQL i PHP

### 2.1. Wersja klienta i działająca usługa

Sprawdziliśmy wersję klienta MySQL i stan usługi:

```bash
mysql --version
systemctl is-active mysql
```

Klient zwrócił wersję `8.4.11-0ubuntu0.26.04.1`, a usługa była
aktywna (`active`).

### 2.2. Sterownik PHP

Sprawdziliśmy sterownik MySQL dla PHP:

```bash
php -m | grep -i '^pdo_mysql$'
```

`php -m` wypisuje moduły PHP uruchamianego w terminalu. Filtr wybiera moduł `pdo_mysql`, używany do połączenia z MySQL przez PDO.

Wynik `pdo_mysql` potwierdził dostępność sterownika.

To sprawdzenie dotyczy PHP CLI. PHP-FPM używany później przez Apache może wczytywać inną konfigurację i zestaw rozszerzeń; lista modułów z terminala sama nie potwierdza konfiguracji serwera WWW.

## 3. Przygotowanie bazy `sportowiada`

### 3.1. Dostęp administracyjny

Próby połączenia z MySQL bez hasła zakończyły się błędem `ERROR 1045`.
Dostęp administracyjny potwierdziliśmy, podając hasło konta MySQL
`root` w terminalu:

```bash
mysql -u root -p -e 'SELECT CURRENT_USER();'
```

`-u` wybiera konto MySQL, `-p` uruchamia pytanie o hasło, a `-e` wykonuje podane zapytanie SQL. `CURRENT_USER()` wskazuje konto, którego uprawnienia obowiązują w połączeniu.

Wynik wskazał `root@localhost`. Hasła nie umieszczaliśmy w poleceniu.

### 3.2. Sprawdzenie zastanej bazy

Sprawdziliśmy, że baza `sportowiada` już istniała:

```bash
mysql -u root -p -e "SHOW DATABASES LIKE 'sportowiada';"
mysql -u root -p -e 'SHOW TABLES FROM sportowiada;'
```

Zawierała liczne tabele z prefiksem `aej2m_`, w tym tabele użytkowników
i danych sportowych. Ustaliliśmy, że te stare dane nie są potrzebne.

### 3.3. Jednorazowe usunięcie starych danych

Po podjęciu tej decyzji usunęliśmy lokalną bazę bez wykonywania kopii:

```bash
mysql -u root -p -e 'DROP DATABASE sportowiada;'
```

To polecenie trwale usunęło jej tabele i dane. Był to jednorazowy krok
porządkowania naszego lokalnego środowiska; nie należy go wykonywać
przy odtwarzaniu projektu, jeśli baza zawiera potrzebne dane.

### 3.4. Utworzenie pustej bazy

Utworzyliśmy nową, pustą bazę:

```bash
mysql -u root -p -e 'CREATE DATABASE sportowiada CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'
mysql -u root -p -e 'SHOW TABLES FROM sportowiada;'
```

`CHARACTER SET utf8mb4` określa zestaw znaków, a `COLLATE utf8mb4_unicode_ci` sposób ich porównywania. `SHOW TABLES` odczytuje listę tabel bez zmiany danych.

Drugie polecenie nie zwróciło żadnych tabel.

## 4. Konto MySQL dla aplikacji

### 4.1. Wybór konta przejściowego

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

### 4.2. Połączenie i nadanie uprawnień

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

## 5. Konfiguracja Laravela

### 5.1. Ustawienia połączenia

Plik: `.env` (lokalny, poza Git)

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

`DB_CONNECTION` wybiera sterownik, `DB_HOST` i `DB_PORT` wskazują serwer, a `DB_DATABASE` nazwę bazy. `DB_USERNAME` i `DB_PASSWORD` są danymi konta MySQL, a nie użytkownika aplikacji.

W projekcie połączenie odczytuje również `DB_URL`, jeśli ta zmienna jest ustawiona. Parametry zawarte w takim adresie mogą zastąpić odpowiadające im wartości `DB_HOST`, `DB_DATABASE` i pozostałych pól. Przy diagnozowaniu połączenia trzeba uwzględnić oba sposoby konfiguracji.

W rzeczywistym `.env` zamiast `...` wpisaliśmy hasło konta `redaktor`.
Nie przenieśliśmy z CMS ustawienia `DB_PREFIX=sr75_`.

`DB_PREFIX` nie jest automatycznie obsługiwane przez każdą aplikację Laravel. W konfiguracji Sportowiady prefiks MySQL jest wpisany bezpośrednio jako pusty ciąg w `config/database.php`; samo dodanie zmiennej do `.env` nie zmieniłoby tego ustawienia.

Jeśli konfiguracja została wcześniej zapisana przez `config:cache`, zmiana `.env` wymaga usunięcia lub ponownego zbudowania tej pamięci. Nie zapisano osobnego wyniku takiej czynności na tym etapie; poniższe polecenia potwierdziły odczytaną wtedy konfigurację. Mechanizm pamięci konfiguracji opisuje [dokumentacja Laravel](https://laravel.com/docs/13.x/configuration#configuration-caching).

## 6. Sprawdzenie połączenia

### 6.1. Konfiguracja i połączenie

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

### 6.2. Migracje i prefiks

Sprawdziliśmy stan migracji oraz prefiks tabel:

```bash
php artisan migrate:status --no-interaction
php artisan config:show database.connections.mysql.prefix --no-interaction
```

Laravel zgłosił `Migration table not found`, co jest oczekiwane
w pustej bazie. Wartość prefiksu była pusta. Migracji jeszcze
nie uruchamialiśmy.

### 6.3. Stan repozytorium

Na końcu sprawdziliśmy stan Git:

```bash
git status -sb
```

Przed utworzeniem tego dokumentu wynik `## main...origin/main`
potwierdził brak zmian w śledzonych plikach. Lokalne dane połączenia
w `.env` nie trafiły do Git.

Sam `git status` nie potwierdza, czy `.env` nigdy wcześniej nie trafił do historii. Osobne sprawdzenie historii i reguł publikacji opisano w [[02-narzedzia-deweloperskie]].
