# 31. Filament — uruchomienie i dostęp do panelu

## Cel i aktualny stan

Stan na 15.11.2026. Filament jest zainstalowany i działa pod `/admin`. Własny panel pozostaje dostępny pod `/legacy-admin`. Użytkownik potwierdził podstawowe scenariusze dostępu i nawigację własnego panelu. Dotychczasowy kod zapisano w osobnych commitach, a użytkownik potwierdził wykonanie pushu. Dedykowany zestaw 11 testów Filamentu potwierdza dostęp do pulpitu i listy użytkowników, logowanie oraz wylogowanie.

Etap obejmuje instalację, konfigurację panelu, kontrolę dostępu i zmianę adresów. Lista kont jest osobnym etapem funkcjonalnym. CRUD, przełącznik PL/EN i migracja pozostałych modułów nie należą do uruchomienia panelu. Rozróżnienie komunikatów logowania opisuje [issue #23](https://github.com/SebMagacy/sportowiada/issues/23).

Issue tego etapu jeszcze nie utworzono. Wcześniejszą próbę publikacji zablokowała automatyczna kontrola, wskazując nieustaloną prywatność repozytorium i zgodę na publikację opisu. Aktualny projekt zgłoszenia znajduje się na końcu rozdziału.

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
| `app/Providers/Filament/AdminPanelProvider.php` | Identyfikator, ścieżka, logowanie, pulpit, middleware i wykrywanie zasobów panelu. |
| `bootstrap/providers.php` | Rejestracja providera panelu obok `AppServiceProvider`. |
| `app/Models/User.php` | Implementacja kontraktu `FilamentUser` i decyzja o dostępie do panelu. |
| `app/Providers/AppServiceProvider.php` | Istniejący Gate `access-admin`, współdzielony z własnym panelem. |
| `.gitignore` | Pominięcie wygenerowanych zasobów `public/css/filament`, `public/fonts/filament` i `public/js/filament`. |

### Panel i logowanie

Istotny fragment konfiguracji w `AdminPanelProvider::panel()`:

```php
->default()
->id('panel')
->path('admin')
->login()
->sidebarCollapsibleOnDesktop()
->sidebarWidth('15rem')
```

`id()` nadaje panelowi identyfikator używany przez Filament, a `path()` określa prefiks adresów. `login()` włącza standardowy formularz logowania. Provider rejestruje `Dashboard::class`, standardowe widgety oraz middleware sesji, cookies, ochrony żądań i uwierzytelniania. `discoverResources()` umożliwia późniejsze wykrywanie klas w `app/Filament/Resources`; samo jego dodanie nie tworzy modułów CRUD.

### Dostęp przez istniejącą bramkę

Model `User` implementuje `Filament\Models\Contracts\FilamentUser` i definiuje:

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

## Zmiana adresów i nazwy providera

Początkowo generator utworzył `PanelPanelProvider` z identyfikatorem i ścieżką `panel`. Następnie użytkownik wykonał trzy powiązane zmiany:

1. W `routes/web.php` przeniósł dziewięć tras własnego panelu z `/admin` pod `/legacy-admin`. Zachował ich nazwy `admin.*`, kontrolery, widoki i middleware. Dzięki temu odnośniki generowane przez `route()` wskazują nowe adresy.
2. Zmienił `->path('panel')` na `->path('admin')` w providerze Filamentu. Identyfikator `->id('panel')` pozostał bez zmian, dlatego nazwy tras nadal zaczynają się od `filament.panel`.
3. Zmienił nazwę pliku i klasy na `AdminPanelProvider` oraz oba wystąpienia nazwy w `bootstrap/providers.php`.

Asystent potwierdził kod. Użytkownik uruchomił `php artisan route:list --path=admin`: cztery trasy Filamentu (`admin`, `admin/login`, `admin/logout`, `admin/users`) i dziewięć tras własnego panelu. Filtr dopasowuje również fragment `admin` w `legacy-admin`.

Dotychczasowy formularz `/login` nadal kieruje administratora przez `admin.dashboard` do własnego panelu. Filament korzysta z własnego formularza `/admin/login`.

## Zwijany pasek boczny

W `AdminPanelProvider::panel()` ustawiono `->sidebarCollapsibleOnDesktop()`. Metoda włącza zwijanie paska na komputerze z pozostawieniem ikon. Alternatywna metoda `->sidebarFullyCollapsibleOnDesktop()` ukrywa pasek w całości; nie jest obecnie wybrana.

Obecność konfiguracji potwierdzono w kodzie. Użytkownik potwierdził 14.11.2026, że zwijanie i rozwijanie paska działa poprawnie.

Następnie użytkownik dodał w `AdminPanelProvider::panel()` ustawienie `->sidebarWidth('15rem')`, zmniejszając szerokość rozwiniętego paska z domyślnych `20rem` do `15rem` (240px przy bazowej wielkości czcionki 16px). Asystent potwierdził zmianę w kodzie. Szerokość zwiniętego paska pozostaje domyślna: `4.5rem`. Użytkownik potwierdził 15.11.2026 poprawny wygląd po zmianie szerokości, w tym mieszczące się nazwy pozycji menu.

Źródło: [Filament 5 — Collapsible sidebar on desktop](https://github.com/filamentphp/filament/blob/5.x/docs/06-navigation/01-overview.md#collapsible-sidebar-on-desktop).

## Weryfikacja

### Potwierdzone ręcznie przez użytkownika po zmianie adresów

| Scenariusz | Wynik |
| --- | --- |
| Gość otwiera `/admin` w prywatnym oknie | Przekierowanie do `/admin/login`, bez pulpitu. |
| Administrator loguje się do Filamentu | Dostęp do panelu. |
| Administrator wylogowuje się i ponownie otwiera `/admin` | Ponowne wymaganie logowania. |
| Konto bez uprawnień podaje poprawne dane | Odmowa dostępu do pulpitu. |
| Administrator otwiera `/legacy-admin` i obie listy z menu | Lista użytkowników i dyscyplin działają pod nowym prefiksem. |

Ostatni scenariusz potwierdza nawigację i wyświetlenie list, nie ponowne sprawdzenie wszystkich filtrów ani operacji zapisu. Wcześniejsze sprawdzenie administratora i odmowy zwykłemu kontu przekazano także z rozmowy instalacyjnej.

### Testy i kontrole

| Moment | Wykonawca | Wynik |
| --- | --- | --- |
| Po aktualizacji Laravel, przed instalacją Filamentu | Wynik przekazany z rozmowy instalacyjnej | 219 testów / 1188 asercji. |
| Po instalacji Filamentu | Użytkownik | 219 testów / 1188 asercji, 7.84 s. |
| Po zmianie adresów i providera | Użytkownik | 219 testów / 1188 asercji, 6.30 s. |
| Po utworzeniu szkieletu testu i uporządkowaniu kodu | Asystent | 220 testów / 1189 asercji, wszystkie poprawne. |

Wynik 220 testów obejmował jeszcze przykład generatora sprawdzający stronę główną. Następnie użytkownik zastąpił go w `tests/Feature/FilamentPanelAccessTest.php` testem `test_guest_is_redirected_to_filament_login`. Asystent potwierdził zapisany kod:

```php
$this->get(route('filament.panel.pages.dashboard'))
    ->assertRedirect(route('filament.panel.auth.login'));
```

Żądanie bez zalogowanego użytkownika sprawdza ochronę pulpitu i docelowy adres przekierowania. Klasa używa `LazilyRefreshDatabase`, zgodnie z istniejącymi testami projektu; baza jest przygotowywana dopiero przy jej użyciu.

Użytkownik uruchomił `php artisan test --compact tests/Feature/FilamentPanelAccessTest.php`: **1 test poprawny, 2 asercje, 0.27 s**. Była to weryfikacja pierwszego scenariusza; późniejsze rozszerzenie i wyniki opisano poniżej.

Użytkownik uruchomił Pint; poprawiono importy i formatowanie Resource oraz usunięto nieużywane importy szkieletu testu. PHPStan w hookach przeszedł przy wszystkich pięciu commitach porządkowania. Wcześniej przekazano poprawny `composer audit` po instalacji; podczas porządkowania nie wykonywano nowego audytu.

### Dedykowane testy dostępu — ukończone

Na wyraźne zlecenie użytkownika asystent rozszerzył `tests/Feature/FilamentPanelAccessTest.php` do **11 testów / 35 asercji**. Zachował pierwszy test użytkownika i dodał 10 kolejnych:

| Scenariusze | Liczba testów |
| --- | --- |
| Pulpit: przekierowanie gościa, odmowa zwykłemu kontu, dostęp administratora i odnośnik do listy | 3 |
| Lista użytkowników: przekierowanie gościa, odmowa zwykłemu kontu, dostęp administratora i widoczność jego adresu e-mail | 3 |
| Wyświetlenie formularza logowania gościowi | 1 |
| Logowanie administratora poprawnymi danymi i przekierowanie na pulpit | 1 |
| Odmowa logowania zwykłemu kontu mimo poprawnych danych | 1 |
| Odmowa logowania administratorowi z błędnym hasłem | 1 |
| Wylogowanie, przekierowanie do logowania i ponowna ochrona pulpitu | 1 |

Testy HTTP przechodzą przez rzeczywiste trasy i middleware. Testy formularza używają `Livewire::test(Login::class)`, ustawiają `data.email` i `data.password`, po czym wywołują `authenticate`. `Filament::setCurrentPanel('panel')` określa kontekst dla komponentu testowanego bez żądania do trasy panelu. `assertAuthenticatedAs` potwierdza zalogowane konto; odrzucone próby sprawdzają błąd formularza, aktualny komunikat Filamentu, brak przekierowania i pozostanie gościem. Komunikatów nie zmieniano — issue #23 pozostaje poza zakresem.

Konta powstają przez istniejącą fabrykę z jawnym `is_admin`. Baza testowa to SQLite w pamięci zgodnie z `phpunit.xml`; testy nie korzystają z kont użytkownika w bazie roboczej. Nie dodano zależności ani zmian w kodzie aplikacji.

Weryfikacja asystenta 14.11.2026:

- Dedykowany plik: **11 testów / 35 asercji**, wszystkie poprawne.
- Po formatowaniu pełny zestaw: **230 testów / 1223 asercje**, wszystkie poprawne, 7.564 s.
- `vendor/bin/pint --dirty --format agent`: uporządkowano importy testu.
- `vendor/bin/phpstan analyse --no-progress --memory-limit=1G`: bez błędów.

Są to testy HTTP i komponentu Livewire, bez sterowania prawdziwą przeglądarką. Nie potwierdzają działania przycisku zwijania paska ani jego wyglądu. Test listy dotyczy dostępu, nie pełnej funkcjonalności Resource.

## Zapis i publikacja prac

| Commit | Zakres |
| --- | --- |
| `79676a1` | Formularz tworzenia kont we własnym panelu; trasy jeszcze przed zmianą prefiksu. |
| `4d59725` | Instalacja i konfiguracja Filamentu, dostęp i nowe adresy paneli. |
| `2b2e83e` | Lista użytkowników Filamentu — osobny etap funkcjonalny. |
| `1aed201` | Wygenerowany szkielet przyszłych testów dostępu. |
| `bbc58fb` | Dokumentacja i kontekst projektu. |
| `5e3cb98` | Uporządkowanie dokumentacji instalacji i spisu treści. |
| `079c09d` | Dedykowane testy dostępu do Filamentu i dokumentacja. |

Użytkownik potwierdził wykonanie pushu pierwszych pięciu wymienionych commitów. Commity `5e3cb98` i `079c09d` potwierdzono w lokalnej historii Git; ich push nie ma osobnego potwierdzenia. Jest to jego potwierdzenie, bez niezależnego odczytu zdalnego repozytorium w tej aktualizacji. Niniejsze porządkowanie dokumentacji jest późniejszą zmianą i nie należy do tamtego pushu.

## Pozostałe kroki

- Zapisać zmianę szerokości paska i końcową aktualizację dokumentacji oraz wykonać push. Testy są już w lokalnym commicie `079c09d`. Na zakończenie pracy 15.11.2026 użytkownik zapowiedział samodzielne dodanie zmian do Git; wykonania commita i pushu jeszcze nie potwierdzono.
- Utworzyć issue z poniższego projektu po rozstrzygnięciu blokady publikacji.
- Listę użytkowników i dalszą migrację prowadzić jako osobne etapy.

## Projekt issue — do publikacji

**Tytuł:** Filament: uruchomienie i dostęp do panelu

**Cel:** uruchomić zaplecze Filamentu pod `/admin`, zachowując istniejące konta i własny panel pod `/legacy-admin`. Zgłoszenie porządkuje pracę rozpoczętą przed jego utworzeniem.

**Zakres:** instalacja, provider `AdminPanelProvider`, identyfikator `panel`, standardowe logowanie i pulpit, dostęp przez `FilamentUser` oraz Gate `access-admin`, konfiguracja zwijanego paska i dokumentacja w rozdziale 31.

### Kryteria akceptacji

- [x] Zainstalowano Filament i zarejestrowano provider.
- [x] Skonfigurowano logowanie i pulpit pod `/admin`.
- [x] Potwierdzono ręcznie przekierowanie gościa, dostęp administratora, odmowę zwykłemu kontu i wylogowanie.
- [x] `User::canAccessPanel()` korzysta z `access-admin`.
- [x] W kodzie włączono zwijanie menu z pozostawieniem ikon.
- [x] Potwierdzono ręcznie zwijanie i rozwijanie menu — wynik użytkownika z 14.11.2026.
- [x] Potwierdzono nawigację i wyświetlenie obu list własnego panelu po przeniesieniu.
- [x] Dotychczasowe testy przeszły po instalacji i zmianie adresów.
- [x] Dodano i uruchomiono dedykowane testy dostępu do Filamentu — 11 testów / 35 asercji; pełny zestaw 230 / 1223.
- [x] Opisano konfigurację i dostępne wyniki w dokumentacji.
- [x] Zapisano i wysłano dotychczasowe commity — push potwierdzony przez użytkownika.

### Poza zakresem

Resources, CRUD, migracja całego panelu, przełącznik PL/EN i tłumaczenia własnych etykiet. Zmiana komunikatów logowania pozostaje w issue #23. Etap nie jest ukończony, dopóki pozostałe kryteria nie zostaną sprawdzone.

## Stan na zakończenie pracy — 15.11.2026

Zrealizowano konfigurację dostępu, 11 dedykowanych testów oraz ręczne sprawdzenie zwijania menu i wyglądu paska o szerokości `15rem`. Ostatnie wyniki automatycznej weryfikacji pochodzą z 14.11.2026, sprzed zmiany szerokości: 230 testów / 1223 asercje, Pint i PHPStan poprawne. Po zmianie szerokości nie uruchamiano ponownie tych kontroli. Pozostają zapis i publikacja bieżących zmian przez użytkownika oraz rozstrzygnięcie utworzenia issue. Dalsze funkcje panelu i issue #23 stanowią osobne zadania.
