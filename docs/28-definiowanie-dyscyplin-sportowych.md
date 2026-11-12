---
tags:
  - sport
---

# Definiowanie dyscyplin sportowych

## Zgłoszenie i cel

Etap 28 realizuje [issue #21 — Zdefiniować pierwsze dyscypliny sportowe i wymagania dla modelu domeny](https://github.com/SebMagacy/sportowiada/issues/21), oznaczone etykietą `obszar: sport`. To pierwszy etap projektowania architektury obsługi dyscyplin Sportowiady. Wynikiem ma być uzgodniony zakres i macierz wymagań, stanowiące podstawę późniejszego projektowania modeli i relacji.

## Potwierdzone ustalenia

- Pierwsze dyscypliny: piłka nożna, siatkówka i koszykówka.
- Pierwsza wersja obejmuje ligi. Użytkownik zaakceptował ograniczenie początkowego zakresu; puchary i turnieje z fazą grupową pozostają przyszłymi rozszerzeniami.
- Punktem wyjścia jest rywalizacja drużynowa w meczach dwóch drużyn.
- Szczegółowe warianty przepisów, punktacja, klasyfikacja i zakres statystyk wymagają dalszego uzgodnienia.
- Użytkownik poprosił o osobny czat i nowy rozdział dokumentacji z tagiem `sport`.

### Definicje: klub i drużyna

Użytkownik zaakceptował następujące rozróżnienie:

- **Klub** jest organizacją sportową, która może działać w kilku dyscyplinach, np. „KS Przykład” z drużynami piłki nożnej, siatkówki i koszykówki.
- **Drużyna** jest konkretnym zespołem w jednej dyscyplinie, np. „KS Przykład — seniorzy” w piłce nożnej albo „KS Przykład — juniorki” w siatkówce.
- Jeden klub może mieć wiele drużyn, również w tej samej dyscyplinie, np. pierwszy zespół, rezerwy i juniorów.
- Przynależność drużyny do klubu jest opcjonalna. Drużyna może funkcjonować bez klubu, np. jako amatorski zespół znajomych uczestniczący w lidze. Użytkownik zaakceptował tę zasadę.

Rozróżnienie pozwala przechowywać wspólne dane klubu raz i osobno zgłaszać poszczególne drużyny do rozgrywek. To potwierdzone definicje domenowe; szczegóły zgłoszeń do rozgrywek i schemat bazy danych wymagają dalszych ustaleń.

### Definicje: osoba, zawodnik i konto użytkownika

Użytkownik zaakceptował następujące zasady:

- Dane jednej osoby zapisujemy raz. Ta sama osoba może uprawiać wiele dyscyplin sportowych.
- Zawodnik to osoba uczestnicząca w rywalizacji sportowej. Jej udział w drużynach, występy i statystyki rozróżniamy według dyscypliny; rozpoczęcie kolejnej dyscypliny lub zmiana drużyny nie wymaga tworzenia drugiej osoby.
- Konto użytkownika służy do logowania i określania uprawnień w serwisie. Zawodnik może istnieć bez konta użytkownika, a użytkownik serwisu nie musi być zawodnikiem.

Przykład: Jan Kowalski gra w piłkę nożną i koszykówkę. Zachowujemy jedną osobę i osobno jej udział oraz statystyki w obu dyscyplinach. Administrator może wprowadzić ją do składów bez zakładania jej konta.

Szczegóły powiązania osoby z kontem wymagają dalszych ustaleń.

### Historia zawodnika i uzupełnianie danych historycznych

Użytkownik zaakceptował zachowanie historii przy zmianie drużyny i wskazał wymaganie dopisywania danych historycznych:

- Przynależność zawodnika do drużyny dotyczy określonego okresu. Zmiana drużyny nie nadpisuje wcześniejszej przynależności.
- Dawne występy i statystyki pozostają przypisane do drużyny, którą zawodnik reprezentował w danym meczu.
- System ma umożliwiać dopisywanie danych historycznych, również po zakończeniu opisywanego okresu lub rozgrywek. Data wprowadzenia danych nie musi odpowiadać dacie zdarzenia sportowego.

Przykład: po przejściu Jana z drużyny A do B można uzupełnić jego wcześniejszy występ w drużynie A bez zmiany jego późniejszych występów w drużynie B.

Użytkownik zaakceptował również stopniowe uzupełnianie niepełnych danych historycznych:

- Można zapisać znane informacje i później uzupełnić pozostałe. Przykładowo dawny mecz może mieć zapisany wynik bez składów i strzelców.
- Brak informacji oznacza „nie wiadomo”, a nie zero. Nie wymagamy wymyślania brakujących szczegółów w celu zapisania znanych danych.

Szczegółowy zakres danych historycznych, dokładność dat i minimalny zestaw danych wymaganych do utworzenia poszczególnych wpisów wymagają dalszego uzgodnienia.

### Definicje: liga, sezon i edycja rozgrywek

Użytkownik zaakceptował następujące rozróżnienie:

- **Liga** to rozgrywki mające własną tożsamość, np. „Miejska Liga Koszykówki”, kontynuowane w kolejnych edycjach.
- **Sezon** to osobna encja domenowa opisująca nazwany okres, współdzielona przez różne ligi. Nie narzuca układu jesień–wiosna. Przykładowe oznaczenia to „2026/2027”, „2026”, „Wiosna 2026” lub „Lato 2026”.
- **Edycja rozgrywek** łączy konkretną ligę z sezonem i ma własne terminy, uczestników, mecze, regulamin oraz tabelę. Konkretne daty należą do edycji, dzięki czemu ligi korzystające z tego samego sezonu mogą mieć różne kalendarze.
- Zmiana uczestników lub zasad w nowej edycji nie zmienia danych poprzednich edycji.

Przykład: edycja ligi piłkarskiej w sezonie „2026” trwa od marca do listopada, a edycja ligi koszykówki w tym samym sezonie od maja do sierpnia.

Potwierdzono rozdzielenie pojęć i osobną encję sezonu. Modele, tabele i szczegółowe ograniczenia relacji pozostają do zaprojektowania w późniejszym etapie.

### Drużyna a udział w edycji rozgrywek

Użytkownik zaakceptował oddzielenie trwałej drużyny od jej zgłoszenia do konkretnej edycji rozgrywek. Ta sama drużyna może uczestniczyć w kolejnych edycjach; każdy udział ma własny skład, mecze i miejsce w tabeli. Nie tworzymy nowej drużyny wyłącznie z powodu rozpoczęcia kolejnej edycji.

## Zmiana kierunku pracy

Użytkownik wstrzymał dalsze ustalanie wymagań i poprosił o rozpoczęcie widoku dyscyplin. Szczegółowy zakres widoku wymaga ustalenia. Sam widok nie został jeszcze wykonany; pozostałe punkty analizy pozostają otwarte.

## Kierunek architektury do oceny

Wspólne dane osób, klubów, drużyn i organizacji rozgrywek oraz oddzielone reguły wyniku i klasyfikacji. Rozróżnienie konta użytkownika i zawodnika oraz wymaganie zachowania historii zostały potwierdzone powyżej; techniczny sposób ich realizacji wymaga dalszych ustaleń. Bramki w piłce nożnej, sety i punkty w siatkówce oraz punkty, kwarty i dogrywki w koszykówce nie powinny być przedwcześnie sprowadzane do identycznej struktury.

To propozycja do oceny, a nie zatwierdzony schemat bazy danych. Rozważany wspólny rdzeń powinien umożliwiać korzystanie z tych samych reguł przez własny panel i ewentualny Filament. Zgody promotora na Filament ani instalacji tego pakietu nie potwierdzono.

## Plan dalszych ustaleń

1. Doprecyzować pozostałe relacje między klubem, drużyną, dyscypliną i zawodnikiem; rozróżnienie klubu i drużyny zostało zaakceptowane.
2. Ustalić zgłoszenie drużyny i skład w edycji rozgrywek; rozróżnienie ligi, współdzielonego sezonu i edycji zostało zaakceptowane.
3. Opisać minimalne dane meczu, statusy, wynik końcowy i jego części dla każdej dyscypliny.
4. Uzgodnić punktację tabel i kolejność kryteriów klasyfikacji zależnie od regulaminu rozgrywek.
5. Wybrać minimalne statystyki pierwszej wersji i zapisać odłożone rozszerzenia.
6. Zestawić macierz wymagań i uzyskać akceptację jako podstawę kolejnego etapu.

## Kontekst porównania

Użytkownik ma wieloletnie doświadczenie z [JSM SportsManagement](https://github.com/diddipoeler/sportsmanagement). Wstępnie porównano wybrane elementy JSM, [SportsPress](https://github.com/ThemeBoy/SportsPress), [CourtHive](https://github.com/CourtHive/competition-factory) i [Bracket](https://github.com/evroon/bracket). Był to przegląd fragmentów schematów, kodu i dokumentacji, bez pełnego audytu. Wnioskiem roboczym jest rozdzielenie danych uczestników, struktury rozgrywek i reguł wyniku; nie kopiowanie całej architektury innego systemu.

## Stan wykonania i weryfikacja

Zapisano dotychczasowe ustalenia, w tym zaakceptowane przez użytkownika definicje klubu i drużyny. Potwierdzeniem tego kroku jest akceptacja użytkownika w rozmowie. Nie utworzono modeli ani tabel domeny sportowej, nie implementowano zasad lig i nie wykonano ich testów. Przed utworzeniem tego pliku katalog roboczy był czysty. Rozdział jest nowy i niecommitowany.

## Sposób pracy i granice etapu

Praca po polsku, po jednym małym kroku, z wyjaśnieniem celu i mechanizmu oraz kryterium sukcesu; następnie oczekiwanie na odpowiedź użytkownika. Użytkownik wykonuje edycje aplikacji, polecenia i Git, chyba że wyraźnie zleci je asystentowi. Asystent aktualizuje ten rozdział po potwierdzonych ustaleniach bez ponownego pytania o zgodę, oddzielając plany od wykonanych działań i weryfikacji.

Zakres #21 obejmuje analizę i wymagania. Implementacja migracji, modeli, terminarzy, formularzy i silnika statystyk jest poza tym etapem. Bez nowych zależności, instalacji Filament, nowej gałęzi i worktree. #19 (Bulk actions) i #20 (wybór wszystkich wyników) pozostają odrębnymi zadaniami CMS.
