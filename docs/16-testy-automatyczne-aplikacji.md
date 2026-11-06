# Testy automatyczne aplikacji

## 1. Punkt wyjścia

Lista użytkowników, jej paginacja i dostosowanie stopki są opisane w [[14-lista-uzytkownikow]], a tłumaczenia w [[15-polskie-tlumaczenia]]. Poprawkę przekierowania z numeru strony przekraczającego dostępny zakres zapisano w commicie `0b71bf3` i opublikowano na `origin/main`. Przed utworzeniem tej notatki wynik `git status --short --branch` wskazywał czysty katalog roboczy i zgodność `main` z `origin/main`.

Sprawdzono listę z istniejącymi kontami, nawigację na wąskim ekranie oraz przekierowanie ze strony poza zakresem. Na początku tego etapu nie sprawdzono jeszcze widoku listy, dla której zapytanie nie zwraca żadnego użytkownika. Pusta strona poza zakresem nie jest tym samym przypadkiem co brak wyników całego zapytania.

## 2. Pierwotny plan ręcznego sprawdzenia

Sprawdzić sposób prezentacji pustej listy bez usuwania kont i bez zmiany danych w bazie. Istniejące konto administratora pozostaje potrzebne do wejścia do panelu.

Pierwotnie planowano tymczasowe dodanie do zapytania w kontrolerze warunku, który nie zwróci żadnego konta. Tego wariantu nie wykonano. Użytkownik zdecydował następnie sprawdzić pustą listę testem automatycznym, opisanym w rozdziale 6, bez zmiany zapytania aplikacji.

## 3. Pierwotna kolejność pracy — niewykonany wariant ręczny

1. Odczytać aktualne instrukcje projektu, stan Git i kod listy użytkowników.
2. Tymczasowo ograniczyć zapytanie tak, aby zwracało pusty wynik, zachowując limit `paginate(10)`.
3. Otworzyć pierwszą stronę listy bez parametru wskazującego dalszą stronę.
4. Sprawdzić komunikat pustej tabeli, stopkę i brak niepotrzebnej nawigacji.
5. Usunąć tymczasowy warunek i potwierdzić ponowne wyświetlenie istniejących kont.
6. Sprawdzić różnice w Git, aby nie zapisać tymczasowego warunku w commicie.
7. Uzupełnić ten rozdział wyłącznie o wykonane czynności i potwierdzone wyniki. Jeśli ujawni się problem, ustalić osobno potrzebną poprawkę.

## 4. Kryteria pierwotnego planu ręcznego

- Tabela wyświetla polski komunikat braku użytkowników i nie zawiera wierszy kont.
- Stopka nie pokazuje pustego zakresu liczb ani przycisków stron.
- Widok nie zgłasza błędu ani nie powoduje pętli przekierowań.
- Po usunięciu tymczasowego warunku ponownie widać istniejące konta.
- W bazie nie usunięto ani nie zmieniono żadnego konta.

Plan obejmował również ocenę, czy powtórzenie komunikatu braku wyników w tabeli i stopce jest czytelne. Tej oceny w przeglądarce nie wykonano. Nie zakładamy z góry konieczności zmiany interfejsu.

## 5. Sposób pracy i status

Pracujemy po polsku, po jednym małym kroku: cel, mechanizm, kryterium sukcesu, następnie wynik użytkownika. Użytkownik wykonuje polecenia i edycje kodu. Dokumentację aktualizujemy na wyraźną prośbę.

Podczas tworzenia tej notatki istniał wyłącznie plan ręcznego sprawdzenia. Później użytkownik zmienił decyzję o odkładaniu testów i rozpoczął naukę PHPUnit na tym scenariuszu. Test automatyczny został wykonany; pierwotnego wariantu ręcznego nie realizowano.


## 6. Wykonane sprawdzenie automatyczne — 06.11.2026

### 6.1. Podstawy i środowisko

Omówiono schemat testu: przygotowanie warunków → wykonanie działania → sprawdzenie wyniku. Asercja określa oczekiwany wynik i powoduje niepowodzenie testu, gdy odpowiedź go nie spełnia. Wybrano test Feature, ponieważ scenariusz obejmuje trasę, uwierzytelnienie, autoryzację, kontroler, bazę i widok.

Wyniki przekazane przez użytkownika potwierdziły Laravel 13.38.0, PHPUnit 12.5.40 i włączony sterownik `pdo_sqlite` (SQLite 3.46.1). Odczyt `phpunit.xml` potwierdził konfigurację `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:` i pusty `DB_URL`. Baza testowa działa w pamięci; nie usuwano istniejących kont ani nie zmieniano zapytania w kontrolerze.

### 6.2. Utworzenie i przygotowanie testu

Użytkownik utworzył szkielet poleceniem:

```bash
php artisan make:test Http/Controllers/Admin/UserControllerTest --phpunit --no-interaction
```

Plik: `tests/Feature/Http/Controllers/Admin/UserControllerTest.php`.

Najpierw uruchomiono wygenerowany przykład sprawdzający odpowiedź HTTP 200 strony głównej. Wynik `1 passed (1 assertions)` potwierdził możliwość uruchamiania testów. Przykład następnie zastąpiono właściwym scenariuszem; pozostałość zakomentowanej metody usunięto.

Klasa dziedziczy po `Tests\TestCase` i używa traitu `LazilyRefreshDatabase`. Trait przygotowuje bazę przy pierwszym jej użyciu w teście i zapewnia izolację zmian. Sam import nad klasą nie włącza tego zachowania — potrzebne jest również `use LazilyRefreshDatabase;` wewnątrz klasy.

### 6.3. Administrator bez rekordu w bazie

Metoda `test_admin_can_open_an_empty_users_list()` przygotowuje administratora:

```php
$admin = User::factory()->make([
    'is_admin' => true,
]);

$response = $this->actingAs($admin)
    ->get(route('admin.users.index'));
```

`make()` tworzy obiekt modelu bez zapisania rekordu. Użycie `create()` dodałoby administratora do bazy testowej i lista przestałaby być pusta. `actingAs()` ustawia obiekt jako uwierzytelnionego użytkownika na potrzeby żądania; nie testuje formularza logowania. Bramka `access-admin` nadal sprawdza uprawnienie `is_admin`.

### 6.4. Pięć asercji

| Sprawdzenie | Znaczenie |
| --- | --- |
| `assertStatus(200)` | Lista jest dostępna dla administratora bez przekierowania i błędu HTTP. |
| `assertSeeText(__('users.empty'))` | Odpowiedź zawiera komunikat braku użytkowników w aktualnym języku. |
| `assertDontSeeText()` z `pagination.overview`, wartościami `null`, `null`, `0` | Nie pojawia się konkretny tekst licznika z pustym początkiem i końcem zakresu. |
| `assertViewHas('users', ...)` i `total() === 0` | Paginator przekazany do widoku ma zero wyników całego zapytania. |
| `assertDontSee()` dla `aria-label` nawigacji | HTML nie zawiera oznaczenia nawigacji paginacji. |

W ostatniej asercji etykieta pochodzi z `pagination.navigation`. Funkcja `e()` escapuje ją jak Blade, a argument `false` wyłącza ponowne escapowanie całego szukanego fragmentu HTML przez asercję.

Oczekiwanie komunikatu początkowo zawierało polskie zdanie. Zastąpiono je `__('users.empty')`, aby scenariusz korzystał z aktualnego języka aplikacji. Nie jest to osobne sprawdzenie poprawności tłumaczeń ani potwierdzenie uruchomienia testu w obu językach.

Do kodu dodano komentarze wyjaśniające niezapisanego administratora i cel poszczególnych asercji. Komentarze mają charakter pomocniczy podczas nauki; nie zmieniają działania testu.

### 6.5. Celowo niespełniona asercja

W ramach nauki tymczasowo zmieniono oczekiwany komunikat na „Tutaj powinien pojawić się użytkownik.”. Test zakończył się jako `FAILED`, wskazując niespełnioną asercję i jej linię. Pokazana odpowiedź zawierała „Brak użytkowników.” w tabeli i stopce. Po przywróceniu właściwego oczekiwania test ponownie przeszedł. Tymczasowego błędnego tekstu nie ma w końcowym kodzie.

### 6.6. Wynik i ograniczenia

Polecenie:

```bash
php artisan test --compact tests/Feature/Http/Controllers/Admin/UserControllerTest.php
```

Wynik pierwszego scenariusza, przed rozszerzeniem pliku o kolejne testy, potwierdzony przez użytkownika: **1 passed (5 assertions)**. Asystent również uruchomił ten test po dodaniu komentarzy; wszystkie pięć asercji przeszło. `vendor/bin/pint --dirty --format agent` zakończył się poprawnie.

Test wykonuje żądanie wewnątrz aplikacji i sprawdza dane widoku oraz odpowiedź HTML. Nie uruchamia przeglądarki ani JavaScriptu, nie ocenia wyglądu, responsywności lub czytelności powtórzonego komunikatu. Obecność komunikatu sprawdza w całej odpowiedzi, a nie osobno w tabeli i stopce. Nie obejmuje logowania przez formularz, odmowy dostępu innym rolom ani przypadku numeru strony poza zakresem.

Na tym etapie nie wykonywano jeszcze pełnego zestawu testów ani PHPStan dla nowego testu. Przy tym odczycie `main` był zgodny z lokalnym refem `origin/main`; plik testu i ten rozdział pozostawały nieśledzone, a `docs/README.md` zmieniony. Nie wykonano commitu ani pushu tych zmian.


## 7. Rozszerzenie zestawu testów aplikacji

### 7.1. Zakres wykonanej pracy

Po pierwszym teście pustej listy użytkownik poprosił o przygotowanie testów dotychczasowych funkcji aplikacji. Dodano testy Feature w PHPUnit oraz testy logiki JavaScript z użyciem narzędzi wbudowanych w Node.js. Nie instalowano nowych zależności.

| Plik w `tests/` | Sprawdzane zachowania |
| --- | --- |
| `Feature/Http/Controllers/Admin/UserControllerTest.php` | Pusta lista, dostęp gościa i zwykłego użytkownika, kolejność kont według ID, etykiety ról, limit 10 kont, druga strona, przekierowanie spoza zakresu także przy pustym wyniku, escapowanie imienia i angielski komunikat pustej listy. |
| `Feature/Http/Controllers/Auth/LoginControllerTest.php` | Formularz, poprawne uwierzytelnienie i przekierowanie według roli, regeneracja identyfikatora sesji, błędne dane, nieznane konto i złe hasło, zachowanie e-maila bez hasła, blokada po pięciu błędach, jej wygaśnięcie i wyczyszczenie licznika po sukcesie. |
| `Feature/Http/Controllers/Auth/RegistrationControllerTest.php` | Formularz, zapis zwykłego konta z zahaszowanym hasłem, odrzucenie próby nadania sobie `is_admin`, walidacja danych i potwierdzenia hasła, zajęty e-mail, zachowanie bezpiecznych danych formularza oraz graniczne długości imienia i hasła. |
| `Feature/Http/Controllers/Auth/LogoutControllerTest.php` | Wylogowanie, usunięcie danych sesji, zmiana identyfikatora sesji i tokenu CSRF, brak dostępu do panelu po wylogowaniu, odmowa operacji dla gościa i brak wylogowania metodą GET. |
| `Feature/AdminDashboardTest.php` | Przekierowanie gościa do logowania, odmowa dostępu zwykłemu kontu oraz pulpit i nawigacja dla administratora. |
| `Feature/AccessAdminGateTest.php` | Decyzje bramki `access-admin` dla administratora, zwykłego konta i gościa. |
| `Feature/AuthFormsTest.php` | Escapowanie poprzedniego e-maila w obu formularzach i imienia w rejestracji, renderowanie przekazanych komunikatów błędów i bezpieczne wyświetlenie pól po zachowaniu tablicy w sesji. |
| `Feature/PublicHomeTest.php` | Widok strony głównej z kontenerem Vue i odnośnikiem do logowania. |
| `Frontend/navigation.test.mjs` | Zwijanie i rozwijanie panelu, etykiety dostępności, zapis i odczyt stanu w localStorage, montowanie Vue przy obecności kontenera oraz wykonanie kodu bez elementów panelu i Vue. |

Dotychczasowe pliki `Feature/ExampleTest.php` i `Unit/ExampleTest.php` pozostawiono bez zmian. Są uwzględnione w wyniku całego zestawu PHP.

### 7.2. Organizacja testów i danych

Każdy test przygotowuje potrzebne warunki, wykonuje działanie i sprawdza wynik. Nazwy metod opisują zachowania. Polskie komentarze wyjaśniają scenariusze i mniej oczywiste decyzje dotyczące sesji, uprawnień, bezpieczeństwa i paginacji.

Atrybut `#[DataProvider(...)]` wskazuje metodę dostarczającą nazwane zestawy danych. PHPUnit uruchamia test osobno dla każdego zestawu, np. dla administratora i zwykłego użytkownika lub poszczególnych błędów walidacji. Dlatego liczba wykonanych testów jest większa od liczby plików i metod testowych.

Testy korzystające z bazy używają `LazilyRefreshDatabase` i skonfigurowanej bazy SQLite w pamięci. `create()` zapisuje potrzebne konta w bazie testowej. `make()` służy m.in. do zachowania pustej listy oraz sprawdzania samej bramki bez zapisu konta. Nie usuwano ani nie zmieniano kont lokalnej aplikacji.

Testy limitu logowania kontrolują czas przez `freezeTime()` i `travel()`, zamiast czekać na rzeczywiste wygaśnięcie blokady.

### 7.3. Poprawka wykryta podczas testowania formularzy

Testowanie ujawniło błąd renderowania po zachowaniu tablicy zamiast tekstowego imienia lub e-maila. Zabezpieczenie przez `is_string()` w widokach rejestracji i logowania oraz trzy przypadki odtwarzające problem opisuje [[11-rejestracja-uzytkownikow#7. Bezpieczne przywracanie danych formularza po błędzie walidacji]].

Poprawkę aplikacji i jej opis zapisano w commicie `2afbe13`. Użytkownik przekazał wynik udanego pushu `0b71bf3..2afbe13 main -> main`. Ten commit nie zawiera nowych plików testowych.

### 7.4. Uruchamianie testów

Cały zestaw PHP:

```bash
php artisan test --compact
```

Jeden plik, np. formularze:

```bash
php artisan test --compact tests/Feature/AuthFormsTest.php
```

Testy JavaScript uruchamia się oddzielnie:

```bash
node tests/Frontend/navigation.test.mjs
```

Nie dodano nowego skryptu do `package.json`; polecenie PHPUnit nie uruchamia testów JavaScript.

### 7.5. Potwierdzone wyniki i ograniczenia

Przy uzupełnianiu tego rozdziału ponownie wykonano oba zestawy:

- **PHP: 57 testów, 282 asercje — wszystkie zaliczone.**
- **JavaScript: 4 testy — wszystkie zaliczone.**

Wcześniejszy zestaw 54 testów PHP, przed dodaniem trzech przypadków tablic w formularzach, przeszedł również w losowej kolejności. Pint przeszedł. PHPStan uruchomiony jawnie dla dodanych testów Feature zakończył się poprawnie; osobno potwierdzono analizę zmienionego `AuthFormsTest.php` po poprawce formularzy.

Próba analizy całego katalogu `tests` wskazała istniejący przykład `Unit/ExampleTest.php`: `assertTrue(true)` zawsze jest prawdziwe. Tego przykładu nie zmieniano. Wyniku analizy nowych testów nie należy przedstawiać jako bezbłędnej analizy wszystkich plików testowych.

Testy PHP wykonują żądania w aplikacji oraz sprawdzają odpowiedzi, sesję i bazę. Test renderowania komunikatów w `AuthFormsTest` przekazuje worek błędów bezpośrednio do widoku — nie potwierdza całego przepływu błędów przez middleware. Testy poprzednich wartości tablicowych odtwarzają stan sesji przed GET formularza, bez wcześniejszego POST w tym samym scenariuszu.

Testy JavaScript wykonują rzeczywisty kod aplikacji w kontrolowanym środowisku z uproszczonym DOM, localStorage i zastąpionym mechanizmem montowania Vue. Nie uruchamiają prawdziwej przeglądarki i nie sprawdzają renderowania komponentu Vue. Ocenę wyglądu, responsywności i rzeczywistej interakcji w przeglądarce nadal wykonuje się osobno. Przejście zestawu potwierdza opisane scenariusze, a nie każde możliwe zachowanie aplikacji.

### 7.6. Stan zapisu pracy

Przy aktualizacji `main` był zgodny z lokalnym refem `origin/main`. Poza commitami pozostawały nowe pliki testów, niniejszy rozdział oraz zmiana wpisu 16 w `docs/README.md`. Nie wykonano ich commitu ani pushu. Poprawka Blade opisana w sekcji 7.3 była już opublikowana osobno.
