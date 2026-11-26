---
tags:
  - filament
  - uwierzytelnianie
  - reset-hasla
---

# 49. Reset hasła użytkownika witryny

[Zgłoszenie #49 — Umożliwić reset zapomnianego hasła zwykłym użytkownikom](https://github.com/SebMagacy/sportowiada/issues/49)

**Status: kryteria funkcjonalne potwierdzono ręcznie i testami resetu. Użytkownik zaliczył pełny zestaw: 200 testów, 1207 asercji, 2 pominięte przypadki MySQL. Zmiany są przygotowane do commita i publikacji.**

## Cel

Umożliwić osobie ze zwykłym kontem odzyskanie dostępu przez istniejący formularz Filamentu „Nie pamiętam hasła”. Po ustawieniu nowego hasła użytkownik ma móc zalogować się przez `/admin/login` i wrócić na stronę główną. Dostęp do panelu nadal wymaga roli `panel_user` lub `super_admin`.

## Punkt wyjścia

- Rejestracja publiczna korzysta z Filamentu, przypisuje rolę `user` z guardem `web` i po zalogowaniu kieruje na `/`. Etap #48 opublikowano w commicie [`4b98350`](https://github.com/SebMagacy/sportowiada/commit/4b98350).
- W [AdminPanelProvider.php](../app/Providers/Filament/AdminPanelProvider.php) jest włączone `->passwordReset()`.
- Własna [odpowiedź logowania](../app/Filament/Auth/LoginResponse.php) kieruje konto bez dostępu do panelu na stronę główną.
- Aktualny opis #49 wskazuje, że wysyłka linku resetującego jest ograniczona do kont mających dostęp do panelu. Zachowanie należy odtworzyć ręcznie w obecnym środowisku przed zmianą kodu.
- W poprzednim etapie ustawiono domyślną politykę nowych haseł: minimum 15 znaków i kontrola wycieków. Trzeba uwzględnić ją przy sprawdzaniu resetu.

Wcześniejsze wyniki testów rejestracji i logowania nie potwierdzają działania resetu zwykłego konta.

## Planowany przepływ

1. Gość otwiera `/admin/login` i wybiera „Nie pamiętam hasła”.
2. Podaje adres e-mail konta i otrzymuje ogólny komunikat zgłoszenia.
3. Dla istniejącego zwykłego konta wysyłana jest wiadomość z linkiem resetującym.
4. Użytkownik przechodzi z linku do formularza i ustawia nowe hasło zgodne z polityką aplikacji.
5. Loguje się nowym hasłem przez `/admin/login` i trafia na `/`.
6. Bezpośrednie wejście na `/admin` nadal kończy się odmową dostępu.

To plan do weryfikacji, nie opis potwierdzonego działania.

## Zakres weryfikacji

Kryteria ukończenia określa aktualny opis zgłoszenia #49. W tym rozdziale zapisujemy sposób realizacji i wyniki:

- Zgłoszenie resetu i otrzymanie linku dla konta z rolą `user`.
- Ustawienie nowego hasła, logowanie nim i powrót na stronę główną.
- Zachowanie odmowy dostępu do panelu oraz działania resetu dla kont panelowych.
- Ogólny komunikat dla nieistniejącego adresu e-mail.
- Ograniczony czas ważności linku i brak możliwości ponownego użycia po skutecznym resecie.

## Sposób pracy

Pracujemy po polsku, po jednym małym kroku. Użytkownik wykonuje edycje aplikacji, polecenia i Git, chyba że wyraźnie zleci je asystentowi. Najpierw sprawdzamy działanie ręcznie, następnie dodajemy testy automatyczne. Po potwierdzonym kroku uzupełniamy ten rozdział, oddzielając wykonaną zmianę od wyniku jej sprawdzenia.

W nowym czacie należy przeczytać reguły projektu i aktualny opis #49, a następnie odtworzyć zgłoszony problem na zwykłym koncie. Sposób odbioru wiadomości oraz ewentualną obsługę kolejki trzeba ustalić z bieżącej konfiguracji lokalnej, bez publikowania sekretów.

## Wykonane kroki i wyniki

26 listopada 2026 r. na prośbę użytkownika utworzono ten rozdział i dodano go do spisu treści. Asystent odczytał aktualny opis #49 i potwierdził w kodzie włączenie resetu hasła w providerze oraz przekierowanie zwykłych kont po logowaniu. Nie zmieniono kodu aplikacji ani konfiguracji poczty; nie wykonano jeszcze ręcznej próby resetu ani testów tego etapu.

### Ręczne odtworzenie problemu — 26 listopada 2026 r.

Użytkownik potwierdził, że zgłoszenie resetu dla zwykłego konta `user` nie zapisuje wiadomości w logu. Uruchomienia `php artisan queue:work --stop-when-empty` zakończyły się komunikatem `Worker STOPPED Queue empty`. Następnie użytkownik sprawdził konto administracyjne i potwierdził, że dla niego wiadomość jest wysyłana, a dla `user` nie. To potwierdza różnicę opisaną w #49; nie potwierdza jeszcze ustawienia nowego hasła ani pozostałych kryteriów akceptacji.

Asystent odczytał lokalną konfigurację: `MAIL_MAILER=log` oraz `QUEUE_CONNECTION=database`. Powiadomienie Filamentu `Filament\Auth\Notifications\ResetPassword` implementuje `ShouldQueue`. W zainstalowanej klasie `Filament\Auth\Pages\PasswordReset\RequestPasswordReset`, w metodzie `request()`, callback wysyłki kończy się przed `notify()`, jeśli `canAccessPanel()` zwraca `false`. Wyjaśnia to brak zadania wysyłki dla zwykłego konta. Kod aplikacji i pliki zależności pozostały bez zmian.

Użytkownik uruchomił `composer show filament/filament` i potwierdził zainstalowaną wersję `v5.11.3`. Dalszą implementację należy dopasować do tej wersji.

### Utworzenie klasy formularza

Użytkownik wykonał `php artisan make:class Filament/Pages/RequestPasswordReset --no-interaction`. Polecenie zakończyło się powodzeniem. Następnie użytkownik zastąpił szkielet klasą dziedziczącą po `Filament\Auth\Pages\PasswordReset\RequestPasswordReset` z aliasem `BaseRequestPasswordReset`, usuwając wygenerowany konstruktor. Asystent potwierdził zapisany kod.

### Podłączenie klasy do panelu

Użytkownik potwierdził wykonanie kroku podłączenia klasy. Asystent sprawdził import `App\Filament\Pages\RequestPasswordReset` oraz `->passwordReset(RequestPasswordReset::class)` w `app/Providers/Filament/AdminPanelProvider.php`. Klasa nadal nie ma własnych metod, więc dziedziczy dotychczasową obsługę i ograniczenie wysyłki. Odpowiedź „Gotowe” potwierdza wykonanie kroku; nie zawiera osobnego opisu obserwacji strony ani wyniku wysyłki. Następny planowany krok to nadpisanie metody `request()` z zachowaniem limitu prób, brokera tokenów i kolejki oraz z ogólnym komunikatem niezależnym od istnienia konta.

### Dostosowanie wysyłki linku

Użytkownik potwierdził zapisanie metody `request()` we własnej klasie, a asystent odczytał i potwierdził kod. Metoda zachowuje `#[Exposed]`, limit dwóch prób, walidację przez formularz oraz `Password::broker(Filament::getAuthPasswordBroker())->sendResetLink(...)`. Callback tworzy powiadomienie Filamentu z adresem resetu i wywołuje `notify()` bez sprawdzania `canAccessPanel()`, następnie emituje `PasswordResetLinkSent`. Uprawnienia dostępu do panelu nie zostały zmienione.

Po obsłudze zgłoszenia formularz wyświetla ogólny komunikat: „Jeśli konto z podanym adresem istnieje, otrzymasz wiadomość z linkiem do resetowania hasła.” Wyniki brokera dotyczące nieistniejącego konta i ograniczenia ponownej wysyłki nie zmieniają tego komunikatu; limit prób formularza zachowuje osobne powiadomienie.

### Tłumaczenia i ręczne sprawdzenie zmiany hasła

Asystent potwierdził w kodzie klucz `passwords.request_received` w formularzu oraz odpowiadające mu teksty w `lang/pl/passwords.php` i `lang/en/passwords.php`. Nie potwierdzono jeszcze wyświetlania obu wersji językowych w przeglądarce.

26 listopada 2026 r. użytkownik początkowo zgłosił działanie zmiany hasła, ale następnie doprecyzował, że po otwarciu linku i zatwierdzeniu nowego hasła otrzymuje komunikat „Nie znaleziono użytkownika o takim adresie email”, mimo istnienia konta. Wcześniejszego zgłoszenia nie traktujemy więc jako potwierdzenia skutecznego resetu.

Asystent sprawdził zainstalowaną klasę `Filament\Auth\Pages\PasswordReset\ResetPassword`. Jej metoda `resetPassword()` również sprawdza `canAccessPanel()`: dla konta bez dostępu pomija zapis hasła i ustawia wynik `Password::INVALID_USER`. To wyjaśnia zaobserwowany komunikat. Dotychczasowa zmiana obejmowała tylko żądanie wysyłki. Następny planowany krok to przygotowanie własnej klasy ustawiania hasła, zachowującej walidację, limity prób i obsługę tokenu. Testy automatyczne i pozostałe kryteria nie są jeszcze potwierdzone.

### Utworzenie klasy ustawiania hasła

Użytkownik wykonał `php artisan make:class Filament/Pages/ResetPassword --no-interaction` i otrzymał potwierdzenie utworzenia `app/Filament/Pages/ResetPassword.php`. Asystent odczytał wygenerowany szkielet. Klasa nie jest jeszcze dostosowana ani podłączona do panelu. Następny planowany krok to rozszerzenie bazowej klasy Filamentu i nadpisanie `resetPassword()` bez warunku dostępu do panelu. Użytkownik wykluczył tworzenie nowego zgłoszenia dotyczącego akcji resetu w panelu administratora; zakres pozostaje przy #49.

### Dostosowanie zapisu nowego hasła

Użytkownik potwierdził zapisanie kodu w `app/Filament/Pages/ResetPassword.php`; asystent odczytał i potwierdził zawartość. Klasa rozszerza bazową stronę Filamentu i nadpisuje `resetPassword()` z atrybutem `#[Exposed]`. Zachowuje limit prób formularza, limit dla adresu e-mail, walidację formularza i sprawdzenie tokenu przez brokera. Callback zapisuje hash hasła, zmienia token zapamiętanego logowania i emituje zdarzenie `PasswordReset`, bez warunku `canAccessPanel()`. Wynik jest obsługiwany przez istniejące tłumaczenia i `PasswordResetResponse`. W providerze nadal wskazana jest tylko własna klasa żądania wysyłki, więc nowa obsługa zapisu nie jest jeszcze aktywna. Następny planowany krok to podłączenie drugiej klasy; działania po zmianie jeszcze nie sprawdzono.

### Sprawdzenie konfiguracji drugiego etapu

Po potwierdzeniu użytkownika asystent odczytał provider: wywołanie zawiera dwa argumenty, ale `ResetPassword::class` nie ma importu. Dodano natomiast import przestrzeni `App\Filament\Pages`. W obecnym zapisie PHP rozwiązuje nazwę drugiej klasy w przestrzeni providera, więc podłączenie wymaga korekty. Planowany mały krok: użycie istniejącego aliasu przestrzeni przez `->passwordReset(Pages\RequestPasswordReset::class, Pages\ResetPassword::class)`. Nie potwierdzono jeszcze poprawienia tego zapisu ani ręcznej próby resetu.

Przy kolejnym odczycie na prośbę użytkownika asystent potwierdził dodanie poprawnego wywołania z `Pages\...`, ale poprzednie wywołanie pozostało bezpośrednio nad nim. Obie klasy istnieją we właściwej przestrzeni i rozszerzają klasy bazowe Filamentu. Przed ręczną próbą należy usunąć poprzednie, zbędne wywołanie oraz nieużywany import `App\Filament\Pages\RequestPasswordReset`. Nie wykonano jeszcze próby działania.

### Ręczna próba z nowego linku po podłączeniu obu klas

Użytkownik potwierdził usunięcie podwójnego wywołania oraz sprawdzenie nowego linku: „Wszystko działa”. Asystent odczytał provider i potwierdził jedno wywołanie `->passwordReset(Pages\RequestPasswordReset::class, Pages\ResetPassword::class)` oraz import `App\Filament\Pages`; zbędny import pojedynczej klasy żądania został usunięty. Wynik użytkownika potwierdza działanie resetu zwykłego konta z nowego linku po poprawce drugiego etapu.

### Logowanie po zmianie hasła

Użytkownik osobno potwierdził, że logowanie konta `user` nowym hasłem działa i przekierowanie na stronę główną jest poprawne. Asystent odczytał `LoginResponse`, które dla konta bez dostępu do panelu zwraca przekierowanie na `/`, oraz `User::canAccessPanel()`, które nadal wymaga roli `super_admin` lub `panel_user`. Odczyt kodu nie zastępuje ręcznej próby odmowy dostępu. Następny planowany krok to wejście na `/admin` jako zalogowany `user` i potwierdzenie 403. Reset kont panelowych po poprawce, nieistniejący adres, jednorazowość i czas ważności linku oraz testy automatyczne pozostają do sprawdzenia.

### Odmowa dostępu do panelu po resecie

Użytkownik potwierdził, że bezpośrednie wejście na `/admin` jako zalogowany `user` po zmianie hasła kończy się 403. Ręcznie potwierdzono więc zachowanie ograniczenia dostępu do panelu. Następny planowany krok to sprawdzenie resetu dla konta panelowego po podłączeniu obu własnych klas. Nieistniejący adres, jednorazowość i czas ważności linku oraz testy automatyczne nadal wymagają weryfikacji.

### Reset konta panelowego po poprawce

Użytkownik odpowiedział „Sukces” na krok obejmujący zgłoszenie resetu konta panelowego, przetworzenie kolejki, ustawienie hasła z nowego linku i logowanie z dostępem do panelu. Potwierdza to ręczne powodzenie tego przepływu po poprawce. Użytkownik nie podał, czy sprawdził rolę `panel_user`, czy `super_admin`; nie przypisujemy wyniku obu rolom osobno. Następny planowany krok to sprawdzenie ogólnego komunikatu dla nieistniejącego adresu. Jednorazowość, czas ważności linku i testy automatyczne pozostają do weryfikacji.

### Komunikat dla nieistniejącego adresu

W odpowiedzi na krok zgłoszenia resetu dla nieistniejącego adresu użytkownik przesłał zrzut ekranu. Asystent odczytał na nim ogólny komunikat: „Jeśli konto z podanym adresem istnieje, otrzymasz wiadomość z linkiem do resetowania hasła.” Formularz nie pokazuje informacji o braku konta, a pole adresu jest wyczyszczone. Jest to potwierdzenie polskiej wersji komunikatu w tej ręcznej próbie; angielska wersja nie została sprawdzona w przeglądarce. Następny planowany krok to próba ponownego użycia linku, którym skutecznie zmieniono hasło. Czas ważności i testy automatyczne nadal wymagają weryfikacji.

### Ponowne użycie wykorzystanego linku

Użytkownik potwierdził, że próba zatwierdzenia kolejnej zmiany hasła z wykorzystanego linku wyświetla „Token resetowania hasła jest nieprawidłowy”. Jest to oczekiwane odrzucenie ponownego użycia tokenu po udanym resecie. Nie stanowi to osobnego sprawdzenia wygaśnięcia niewykorzystanego linku.

Asystent odczytał `config/auth.php`: broker `users` ma `expire` ustawione na 60 minut oraz `throttle` na 60 sekund. Provider panelu nie wskazuje własnego brokera. Są to wartości z pliku, nie wynik sprawdzenia konfiguracji uruchomionej aplikacji ani próby wygaśnięcia. Następny planowany krok to odczyt aktywnej konfiguracji resetu; wygaśnięcie wymaga osobnej weryfikacji.

### Aktywna konfiguracja i przygotowanie testów

Użytkownik uruchomił `php artisan config:show auth`. Wynik potwierdza domyślny broker `users`, model `App\Models\User`, tabelę `password_reset_tokens`, ważność tokenu `expire = 60` minut i odstęp między wysyłkami `throttle = 60` sekund. To potwierdzenie konfiguracji uruchomionej aplikacji, a nie wykonany test wygaśnięcia. Po ręcznych próbach przechodzimy do przygotowania testów automatycznych; plan obejmuje odrzucenie niewykorzystanego linku po symulowanym upływie skonfigurowanego czasu. Testy nie zostały jeszcze utworzone ani uruchomione.

### Testy automatyczne wykonane przez asystenta

Na wyraźne zlecenie użytkownika asystent utworzył przez Artisan i uzupełnił `tests/Feature/PasswordResetTest.php`. Osiem metod z dostawcami danych daje 15 przypadków. Testy obejmują podłączenie obu własnych formularzy do tras, wysyłkę prawidłowego linku dla `user`, `panel_user` i `super_admin`, ogólny komunikat PL/EN bez wysyłki dla nieistniejącego konta, zapis hasła i logowanie dla trzech ról z zachowaniem ich dostępu do panelu, odrzucenie wykorzystanego tokenu, wygaśnięcie niewykorzystanego tokenu po symulowanych 61 minutach, odrzucenie krótkiego i ujawnionego hasła oraz niezgodnego potwierdzenia, a także ograniczenie ponownej wysyłki i liczby prób formularza.

Testy korzystają z `LazilyRefreshDatabase` i bazy SQLite w pamięci. Powiadomienia i żądania do usługi wycieków są symulowane, a upływ czasu kontrolowany przez narzędzia testowe Laravel. Nie wysyłają rzeczywistych wiadomości i nie korzystają z bazy użytkowników aplikacji.

Własne uruchomienie asystenta `php artisan test --compact tests/Feature/PasswordResetTest.php`: **15 testów zaliczonych, 171 asercji**. `php vendor/bin/pint --dirty --format agent` poprawił formatowanie zmienionych plików PHP, w tym kolejność importów providera. `php vendor/bin/phpstan analyse --memory-limit=512M --no-progress`: **0 błędów**. `git diff --check`: bez błędów. Odczyt plików i zapis przez narzędzia PHP były blokowane w piaskownicy, dlatego te polecenia wykonano po uzyskaniu zgody poza nią. Pełny zestaw `php artisan test --compact` został uruchomiony po formatowaniu, ale przerwano go sygnałem Ctrl+C na wyraźną prośbę użytkownika, bez końcowego raportu. Nie przypisujemy temu przebiegowi wyniku zaliczenia ani niepowodzenia testów. Użytkownik zapowiedział samodzielne uruchomienie i przekazanie wyniku.

### Pełny zestaw i końcowy przegląd

26 listopada 2026 r. użytkownik przekazał wynik własnego `php artisan test --compact`: **200 testów zaliczonych, 1207 asercji, 2 pominięte, czas 1046,58 s**. Dwa pomijane przypadki wymagają MySQL i należą do wcześniejszego etapu; ten przebieg SQLite nie potwierdza ich ponownego wykonania. Asystent ponownie sprawdził kod obu formularzy, podłączenie w providerze, status Git i `git diff --check`; nie stwierdził problemów blokujących przygotowanie commita.

| Kryterium #49 | Potwierdzenie |
| --- | --- |
| Zwykłe konto otrzymuje link resetujący | Ręczna próba użytkownika i test wysyłki dla `user` |
| Nowe hasło pozwala zalogować się i wrócić na `/` | Ręczna próba oraz test zapisu i logowania |
| `/admin` zwraca 403 dla `user`, a reset kont panelowych działa | Ręczne próby i testy dla `user`, `panel_user`, `super_admin` |
| Ogólny komunikat dla nieistniejącego adresu | Zrzut ekranu użytkownika i testy PL/EN |
| Link jest jednorazowy i ma ograniczony czas ważności | Ręczne odrzucenie wykorzystanego tokenu; testy ponownego użycia i wygaśnięcia po symulowanych 61 minutach przy konfiguracji 60 minut |

Pozostają commit, push i zamknięcie zgłoszenia po potwierdzeniu publikacji. Plik `test.txt` pozostaje poza zakresem i nie powinien trafić do commita tego etapu.
