# 31. Filament — uruchomienie i dostęp do panelu


## Zapis prac w Git — 14.11.2026

Na wyraźne zlecenie użytkownika asystent uporządkował indeks i utworzył lokalne commity:

- `79676a1` — formularz tworzenia kont we własnym panelu, wraz z trasami przed zmianą prefiksu.
- `4d59725` — zależności i konfiguracja Filamentu, dostęp oraz przeniesienie własnego panelu pod `/legacy-admin`.
- `2b2e83e` — lista kont w Filamencie (osobny etap funkcjonalny).
- `1aed201` — zachowany wygenerowany szkielet testu dostępu; nadal zawiera wyłącznie przykładowy test strony głównej.

PHPStan uruchamiany przez hooki przeszedł przy każdym z tych commitów. Asystent uruchomił cały zestaw PHP: **220 testów, 1189 asercji, wszystkie poprawne**. Dodatkowy test względem poprzednich 219 to przykład z generatora, a nie test uprawnień Filamentu. Dedykowane testy dostępu oraz tworzenia kont nadal wymagają implementacji. Dokumentację zapisujemy osobnym commitem. Nie wykonano pushu; wcześniejsze wpisy o niezapisanym kodzie opisują stan historyczny.

## Status rozdziału

**Testy po zmianie adresów i nazwy providera:** użytkownik ponownie uruchomił `php artisan test --compact`: **219 passed (1188 assertions)**, czas **6.30 s**. Dotychczasowy zestaw przeszedł po przeniesieniu własnego panelu pod `/legacy-admin`, Filamentu pod `/admin` i zmianie klasy na `AdminPanelProvider`. Poniższa zapowiedź ponownego uruchomienia jest już wykonana. Wynik nie zastępuje nadal brakujących dedykowanych testów Filamentu.

**Ręczna weryfikacja własnego panelu po przeniesieniu:** użytkownik potwierdził otwarcie `/legacy-admin` jako administrator oraz przejście z menu do list użytkowników i dyscyplin. Obie listy otwierają się poprawnie pod adresami zaczynającymi się od `/legacy-admin`. Sprawdzenie obejmuje nawigację i wyświetlenie list; nie potwierdza ponownego wykonania operacji zapisu ani wszystkich filtrów. Wcześniejszy wynik 219 testów / 1188 asercji pochodzi sprzed zmiany adresów i nazwy providera; ponowne uruchomienie zestawu po tych zmianach pozostaje do wykonania.

**Ręczna weryfikacja zwykłego konta po zmianie adresu:** użytkownik potwierdził, że poprawne dane istniejącego konta bez uprawnień administratora w formularzu `/admin/login` nie dają dostępu do pulpitu. Ogólny komunikat odmowy pozostaje zgodny z obecnym stanem; rozróżnienie komunikatów należy do issue #23. Wraz ze sprawdzeniami gościa, administratora i wylogowania potwierdza to podstawowe scenariusze ręczne nowego panelu. Dedykowane testy automatyczne nadal pozostają do wykonania.

**Ręczna weryfikacja logowania i wylogowania po zmianie adresu:** użytkownik potwierdził zalogowanie administratora w prywatnym oknie, wylogowanie przez panel oraz ponowne otwarcie `/admin`. Po wylogowaniu pojawia się formularz logowania, bez dostępu do pulpitu. Wynik pochodzi od użytkownika i nie zastępuje testów automatycznych.

**Bieżąca konfiguracja po zmianie nazwy providera:** plik i klasa nazywają się `app/Providers/Filament/AdminPanelProvider.php` oraz `AdminPanelProvider`. W `bootstrap/providers.php` zmieniono import i wpis rejestrujący. Asystent potwierdził zapis; użytkownik ponownie wykonał `php artisan route:list --path=admin` i otrzymał te same 13 tras bez błędu klasy. Identyfikator pozostał `panel`, a ścieżka to `admin`. Dawna nazwa `PanelPanelProvider` w opisach instalacji poniżej jest historyczna. Użytkownik potwierdził ręcznie dostęp gościa pod nowym adresem: w prywatnym oknie wejście na `/admin` przekierowuje do `/admin/login` i pokazuje formularz zamiast pulpitu. Jest to sprawdzenie ręczne, nie test automatyczny.

**Aktualizacja adresów — 14.11.2026:** użytkownik zatwierdził Filament pod `/admin` i dotychczasowy panel pod `/legacy-admin`. Wykonano pierwszy krok: w `routes/web.php` zmieniono adresy wszystkich dziewięciu tras własnego panelu, zachowując nazwy `admin.*`, kontrolery, widoki i middleware. Użytkownik przekazał wynik `php artisan route:list --path=legacy-admin`: 9 tras. Asystent potwierdził zapis w pliku. Dzięki zachowanym nazwom wywołania `route()` generują nowe adresy; również dotychczasowy formularz logowania nadal kieruje administratora do własnego panelu przez `admin.dashboard`. Nie sprawdzono jeszcze działania tych tras w przeglądarce. Filament nadal ma `->path('panel')`; zmiana na `admin` jest następnym krokiem. Poniższe opisy `/panel` i własnego `/admin` dokumentują wcześniejszy stan instalacji.

Rozdział porządkuje instalację i konfigurację pierwszego panelu Filamentu. Uzupełniono go 14.11.2026 na prośbę użytkownika. Instalacja i podstawowa kontrola dostępu są wykonane; weryfikacja po instalacji oraz publikacja zmian pozostają do zakończenia. Lista użytkowników i migracja pozostałych funkcji są osobnymi etapami.

**Drugi krok zmiany adresów wykonany:** użytkownik zmienił w `PanelPanelProvider` wyłącznie `->path('panel')` na `->path('admin')`, zachowując `->id('panel')`. Asystent potwierdził kod. Wynik użytkownika z `php artisan route:list --path=admin` pokazuje 13 tras: cztery Filamentu (`admin`, `admin/login`, `admin/logout`, `admin/users`) oraz dziewięć własnego panelu pod `legacy-admin`. Nazwy tras Filamentu nadal zaczynają się od `filament.panel`. Jest to potwierdzenie rejestracji tras, nie nowy test logowania ani renderowania w przeglądarce. Wcześniejsza zapowiedź zmiany ścieżki powyżej jest już wykonana. Następnym krokiem jest uporządkowanie nazwy providera.

Issue nie zostało jeszcze utworzone: automatyczna kontrola odrzuciła publikację opisu architektury i kontroli dostępu, wskazując nieustaloną prywatność repozytorium i zgodę na publikację tej treści. Gotowy projekt zgłoszenia znajduje się na końcu rozdziału. Po jego utworzeniu należy dodać tutaj numer i odnośnik.

## Cel i granice etapu

Uruchomić Filament jako podstawę stopniowego rozwoju zaplecza Sportowiady, wykorzystując istniejący model kont i kontrolę dostępu. Dotychczasowy panel `/admin` pozostaje w aplikacji. Nowy panel ma osobną ścieżkę `/panel`.

Ten etap nie obejmuje Resources użytkowników ani dyscyplin, CRUD, zmian danych, przełącznika PL/EN ani migracji całego panelu. Osobne [issue #23](https://github.com/SebMagacy/sportowiada/issues/23) dotyczy rozróżnienia błędnych danych logowania i braku uprawnień; nie wdrażamy go w ramach uruchomienia panelu.

## Wersje i wykonane polecenia

Asystent potwierdził lokalnie 14.11.2026: PHP CLI **8.5.4**, Laravel **13.42.0**, Filament **5.11.3** i Livewire **4.4.8**. Wersja PHP CLI nie jest osobnym pomiarem wersji PHP obsługującej żądania WWW.

Z przekazania rozmowy instalacyjnej wynika, że przed instalacją Filamentu zaktualizowano Laravel i siedem pakietów Symfony w związku z ostrzeżeniami audytu. Następnie zainstalowano Filament i wykonano:

```bash
php artisan filament:install --no-interaction
php artisan make:filament-panel panel --no-interaction
```

To zapis wykonanych działań, a nie instrukcja ponownego uruchamiania instalacji. Dokładnego polecenia dodającego zależność przez Composer nie odtwarzamy z domysłu. Aktualny `composer.json` zawiera `filament/filament: ^5.0`; konkretne rozwiązane wersje zapisuje `composer.lock`.

## Pliki i mechanizm konfiguracji

| Plik | Odpowiedzialność |
| --- | --- |
| `composer.json`, `composer.lock` | Zależności, rozwiązane wersje i skrypt `filament:upgrade` po odświeżeniu autoloadingu. |
| `app/Providers/Filament/PanelPanelProvider.php` | Identyfikator, ścieżka, logowanie, pulpit, middleware i wykrywanie zasobów panelu. |
| `bootstrap/providers.php` | Rejestracja providera panelu obok `AppServiceProvider`. |
| `app/Models/User.php` | Implementacja kontraktu `FilamentUser` i decyzja o dostępie do panelu. |
| `app/Providers/AppServiceProvider.php` | Istniejący Gate `access-admin`, współdzielony z własnym panelem. |
| `.gitignore` | Pominięcie wygenerowanych zasobów `public/css/filament`, `public/fonts/filament` i `public/js/filament`. |

### Panel i logowanie

Istotny fragment konfiguracji w `PanelPanelProvider::panel()`:

```php
->default()
->id('panel')
->path('panel')
->login()
->sidebarCollapsibleOnDesktop()
```

`id()` nadaje panelowi identyfikator używany przez Filament, a `path()` określa prefiks adresów. `login()` włącza standardowy formularz logowania. Provider rejestruje `Dashboard::class`, standardowe widgety oraz middleware sesji, cookies, ochrony żądań i uwierzytelniania. `discoverResources()` umożliwia późniejsze wykrywanie klas w `app/Filament/Resources`; samo jego dodanie nie tworzy modułów CRUD.

### Dostęp przez istniejącą bramkę

Model `User` implementuje `Filament\\Models\\Contracts\\FilamentUser` i definiuje:

```php
public function canAccessPanel(Panel $panel): bool
{
    return $this->can('access-admin');
}
```

Filament korzysta z tej metody do oceny dostępu użytkownika do panelu. Decyzja jest delegowana do istniejącej bramki, zamiast powielać regułę w konfiguracji panelu:

```php
Gate::define('access-admin', fn (User $user): bool => $user->is_admin);
```

`is_admin` ma w modelu rzutowanie na `boolean`. W obecnej implementacji argument `$panel` nie rozróżnia uprawnień pomiędzy panelami: ta sama bramka decyduje dla każdego panelu, który wywoła tę metodę. Nie tworzono nowej tabeli kont ani nie kopiowano istniejących kont na potrzeby Filamentu.

## Wyniki sprawdzeń i pozostała praca

- **Odczyt kodu przez asystenta 14.11.2026:** provider jest zarejestrowany, standardowe logowanie i pulpit skonfigurowane, a `canAccessPanel()` korzysta z `access-admin`.
- **Wcześniejsze ręczne sprawdzenie użytkownika, przekazane z rozmowy instalacyjnej:** administrator uzyskał dostęp; po odebraniu `is_admin` potwierdzono odmowę dostępu zwykłemu użytkownikowi. W tej aktualizacji dokumentacji nie powtarzano tych prób.
- **Historyczny wynik przed instalacją Filamentu:** 219 testów i 1188 asercji przeszło po aktualizacji Laravel. Ten wynik nie potwierdza integracji Filamentu.
- **Ponowne uruchomienie po instalacji, 14.11.2026:** użytkownik wykonał `php artisan test --compact` i przekazał wynik: **219 passed (1188 assertions)**, czas **7.84 s**. Dotychczasowy zestaw nie wykrył regresji w objętych nim zachowaniach. Nadal brakuje dedykowanych testów nowego panelu Filamentu.
- **Wcześniejszy wynik przekazany z instalacji:** `composer audit` nie wykazał podatności po instalacji. Nie uruchamiano ponownego audytu podczas tej aktualizacji.
- **Do sprawdzenia:** gość, wylogowanie, działanie zwijania paska, brak regresji własnego panelu oraz właściwe testy po instalacji Filamentu.
- **Git:** zmiany pozostają lokalne na `main`, razem z innymi pracami użytkownika. Ta aktualizacja nie wykonuje commitu ani pushu i nie oznacza etapu jako opublikowanego.

Nie zapisujemy danych logowania ani sekretów. Przy późniejszym zapisie w Git należy przejrzeć zakres plików, ponieważ katalog roboczy zawiera także zmiany z innych etapów.

## Zwijany lewy pasek nawigacji

### Cel i miejsce konfiguracji

Lewy pasek nawigacji w Filament nazywa się **sidebar**. Jego zwijanie na komputerze włącza się w metodzie `panel()` w pliku `app/Providers/Filament/PanelPanelProvider.php`.

Polecenie `composer show filament/filament` wykonane przez asystenta 14.11.2026 potwierdziło wersję **5.11.3**. Poniższe metody sprawdzono w dokumentacji Filament 5.

### Wariant z pozostawieniem ikon

W istniejącym łańcuchu konfiguracji, np. po `->login()`, dodaj:

```php
->login()
->sidebarCollapsibleOnDesktop()
```

Filament udostępni przycisk zwijania paska również na komputerze. Po zwinięciu pozostaną widoczne ikony nawigacji.

### Wariant z całkowitym ukryciem paska

Jeżeli pasek ma znikać w całości, zamiast poprzedniej metody użyj:

```php
->login()
->sidebarFullyCollapsibleOnDesktop()
```

Wybierz jeden wariant zgodnie z oczekiwanym zachowaniem.

### Weryfikacja

Po zapisaniu konfiguracji odśwież panel, zwiń pasek i ponownie go rozwiń. Kryterium sukcesu: przycisk działa na komputerze, a zwinięty pasek pozostawia ikony albo znika całkowicie, zależnie od wybranego wariantu.

Podczas zapisywania dokumentacji asystent potwierdził w pliku providera obecność `->sidebarCollapsibleOnDesktop()`. Wariant z pozostawieniem ikon jest więc już ustawiony w kodzie. Użytkownik nie potwierdził jeszcze wyniku sprawdzenia w przeglądarce. Zapis dokumentacji nie zmienia konfiguracji aplikacji.

Źródło: [Filament 5 — Collapsible sidebar on desktop](https://github.com/filamentphp/filament/blob/5.x/docs/06-navigation/01-overview.md#collapsible-sidebar-on-desktop).

## Projekt issue — do publikacji

**Tytuł:** Filament: uruchomienie i dostęp do panelu

### Cel

Uruchomić pierwszy panel Filamentu jako podstawę stopniowego rozwoju zaplecza Sportowiady. Zachować dotychczasowy panel `/admin` i istniejące konta. Zgłoszenie porządkuje retrospektywnie pracę rozpoczętą przed jego utworzeniem.

### Zakres

Instalacja Filamentu, panel o identyfikatorze i ścieżce `panel`, rejestracja providera, standardowe logowanie i pulpit, dostęp przez `FilamentUser` i istniejący Gate `access-admin`, zwijany pasek boczny oraz dokumentacja w `docs/31-filament-pierwszy-panel.md`.

### Kryteria akceptacji i stan

- [x] Zainstalowano Filament i zarejestrowano provider — potwierdzono odczytem kodu i wersji.
- [x] Skonfigurowano standardowe logowanie i pulpit pod `/panel`.
- [x] Administrator ma dostęp, a konto bez `is_admin` otrzymuje odmowę — wcześniejsze sprawdzenie ręczne użytkownika przekazane z rozmowy instalacyjnej.
- [x] `User::canAccessPanel()` korzysta z Gate `access-admin`.
- [x] W kodzie włączono zwijanie menu z pozostawieniem ikon.
- [ ] Potwierdzono ręcznie zwijanie i rozwijanie menu na komputerze.
- [ ] Sprawdzono gościa, wylogowanie i brak regresji dotychczasowego panelu po instalacji.
- [x] Uruchomiono istniejące testy po instalacji Filamentu: 219 testów / 1188 asercji, wynik użytkownika z 14.11.2026.
- [ ] Dodano i uruchomiono dedykowane testy dostępu do panelu Filamentu.
- [x] Uzupełniono i sprawdzono dokumentację etapu.
- [ ] Zapisano oraz opublikowano zmiany w Git.

### Poza zakresem

Lista użytkowników i pozostałe Resources, CRUD, migracja całego panelu, przełącznik PL/EN i tłumaczenia własnych etykiet. Rozróżnienie błędnych danych logowania i braku uprawnień pozostaje w osobnym issue #23.

### Stan zależności i sposób pracy

14.11.2026 potwierdzono: PHP CLI 8.5.4, Laravel 13.42.0, Filament 5.11.3 i Livewire 4.4.8. Wcześniej zgłoszono poprawny `composer audit` po instalacji; nie jest to ponowny audyt przy przygotowaniu issue.

Praca małymi krokami po polsku; użytkownik wykonuje zmiany aplikacji, polecenia i Git. Issue powinno pozostać otwarte do zakończenia weryfikacji i publikacji.
