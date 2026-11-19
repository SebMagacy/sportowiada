---
tags:
  - filament
  - vite
  - css
---

# 46. Motyw panelu Filament przez Vite

## Cel i status etapu

Rozdział utworzono 19.11.2026 na wyraźną prośbę użytkownika dla [#36 — podłączenia własnych stylów panelu do Vite jako motywu](https://github.com/SebMagacy/sportowiada/issues/36).

Cel: podczas pracy lokalnej zmiany CSS panelu mają być odświeżane przez `npm run dev`, bez ponawiania `php artisan filament:assets` po każdej edycji własnego arkusza. Zasoby do publikacji mają powstawać przez `npm run build`.

**Status: motyw skonfigurowany, build wykonany poprawnie i zastąpiona rejestracja osobnego CSS usunięta; trwa weryfikacja.** Asystent potwierdził wpis w manifeście, wybrane własne selektory w wynikowym CSS i usunięcie rejestracji z providera. Sprawdzenie panelu pozostaje niewykonane.

To osobny etap po częściowej realizacji #34, opisanej w [rozdziale 45](45-pasek-narzedzi-list-filament.md). Nie zamyka #34 ani nie obejmuje napraw #37 (zapis logu) i #38 (odmowa usuwania dyscypliny powiązanej z ligami). Bieżący widok roboczy: **1024 × 800 px**.

## Punkt wyjścia — potwierdzony odczytem

- `AppServiceProvider` rejestruje `resources/css/filament.css` przez `FilamentAsset::register()` i `Css::make('sportowiada-filament', ...)`.
- Własny arkusz jest publikowany do `public/css/app/sportowiada-filament.css` poleceniem `php artisan filament:assets --no-interaction`.
- `vite.config.js` zawiera wejścia `resources/css/app.css` i `resources/js/app.js`. Korzysta z wtyczek Laravel, Vue i Tailwind; własny arkusz panelu nie jest wejściem Vite.
- Przy rozpoczęciu #36 lokalne `main` i `origin/main` wskazywały `cefefff` — częściowy commit paska akcji list. Pozostał lokalny dopisek w rozdziale 45, który należy zachować.

Potwierdzone wersje: Filament **5.11.3** (`composer show filament/filament`), Vite **8.3.5**, Tailwind CSS i `@tailwindcss/vite` **4.3.3**, `laravel-vite-plugin` **3.2.0**, Vue **3.5.44** (`npm list --depth=0`). Wcześniejsze dane Boost dla tego checkoutu: PHP 8.5 i Laravel 13.42.0.

## Mechanizm planowanej zmiany

Motyw Filamentu jest arkuszem kompilowanym przez Vite, zastępującym standardowy arkusz panelu i obejmującym jego własne nadpisania. Nie wystarczy samodzielnie podłączyć dotychczasowego pliku nadpisań jako kompletnego motywu — trzeba zachować bazowe style Filamentu i wymagane źródła klas.

Docelowy przepływ:

1. Motyw panelu jest wejściem w `vite.config.js`.
2. Provider panelu wskazuje ten plik przez `viteTheme()`.
3. W pracy lokalnej Vite obsługuje zmiany przez `npm run dev`.
4. Do publikacji `npm run build` tworzy pliki i manifest.
5. Po potwierdzeniu następstwa usuwamy zastąpioną rejestrację własnego CSS z `AppServiceProvider`, aby nie ładować go podwójnie.

Migracja nie oznacza rezygnacji z `filament:assets` dla wszystkich zasobów pakietów. Nie zmieniamy wyglądu panelu ani publicznego interfejsu Vue przy okazji zmiany sposobu dostarczania CSS.

## Krok 1 — generator motywu (wykonany)

Asystent sprawdził dokumentację przez Boost `search-docs` i parametry przez `php artisan make:filament-theme --help`.

Polecenie przekazane użytkownikowi:

```bash
php artisan make:filament-theme panel --skip-install --skip-build --no-interaction
```

- `panel` to identyfikator obecnego panelu, nie fragment jego adresu.
- `--skip-install` pomija instalowanie zależności.
- `--skip-build` pomija budowanie zasobów w tym kroku.
- `--no-interaction` wyłącza pytania interaktywne.

Zgodnie z dokumentacją generator tworzy motyw w `resources/css/filament/{panel}/theme.css` i próbuje automatycznie dopisać wejście Vite oraz `viteTheme()` w providerze. Faktyczne pliki i zmiany sprawdzimy po otrzymaniu wyniku polecenia; nie zakładamy, że automatyczna konfiguracja się powiedzie.

**Kryterium sukcesu:** komunikat utworzenia motywu i przegląd wygenerowanych zmian. Przed zbudowaniem zasobów lub uruchomieniem Vite nie odświeżamy panelu, ponieważ nowa rejestracja motywu może wskazywać plik nieobecny jeszcze w manifeście.

### Potwierdzony wynik

Użytkownik uruchomił polecenie i przekazał komunikat pomyślnego utworzenia motywu. Odczyt asystenta potwierdził:

- `resources/css/filament/panel/theme.css` importuje bazowy `vendor/filament/filament/resources/css/theme.css` i zawiera dyrektywy `@source` dla `app/Filament/**/*` oraz `resources/views/filament/**/*`.
- `vite.config.js` ma nowe wejście `resources/css/filament/panel/theme.css`; istniejące wejścia i wtyczki pozostały zachowane.
- `app/Providers/Filament/AdminPanelProvider.php` nie został automatycznie uzupełniony. Generator polecił ręcznie dodać `->viteTheme('resources/css/filament/panel/theme.css')`.

Komunikat sugerujący instalację Tailwind jest wskazówką generatora. `package.json` zawiera już `tailwindcss` i `@tailwindcss/vite`; nie zlecono dodatkowej instalacji. Build i kontrola panelu nadal nie zostały wykonane.

## Krok 2 — wskazanie motywu w panelu (wykonany)

Użytkownik dodał w `app/Providers/Filament/AdminPanelProvider.php`, po `->path('admin')`:

```php
->viteTheme('resources/css/filament/panel/theme.css')
```

Asystent potwierdził wpis odczytem pliku. Konfiguracja wskazuje arkusz motywu obsługiwany przez Vite. Nie jest to jeszcze potwierdzenie działania w przeglądarce: nie wykonano buildu ani kontroli panelu. Dotychczasowa rejestracja `filament.css` w `AppServiceProvider` nadal istnieje.

## Krok 3 — dołączenie dotychczasowych nadpisań (wykonany)

Użytkownik dodał w `resources/css/filament/panel/theme.css` bezpośrednio po imporcie bazowego motywu:

```css
@import '../../filament.css';
```

Asystent potwierdził import odczytem. Ścieżka prowadzi do istniejącego `resources/css/filament.css`, dzięki czemu Vite ma dołączyć dotychczasowe nadpisania do kompilowanego motywu. Reguły pozostają w dotychczasowym pliku; nie kopiowano ich. Dyrektywy `@source` pozostały zachowane. Poprawność kompilacji i wygląd wymagają sprawdzenia. Przed oceną działania nadpisań trzeba także usunąć zastąpioną rejestrację osobnego arkusza, aby nie maskowała ewentualnych braków motywu.

## Krok 4 — pierwsze zbudowanie motywu (wykonany)

Użytkownik wykonał `npm run build`. Vite 8.3.5 zakończył kompilację poprawnie w 751 ms. Powstał `public/build/assets/theme-Cj9IH1PZ.css` (702,95 kB; gzip 71,57 kB), a także zasoby publicznej części aplikacji.

Asystent sprawdził, że `public/build/manifest.json` zawiera wejście `resources/css/filament/panel/theme.css` wskazujące wygenerowany arkusz. W wynikowym CSS potwierdził selektory `.rogacki-brand` i `.catalog-toolbar`. To potwierdza kompilację i dołączenie nadpisań, ale nie ich wygląd w przeglądarce.

Wtyczka fontów zgłosiła brak opcjonalnego pakietu `fontaine` potrzebnego do optymalizacji fontów zastępczych. Komunikat nie przerwał buildu; nie instalowano zależności ani nie zmieniano konfiguracji fontów w tym kroku.

## Krok 5 — usunięcie zastąpionej rejestracji CSS (wykonany)

Użytkownik usunął z `app/Providers/AppServiceProvider.php` importy `Css` i `FilamentAsset` oraz wywołanie `FilamentAsset::register()` dla `sportowiada-filament`. Metoda `boot(): void` pozostaje z pustą implementacją oznaczoną `//`. Asystent potwierdził stan odczytem pliku oraz brak tej rejestracji w providerach.

Własny arkusz `resources/css/filament.css` pozostaje źródłem importowanym przez motyw. Zmiana usuwa dotychczasową drogę ładowania nadpisań; faktyczne żądania zasobów i wygląd wymagają jeszcze kontroli w przeglądarce. Przy odczycie nie było pliku `public/hot`, więc konfiguracja Vite powinna korzystać z przygotowanego manifestu zamiast serwera deweloperskiego.

## Krok 6 — pierwsza kontrola wyglądu (częściowo wykonany)

Użytkownik przekazał zrzuty panelu przy 1024 × 800 px. Na liście Dyscyplin asystent potwierdził widoczność marki Rogacki, tytułu w pasku górnym, okruszków, przycisku „Dodaj dyscyplinę”, nieaktywnego menu „Działania” bez zaznaczenia oraz tabeli i jej stopki. Użytkownik ocenił wygląd jako poprawny. Na wcześniejszym zrzucie Użytkowników brak paska akcji jest zgodny z przyjętym zakresem tej listy.

Po tej kontroli użytkownik potwierdził sukces zwinięcia i ponownego rozwinięcia menu bocznego: przełączanie marki między „R” i „Rogacki” oraz położenie tytułu „Dyscypliny” oceniono jako poprawne.

Nie potwierdzono jeszcze działania menu po zaznaczeniu rekordów, braku podwójnych żądań CSS ani odświeżania zmian przez serwer Vite. Pozostałe widoki i rozmiary wymagają osobnych kontroli.

## Krok 7 — panel przy uruchomionym serwerze Vite (potwierdzony przez użytkownika)

Użytkownik potwierdził uruchomienie `npm run dev`, a następnie odświeżenie panelu i poprawny wygląd listy Dyscyplin bez zgłoszonych błędów. Nie przekazał wyniku terminala; zapis dotyczy jego potwierdzenia działania panelu. Aktualizacja CSS po zapisaniu zmiany bez ręcznego odświeżania pozostaje do sprawdzenia.

## Krok 8 — aktualizacja CSS bez ręcznego odświeżania (potwierdzony przez użytkownika)

Przy działającym `npm run dev` użytkownik dodał na końcu `resources/css/filament.css` tymczasową regułę `.catalog-toolbar { outline: 2px solid magenta; }`. Potwierdził pojawienie się ramki po zapisie bez ręcznego odświeżania przeglądarki. Następnie usunął regułę i potwierdził automatyczne zniknięcie ramki. Asystent sprawdził usunięcie reguły odczytem pliku. Próba potwierdza aktualizację importowanych nadpisań przez Vite w obu kierunkach.

## Krok 9 — kontrola starego arkusza w sieci (wykonany)

Użytkownik przekazał zrzut zakładki Sieć po kontroli z filtrem nazwy starego arkusza `sportowiada-filament`. Lista żądań jest pusta, a panel pozostaje widoczny i ostylowany. W tej kontroli nie wystąpiło żądanie starego `sportowiada-filament.css`, co jest zgodne z usunięciem jego rejestracji. Kontrola dotyczy tego konkretnego arkusza, nie pełnego audytu wszystkich zasobów.

## Krok 10 — panel po zatrzymaniu serwera Vite (potwierdzony przez użytkownika)

Użytkownik zatrzymał `npm run dev` przez Ctrl+C, odświeżył panel i potwierdził poprawne działanie oraz wygląd. Potwierdza to działanie sprawdzanej listy z wcześniej zbudowanym motywem bez serwera deweloperskiego. Nie zgłoszono błędu manifestu ani brakujących stylów. Pozostałe widoki wymagają dalszej kontroli.

## Krok 11 — wygląd formularza dyscypliny (częściowo wykonany)

Użytkownik przekazał zrzut edycji dyscypliny „Liga testowa” przy 1024 × 800 px, po wcześniejszym zatrzymaniu Vite. Widoczne są poprawnie ostylowane pola nazwy i aliasu, tekst pomocniczy, edytor opisu, pole przesyłania grafiki oraz przyciski „Zapisz”, „Anuluj” i „Usuń”. Nie widać błędu strony ani utraty stylów. Pole grafiki jest puste, więc zrzut nie potwierdza wyglądu podglądu obrazu. Kontrola nie obejmuje zapisu ani usuwania danych.

## Krok 12 — widoczna część formularza Shield (wykonany)

Użytkownik przekazał zrzut formularza edycji roli `super_admin` przy 1024 × 800 px. Asystent potwierdził poprawny wygląd widocznych pól nazwy i guarda, przełącznika, zakładek z licznikami oraz sekcji uprawnień z checkboxami. Dolna część formularza nie jest widoczna. Zrzut nie przedstawia listy ról, więc wygląd jej tabeli i paska akcji pozostaje do sprawdzenia. Nie potwierdza także zmiany ani zapisu uprawnień.

Użytkownik następnie wrócił do listy Ról i potwierdził poprawny wygląd tabeli, przycisku tworzenia i menu „Działania”. Jest to potwierdzenie użytkownika; nie przekazano dodatkowego zrzutu listy.

### Obserwacja — tytuły formularzy do ujednolicenia w #34

Użytkownik zwrócił uwagę, że tytuł edycji powinien znajdować się w pasku górnym. Na zrzutach edycji dyscypliny i roli tytuły pozostają nad formularzem. Dotychczasowe przeniesienie tytułów obejmowało listy i pulpit; nie uznano tego za regresję motywu Vite. Uzgodniono pozostawienie ujednolicenia formularzy do #34 i kontynuowanie weryfikacji #36. Nie wykonano zmiany tytułów formularzy.

## Krok 13 — tryb ciemny listy Dyscyplin (potwierdzony przez użytkownika)

Użytkownik przełączył panel na tryb ciemny i potwierdził czytelność tytułu, tabeli i przycisków oraz poprawne kolory teł i przycisków na liście Dyscyplin. Nie przekazano zrzutu. Wynik dotyczy sprawdzanej listy, nie wszystkich formularzy ani podglądu grafiki.

## Dalsza weryfikacja — plan

- Zachować nadpisania nagłówków, marki Rogacki, paska akcji, fokusu i podglądu grafiki dyscypliny.
- Sprawdzić wymagania źródeł klas dla własnych widoków i Shield.
- Potwierdzić działanie odświeżania CSS przy `npm run dev` oraz brak podwójnego ładowania własnego arkusza.
- Wykonać build i sprawdzić panel bez uruchomionego serwera Vite, bez błędów manifestu i brakujących plików.
- Sprawdzić logowanie, pulpit, listy, formularze, menu rozwinięte/zwinięte i obsługiwany tryb ciemny oraz brak regresji witryny publicznej. Kontrole 320/375 px określone w #36 pozostają niewykonane; wcześniejsze odłożenie poprawek mobilnych #34 nie stanowi ich potwierdzenia.
- Uruchomić odpowiednie testy, formatowanie PHP po jego zmianach i analizę statyczną. Wyniki z poprzedniego etapu nie są wynikami migracji motywu.

Użytkownik wykonuje kod, polecenia i Git. Asystent czyta pliki i uzupełnia ten rozdział po potwierdzonych krokach. Bez zmian zależności, edycji `vendor`, nowych gałęzi i worktree. Build wykonano; nie wykonano jeszcze testów ani commita tego etapu.
