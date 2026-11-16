---
tags:
  - sport
  - filament
---

# 34. Grafika dyscypliny na liście Filamentu

## Cel i zakres

Wyświetlenie grafiki konkretnej dyscypliny obok nazwy na liście Filamentu, jako kolejny etap po [rozdziale 33](33-opis-i-grafika-dyscypliny.md). Uwzględniamy dyscypliny bez grafiki oraz unikanie zapytań N+1. Opis pozostaje w formularzu, a gwizdek nawigacji pozostaje odrębną ikoną. Bez nowych zależności i wspólnej biblioteki Multimedia.

Nie przypisano osobnego issue. Powiązane wcześniejsze zgłoszenia [#21](https://github.com/SebMagacy/sportowiada/issues/21), [#22](https://github.com/SebMagacy/sportowiada/issues/22) i [#24](https://github.com/SebMagacy/sportowiada/issues/24) nie są automatycznie zamykane ani aktualizowane w tym etapie.

## Potwierdzony przegląd początkowy

Asystent odczytał instrukcje, reguły projektu, kontekst, rozdział 33 oraz aktualny zasób i model. Git przed utworzeniem tego rozdziału był czysty; ostatni commit to `f598009`, zawierający potwierdzenie publikacji poprzedniego etapu.

Potwierdzone wersje: Laravel `13.42.0`, Filament i integracja Spatie `5.11.3`, Media Library `11.23.13`. Model `Discipline` ma kolekcję `icon` z `singleFile()`. Lista obecnie zawiera nazwę, alias i ID, bez grafiki.

Wyszukiwanie Boost nie zwróciło dokumentacji klasy `SpatieMediaLibraryImageColumn`. Asystent sprawdził jej kod w zainstalowanym pakiecie: obsługuje wybór kolekcji i samodzielnie dodaje eager loading relacji `media` przez `applyEagerLoading()`. To podstawa wyboru kolumny; działanie w tej aplikacji wymaga jeszcze weryfikacji.

## Plan — niewykonany

1. Zaimportować i dodać `SpatieMediaLibraryImageColumn` przed nazwą, korzystając z kolekcji `icon`.
2. Sprawdzić wygląd rekordów z grafiką i bez niej oraz ładowanie mediów.
3. Wykonać odpowiednie kontrole i poprowadzić użytkownika przez commit oraz push.

Kod aplikacji nie został zmieniony w ramach przeglądu. Nie uruchamiano testów nowego etapu.

## Wykonanie i weryfikacja

### 1. Import kolumny mediów

Użytkownik potwierdził dodanie importu. Asystent odczytał `app/Filament/Resources/Disciplines/DisciplineResource.php` i potwierdził `use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;`. Import umożliwia użycie krótkiej nazwy klasy w definicji tabeli; sama kolumna nie została jeszcze dodana. Nie sprawdzano działania listy ani diagnostyki edytora. Uporządkowanie importów przez Pint pozostaje do wykonania na etapie kontroli.

### 2. Definicja kolumny grafiki

Po potwierdzeniu użytkownika asystent odczytał zasób i potwierdził dodanie `SpatieMediaLibraryImageColumn::make('icon')` przed kolumną nazwy. Kolumna używa etykiety `__('disciplines.icon')`, kolekcji `icon`, publicznej widoczności i rozmiaru `imageSize(40)`. Korzysta z istniejących mediów dyscypliny; nie dodaje kolumny do bazy. Formularz i ikona nawigacji pozostają bez zmian. Potwierdzono kod, ale nie wygląd ani zachowanie listy w przeglądarce. Kontrola rekordów z grafiką i bez niej, zapytań oraz końcowe kontrole pozostają do wykonania.

### 3. Ręczne sprawdzenie wyglądu listy

Użytkownik przekazał zrzut listy Filamentu: przy „Piłce nożnej” widoczna jest miniatura wgranej grafiki, a przy „Siatkówce” i „Koszykówce” komórki grafiki są puste. Kolumna znajduje się przed nazwą; widoczne są również aliasy, ID i akcje edycji. Zrzut potwierdza poprawne wyświetlenie listy w tym scenariuszu, bez widocznego komunikatu błędu. Nie potwierdza liczby zapytań ani braku N+1; kontrola ładowania mediów oraz końcowe kontrole pozostają do wykonania.

### 4. Formatowanie kodu

Użytkownik uruchomił `vendor/bin/pint --dirty --format agent` i przekazał wynik `fixed`: fixer `ordered_imports` uporządkował importy w `DisciplineResource.php`. Asystent potwierdził kolejność importów w diffie. Nie zgłoszono błędów formatowania. Testy regresji i analiza statyczna bieżącego etapu pozostają do wykonania.

### 5. Testy regresji panelu dyscyplin

Użytkownik wykonał `php artisan test --compact tests/Feature/FilamentDisciplineTest.php`: **23 passed / 153 assertions**, czas 1,45 s. Wynik potwierdza przejście istniejących testów panelu po dodaniu kolumny. Nie dodano nowych testów wyświetlania grafiki ani liczby zapytań; wynik nie stanowi osobnego potwierdzenia braku N+1. Asystent ponownie odczytał diff zasobu i wykonał `git diff --check` bez błędów. Analiza statyczna i kontrola ładowania mediów pozostają do wykonania.

### 6. Zwężenie kolumny grafiki

Na prośbę użytkownika dopasowano szerokość kolumny. Asystent potwierdził w kodzie `label('')` i `width('1%')`, z zachowaniem miniatury 40 px. Pusta etykieta usuwa długi nagłówek, a szerokość procentowa pozwala przeglądarce zwęzić kolumnę z uwzględnieniem zawartości i odstępów. Użytkownik odpowiedział „ok”; nie przekazał nowego zrzutu, więc końcowego wyglądu po tej zmianie asystent nie obserwował. Wcześniejsze testy i Pint wykonano przed zmianą szerokości. Analiza statyczna nadal nie ma potwierdzonego wyniku.

### 7. Analiza statyczna

Użytkownik wykonał `vendor/bin/phpstan analyse --memory-limit=1G`. Analiza 16 plików przy użyciu `phpstan.neon` zakończyła się `[OK] No errors`. Wynik dotyczy kodu po zwężeniu kolumny. Asystent ponownie potwierdził definicję kolumny w zasobie. Brak N+1 wymaga jeszcze kontroli danych rzeczywistego żądania; analiza statyczna go nie potwierdza.

### 8. Wstępny przegląd żądań Debugbar

Użytkownik wykonał `php artisan debugbar:find --issues --max=50`. Raport wskazał dwa żądania POST Livewire z godziny 17:27:55–17:27:58: upload pliku z trzema potencjalnymi grupami N+1 oraz aktualizację komponentu z dwiema grupami duplikatów. Nie ustalono ich związku z aktualną listą dyscyplin; nie stanowią dowodu N+1 przy wyświetlaniu grafik na liście. Do dalszej kontroli trzeba wskazać świeże żądanie GET listy. Nie zmieniano kodu na podstawie tych ostrzeżeń.

### 9. Wskazanie żądania listy

Użytkownik przekazał wynik filtrowania żądań GET po URI dyscyplin. Najnowsze żądanie listy `/admin/disciplines`, `01M7G4MSQDCZZB1VHPT4FNVZ1W` z 15 listopada 2026 r., godz. 17:38:54 według Debugbar, zakończyło się statusem 200: 97,06 ms, 4 MB, 9 zapytań w 45,86 ms. Pozostałe pokazane żądania listy również mają po 9 zapytań. Sama liczba nie potwierdza sposobu ładowania mediów; następnym krokiem jest analiza SQL wskazanego żądania.

### 10. Potwierdzenie wspólnego ładowania mediów

Użytkownik wykonał `php artisan debugbar:queries 01M7G4MSQDCZZB1VHPT4FNVZ1W`. Raport zawiera 9 zapytań, 45,86 ms łącznie i 0 nieudanych. Zapytanie nr 4 pobiera media wspólnie dla dyscyplin przez `model_id in (1, 2, 3)` i `model_type = App\Models\Discipline`, z sortowaniem po `order_column`. Nie ma osobnych zapytań do mediów dla kolejnych wierszy. Potwierdza to działanie eager loading i brak N+1 dla grafik w sprawdzonym żądaniu listy trzech dyscyplin. Nie badano innych żądań ani dużego zbioru danych. Asystent przejrzał końcowy diff zasobu i wykonał `git diff --check` bez błędów. Commit i push pozostają do wykonania.

### 11. Końcowe formatowanie i przygotowanie do commita

Użytkownik ponownie wykonał `vendor/bin/pint --dirty --format agent` po dopasowaniu szerokości kolumny: wynik `passed`, bez poprawek. Asystent sprawdził `git diff --check` bez błędów. Grafika i puste komórki zostały sprawdzone na zrzucie przed zwężeniem; kod zwężenia potwierdzono przez odczyt. Istniejące testy panelu przeszły przed zmianą szerokości (23 / 153), PHPStan po niej zakończył się bez błędów, a rzeczywiste żądanie listy potwierdziło wspólne ładowanie mediów. Nie dodano nowych testów i nie uruchamiano pełnej suity w tym etapie. Commit i push pozostają niepotwierdzone.

## Piktogramy dyscyplin — źródła i warunki użycia

15 listopada 2026 r. użytkownik wskazał trzy piktogramy z Wikimedia Commons do wykorzystania w Sportowiadzie. Asystent sprawdził sekcje „Licensing” stron źródłowych. Wszystkie trzy mają status **Public Domain — domena publiczna**; autorzy to **Parutakupiu** (oryginalny rysunek) i **Thadius856** (wersja SVG).

| Dyscyplina | Lokalny plik PNG | Źródło i potwierdzenie statusu |
| --- | --- | --- |
| Piłka nożna | `Football_pictogram.png` | [Football pictogram.svg — Licensing](https://commons.wikimedia.org/wiki/File:Football_pictogram.svg#Licensing) |
| Siatkówka halowa | `Volleyball_(indoor)_pictogram.png` | [Volleyball (indoor) pictogram.svg — Licensing](https://commons.wikimedia.org/wiki/File:Volleyball_(indoor)_pictogram.svg#Licensing) |
| Koszykówka | `Basketball_pictogram.png` | [Basketball pictogram.svg — Licensing](https://commons.wikimedia.org/wiki/File:Basketball_pictogram.svg#Licensing) |

Zgodnie z deklaracjami autorów na stronach źródłowych pliki można wykorzystywać do dowolnego celu, także komercyjnego, oraz modyfikować, bez warunku oznaczania autorstwa, z zastrzeżeniem wymogów wynikających z prawa. Autorzy deklarują przekazanie prac do domeny publicznej, a tam, gdzie nie jest to możliwe, udzielają bezwarunkowego zezwolenia na użycie w granicach prawa. Konwersja SVG do PNG nie zmienia tych warunków. Źródła i autorów zapisujemy tutaj dla zachowania pochodzenia grafik.

Są to nieoficjalne piktogramy dyscyplin, a nie deklarowane oficjalne grafiki konkretnej edycji igrzysk. Statusu tych trzech plików nie należy przenosić na całą kolekcję: inne pobrane ikony mają również licencje CC BY lub CC BY-SA i wymagają osobnego sprawdzenia.

### Pobranie i stan wykorzystania

Asystent przygotował paczkę zawierającą **84 piktogramy w SVG i PNG o szerokości 256 px**, tabelę `licencje.csv`, metadane źródłowe i listę 36 brakujących plików. Z planowanych 120 plików nie udało się pobrać całości z powodu HTTP 429 po stronie Wikimedia, także po wskazanej przez serwer przerwie. Zestaw obejmuje również sporty zimowe i historyczne. Użytkownik wskazał lokalne pliki w katalogu `/home/sebastian/Obrazy/Icons/sportowiada-piktogramy/png/`; pliki te pozostają poza repozytorium.

Trzy powyższe PNG są zgodne z formatem przyjmowanym przez istniejący formularz. Ich wskazanie i sprawdzenie warunków użycia nie potwierdzają jeszcze wgrania do rekordów dyscyplin ani wyświetlenia tych konkretnych piktogramów w aplikacji. Nie zmieniano kodu aplikacji w ramach dokumentowania źródeł.

## Sposób pracy

## Commit zmian

### Potwierdzony push

Użytkownik przekazał udany wynik `git push origin main`: `f598009..0305cda main -> main`. Potwierdza to publikację commita `0305cda` na `main`. Poniższe stwierdzenie o niepotwierdzonym pushu opisuje stan wcześniejszy. Nie zmieniano zgłoszeń GitHub. Potwierdzenia commita i pushu w tym rozdziale oraz kontekście projektu dopisano po commicie i pozostają lokalne do kolejnego zapisu dokumentacji w Git.

Użytkownik utworzył commit `0305cda` — „Dodaj grafiki na liście dyscyplin i edytor opisu”. PHPStan w hooku GrumPHP przeszedł. Asystent potwierdził commit w lokalnym Git; obejmuje 6 plików, również późniejszy edytor opisu, normalizację pustej treści do `NULL`, jasne tło podglądu i rejestrację CSS. Weryfikacja późniejszych zmian: użytkownik potwierdził zapis i odczyt pogrubienia, testy panelu przeszły 23 / 153, PHPStan bez błędów, końcowy Pint `passed`. Szczegóły tych późniejszych prac zapisano w `docs/00-kontekst-projektu.md`. Push pozostaje niepotwierdzony. Niniejsze potwierdzenie dopisano po commicie i pozostaje lokalną zmianą dokumentacji.

Po polsku, po jednym małym kroku z celem, mechanizmem, konkretną edycją lub poleceniem i kryterium sukcesu. Użytkownik wykonuje kod, polecenia i Git; asystent uzupełnia dokumentację po potwierdzonych krokach.
