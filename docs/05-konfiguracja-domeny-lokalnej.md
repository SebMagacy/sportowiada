# Konfiguracja domeny lokalnej

Opis konfiguracji lokalnego komputera, poza repozytorium aplikacji. Podane wyniki dotyczą wykonanych wtedy sprawdzeń; nie oznaczają ponownej kontroli serwera podczas redakcji dokumentacji.

## 1. Stan początkowy

### 1.1. Cel i istniejące narzędzia

W rozdziale 01 uruchamialiśmy aplikację przez `php artisan serve` pod
adresem `http://127.0.0.1:8000`. Rozdziały 02–04 opisują narzędzia
deweloperskie i bazę MySQL. W tym kroku podłączyliśmy istniejącą
aplikację do lokalnej domeny `sportowiada.local` przez Apache i HTTPS.

Na komputerze działały już Apache i PHP-FPM 8.5, a narzędzie `mkcert`
było wcześniej zainstalowane. Nie instalowaliśmy ich w ramach tego kroku.

Konfiguracja zakłada działające gniazdo PHP-FPM, obsługę TLS oraz moduły Apache potrzebne do przekazywania PHP do FPM i przepisywania adresów przez `public/.htaccess`. Nie zapisano tutaj pełnego przebiegu przygotowania tych elementów systemu. Jest to zapis konfiguracji istniejącego środowiska, a nie kompletna instalacja serwera na pustym systemie.

## 2. Rozpoznawanie nazwy domeny

### 2.1. Wpis lokalnej domeny

Plik: `/etc/hosts` (konfiguracja systemowa)

Dodaliśmy wpis:

```text
127.0.0.1 sportowiada.local
```

### 2.2. Sprawdzenie rozpoznawania nazwy

Sprawdziliśmy wynik polecenia:

```bash
getent hosts sportowiada.local
```

Zwróciło ono `127.0.0.1 sportowiada.local`, więc komputer kierował tę
nazwę na lokalny adres.

## 3. Konfiguracja Apache dla HTTP

### 3.1. Wirtualny host i PHP-FPM

Plik: `/etc/apache2/sites-available/sportowiada.local.conf`

Utworzyliśmy konfigurację witryny.
Początkowo zawierała blok `<VirtualHost *:80>` z nazwą domeny. Dodaliśmy
`DocumentRoot` wskazujący katalog `public` aplikacji, ustawienia
dostępu do tego katalogu oraz przekazanie plików PHP do działającego
gniazda PHP-FPM `/run/php/php8.5-fpm.sock`.

`DocumentRoot` wskazuje katalog plików udostępnianych przez serwer. Dla Laravel jest nim `public`, a nie główny katalog projektu. PHP-FPM wykonuje kod PHP przekazany przez Apache.

### 3.2. Włączenie witryny

Następnie włączyliśmy witrynę:

```bash
sudo a2ensite sportowiada.local.conf
sudo apache2ctl configtest
sudo systemctl reload apache2
```

`a2ensite` włącza przygotowaną konfigurację, `configtest` sprawdza jej składnię, a `reload` przeładowuje konfigurację działającej usługi.

Kontrola składni zwróciła `Syntax OK`. Zgłosiła też ostrzeżenia o
nieistniejących katalogach `public` dwóch innych witryn (`js` i `php`).
Po przeładowaniu Apache strona otworzyła się pod adresem
`http://sportowiada.local`, co użytkownik potwierdził w przeglądarce.

### 3.3. Dostęp do zapisu plików Laravel

Przy pierwszym otwarciu pojawił się problem z zapisem skompilowanego
widoku. PHP-FPM działał jako `www-data`, który nie miał dostępu do
zapisu w `storage/framework/views`. Nadaliśmy mu dostęp do katalogów
`storage` i `bootstrap/cache`:

```bash
setfacl -R -m u:www-data:rwX /home/sebastian/Development/Laravel/sportowiada/storage /home/sebastian/Development/Laravel/sportowiada/bootstrap/cache
```

`setfacl` nadaje wskazanemu użytkownikowi prawa przez ACL. `-R` obejmuje podkatalogi, a `rwX` pozwala czytać, zapisywać i przechodzić przez katalogi.

`X` nadaje prawo wykonania katalogom i plikom mającym już jakiś bit wykonania. To polecenie zmienia ACL istniejących elementów; bez opcji ustawiającej domyślne ACL nie gwarantuje dziedziczenia tych praw przez wszystkie nowe pliki. Sukces po odświeżeniu potwierdza opisany przypadek, nie trwałą poprawność uprawnień każdego przyszłego pliku.

Po odświeżeniu użytkownik potwierdził, że strona działa.

## 4. Certyfikat lokalny i HTTPS

### 4.1. Wygenerowanie certyfikatu

Użyliśmy wcześniej zainstalowanego `mkcert` do wygenerowania
certyfikatu dla `sportowiada.local`:

```bash
mkcert -cert-file ~/sportowiada.local.pem -key-file ~/sportowiada.local-key.pem sportowiada.local
```

Wygenerowanie certyfikatu nie konfiguruje Apache ani zaufania każdej przeglądarki. Do zaufanego HTTPS potrzebny jest także lokalny urząd certyfikacji mkcert zainstalowany w odpowiednim magazynie zaufania. Nie odtwarzano tutaj wcześniejszego przygotowania tego zaufania. [Opis działania mkcert](https://github.com/FiloSottile/mkcert#readme).

### 4.2. Umieszczenie plików dla Apache

Polecenie potwierdziło utworzenie certyfikatu i klucza. Następnie
umieściliśmy je w katalogu używanym przez Apache.

Poniższe polecenia wymagają istniejącego katalogu `/etc/ssl/local`. Nie zapisano osobnego polecenia jego utworzenia; nie należy zakładać jego obecności na innym komputerze.

```bash
sudo install -m 644 ~/sportowiada.local.pem /etc/ssl/local/sportowiada.local.pem
sudo install -m 600 ~/sportowiada.local-key.pem /etc/ssl/local/sportowiada.local-key.pem
```

Sprawdzenie plików potwierdziło ich obecność oraz uprawnienia `644`
dla certyfikatu i `600` dla prywatnego klucza. Zawartości klucza nie
zapisujemy w dokumentacji ani repozytorium.

### 4.3. Włączenie TLS

Plik: `/etc/apache2/sites-available/sportowiada.local.conf`

W konfiguracji Apache dodaliśmy blok `<VirtualHost *:443>` z tym samym
`DocumentRoot`, ustawieniami katalogu i obsługą PHP-FPM. Włączyliśmy
TLS przez `SSLEngine on` oraz wskazaliśmy pliki certyfikatu i klucza:

```apache
SSLCertificateFile /etc/ssl/local/sportowiada.local.pem
SSLCertificateKeyFile /etc/ssl/local/sportowiada.local-key.pem
```

Po wyniku `Syntax OK` ponownie przeładowaliśmy Apache. Użytkownik
potwierdził, że strona otwiera się przez HTTPS.

## 5. Adres aplikacji i przekierowanie

### 5.1. Adres aplikacji

Plik: `.env` (lokalny, poza Git)

W lokalnym pliku `.env` ustawiliśmy końcowy adres aplikacji:

```dotenv
APP_URL=https://sportowiada.local
```

Laravel odczytał tę wartość jako `app.url`. Plik `.env` jest ignorowany
przez Git i nie trafia do repozytorium.

`APP_URL` jest adresem bazowym używanym przez aplikację, m.in. przy generowaniu adresów poza żądaniem HTTP. Nie tworzy wpisu DNS, nie włącza TLS i nie zastępuje przekierowania w Apache. Podczas żądania generowanie adresów uwzględnia także bieżący host i schemat żądania. Jeśli konfiguracja jest zbuforowana, samo zapisanie `.env` nie odświeży odczytanej wartości.

### 5.2. Przekierowanie i końcowa konfiguracja Apache

Plik: `/etc/apache2/sites-available/sportowiada.local.conf`

Blok portu 80 zmieniliśmy tak, aby przekierowywał na HTTPS.
Końcowa zawartość pliku konfiguracji Apache jest następująca:

```apache
<VirtualHost *:80>
    ServerName sportowiada.local
    Redirect permanent / https://sportowiada.local/
</VirtualHost>

<VirtualHost *:443>
    ServerName sportowiada.local
    DocumentRoot /home/sebastian/Development/Laravel/sportowiada/public

    <Directory /home/sebastian/Development/Laravel/sportowiada/public>
        AllowOverride All
        Require all granted
    </Directory>

    <FilesMatch \.php$>
        SetHandler "proxy:unix:/run/php/php8.5-fpm.sock|fcgi://localhost/"
    </FilesMatch>

    SSLEngine on
    SSLCertificateFile /etc/ssl/local/sportowiada.local.pem
    SSLCertificateKeyFile /etc/ssl/local/sportowiada.local-key.pem
</VirtualHost>
```

Blok portu 443 nadal obsługuje aplikację. Po kolejnej kontroli
`Syntax OK` i przeładowaniu Apache użytkownik sprawdził w przeglądarce,
że wejście przez HTTP przenosi na działającą stronę HTTPS.

## 6. Stan końcowy

### 6.1. Wynik historycznego sprawdzenia

W plikach na zakończenie tego etapu potwierdziliśmy wpis `/etc/hosts`, włączoną
konfigurację witryny Apache, certyfikat i klucz oraz
`APP_URL=https://sportowiada.local`. Laravel Boost rozwiązał wtedy adres
strony głównej jako `https://sportowiada.local`.

## 7. Przygotowanie dokumentacji w Git

### 7.1. Zakres przygotowanych zmian

Przed commitem sprawdziliśmy stan repozytorium i przygotowane zmiany.
Do tego kroku wybraliśmy tylko rozdział 05 oraz jego wpis w
`docs/README.md`. Kontrola `git diff --cached --check` nie wykazała
błędów formatowania, a lokalne gałęzie `main` i `origin/main` były
zsynchronizowane.

Zmiany w `routes/web.php`, `resources/views/home.blade.php` oraz
usunięcie `resources/views/welcome.blade.php` pozostawiliśmy do
osobnego kroku. Nie są one częścią dokumentacji konfiguracji domeny.

Lokalny plik `.env`, wpis w `/etc/hosts`, konfiguracja Apache oraz
certyfikat i prywatny klucz pozostają poza repozytorium Git.
