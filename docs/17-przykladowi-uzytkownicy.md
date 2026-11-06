# Przykładowi użytkownicy

## 1. Punkt wyjścia

Lista użytkowników i paginacja są opisane w [[14-lista-uzytkownikow]], a testy aplikacji w [[16-testy-automatyczne-aplikacji]]. Testy i dokumentację zapisano w commicie `e1984af`; użytkownik potwierdził jego push na `origin/main`.

Przed utworzeniem tego rozdziału odczyt Git wskazywał czysty katalog roboczy na `main`, zgodny z lokalnym refem `origin/main`. Sprawdzono pliki fabryki i głównego seedera. Nie odczytywano bieżącej zawartości bazy, więc liczba istniejących kont wymaga potwierdzenia przed dodaniem danych.

## 2. Cel etapu — plan

Dodać do lokalnej bazy przykładowe konta, aby wygodnie oglądać listę i sprawdzać paginację na kilku stronach. Wstępna propozycja to **25 zwykłych kont**, przy zachowaniu limitu 10 rekordów na stronie.

Istniejące konta, w tym administrator, mają pozostać zachowane. Nie planujemy czyszczenia bazy ani uruchamiania `migrate:fresh`. Przykładowe dane lokalnej aplikacji są osobne od danych tworzonych w bazie testowej SQLite w pamięci.

## 3. Obecne narzędzia

### 3.1. Fabryka użytkowników

Plik: `database/factories/UserFactory.php`.

Fabryka określa sposób przygotowania przykładowych atrybutów modelu. Obecnie generuje imię, adres e-mail, datę weryfikacji, zahaszowane hasło i token zapamiętania. Korzysta z Faker, m.in. `fake()->name()` i `fake()->unique()->safeEmail()`.

Fabryka nie ustawia obecnie `is_admin`. Sposób jawnego oznaczenia przykładowych kont jako zwykłych użytkowników ustalimy przy przygotowaniu seedera. Obecność `unique()` w generatorze nie oznacza sprawdzenia adresów już istniejących w bazie.

### 3.2. Główny seeder

Plik: `database/seeders/DatabaseSeeder.php`.

Seeder to klasa wykonująca przygotowany kod zasilania bazy danymi. Aktualny `DatabaseSeeder` ma zakomentowane tworzenie 10 kont oraz aktywne utworzenie jednego konta z ustalonym adresem `test@example.com`. Nie uruchamiano go w tym etapie.

Przed dodaniem danych wybierzemy sposób uruchomienia odpowiedniego seedera, aby nie wykonać przypadkowo istniejącego kodu głównego seedera. Ustalimy też zachowanie przy ponownym uruchomieniu: czy ma dodawać kolejne konta, czy utrzymywać ustalony zestaw.

## 4. Kolejne kroki — do wykonania

1. Potwierdzić wersję Laravel i potrzebne zasady dotyczące fabryk oraz seederów.
2. Sprawdzić liczbę istniejących kont bez ujawniania ich danych i potwierdzić docelowy zestaw przykładowych użytkowników.
3. Ustalić sposób identyfikowania przykładowych kont oraz zachowanie przy ponownym uruchomieniu seedera.
4. Przygotować seeder z wykorzystaniem istniejącej fabryki, bez zmiany istniejących kont.
5. Sprawdzić jego działanie testem w bazie testowej.
6. Uruchomić uzgodniony seeder w lokalnej aplikacji i sprawdzić listę oraz przechodzenie między stronami.
7. Uzupełnić dokumentację o wykonane działania i potwierdzone wyniki, a następnie przejrzeć zmiany przed zapisem w Git.

## 5. Kryteria sukcesu i status

- W lokalnej bazie znajdują się uzgodnione przykładowe konta zwykłych użytkowników.
- Istniejące konta i uprawnienia administratora pozostają zachowane.
- Lista pozwala przechodzić między stronami i pokazuje właściwe zakresy wyników.
- Zachowanie ponownego uruchomienia seedera jest określone i sprawdzone.

**Status:** otwarto rozdział dokumentacji i odczytano istniejącą fabrykę oraz główny seeder. Nie przygotowano nowego seedera, nie uruchomiono zasilania bazy i nie dodano kont. Powyższe kroki pozostają planem.

Pracujemy po jednym małym kroku: cel, mechanizm, kryterium sukcesu, następnie wynik użytkownika. Użytkownik wykonuje polecenia, edycje kodu i operacje Git; asystent nie wykonuje ich za niego bez wyraźnej prośby. Dokumentację aktualizujemy na wyraźną prośbę.
