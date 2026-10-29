# 05. Konfiguracja domeny lokalnej

## Stan początkowy

W rozdziale 01 uruchamialiśmy aplikację przez `php artisan serve` pod
adresem `http://127.0.0.1:8000`. Rozdziały 02–04 opisują narzędzia
deweloperskie i bazę MySQL. W tym kroku podłączyliśmy istniejącą
aplikację do lokalnej domeny `sportowiada.local` przez Apache i HTTPS.

Na komputerze działały już Apache i PHP-FPM 8.5, a narzędzie `mkcert`
było wcześniej zainstalowane. Nie instalowaliśmy ich w ramach tego kroku.

## Rozpoznawanie nazwy domeny

W pliku `/etc/hosts` dodaliśmy wpis:

```text
127.0.0.1 sportowiada.local
```

Sprawdziliśmy wynik polecenia:

```bash
getent hosts sportowiada.local
```

Zwróciło ono `127.0.0.1 sportowiada.local`, więc komputer kierował tę
nazwę na lokalny adres.

## Konfiguracja Apache dla HTTP

Utworzyliśmy plik `/etc/apache2/sites-available/sportowiada.local.conf`.
Początkowo zawierał blok `<VirtualHost *:80>` z nazwą domeny. Dodaliśmy
`DocumentRoot` wskazujący katalog `public` aplikacji, ustawienia
dostępu do tego katalogu oraz przekazanie plików PHP do działającego
gniazda PHP-FPM `/run/php/php8.5-fpm.sock`.

Następnie włączyliśmy witrynę:

```bash
sudo a2ensite sportowiada.local.conf
sudo apache2ctl configtest
sudo systemctl reload apache2
```

Kontrola składni zwróciła `Syntax OK`. Zgłosiła też ostrzeżenia o
nieistniejących katalogach `public` dwóch innych witryn (`js` i `php`).
Po przeładowaniu Apache strona otworzyła się pod adresem
`http://sportowiada.local`, co użytkownik potwierdził w przeglądarce.

Przy pierwszym otwarciu pojawił się problem z zapisem skompilowanego
widoku. PHP-FPM działał jako `www-data`, który nie miał dostępu do
zapisu w `storage/framework/views`. Nadaliśmy mu dostęp do katalogów
`storage` i `bootstrap/cache`:

```bash
setfacl -R -m u:www-data:rwX /home/sebastian/Development/Laravel/sportowiada/storage /home/sebastian/Development/Laravel/sportowiada/bootstrap/cache
```

Po odświeżeniu użytkownik potwierdził, że strona działa.

## Certyfikat lokalny i HTTPS

Użyliśmy wcześniej zainstalowanego `mkcert` do wygenerowania
certyfikatu dla `sportowiada.local`:

```bash
mkcert -cert-file ~/sportowiada.local.pem -key-file ~/sportowiada.local-key.pem sportowiada.local
```

Polecenie potwierdziło utworzenie certyfikatu i klucza. Następnie
umieściliśmy je w katalogu używanym przez Apache:

```bash
sudo install -m 644 ~/sportowiada.local.pem /etc/ssl/local/sportowiada.local.pem
sudo install -m 600 ~/sportowiada.local-key.pem /etc/ssl/local/sportowiada.local-key.pem
```

Sprawdzenie plików potwierdziło ich obecność oraz uprawnienia `644`
dla certyfikatu i `600` dla prywatnego klucza. Zawartości klucza nie
zapisujemy w dokumentacji ani repozytorium.

W konfiguracji Apache dodaliśmy blok `<VirtualHost *:443>` z tym samym
`DocumentRoot`, ustawieniami katalogu i obsługą PHP-FPM. Włączyliśmy
TLS przez `SSLEngine on` oraz wskazaliśmy pliki certyfikatu i klucza:

```apache
SSLCertificateFile /etc/ssl/local/sportowiada.local.pem
SSLCertificateKeyFile /etc/ssl/local/sportowiada.local-key.pem
```

Po wyniku `Syntax OK` ponownie przeładowaliśmy Apache. Użytkownik
potwierdził, że strona otwiera się przez HTTPS.

## Adres aplikacji i przekierowanie

W lokalnym pliku `.env` ustawiliśmy końcowy adres aplikacji:

```dotenv
APP_URL=https://sportowiada.local
```

Laravel odczytał tę wartość jako `app.url`. Plik `.env` jest ignorowany
przez Git i nie trafia do repozytorium.

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

## Stan końcowy

W aktualnych plikach potwierdziliśmy wpis `/etc/hosts`, włączoną
konfigurację witryny Apache, certyfikat i klucz oraz
`APP_URL=https://sportowiada.local`. Laravel Boost rozwiązuje adres
strony głównej jako `https://sportowiada.local`.

## Przygotowanie dokumentacji w Git

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
