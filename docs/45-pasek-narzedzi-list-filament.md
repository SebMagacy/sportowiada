---
tags:
  - filament
  - panel
  - listy
---

# 45. Pasek narzędzi i nagłówek list Filament

## Cel i status etapu

Rozdział utworzono 19.11.2026 na wyraźną prośbę użytkownika dla [#34 — osobnego toolbaru nad listą](https://github.com/SebMagacy/sportowiada/issues/34), wydzielonego z [#29 — ujednolicenia list panelu](https://github.com/SebMagacy/sportowiada/issues/29).

Etap rozpoczęto od przeglądu zgłoszeń i obecnego kodu. Wykonano pierwszy krok przygotowawczy — kontener na tytuł w górnym pasku. Przeniesienie tytułu i okruszków pozostaje do wykonania. Poprzedni etap opisuje [rozdział 43](43-ujednolicenie-list-filament.md); koncepcję produktu zachowuje [rozdział 44](44-koncepcja-produktu-sportowiada.md).

## Zatwierdzony kierunek układu

Zrzuty przekazane przez użytkownika są odniesieniem wyłącznie dla rozmieszczenia elementów. Obecne kolory, wygląd przycisków i pozostałe stylowanie Filamentu pozostają zachowane.

Uzgodniono osobny poziomy pasek przycisków pod tytułem, nad wyszukiwaniem, filtrami i tabelą. Lewa strona jest przeznaczona na główne akcje, prawa na ewentualne dodatkowe narzędzia. Konkretny zestaw przycisków pozostaje do zatwierdzenia; zrzut Joomla nie oznacza dodania „Opcji” ani „Pomocy”.

Następnie użytkownik wskazał jako pierwszy krok przeniesienie tytułu katalogu i rozmieszczenie okruszków zgodnie z drugim zrzutem:

- tytuł katalogu w górnym pasku panelu, obok obszaru marki, przed narzędziami po prawej;
- okruszki poniżej górnego paska, wyrównane do początku głównej treści;
- bez zmiany stylowania na wzór zrzutu.

## Przegląd początkowy

Asystent odczytał aktualne #34 i #29, instrukcje projektu, odpowiednie reguły i skille oraz kod pięciu list: użytkowników, dyscyplin, sezonów, lig i ról Shield. Początkowy odczyt Git nie wykazał lokalnych zmian.

Composer i Boost potwierdziły PHP 8.5, Laravel 13.42.0, Filament 5.11.3, Shield 4.3.1 oraz Spatie Permission 8.5.0. Sprawdzono dokumentację przez Boost oraz zainstalowane widoki Filamentu dotyczące górnego paska i nagłówka strony.

Stan zastany:

- dodawanie dyscyplin, sezonów, lig i ról jest akcją nagłówka strony; w pustym katalogu przycisk znajduje się tylko w osobnym widoku pustego stanu;
- operacje zbiorcze znajdują się w tabelach katalogów sportowych i ról;
- akcje rekordu pozostają przy wierszach;
- użytkownicy mają przypisywanie ról, bez tworzenia/edycji kont i bez operacji zbiorczych.

## Krok 1 — miejsce na tytuł w górnym pasku (wykonany)

Cel: przygotować punkt osadzenia tytułu bez edycji plików `vendor`.

Zaproponowano użytkownikowi dodanie w `app/Providers/Filament/AdminPanelProvider.php` importów:

```php
use Filament\View\PanelsRenderHook;
use Illuminate\Support\HtmlString;
```

Następnie po `->brandName('Sportowiada')`:

```php
->renderHook(
    PanelsRenderHook::TOPBAR_LOGO_AFTER,
    fn (): HtmlString => new HtmlString(
        '<div id="catalog-page-heading"></div>',
    ),
)
```

Mechanizm: punkt rozszerzenia Filamentu dodaje pusty kontener za obszarem logo w górnym pasku. Sam ten krok nie przenosi jeszcze tytułu ani okruszków; dokładne wyrównanie pozostaje do wdrożenia i sprawdzenia.

Użytkownik potwierdził wykonanie kroku i sukces sprawdzenia: panel otwiera się po odświeżeniu bez błędu. Asystent potwierdził w kodzie providera obecność obu importów i rejestracji hooka z kontenerem `catalog-page-heading`. Nie przeprowadzono własnej kontroli przeglądarkowej ani testów dla tej zmiany. Formatowanie PHP pozostaje do sprawdzenia Pintem przed zakończeniem zmian.

## Krok 2 — wspólny widok nagłówka (wykonany)

Użytkownik potwierdził utworzenie `resources/views/filament/catalog-header.blade.php` po wskazaniu polecenia `php artisan make:view filament.catalog-header --no-interaction` i zawartości widoku. Asystent odczytał plik i potwierdził kod.

Widok zawiera kontener Alpine `x-data` oraz `template` z `x-teleport="#catalog-page-heading"`. Wewnątrz znajduje się tytuł z `$this->getHeading()` i obecną klasą Filamentu `fi-header-heading`. Standardowy komponent `x-filament-panels::header` zachowuje okruszki, akcje, ich wyrównanie i podtytuł, ale nie otrzymuje tytułu, aby uniknąć jego powielenia.

Sam zapis pliku nie zmienia wyglądu panelu: widok nie został jeszcze podłączony do stron list. Działanie przenoszenia tytułu i jego wyrównanie nie zostały sprawdzone w przeglądarce.

## Krok 3 — podłączenie nagłówka i objęcie pulpitu

Asystent potwierdził w kodzie pięciu stron list (`ListUsers`, `ListDisciplines`, `ListSeasons`, `ListLeagues`, `ListRoles`) import `Illuminate\Contracts\View\View as BladeView` i metodę `getHeader()` zwracającą `view('filament.catalog-header')`. Wynik sprawdzenia wyglądu i zachowania po wyszukiwaniu pozostaje niepotwierdzony.

Użytkownik rozszerzył zakres wspólnego nagłówka także na Panel (pulpit). Wykonał `php artisan make:class Filament/Pages/Dashboard --no-interaction` i przekazał komunikat sukcesu. Następnie zastąpił szkielet klasą dziedziczącą po `Filament\Pages\Dashboard` (alias `FilamentDashboard`) i dodał `getHeader(): BladeView`, zwracające `view('filament.catalog-header')`. W `AdminPanelProvider` import pulpitu zamieniono na `App\Filament\Pages\Dashboard`; istniejący wpis `Dashboard::class` korzysta z klasy aplikacji. Asystent potwierdził te zmiany w plikach.

Użytkownik potwierdził sukces sprawdzenia pulpitu według podanych kryteriów: Panel otwiera się, widżety pozostają widoczne, tytuł występuje raz w górnym pasku i w menu jest jedna pozycja pulpitu. Jest to wynik użytkownika, bez własnej kontroli przeglądarkowej asystenta. Dokładne wyrównanie względem zrzutu, małe ekrany i zachowanie pięciu list po wyszukiwaniu pozostają do sprawdzenia.

## Ocena pierwszego zrzutu po podłączeniu

Użytkownik przesłał zrzut listy użytkowników. Asystent potwierdził na nim pojedynczy tytuł w górnym pasku oraz okruszki pod paskiem, wyrównane do początku tabeli. Tytuł przylega do marki „Sportowiada”, zamiast zaczynać się nad główną treścią — wyrównanie wymaga poprawki. Zrzut nie potwierdza zachowania pozostałych list, wyszukiwania ani małych ekranów.

Odczyt stylów Filamentu ujawnił również, że `TOPBAR_LOGO_AFTER` znajduje się wewnątrz `.fi-topbar-start`, ukrywanego na małych ekranach. Punkt osadzenia tytułu wymaga korekty przed kontrolą mobilną.

## Krok 4 — przeniesienie kontenera poza obszar logo (potwierdzony zrzutem)

Po poleceniu zamiany `TOPBAR_LOGO_AFTER` na `TOPBAR_START` użytkownik przesłał kolejny zrzut. Tytuł jest widoczny przed przyciskiem zwijania menu i marką; okruszki oraz tabela pozostają widoczne. To położenie przejściowe, wymagające ustawienia kolejności i szerokości obszaru marki. Zrzut potwierdza widoczny układ desktopowy, nie zachowanie mobilne.

## Krok 5 — wyrównanie tytułu na desktopie (potwierdzone dla rozwiniętego menu)

Asystent potwierdził w `resources/css/filament.css` regułę dla ekranów od `64rem`: `.fi-topbar:has(#catalog-page-heading > h1) .fi-topbar-start` otrzymuje `order: -1`, `flex: 0 0 calc(var(--sidebar-width) + 1rem)` i `margin-inline-end: 0`. Reguła ustawia obszar marki przed tytułem i rezerwuje szerokość potrzebną do jego wyrównania z początkiem treści przy rozwiniętym menu.

Użytkownik przekazał wynik `php artisan filament:assets --no-interaction`: `Successfully published assets!`. Lista opublikowanych plików obejmuje `public/css/app/sportowiada-filament.css`. Potwierdza to publikację zasobów; wyrównanie po odświeżeniu strony, zachowanie zwiniętego menu i małych ekranów pozostają do sprawdzenia.

Następnie użytkownik przesłał zrzut pustego katalogu dyscyplin z rozwiniętym menu. Asystent potwierdził wizualnie markę po lewej oraz wyrównanie początku tytułu „Dyscypliny” do okruszków i karty pustego katalogu. Na zrzucie przycisk dodawania występuje tylko w pustym widoku. Ten wynik nie obejmuje zwiniętego menu ani małych ekranów.

## Sprawdzenie zwiniętego menu — wymaga decyzji

Użytkownik przesłał zrzut pustego katalogu dyscyplin po zwinięciu menu. Okruszki i karta przesunęły się w lewo, natomiast tytuł zachował położenie z rozwiniętego menu. Wyrównanie nie jest zachowane. Widoczna marka „Sportowiada” zajmuje przestrzeń, w której musiałby zaczynać się tytuł wyrównany do treści. Zachowanie marki i tytułu w tym stanie wymaga uzgodnienia przed kolejną zmianą.

## Marka Rogacki i wybór ikony

Użytkownik zdecydował o zastąpieniu marki „Sportowiada” marką Rogacki w panelu. Asystent potwierdził w providerze zapis `->brandName('Rogacki')`.

Użytkownik zatwierdził wygenerowaną propozycję ikony: bursztynowy, geometryczny monogram „R”. Zaakceptowany kierunek prezentacji to ikona z nazwą przy rozwiniętym menu i sama ikona przy zwiniętym. Asystent zapisał wybrany obraz jako `public/rogacki-icon.png`. Plik nie został jeszcze podłączony jako logo panelu; zachowanie i czytelność w docelowym rozmiarze wymagają sprawdzenia.

Przez Boost sprawdzono API Filamentu 5 dotyczące `brandLogo()` z własnym widokiem i `brandLogoHeight()`. Użytkownik wykonał `php artisan make:view filament.brand --no-interaction` i przekazał komunikat poprawnego utworzenia `resources/views/filament/brand.blade.php`. Asystent odczytał wygenerowany plik. Zawartość marki i podłączenie widoku w providerze pozostają do wykonania.

### Zawartość widoku marki — zapisana

Użytkownik potwierdził zapis `filament/brand.blade.php`, a asystent sprawdził plik. Widok zawiera `.rogacki-brand`, obraz `asset('rogacki-icon.png')` o zadeklarowanych wymiarach 32 × 32 px z pustym `alt` oraz nazwę z `filament()->getBrandName()`. Widoczność napisu sterowana jest przez `x-show="$store.sidebar?.isOpen ?? true"`. Podłączenie widoku i styl układu pozostają do wykonania; zachowanie przy zwiniętym menu, na ekranie logowania i dostępność wymagają późniejszej kontroli.

### Podłączenie logo — pierwsza kontrola

Asystent potwierdził `brandLogo(fn () => view('filament.brand'))` i `brandLogoHeight('2rem')` w providerze oraz reguły `.rogacki-brand` (`inline-flex`, wyśrodkowanie, odstęp i brak zawijania) w źródłowym arkuszu. Na zrzucie użytkownika ikona jest widoczna, ale napis znajduje się pod nią. Porównanie plików wykazało brak nowych reguł marki w `public/css/app/sportowiada-filament.css`, mimo ich obecności w `resources/css/filament.css`. Konieczna jest ponowna publikacja arkusza po zapisaniu nowych reguł; poprawny układ logo i stan zwinięty nie są jeszcze potwierdzone.

### Pierwszy zrzut mobilny

Użytkownik przesłał zrzut katalogu dyscyplin w mobilnym Safari. Tytuł, wyszukiwanie, menu użytkownika i karta pustego katalogu są widoczne. Przycisk otwierania menu występuje bezpośrednio za tytułem, co wymaga korekty kolejności na małych ekranach. Okruszki nie są widoczne na tym zrzucie. Nie potwierdzono dokładnej szerokości obszaru strony ani działania przycisku menu; zrzut nie stanowi pełnej weryfikacji 320/375 px.

### Korekta proporcji — uzgodniony kierunek

Użytkownik zlecił przesunięcie marki z ikoną w lewo, zbliżenie ikony do napisu, lekkie powiększenie marki i zmniejszenie tytułu. Zrzut Joomla służy jako odniesienie układu i proporcji. Korekta nie została jeszcze wykonana ani sprawdzona. Asystent potwierdził w źródłowym CSS wcześniejszą regułę kolejności przycisków mobilnych; jej działania użytkownik nie potwierdził.

### Monogram jako pierwsza litera marki — zatwierdzone

Użytkownik zatwierdził zastąpienie pierwszej litery nazwy ikoną: monogram „R” + napis „ogacki”, a po zwinięciu sama ikona. Dla czytników ekranu ma pozostać pełna nazwa „Rogacki”. Widok nie został jeszcze zmieniony na ten wariant.

Asystent potwierdził w źródłowym CSS wcześniejsze zmiany proporcji: marka 1.5rem, odstęp 0.125rem, obraz 2.5rem; logo przed przyciskiem zwijania i bez początkowego marginesu; tytuł 1.5rem, na małych ekranach 1.25rem. Odczyt kodu potwierdza zapis reguł, nie ich publikację ani wynik wizualny.

### Pierwszy zrzut marki „R” + „ogacki”

Użytkownik przesłał zrzut górnego paska na stronie edycji roli. Monogram i fragment „ogacki” są widoczne obok siebie, ale odstęp optyczny jest zbyt duży, by tworzyły jeden napis. Do korekty pozostaje odstęp uwzględniający wolną przestrzeń wewnątrz obrazu. Zrzut nie potwierdza zachowania zwiniętego menu ani dostępności.

### Różna szerokość monogramu po zwinięciu menu

Asystent potwierdził dodany `margin-inline-end: -0.5rem` w regule obrazu. Użytkownik przesłał zrzuty marki rozwiniętej i zwiniętej oraz zgłosił zniekształcenie „R”. Na zrzutach monogram w stanie zwiniętym jest węższy. Hipoteza do sprawdzenia: po ukryciu napisu i zmniejszeniu szerokości rodzica obraz ogranicza globalne `max-width`, mimo deklaracji szerokości i `flex-shrink: 0`. Nie odczytano stylów obliczonych w przeglądarce; przyczyna nie jest jeszcze potwierdzona. Kolejna korekta ma wymusić stałe wymiary i zachowanie proporcji obrazu w obu stanach.

### Przesuwanie tytułu ze zwijanym menu — kolejny krok

Użytkownik poprosił o przesuwanie tytułu wraz z treścią po zwinięciu menu. W zainstalowanym Filamencie potwierdzono klasę `.fi-sidebar-open` sterowaną przez stan Alpine oraz zmienną `--sidebar-collapsed-width` używaną dla zwiniętego menu. Planowana korekta ma zmieniać szerokość obszaru marki zależnie od tej klasy; wynik pozostaje do sprawdzenia.

Przy tym odczycie asystent potwierdził w źródłowym CSS stałe wymiary obrazu, `min-width`, `max-width: none` i `object-fit: contain` z poprzedniego kroku. Nie potwierdzono jeszcze wizualnie usunięcia zniekształcenia monogramu.

### Kontrola po dodaniu reguły zwiniętego menu

Użytkownik określił szerokość przeglądarki jako 891 px i przesłał zrzut listy ról ze zwiniętym menu. Tytuł pozostaje odsunięty od okruszków. Asystent odczytał arkusz źródłowy i opublikowany: reguła zmieniająca `flex-basis` znalazła się w `@media (width < 64rem)`, zamiast w desktopowym `@media (min-width: 64rem)`. Oba pliki zawierają tę samą wersję, więc problem nie wynika z braku publikacji. Kolejny krok to przeniesienie reguły do właściwego bloku. Nie ustalono rzeczywistej szerokości viewportu w pikselach CSS ani powiększenia przeglądarki.

## Ocena ostatniego zrzutu i zapis uwag w GitHub

Na ostatnim zrzucie listy ról ze zwiniętym menu tytuł jest wyrównany do okruszków i początku tabeli. To potwierdzenie wizualne jednego widoku; pełna weryfikacja pozostaje otwarta.

Na wyraźne zlecenie użytkownika asystent zapisał uwagi w komentarzach GitHub:

- [#34 — układ, odstępy i marka](https://github.com/SebMagacy/sportowiada/issues/34#issuecomment-7010291897): pusta przestrzeń nad tabelą, relacja okruszków i toolbaru, rozdzielenie strzałki, monogramu i tytułu, czytelność detalu ikony w małym rozmiarze.
- [#29 — wyszukiwanie i język](https://github.com/SebMagacy/sportowiada/issues/29#issuecomment-7010293409): identyczne etykiety dwóch wyszukiwarek oraz mieszanie terminów technicznych i języków na liście ról. Rozróżniono tłumaczenie prezentacji od zmiany technicznych nazw ról i guardów.

Oba zapisy zostały potwierdzone przez GitHub. Uwagi są propozycjami do uzgodnienia, bez automatycznego rozszerzania implementacji, zmiany checklist ani zamykania zgłoszeń.

## Zatwierdzony pierwszy zakres toolbaru

Użytkownik zatwierdził osobny pasek pod okruszkami i nad tabelą z przyciskiem „Dodaj…” po lewej. Pierwszy zakres obejmuje wyłącznie istniejące akcje dodawania. Akcje rekordów i operacje zbiorcze pozostają na dotychczasowych miejscach. W pustym katalogu dodawanie pozostaje wyłącznie w karcie pustego stanu; lista użytkowników nie otrzymuje tworzenia kont.

Asystent ponownie odczytał wspólny nagłówek oraz istniejące akcje dodawania z warunkiem `hidden` dla pustego katalogu. Przygotowanie wspólnego komponentu toolbaru i jego podłączenie pozostają do wykonania.

## Przygotowanie częściowego commita

Użytkownik poprosił o commit obecnego etapu. Ustalono przygotowanie częściowego zapisu nagłówka i marki, bez zamykania #34 i bez pustego szkieletu `catalog-toolbar.blade.php`. Przegląd wykazał również lokalną zmianę nazwy grupy nawigacyjnej z „Sport” na „Sportowiada” w zasobach dyscyplin, lig i sezonów.

Użytkownik wykonał `vendor/bin/pint --dirty --format agent`. Wynik `fixed` potwierdza poprawki formatowania w czterech stronach list (dyscypliny, ligi, role, sezony), pulpicie oraz providerze panelu. Formatowanie nie potwierdza działania ani pełnej zgodności wizualnej.

Następnie użytkownik wykonał `php artisan test --compact --filter=Filament`: **105 testów przeszło, 640 asercji**, czas 25.89 s. Jest to wynik użytkownika dla istniejących testów regresji Filamentu, nie potwierdzenie pełnej weryfikacji wyglądu ani nowego toolbaru.

Użytkownik wykonał również `vendor/bin/phpstan analyse --memory-limit=512M`: **[OK] No errors**, sprawdzono 33/33 pliki. Asystent wykonał `git diff --check` bez zgłoszonych problemów. Commit i push pozostają do wykonania; hook GrumPHP uruchamiany przy commicie nie został jeszcze potwierdzony.

### Końcowy przegląd przed commitem — poprawki

Przegląd wykazał zmianę `brandName` na „ogacki”, podczas gdy widok już wyświetla ten fragment osobno. Asystent wskazał przywrócenie pełnej nazwy „Rogacki” w konfiguracji, ponieważ korzysta z niej tekst alternatywny ikony, a następnie potwierdził tę poprawkę w kodzie. W CSS znaleziono również powtórzony blok desktopowy oraz brak wcześniejszej reguły kolejności przycisków menu mobilnego.

Użytkownik wykonał krok zastąpienia pierwszego, powtórzonego bloku desktopowego regułą mobilną ustawiającą `order: -1` przycisków menu oraz publikacji zasobów i potwierdził „działa” dla przekazanych kryteriów: tytuł przesuwa się z treścią na desktopie, a na telefonie przycisk menu jest przed tytułem. Asystent sprawdził końcowy CSS i ponownie wykonał `git diff --check` bez problemów. Potwierdzenie użytkownika nie zastępuje pełnej kontroli 320/375 px, wszystkich katalogów i obsługi klawiaturą. Przygotowywany commit pozostaje częściowym etapem #34.

## Dalsze uzgodnienia i granice

Do ustalenia pozostają zestaw przycisków, zachowanie paska w pustym katalogu, szczegóły małych ekranów oraz zakres przenoszonych operacji. Propozycja menu „Działania” nie została jeszcze zatwierdzona.

Zachowujemy Policies/Shield i autoryzację po stronie serwera. Nie dodajemy funkcji tylko dla spójności wyglądu, nie zmieniamy zależności i nie edytujemy `vendor`. Toolbar formularzy, samouczki i kolejność menu należą do osobnych zgłoszeń.

Użytkownik wykonuje edycje aplikacji, polecenia i Git, chyba że wyraźnie deleguje je asystentowi. Asystent uzupełnia ten rozdział po potwierdzonych krokach, oddzielając uzgodnienia, wykonanie i wyniki sprawdzeń.
