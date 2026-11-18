---
tags:
  - produkt
  - projektowanie
  - kierunek-rozwoju
---

# 44. Sportowiada — koncepcja produktu i kierunek projektowania

## Cel i status dokumentu

Rozdział utworzono 18.11.2026 na wyraźną prośbę użytkownika, aby zachować koncepcję produktu i wracać do niej przy projektowaniu. Zapisuje kierunek omówiony w rozmowie, a nie gotową specyfikację ani stan wdrożenia.

Dokument nie jest osobnym zadaniem implementacyjnym i nie ma własnego zgłoszenia GitHub. Nie rozszerza bieżącego [#31 — widoku pustego katalogu](https://github.com/SebMagacy/sportowiada/issues/31) ani pozostałych etapów. Ich wykonanie i weryfikację opisuje m.in. [rozdział 43](43-ujednolicenie-list-filament.md).

## Jedna platforma, różni odbiorcy

### Główny cel — publikacja udostępnionych treści

W dalszej rozmowie użytkownik doprecyzował priorytet: **głównym celem jest stworzenie publicznej witryny prezentującej udostępnione treści sportowe**. To ustalenie określa pierwszeństwo względem szerokiego katalogu przyszłych modułów opisanego poniżej.

Podstawowy przebieg do zaprojektowania: utworzenie treści, określenie miejsc i warunków jej udostępnienia, a następnie publikacja w witrynie. Treści mogą obejmować aktualności, informacje o klubach i wydarzeniach oraz terminarze, wyniki i tabele. Trzeba osobno ustalić ich zakres, autorów, zatwierdzanie i zasady publikowania; samo zapisanie danych nie oznacza zgody na ich publiczne udostępnienie.

Panele redakcji, klubów i organizatorów mają wspierać dostarczanie i kontrolę treści. Rozliczenia, bilety i pozostałe rozszerzenia pozostają przyszłymi możliwościami, nie warunkiem uruchomienia pierwszej witryny. Nie ustalono jeszcze specyfikacji jej pierwszej wersji. Bieżący etap #31 pozostaje odrębnym, rozpoczętym zadaniem panelu.

Użytkownik określił docelowy kierunek: **jeden produkt dla wielu niezależnych organizacji**, z osobnymi modułami dla OSiR-ów i klubów, możliwością sprzedaży biletów oraz tworzenia własnych stron internetowych.

„Jeden produkt dla wszystkich” oznacza wspólny silnik i powiązane dane, ale funkcje, panel i dostęp dostosowane do potrzeb konkretnej organizacji. Nie oznacza identycznego zestawu ekranów ani wzajemnego dostępu do wszystkich danych.

Platforma nie ogranicza się do jednego klubu, jednej ligi ani jednej dyscypliny. Może obsługiwać zarówno organizatorów rozgrywek, jak i regionalnego wydawcę, który sam nie organizuje zawodów, lecz prezentuje informacje pochodzące z różnych organizacji.

## Firma, produkt i konkretna witryna

| Nazwa | Znaczenie w koncepcji |
| --- | --- |
| **Rogacki** | Firma lub marka producenta oprogramowania. |
| **Sportowiada** | Produkt: modułowa platforma sportowa. |
| **Sportsztyn**, domena **sportsztyn.pl** | Konkretna regionalna witryna korzystająca ze Sportowiady; planowane pierwsze wdrożenie i przykład wykorzystania produktu. |

Sportsztyn ma prezentować wyniki wielu niezależnych klubów w regionie i różnych dyscyplinach, a nie być wyłącznie witryną pojedynczego klubu lub narzędziem administracji miasta. Miasto, gmina, powiat, MOSiR lub OSiR mogą być klientami platformy, ale nie definiują granic całego produktu.

Rozważano nazwę „Rogacki” na górze wspólnego panelu i „Sportowiada” zamiast grupy menu „Sport”. To pomysł dotyczący prezentacji marek, nie wykonana zmiana. Po doprecyzowaniu, że Sportowiada jest całym produktem, dokładne nazwy panelu, grup i modułów wymagają ponownego uzgodnienia; nie należy automatycznie utożsamiać Sportowiady wyłącznie z modułem rozgrywek.

## Docelowe obszary funkcjonalne

Poniższy podział porządkuje koncepcję. Nie jest listą już istniejących modułów ani zatwierdzoną kolejnością ich implementacji.

| Obszar | Planowana odpowiedzialność |
| --- | --- |
| **Rozgrywki** | Dyscypliny, ligi, sezony, edycje/projekty, uczestnicy, terminarze, wyniki, tabele i historia. |
| **OSiR / organizator** | Prowadzenie własnych lig, turniejów i wydarzeń, obsługa organizacyjna oraz weryfikacja danych rozgrywek. |
| **Klub** | Własne drużyny i zawodnicy, zgłoszenia do rozgrywek; w przyszłości treningi, kontakt z rodzicami i rozliczenia opłat za treningi. |
| **Bilety** | Sprzedaż biletów na wydarzenia organizowane przez uprawnione kluby lub ośrodki; szczegółowy zakres płatności i kontroli wejścia do zaprojektowania. |
| **Witryny i portal publiczny** | Tworzenie własnych stron klubów, organizatorów i portali regionalnych oraz prezentowanie udostępnionych treści i wyników. |

OSiR i klub są różnymi kontekstami pracy, ale mogą współpracować przy tych samych rozgrywkach. Nie wymagają z tego powodu osobnych, niepowiązanych kopii drużyn lub wyników.

## Własna strona jako część produktu

Użytkownik wskazał możliwość stworzenia własnej strony jako element produktu. Przykładowe zastosowania:

- klub publikuje informacje o drużynach, aktualności, terminarze i wyniki;
- OSiR prezentuje własne wydarzenia i rozgrywki;
- portal regionalny zbiera opublikowane informacje z wielu klubów i od różnych organizatorów.

Kierunek do dopracowania obejmuje markę klienta, wygląd i nawigację witryny, jej adres oraz wybór publikowanych danych. Własne domeny, sposób konfiguracji wyglądu i ewentualny edytor stron nie mają jeszcze specyfikacji ani implementacji. Nie zakładamy automatycznie rozbudowanego kreatora wizualnego.

## Scenariusz łączący moduły

Przykładowy docelowy przebieg, do wykorzystania przy projektowaniu:

1. Klub utrzymuje profil i drużynę oraz zgłasza ją do rozgrywek.
2. OSiR lub inny organizator prowadzi rozgrywki i publikuje terminarz.
3. Uprawniona osoba wprowadza wynik, który przechodzi uzgodnioną weryfikację.
4. Opublikowany wynik pojawia się na właściwych stronach: rozgrywek, klubu i portalu regionalnego, bez ręcznego przepisywania.
5. Jeżeli wydarzenie ma sprzedaż biletów, odbiorca może przejść od informacji o nim do zakupu.

To scenariusz docelowy, nie potwierdzona obecnie funkcjonalność. Powiązanie danych nie oznacza automatycznej publikacji na każdej witrynie.

## Konsekwencje do uwzględnienia w projektowaniu

Poniższe punkty są wnioskami projektowymi z koncepcji; szczegółowe decyzje wymagają osobnych ustaleń.

- **Granice organizacji:** ustalić, które dane należą do klubu, organizatora, wydawcy lub operatora platformy i kto może je zmieniać.
- **Wspólna tożsamość danych:** zaplanować wiązanie klubów, drużyn, uczestników i wydarzeń między modułami bez przypadkowego dublowania rekordów.
- **Prywatność i publikacja:** oddzielić wewnętrzne dane od informacji przeznaczonych do publikacji. Dostęp organizatora do drużyny nie daje dostępu do rozliczeń rodziców lub innych prywatnych spraw klubu.
- **Uprawnienia w kontekście organizacji:** określić role w konkretnej organizacji lub rozgrywkach. Jedna osoba może pełnić różne funkcje w różnych miejscach.
- **Udostępnianie:** ustalić zgodę, zakres i warunki prezentowania danych organizacji w portalu regionalnym lub na innej stronie.
- **Wiarygodność wyników:** określić źródło, status weryfikacji, korekty i historię zmian. Nie utożsamiać możliwości wpisania wyniku z prawem jego zatwierdzania.
- **Moduły:** organizacja otrzymuje potrzebne funkcje bez obowiązku korzystania z całej platformy. Model licencji i rozliczania dostępu nie jest jeszcze ustalony.
- **Bilety i płatności:** przed wdrożeniem rozstrzygnąć tożsamość sprzedawcy, przepływ środków, rozdzielenie rozliczeń organizacji, obsługę zwrotów i integrację z operatorem płatności.

Ten rozdział nie wybiera architektury baz danych, modelu izolacji organizacji, systemu domen, dostawcy płatności ani technologii kreatora stron. Nie upoważnia do dodawania takich mechanizmów przy okazji bieżących prac.

## Konkurencja i możliwy wyróżnik

W rozmowie rozróżniono konkurencję **Sportsztyna o odbiorców portalu** od konkurencji **Sportowiady jako produktu**. Inny portal regionalny może być potencjalnym klientem oprogramowania, nawet jeśli publikuje podobne informacje.

Wstępny przegląd publicznych ofert 18.11.2026 wskazał następujące punkty odniesienia:

| Obszar | Przykłady ofert sprawdzonych w rozmowie |
| --- | --- |
| Rozgrywki i publikacja na witrynie | [JoomSport](https://joomsport.com/), [JoomLeague](https://docs.joomleague.eu/getting-started), [SportsPress Pro](https://www.themeboy.com/sportspress-pro/compare/). |
| Organizatorzy i udostępnianie danych rozgrywek | [LeagueRepublic — integracje](https://help.leaguerepublic.com/en/articles/6324627-which-integration-is-right-for-me), [Virium](https://www.virium.pl/pl), [RozgrywkiSportowe.pl](https://rozgrywkisportowe.pl/pl). |
| Kluby, treningi i składki | [ProTrainUp](https://protrainup.com/pl/pricing), [SportsManago](https://sportsmanago.pl/). |
| Bilety | [Bilety24 — współpraca](https://www.bilety24.pl/cms/wspolpraca), [Sportmanager — bilety dla klubów](https://sportmanager.dk/en/resources/billetsystem-til-sportsklubber). |

To przegląd ofert producentów, nie niezależne testy produktów ani pełny audyt rynku. Funkcje i warunki należy ponownie sprawdzać przed decyzją biznesową. Nie wykazano, że żaden konkurent nie potrafi obsłużyć całego scenariusza Sportowiady.

**Nie potwierdzono unikatowości produktu ani wielkości popytu.** Wiele sportów, wiele klubów, własna witryna lub bilety nie są same w sobie dowodem przewagi. Hipotezą wyróżnika jest wygodna współpraca niezależnych organizacji, redakcji i odbiorców na spójnych, kontrolowanie udostępnianych danych.

Warto sprawdzać tę hipotezę w rozmowach z klubami, OSiR-ami i wydawcami: jak obecnie zbierają wyniki, co przepisują wielokrotnie, kto zatwierdza dane, jakie systemy już mają oraz za jaką zmianę byliby gotowi zapłacić. Sportsztyn może służyć jako pierwsze praktyczne wdrożenie i demonstracja produktu; nie jest jeszcze dowodem zapotrzebowania innych klientów.

## Powrót do koncepcji przed kolejnymi etapami

Przed projektowaniem organizacji i członkostwa, uprawnień, stron klientów, integracji danych, modułu klubowego lub biletów wrócić do tego rozdziału i odpowiedzieć:

1. Dla którego odbiorcy powstaje funkcja i w kontekście jakiej organizacji działa?
2. Kto jest odpowiedzialny za dane i kto może je odczytać, zmienić, zatwierdzić oraz opublikować?
3. Czy funkcja korzysta z istniejącego rekordu, czy tworzy uzasadniony nowy byt?
4. Jak dane trafią do innych uprawnionych modułów i witryn bez przepisywania?
5. Co jest niezbędne w najbliższym etapie, a co pozostaje przyszłym rozszerzeniem?

Zalecenie omówione przez asystenta: rozpocząć od rozgrywek, terminarzy, wyników i regionalnego portalu, a kolejne moduły rozwijać na podstawie potrzeb pierwszych użytkowników. Nie ustalono jeszcze wiążącego harmonogramu całego produktu.

## Co wykonano w ramach tego zapisu

Utworzono wyłącznie dokument koncepcji i odnośnik w spisie treści. Nie zmieniono kodu aplikacji, nazwy panelu, menu, schematu danych, zależności ani uprawnień. Nie utworzono nowych zgłoszeń ani nie wykonano operacji Git. Funkcje opisane jako docelowe nie zostały przez ten zapis wdrożone.
