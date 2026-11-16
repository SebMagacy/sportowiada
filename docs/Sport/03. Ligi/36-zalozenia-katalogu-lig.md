---
tags:
  - sport
---

# 36. Założenia katalogu lig

## Cel i zgłoszenie

Pierwsza część [issue #25 — Sport: powiązać ligi z dyscyplinami i projekty z ligami oraz sezonami](https://github.com/SebMagacy/sportowiada/issues/25) zapisuje uzgodnione wymagania przed implementacją. Całe zgłoszenie obejmuje również przyszłe projekty i ochronę powiązań.

## Zatwierdzone założenia

### 1. Liga, dyscyplina i sezon

Liga jest trwałą tożsamością rozgrywek, np. „Miejska Liga Koszykówki”. Należy do dokładnie jednej dyscypliny. Projekt jest konkretną edycją tej ligi w jednym sezonie ze wspólnego katalogu:

```text
Discipline -> League -> Project <- Season
```

Przy tworzeniu projektu wybiera się ligę i sezon. Dyscyplina wynika z ligi; nie wybiera się jej ponownie. Liga łączy się z sezonami przez projekty. Osobny katalog lig powstaje od początku — wcześniejsze odłożenie go na później jest nieaktualne.

### 2. Dane ligi

- Wymagana nazwa do 255 znaków.
- Jedna wymagana dyscyplina z istniejącego katalogu.
- Opcjonalny opis.
- Właściciel przypisywany automatycznie przez system na podstawie twórcy.

Ten sam właściciel nie może mieć dwóch lig o tej samej nazwie w tej samej dyscyplinie. Różni właściciele mogą używać jednakowej nazwy. Unikalność dotyczy łącznie właściciela, dyscypliny i nazwy.

### 3. Uprawnienia i własność

Ligę może utworzyć zalogowany użytkownik, któremu administrator nadał uprawnienie do tworzenia lig. Samo zalogowanie nie wystarcza. Twórca zostaje właścicielem ligi i zarządza własną ligą; administrator zarządza wszystkimi.

Techniczny sposób nadawania uprawnienia pozostaje do uzgodnienia. Nadawanie i sprawdzanie uprawnienia muszą działać przed udostępnieniem tworzenia lig. Rozpoczęcie modelu nie wymaga pełnego zarządzania użytkownikami ani automatycznego rozpoczęcia #26 dotyczącego Filament Shield i ról. Nie zatwierdzono instalacji nowych zależności w tym etapie.

### 4. Ochrona historii

Dyscyplinę ligi można zmienić tylko wtedy, gdy liga nie ma żadnego projektu. Po utworzeniu pierwszego projektu zmiana dyscypliny jest blokowana.

Ligę można usunąć tylko wtedy, gdy nie ma żadnych projektów. Nie usuwamy ligi powiązanej z projektami, aby zachować historię jej edycji.

## Plan ośmiu części i commitów

Użytkownik zaakceptował osiem części, osiem rozdziałów dokumentacji i odpowiadające im commity. Poniższa tabela jest planem, nie wykazem wykonanych zmian.

| Część | Zakres |
| --- | --- |
| 1 | Założenia katalogu lig i dokumentacja — ten rozdział. |
| 2 | Model i tabela lig: pola, klucze oraz unikalność. |
| 3 | Relacje ligi z dyscypliną i właścicielem. |
| 4 | Nadawanie uprawnienia do tworzenia lig i ograniczenie zarządzania do własnych lig. |
| 5 | Lista, tworzenie, edycja i usuwanie w Filamencie. |
| 6 | Projekty powiązane z ligą i sezonem oraz ochrona historii. |
| 7 | Domknięcie weryfikacji: testy, formularze, Pint i PHPStan. |
| 8 | Końcowy przegląd kryteriów #25, dokumentacja wyników i publikacji. |

Testy dodajemy przy właściwych zmianach, a wyniki zapisujemy w odpowiadających im rozdziałach. Część 7 domyka weryfikację. Każdy commit ma obejmować spójną zmianę i wskazywać `Refs #25`. Część 8 nie zastępuje sprawdzeń ani dokumentacji wcześniejszych commitów. Granice commitów można dostosować do rzeczywistej pracy; issue zamykamy dopiero po spełnieniu całego zakresu i potwierdzeniu publikacji.

Drużyny, składy, mecze, terminarze, wyniki, puchary i integracje pozostają poza zakresem #25.

## Wykonanie i weryfikacja

Użytkownik zatwierdził powyższe zasady w rozmowie. Asystent uzupełnił opis i kryteria akceptacji issue #25; GitHub potwierdził aktualizację. Zgłoszenie pozostało otwarte, a kryteria niezaznaczone.

Użytkownik utworzył katalog `docs/Sport/03. Ligi/`; asystent potwierdził jego obecność i zapisał ten rozdział. Wcześniejszy przegląd kodu i bazy wykazał katalogi dyscyplin oraz sezonów, brak lig i projektów, Laravel `13.42.0` i Filament `5.11.3`. Przed zapisaniem rozdziału ponownie sprawdzono listę plików modeli i bazy: nadal nie znaleziono plików lig ani projektów. Nie ponawiano odczytu schematu ani wersji.

Podano wcześniej polecenie wygenerowania `League` z migracją, fabryką i seederem, ale użytkownik nie potwierdził wykonania. Nie zapisujemy go jako wykonanego kroku. W tej części zmieniono dokumentację, nie kod aplikacji; nie uruchamiano migracji ani testów aplikacji. Commit i push pozostają do wykonania przez użytkownika.

### Lokalny commit pierwszej części

Użytkownik przekazał wynik udanego `git commit`: `caf894f` — „Zapisz założenia katalogu lig — Refs #25”. Commit obejmuje dwa pliki dokumentacji: kontekst projektu i ten rozdział (96 dodanych i 6 usuniętych linii). PHPStan uruchomiony przez GrumPHP w hooku przeszedł poprawnie. Nie jest to test działania katalogu lig. Push nie został jeszcze potwierdzony. Ten wpis dopisano po commicie i pozostaje lokalną zmianą dokumentacji do następnego zapisu.

## Sposób pracy

Po polsku, jeden mały krok naraz: cel, mechanizm, konkretna edycja lub polecenie, kryterium sukcesu, następnie oczekiwanie na wynik. Użytkownik wykonuje kod aplikacji, polecenia i Git. Asystent aktualizuje dokumentację po potwierdzonych krokach. Zachowujemy lokalne zmiany, bez nowych gałęzi, worktree i zmian zależności.
