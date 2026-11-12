---
tags:
  - gorny-pasek-panelu
---

# Górny pasek panelu administratora

## Zgłoszenie i cel

Etap 26 dotyczy [issue #18 — Dodać górny pasek panelu administratora z linkiem do publicznej witryny](https://github.com/SebMagacy/sportowiada/issues/18).

Użytkownik poprosił o osobny czat i rozdział dokumentacji. Celem jest wspólny górny pasek, inspirowany zapleczem Joomla, na początek z linkiem do publicznej strony głównej Sportowiady.

## Uzgodniony zakres

- Pasek znajduje się nad treścią widoków, po prawej stronie bocznego menu. Po doprecyzowaniu przez użytkownika nagłówek bieżącego widoku z dotychczasową ikoną znajduje się wewnątrz paska po lewej, a link do witryny po prawej, w tym samym wierszu.
- Po prawej stronie paska znajduje się „Zobacz witrynę” z dekoracyjną ikoną otwarcia w nowej karcie.
- Link otwiera stronę publiczną w nowej karcie, zachowując panel i sesję; wymaga dostępnej informacji o nowej karcie oraz `rel="noopener"`.
- Pasek jest wspólny dla wszystkich widoków panelu i dopasowuje się do szerokości ekranu bez ściskania nagłówków i poziomego przepełnienia.
- Tłumaczenia PL/EN, obsługa klawiatury i widoczny fokus należą do zakresu.
- Link korzysta z nazwanej trasy publicznej strony głównej. Przy przygotowaniu etapu trasa `/` nie miała nazwy; sprawdzić stan przed zmianą.

Użytkownik zaakceptował biały pasek z delikatną dolną linią, przewijający się razem ze stroną. Orientacyjna wysokość to 64 px, z możliwością wzrostu przy zawijaniu długiego tytułu. Na wąskim ekranie tytuł może zawijać się w lewej części, a link pozostaje po prawej. Ta decyzja zastępuje wcześniejszy układ z nagłówkami poniżej paska; aktualny opis issue odczytany podczas przeglądu zawiera jeszcze wcześniejsze położenie nagłówków.

## Plan dalszych kroków

Sprawdzić bieżący layout, CSS, trasy, komponenty i wersje zależności. Uzgodnić wygląd paska i realizować zmiany po jednym małym kroku. Zweryfikować link, zachowanie sesji, dostępność oraz szeroki i wąski ekran; dodać odpowiednie testy bez nowych zależności.

## Stan wykonania

### Przygotowanie miejsca na nagłówek

W `resources/views/components/admin-layout.blade.php` dodano przed głównym slotem warunkowe wyświetlenie `{{ $heading }}` przez `@isset($heading)`. Layout jest gotowy do przyjęcia nazwanego slotu z widoków. Własny przegląd różnicy Git potwierdził poprawną strukturę tej zmiany. Nie jest to gotowy pasek ani potwierdzenie działania całej funkcji.

### Przekazanie nagłówka pulpitu

W `resources/views/admin/dashboard.blade.php` użytkownik otoczył dotychczasowy komponent `x-page-heading` nazwanym slotem `x-slot:heading`. Zachowano ikonę pulpitu i tłumaczenie `dashboard.title`. Dzięki temu o miejscu wyświetlenia nagłówka decyduje layout. Własny odczyt pliku potwierdził zgodność kodu z instrukcją; użytkownik potwierdził wykonanie kroku. Lista użytkowników i formularz edycji wymagają jeszcze analogicznej zmiany. Nie wykonano testów automatycznych tego etapu.

### Przekazanie nagłówka listy użytkowników

W `resources/views/admin/users/index.blade.php` użytkownik otoczył istniejący nagłówek slotem `x-slot:heading`, zachowując ikonę `users` i tłumaczenie `users.title`. Własny przegląd różnicy Git potwierdził, że zmiana dotyczy wyłącznie opakowania nagłówka. Użytkownik potwierdził poprawne otwarcie listy i pojedyncze wyświetlenie nagłówka z ikoną. Formularz edycji pozostaje do dostosowania.

Przygotowano ten rozdział i zaktualizowano wcześniej opis issue #18 do nowej koncepcji. Kolejne wykonane kroki opisano poniżej; testy automatyczne etapu 26 nie zostały jeszcze wykonane.

### Przekazanie nagłówka edycji użytkownika

W `resources/views/admin/users/edit.blade.php` użytkownik otoczył dotychczasowy nagłówek slotem `x-slot:heading`, zachowując ikonę `edit` oraz tłumaczenie `users.edit_title` z nazwą użytkownika. Własny przegląd różnicy Git potwierdził zmianę. Użytkownik potwierdził poprawne otwarcie formularza i pojedyncze wyświetlenie nagłówka z ikoną. Wszystkie trzy obecne widoki panelu przekazują już nagłówek do layoutu; wydzielenie i stylowanie paska pozostaje kolejnym krokiem.

Etap 25 / #15 jest zakończony i opublikowany na `main`: implementacja `4a46de3`, dokumentacja `7f9ac61` i końcowe podsumowanie `bba34e0`. Wcześniejsze wyniki 201 testów PHP / 1052 asercji i build Vite dotyczą etapu 25, nie potwierdzają działania nowego paska.

## Wydzielenie paska nad treścią

W `resources/views/components/admin-layout.blade.php` użytkownik zastąpił dotychczasowy obszar `main` wspólną kolumną `min-w-0 flex-1`, zawierającą `header` i osobny `main`. Nagłówek ze slotu jest wyświetlany w lewej części paska. Klasy `min-h-16`, `bg-white` i `border-b border-gray-200` zapewniają minimalną wysokość 64 px, białe tło i delikatną dolną linię; `px-4 sm:px-8` dostosowuje poziome odstępy do szerokości ekranu. Pasek pozostaje w normalnym przepływie dokumentu. Treść widoków zachowuje `p-8`.

W `resources/views/components/page-heading.blade.php` usunięto `mb-6`, ponieważ odstęp pod paskiem zapewnia teraz obszar treści. Własny przegląd różnicy Git potwierdził obie zmiany. Użytkownik potwierdził sukces ręcznego sprawdzenia wyglądu trzech widoków i zwijania menu. Link do witryny nie jest jeszcze dodany; nie wykonano testów automatycznych tego etapu.

## Nazwana trasa strony głównej

W `routes/web.php` użytkownik dopisał `->name('home')` do istniejącej trasy `GET /`, pozostawiając jej dotychczasową zawartość. Własny przegląd różnicy Git potwierdził zmianę. Użytkownik uruchomił `php artisan route:list --name=home`; wynik pokazał jedną trasę `GET|HEAD /` o nazwie `home`. Pozwala to wygenerować adres linku przez `route('home')`. Ten wynik potwierdza rejestrację nazwy, nie sprawdza jeszcze linku ani sesji.

## Tłumaczenia linku

W `lang/pl/dashboard.php` i `lang/en/dashboard.php` użytkownik dodał klucze `view_site` i `opens_in_new_tab`. Pierwszy opisuje widoczny link, drugi przekazuje informację o otwarciu nowej karty. Zachowano dotychczasowy klucz `title`. Własny odczyt obu plików potwierdził zgodność tłumaczeń z instrukcją; użytkownik potwierdził wykonanie kroku. Teksty nie są jeszcze użyte w layoucie.

## Link do publicznej witryny

W `resources/views/components/admin-layout.blade.php` użytkownik dodał po prawej stronie nagłówka link generowany przez `route('home')`, z `target="_blank"` i `rel="noopener"`. Widoczny tekst korzysta z `dashboard.view_site`, a informacja o nowej karcie z `dashboard.opens_in_new_tab` w elemencie `sr-only`. Dekoracyjny SVG ma `aria-hidden="true"`. Klasy linku obejmują widoczny fokus klawiatury. Własny odczyt kodu potwierdził te elementy. Użytkownik potwierdził wyświetlenie linku po prawej, otwarcie strony publicznej w nowej karcie, pozostawienie panelu i działanie nawigacji do listy bez ponownego logowania. Nie sprawdzono jeszcze wąskiego ekranu ani obsługi klawiatury; testy automatyczne pozostają do wykonania.

## Weryfikacja na telefonie — problem do poprawienia

Przesłany przez użytkownika zrzut pulpitu przy zwiniętym menu pokazał nadmierne ściskanie nagłówka: słowo „administratora” rozpada się na krótkie fragmenty, znacznie zwiększając wysokość paska. Nie potwierdzono spełnienia kryterium mobilnego. Zaproponowano ograniczenie szerokości linku klasami `w-24 sm:w-auto`, z zachowaniem `shrink-0`, ale wdrożenie ani skuteczność tej zmiany nie zostały potwierdzone. Na prośbę użytkownika dopisano problem, proponowany pierwszy krok i wymagane sprawdzenia do issue #18, wraz z doprecyzowaniem aktualnego położenia nagłówka wewnątrz paska.

### Wynik ograniczenia szerokości linku

Użytkownik dodał do linku `w-24 sm:w-auto`, zachowując `shrink-0`; własny odczyt layoutu potwierdził zmianę. Kolejny zrzut telefonu pokazał zawinięcie „Zobacz witrynę” do dwóch wierszy i więcej miejsca na tytuł, ale słowo „administratora” nadal dzieli się w środku. Poprawka przyniosła częściową poprawę; kryterium czytelności mobilnej pozostaje niespełnione. Następna propozycja to zmniejszenie tekstu, ikony i odstępu nagłówka na wąskim ekranie z zachowaniem dotychczasowego wyglądu od progu `sm`.

### Odłożenie dalszych poprawek mobilnych

12 listopada 2026 użytkownik zdecydował o odłożeniu dalszej pracy nad widokiem telefonu. Problem czytelności nagłówka pozostaje otwarty i jest zapisany w issue #18; kryterium mobilne nie jest spełnione. Zaproponowane zmniejszenie nagłówka i ikony nie zostało wdrożone — własny odczyt `page-heading.blade.php` potwierdził dotychczasowe `text-2xl`, `gap-3` i `size-10`. Zachowano wykonaną wcześniej zmianę szerokości linku. Dalsza praca może obejmować weryfikację wersji komputerowej, klawiatury i testy bez kontynuowania poprawek mobilnych.

## Ręczna weryfikacja klawiatury na komputerze

Użytkownik potwierdził przejście klawiszem Tab do linku „Zobacz witrynę”, widoczny fokus i otwarcie witryny klawiszem Enter w nowej karcie z pozostawieniem panelu. Przesłany zrzut listy użytkowników potwierdza wyraźną niebieską obwódkę fokusu wokół linku. Nie stanowi to pełnego audytu dostępności ani weryfikacji czytnikiem ekranu. Testy automatyczne nowego linku pozostają do przygotowania; istniejący `PageHeadingTest` wymaga dostosowania selektorów po przeniesieniu nagłówków z `main` do `header`.

## Testy automatyczne etapu 26

Na wyraźne zlecenie użytkownika asystent dostosował `tests/Feature/PageHeadingTest.php`: cztery selektory XPath szukają teraz nagłówka w `header` zamiast `main`. Zachowano sprawdzanie tłumaczeń, ikony i bezpiecznego wyświetlania nazwy użytkownika.

Przez `php artisan make:test --phpunit AdminTopBarTest --no-interaction` utworzono `tests/Feature/AdminTopBarTest.php`. Dodano cztery przypadki: dwa warianty językowe wspólnego paska na pulpicie, liście i formularzu edycji, dostępność publicznej strony głównej dla gościa oraz zachowanie uwierzytelnienia administratora po wejściu na stronę publiczną. Testy sprawdzają adres `/`, `target="_blank"`, token `noopener`, widoczny tekst PL/EN, informację dla czytnika ekranu i dekoracyjny SVG. Nie symulują nowej karty przeglądarki ani wyglądu na telefonie; otwarcie karty i fokus potwierdzono wcześniej ręcznie.

Własna weryfikacja asystenta 12 listopada 2026:

- Testy obu plików: 12 przypadków, 127 asercji — poprawne.
- Cały zestaw PHP: 205 testów, 1127 asercji — poprawne.
- Testy uruchomiono z osobnym `VIEW_COMPILED_PATH` w `/tmp`.
- `vendor/bin/pint --dirty --format agent` — poprawny.
- PHPStan obu zmienionych plików testowych z `--debug --no-progress` — bez błędów.
- `git diff --check` — poprawny.

Problem mobilny pozostaje odłożony. Wyniki testów nie oznaczają spełnienia tego kryterium ani zamknięcia issue #18.

## Zatwierdzenie zmian

Użytkownik utworzył commit `4ca6aea` — `Dodaj górny pasek panelu z linkiem do witryny`, z odwołaniem `Refs #18`. Commit obejmuje 11 plików: implementację, tłumaczenia, testy i dokumentację. Kontrola PHPStan w hooku GrumPHP zakończyła się poprawnie, co potwierdził przesłany wynik polecenia. Własny odczyt Git potwierdził commit i czysty stan przed dopisaniem niniejszej informacji. Publikacja przez push pozostaje do potwierdzenia.

## Poza zakresem


Informacja o zalogowanym użytkowniku (#17), nawigacja okruszkowa (#16), widok szczegółów (#9), przebudowa list (#13) i odłożone problemy mobilne (#12/#4) pozostają osobnymi zadaniami. Nie dodajemy teraz powiadomień, menu konta ani innych informacji na pasku. Nie instalujemy Filament ani nowych zależności.
