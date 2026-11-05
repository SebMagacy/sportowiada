# Polskie tłumaczenia aplikacji

## 1. Punkt wyjścia

### 1.1. Zapisany etap listy użytkowników

Etap opisany w [[14-lista-uzytkownikow]] zapisano lokalnie w commicie `8e6c699` — „Dodaj listę użytkowników z paginacją”. Commit obejmuje kontroler, widok i notatkę 14. PHPStan uruchomiony przez GrumPHP przeszedł poprawnie. Użytkownik potwierdził również brak komunikatów z `git diff --check` przed przygotowaniem commitu. Nie potwierdzono pushu tego commitu ani scalenia do `main`.

Lista pobiera konta przez `User::query()->orderBy('id')->paginate(10)`. Widok zawiera tabelę, własny polski licznik i warunkowe `links()`. Przechodzenie między stronami sprawdzono ręcznie z tymczasowym limitem jednego konta; późniejszy odczyt kontrolera potwierdził powrót do limitu 10. Testy automatyczne pozostają odłożone.

### 1.2. Powód rozpoczęcia tłumaczeń

Wbudowana paginacja wyświetla angielskie teksty. Podczas sprawdzania użytkownik zauważył też dwa liczniki: własny polski i wbudowany angielski. Proponowane warunkowe ukrywanie własnego licznika nie było obecne w ostatnim odczycie widoku. Nie przedstawiamy tej poprawki jako wykonanej. Wygląd przycisków paginacji również różnił się od wskazanego wzoru Filament.

Wcześniejsze etapy dokumentują angielskie komunikaty walidacji. Ich spolszczenie nie jest jeszcze wykonane. Użytkownik zdecydował wydzielić tłumaczenia do etapu 15 i osobnego commitu.

### 1.3. Język aplikacji i pliki tłumaczeń

Przekazany przez użytkownika wynik poleceń potwierdził usunięcie pamięci konfiguracji i aktywne `app.locale` równe `pl`:

```bash
php artisan config:clear --no-interaction
php artisan config:show app.locale --no-interaction
```

Omówiono pozostawienie angielskiego języka zapasowego oraz możliwość ustawienia `APP_FAKER_LOCALE=pl_PL`. Nie sprawdzono osobno aktywnej wartości konfiguracji Fakera. Zmiana jego języka dotyczy generowanych danych, a nie tekstów interfejsu. Lokalny `.env` pozostaje poza Gitem.

Samo ustawienie języka nie dostarcza tłumaczeń. Polecenie `php artisan lang:publish --no-interaction` zaproponowano wcześniej, ale nie potwierdzono jego wykonania. Przy tworzeniu tej notatki katalog `lang` nie istniał. Późniejszą publikację i dodanie tłumaczeń opisują rozdziały 3–6.

### 1.4. Stan Git przy utworzeniu notatki

Odczyt 05.11.2026 potwierdził:

- bieżącą gałąź `codex/polish-translations`;
- `HEAD` wskazujący `8e6c699`;
- czysty katalog roboczy przed utworzeniem niniejszej notatki.

Nowa gałąź rozpoczyna się od zapisanego etapu listy użytkowników. Utworzenie notatki dodaje nowy, nieśledzony plik. Nie wykonano jego commitu ani pushu. Nie potwierdzono publikacji nowej gałęzi.

## 2. Zakres kolejnego etapu — plan

### 2.1. Prace do wykonania

1. Przygotować standardowy katalog plików językowych Laravel.
2. Dodać polskie tłumaczenia tekstów używanych przez wbudowaną paginację, w tym opisów dostępności.
3. Sprawdzić wynik w przeglądarce na więcej niż jednej stronie danych.
4. Ustalić i wprowadzić docelową stopkę z jednym licznikiem wyników.
5. Ustalić osobno zakres pozostałych tłumaczeń, np. komunikatów walidacji. Nie zakładać, że cała aplikacja została już spolszczona.
6. Wykonać kontrole odpowiednie do zmian, uzupełnić dokumentację i zapisać tłumaczenia w osobnym commicie.

Powyższe punkty są planem, nie opisem zakończonej implementacji. Zmiana wyglądu przycisków wymaga osobnego ustalenia; tłumaczenie tekstów samo nie zmieni ich stylów. Nie instalujemy zależności bez zgody użytkownika.

### 2.2. Sposób pracy

Pracujemy po polsku, po jednym małym kroku. Użytkownik wykonuje polecenia i edycje kodu. Asystent wyjaśnia mechanizm i kryterium sukcesu, a następnie czeka na wynik. Dokumentację może aktualizować na wyraźną prośbę użytkownika, opisując wyłącznie potwierdzone działania i oddzielając plany.

Przed zmianami sprawdzamy aktualne instrukcje repozytorium, potrzebne pliki, zainstalowane wersje i dokumentację właściwą dla tych wersji. W poprzednim etapie wynik Composera potwierdził Laravel 13.38.0. Stan Git i wyniki kontroli opisujemy na podstawie bieżących dowodów. Nie wykonujemy Git za użytkownika.

## 3. Publikacja plików językowych — wykonane

Użytkownik uruchomił:

```bash
php artisan lang:publish --no-interaction
```

Polecenie zakończyło się komunikatem `Language files published successfully.`. Utworzyło standardowe angielskie pliki w `lang/en`: `auth.php`, `pagination.php`, `passwords.php` i `validation.php`. Publikacja udostępnia pliki do modyfikacji; nie pobiera polskich tłumaczeń i sama nie zmienia języka interfejsu. Zainstalowaną wersję Laravel 13.38.0 ponownie potwierdzono przez Composer, a mechanizm publikacji sprawdzono w dokumentacji przez Boost. Nie instalowano zależności.

## 4. Polskie tłumaczenia paginacji — wykonane

### 4.1. Klucze w pliku PHP

Dodano `lang/pl/pagination.php`:

```php
<?php

return [
    'previous' => '&laquo; Poprzednia',
    'next' => 'Następna &raquo;',
];
```

Wywołanie `__('pagination.next')` wskazuje plik `pagination.php` dla aktywnego języka i klucz `next` w jego tablicy. Plik jest dostępny w całej aplikacji, a przedrostek wskazuje źródło tekstu. Encje `&laquo;` i `&raquo;` oznaczają znaki « i ».

### 4.2. Pełne teksty w JSON — pierwsza wersja

Wbudowany widok paginacji używa również pełnych tekstów jako kluczy. Dodano je do `lang/pl.json`:

```json
{
    "Showing": "Wyświetlono",
    "to": "–",
    "of": "z",
    "results": "wyników",
    "Pagination Navigation": "Nawigacja po stronach",
    "Go to page :page": "Przejdź do strony :page"
}
```

`__('Showing')` pobiera polską wartość z JSON. Dla języka angielskiego, bez osobnego tłumaczenia tego wpisu, wyświetlany jest oryginalny tekst `Showing`. Wielkość liter i interpunkcja należą do klucza.

Dwa ostatnie wpisy tłumaczą etykiety dostępności nawigacji i odnośników do stron. `:page` pozostaje symbolem zastępczym, do którego Laravel podstawia numer strony. Obecność wpisów potwierdzono w pliku; nie przeprowadzono osobnego sprawdzenia czytnikiem ekranu ani inspekcji wyrenderowanych etykiet dostępności.

## 5. Tłumaczenia własnych tekstów listy — pierwsza wersja

W `resources/views/admin/users/index.blade.php` zastąpiono teksty tytułu i nagłówków wywołaniami:

```blade
{{ __('Users') }}
{{ __('Name') }}
{{ __('Email') }}
{{ __('Account type') }}
```

Typ konta wyświetlany jest przez:

```blade
{{ $user->is_admin ? __('Administrator') : __('User') }}
```

Baza przechowuje wartość logiczną `is_admin`; tłumaczymy etykietę wyświetlaną w widoku, a nie dane o uprawnieniach. Nie tłumaczymy imion ani adresów e-mail użytkowników.

Do istniejącego `lang/pl.json` dodano:

```json
"Users": "Użytkownicy",
"Administrator": "Administrator",
"User": "Użytkownik",
"Name": "Imię",
"Email": "E-mail",
"Account type": "Typ konta"
```

Powyżej pokazano fragment istniejącego obiektu JSON, nie osobny plik. Wpisy oddzielamy przecinkami.

W pierwszej wersji własny licznik otrzymał wywołania:

```blade
{{ __('Showing') }} {{ $users->firstItem() }} {{ __('to') }} {{ $users->lastItem() }}
{{ __('of') }} {{ $users->total() }} {{ __('results') }}
```

Początkowo separator zakresu był myślnikiem zapisanym bezpośrednio w Blade. Zastąpienie go `__('to')` pozwoliło wyświetlać „–” po polsku i „to” po angielsku. Na tym etapie dwa komunikaty „Brak użytkowników.” — w pustej tabeli i w stopce — pozostawały wpisane na stałe po polsku. Późniejsze przeniesienie etykiet i tych komunikatów do `users.php` opisuje rozdział 8.

## 6. Ręczne sprawdzenia i ich zakres

Na potrzeby sprawdzenia dwóch istniejących kont użytkownik ustawił tymczasowo `paginate(1)` w kontrolerze. Zrzuty potwierdziły:

- polski licznik wbudowanej paginacji na pierwszej stronie;
- przejście na stronę 2, wyświetlenie drugiego konta i zmianę zakresu na „2 – 2 z 2 wyników”;
- nadal obecne dwa liczniki wyników;
- po przełączeniu `APP_LOCALE=en` — oba liczniki po angielsku;
- po dodaniu tłumaczenia separatora — angielskie „to” w obu licznikach;
- angielski tytuł „Users” i etykietę zwykłego konta „User”.

Po zmianie języka wskazano czyszczenie pamięci konfiguracji:

```bash
php artisan config:clear --no-interaction
```

Zrzuty potwierdzają efekt języka angielskiego, lecz użytkownik nie przekazał osobnego wyniku tego polecenia w tej części pracy. Ostatni zrzut poprzedza zamianę trzech nagłówków kolumn; ich wywołania tłumaczeń potwierdzono później w kodzie, bez kolejnego sprawdzenia w przeglądarce.

Odczyt kontrolera przy aktualizacji tej notatki nadal pokazuje `paginate(1)`. Powrót do `paginate(10)` był proponowany, ale nie jest wykonany w aktualnym pliku. Nie potwierdzono również przywrócenia języka `pl` po sprawdzeniu angielskiego. Nie odczytywano `.env` ani nie ujawniano jego zawartości.

Nie przeprowadzono sprawdzenia pustej listy ani wariantu mobilnego. Testy automatyczne pozostają odłożone. Na etapie tych ręcznych sprawdzeń nie uruchamiano Pint ani PHPStan. Kontrole wykonane podczas późniejszego porządkowania tłumaczeń opisuje rozdział 8.4.

## 7. Organizacja tłumaczeń — omówiony mechanizm

JSON stanowi wspólny słownik pełnych tekstów dla danego języka. Alternatywą dla własnych etykiet są pliki PHP grupowane według obszaru aplikacji, np. `users.php`, i stałe klucze niezależne od brzmienia tekstu.

Przykład omówiony w rozmowie:

```php
// lang/pl/users.php
return [
    'name' => 'Imię',
];
```

```php
// lang/en/users.php
return [
    'name' => 'Name',
];
```

```blade
{{ __('users.name') }}
```

Przy takim podziale pierwszy człon wskazuje plik, a drugi wpis w tablicy. Dla poprawnego angielskiego tekstu trzeba zapewnić odpowiednią wartość w angielskim pliku; bez znalezionego tłumaczenia klucz `users.name` może pojawić się w interfejsie.

Zaproponowano grupowanie własnych tekstów według obszaru, np. `users.php`, a rzeczywiście wspólnych działań według znaczenia, np. `common.php`. Rozróżnienie kontekstu pozwala używać osobnych kluczy dla imienia użytkownika i nazwy wydarzenia, nawet gdy angielskie wartości brzmią tak samo. Wbudowaną paginację tłumaczymy zgodnie z kluczami użytymi przez Laravel.

Po omówieniu mechanizmu użytkownik zlecił utworzenie plików i przeniesienie tłumaczeń; wykonanie opisuje rozdział 8. Snippet edytora do szybkiego wpisywania `{{ __('') }}` odłożono na później.

## 8. Podział tłumaczeń na obszary — wykonane

### 8.1. Pliki `users.php`

Na wyraźną prośbę użytkownika utworzono `lang/pl/users.php` i `lang/en/users.php`. Oba pliki zawierają takie same klucze:

| Klucz | Polski | Angielski |
| --- | --- | --- |
| `title` | Użytkownicy | Users |
| `name` | Imię | Name |
| `email` | E-mail | Email |
| `account_type` | Typ konta | Account type |
| `administrator` | Administrator | Administrator |
| `user` | Użytkownik | User |
| `empty` | Brak użytkowników. | No users found. |

Widok listy korzysta teraz z `__('users.title')`, `__('users.name')` i pozostałych kluczy `users.*`. Oba komunikaty pustej listy zastąpiono przez `__('users.empty')`. Typ konta jest prezentowany przez:

```blade
{{ $user->is_admin ? __('users.administrator') : __('users.user') }}
```

Przeniesione etykiety usunięto z JSON po sprawdzeniu ich wywołań w aplikacji. W obu językach zapewniono wartości dla wszystkich siedmiu kluczy. Nie zmieniano danych kont ani zasad autoryzacji.

### 8.2. Wszystkie teksty paginacji w `pagination.php`

Teksty przeniesiono z JSON do plików PHP dla obu języków. Wymagało to dostosowania wywołań w lokalnych szablonach. Publikację widoków, ich wybór oraz zasady utrzymania opisuje [[14-lista-uzytkownikow#6.5. Publikacja i dostosowanie widoków paginacji]].

Do `lang/pl/pagination.php` oraz `lang/en/pagination.php` dodano:

| Klucz | Polski | Angielski |
| --- | --- | --- |
| `showing` | Wyświetlono | Showing |
| `to` | – | to |
| `of` | z | of |
| `results` | wyników | results |
| `navigation` | Nawigacja po stronach | Pagination Navigation |
| `go_to_page` | Przejdź do strony :page | Go to page :page |

Istniejące `previous` i `next` pozostają w tych samych plikach. Widoki używają teraz np. `__('pagination.showing')` i `__('pagination.navigation')`. Etykieta odnośnika zachowuje podstawianie numeru:

```blade
{{ __('pagination.go_to_page', ['page' => $page]) }}
```

Dla własnego licznika dodano następnie jeden wpis `overview`:

| Klucz | Polski | Angielski |
| --- | --- | --- |
| `overview` | Wyświetlono :first–:last z :total wyników | Showing :first to :last of :total results |

Całe zdanie pozwala ustalać szyk i separator zakresu osobno dla każdego języka. Wywołanie przekazuje parametry `first`, `last` i `total`. Podłączenie licznika i usunięcie powielonego bloku opisuje [[14-lista-uzytkownikow#6.6. Jeden licznik — wykonane zmiany i dalszy plan]].

### 8.3. Rola pustego `pl.json`

Po przeniesieniu wszystkich wpisów pusty plik JSON początkowo usunięto. Na życzenie użytkownika przywrócono `lang/pl.json` z zawartością:

```json
{}
```

To poprawny pusty obiekt JSON, gotowy na szybkie, pojedyncze tłumaczenia nieprzypisane jeszcze do konkretnego obszaru. Gdy utworzą spójną grupę, można przenieść je do odpowiedniego pliku PHP i zmienić wywołania w widokach. Nie utrzymujemy tych samych tłumaczeń równolegle w JSON i plikach obszarowych.

Obecny podział:

- `users.php` — własne etykiety i komunikaty modułu użytkowników;
- `pagination.php` — teksty paginacji, w tym licznik i dostępność;
- `pl.json` — pozostawiony pusty wspólny słownik na pojedyncze wpisy.

### 8.4. Kontrole po przeniesieniu

Wykonano:

- sprawdzenie odczytu wszystkich siedmiu kluczy `users.*` dla `pl` i `en` — poprawne;
- sprawdzenie składni PHP obu plików `users.php` i obu `pagination.php` — bez błędów;
- `vendor/bin/pint --dirty --format agent` — pierwsze uruchomienie poprawiło końcową pustą linię w polskim `pagination.php`, kolejne przeszło bez poprawek;
- `git diff --check` — bez komunikatów dla śledzonych zmian;
- parsowanie przywróconego `pl.json` — poprawny obiekt JSON.

Kontrole kompilacji i renderowania widoków opisano w [[14-lista-uzytkownikow#6.5. Publikacja i dostosowanie widoków paginacji]]. Nie dodawano testów automatycznych ani nie uruchamiano zestawu PHPUnit lub PHPStan. Późniejszy zrzut z jednym licznikiem i angielskimi nagłówkami opisano w punkcie 6.6 rozdziału 14.

### 8.5. Polskie komunikaty walidacji

Na prośbę użytkownika dodano `lang/pl/validation.php` z 137 komunikatami na podstawie [polskich tłumaczeń Laravel Lang](https://github.com/Laravel-Lang/lang/blob/main/locales/pl/php.json). Dopasowano strukturę do lokalnego `lang/en/validation.php` dla Laravel 13.38.0 i sprawdzono kompletność kluczy komunikatów oraz zgodność symboli zastępczych, np. `:attribute` i `:min`. W pliku zachowano oznaczenie źródła i licencję MIT. Nie instalowano pakietu ani nie zmieniano angielskiego pliku.

W pierwszej wersji sekcje `custom` i `attributes` pozostawiono puste. Później dodano polskie nazwy pól do `attributes`, zgodnie z punktem 8.7. Sekcja `custom` nadal jest pusta. Nie oznacza to przetłumaczenia wszystkich komunikatów formularzy, w szczególności tekstów wpisanych bezpośrednio w kodzie.

Sprawdzono składnię PHP, formatowanie przez `vendor/bin/pint --dirty --format agent` i `git diff --check` — bez błędów. Próbne uruchomienie walidatora w kontekście aplikacji dla reguł `required`, `email` i `min` potwierdziło polskie komunikaty oraz podstawienie nazwy pola i limitu. Nie dodawano testów PHPUnit ani nie sprawdzano jeszcze formularzy w przeglądarce. Ewentualne dopracowanie brzmienia komunikatów pozostaje osobnym krokiem.

### 8.6. Uwierzytelnianie, resetowanie hasła i polskie komentarze

Na prośbę użytkownika dodano pozostałe standardowe polskie pliki:

- `lang/pl/auth.php` — komunikaty nieprawidłowych danych logowania (`failed`), błędnego hasła (`password`) i zbyt wielu prób logowania (`throttle`);
- `lang/pl/passwords.php` — komunikaty zresetowania hasła (`reset`), wysłania odnośnika (`sent`), ograniczenia ponownych prób (`throttled`), błędnego tokenu (`token`) oraz braku konta (`user`).

Zachowano klucze angielskich odpowiedników i symbol zastępczy `:seconds`. Dodano polskie odpowiedniki komentarzy opisowych obecnych w angielskich plikach `auth.php`, `passwords.php`, `pagination.php` i `validation.php`. Obejmują one przeznaczenie komunikatów oraz sekcje własnych komunikatów i nazw pól walidacji. Oryginalną treść licencji MIT tłumaczeń walidacji zachowano bez tłumaczenia.

Sprawdzono składnię PHP czterech polskich plików, zgodność kluczy komunikatów i zestawów symboli zastępczych z angielskimi odpowiednikami, formatowanie Pint oraz `git diff --check` — kontrole przeszły poprawnie. Porównanie pomijało przykładowy wpis `custom.attribute-name.rule-name` z angielskiego pliku walidacji; nie jest to standardowy komunikat reguły.

Dodanie plików językowych nie wdraża resetowania hasła ani samo nie zmienia tekstów wpisanych bezpośrednio w kontrolerach. Późniejsze podłączenie komunikatów logowania do tłumaczeń i ręczne sprawdzenia opisuje punkt 8.8.

### 8.7. Polskie nazwy pól w `attributes`

Po sprawdzeniu pól formularzy logowania i rejestracji uzupełniono sekcję w `lang/pl/validation.php`:

```php
'attributes' => [
    'name' => 'imię',
    'email' => 'adres e-mail',
    'password' => 'hasło',
    'password_confirmation' => 'potwierdzenie hasła',
],
```

Laravel podstawia te wartości w miejsce `:attribute` w komunikatach walidacji. Nie zmienia to nazw pól formularzy, kolumn w bazie ani widocznych etykiet HTML. Przykładowo komunikat dla wymaganego pola `password` brzmi teraz „Pole hasło jest wymagane.”.

Próbne uruchomienie walidatora dla reguły `required` na wszystkich czterech polach potwierdziło dokładne komunikaty z polskimi nazwami. Formatowanie Pint i `git diff --check` przeszły poprawnie. Nie dodawano testów PHPUnit; ręczne sprawdzenie formularzy w przeglądarce nadal pozostaje do wykonania.

### 8.8. Tłumaczenia komunikatów w kontrolerze logowania

Użytkownik zmienił dwa wpisy w `withErrors()` w `app/Http/Controllers/Auth/LoginController.php`:

```php
'email' => __('auth.throttle', ['seconds' => $seconds]),
```

```php
'email' => __('auth.failed'),
```

Pierwszy komunikat dotyczy ograniczenia liczby prób logowania. Parametr `seconds` zastępuje `:seconds` w pliku językowym. Drugi komunikat jest wyświetlany po nieudanej próbie uwierzytelnienia. Laravel pobiera wartości z `auth.php` dla aktywnego języka.

Reguły w `$request->validate()` już korzystają z komunikatów `validation.php`; nie trzeba zastępować nazw reguł wywołaniami tłumaczeń. Zmiana dwóch wpisów `withErrors()` nie modyfikuje logiki uwierzytelniania ani ograniczania prób.

Potwierdzone sprawdzenia:

- użytkownik potwierdził działanie komunikatu błędnego logowania w językach polskim i angielskim;
- użytkownik potwierdził, że po osiągnięciu limitu prób pojawia się komunikat ograniczenia z liczbą sekund zamiast symbolu `:seconds`; nie potwierdzono osobno obu języków dla tego scenariusza;
- użytkownik uruchomił `vendor/bin/pint --dirty --format agent` i przekazał wynik `passed`;
- przekazany `git diff` pokazał wyłącznie zastąpienie dwóch zdań wywołaniami tłumaczeń w kontrolerze;
- użytkownik potwierdził brak komunikatów z `git diff --check`.

Są to ręczne sprawdzenia oraz kontrole formatowania i różnic, nie wyniki testów PHPUnit. Nie potwierdzają jeszcze komunikatów walidacji pustych lub niepoprawnych pól formularzy opisanych w punkcie 8.7.

## 9. Aktualny stan i pozostałe prace

### 9.1. Odczyt przy aktualizacji dokumentacji

Gałąź: `codex/polish-translations`. Git pokazuje:

- zmieniony `app/Http/Controllers/Admin/UserController.php`;
- zmieniony `resources/views/admin/users/index.blade.php`;
- nieśledzony `docs/15-polskie-tlumaczenia.md`;
- nieśledzony katalog `lang/`;
- nieśledzony katalog `resources/views/vendor/` z opublikowanymi widokami paginacji.

Później potwierdzono również zmianę `app/Http/Controllers/Auth/LoginController.php` opisaną w punkcie 8.8. Powyższa lista Git pochodzi z wcześniejszego odczytu.

Ostatni potwierdzony odczyt kontrolera wskazywał `paginate(1)`. Nie potwierdzono przywrócenia limitu 10 ani języka `pl`. Nie wykonywano operacji zapisu w Git ani nie potwierdzano pushu lub scalenia etapu tłumaczeń.

### 9.2. Do wykonania — plan

1. Dokończyć układ wspólnej stopki i sprawdzić paginację zgodnie z [[14-lista-uzytkownikow#6.6. Jeden licznik — wykonane zmiany i dalszy plan]]. Usunięcie powielonego licznika jest już potwierdzone.
2. Sprawdzić w przeglądarce etykiety po przeniesieniu do plików PHP, dostępność, pustą listę oraz komunikaty walidacji formularzy z polskimi nazwami pól.
3. Przywrócić docelowy limit `paginate(10)` oraz język `pl`, a następnie sprawdzić wynik.
4. Wykonać pozostałe kontrole odpowiednie do końcowych zmian i przygotować osobny commit etapu przez użytkownika.

Tłumaczenia innych obszarów aplikacji oraz wygląd przycisków pozostają do osobnego ustalenia. Komunikaty logowania z kontrolera zostały już przeniesione do tłumaczeń i sprawdzone w zakresie opisanym w punkcie 8.8. Rozdział 2 zachowuje początkowy plan, rozdziały 4–5 opisują pierwszą wersję korzystającą z JSON, a rozdział 8 przedstawia wykonane później przeniesienie do plików obszarowych.
