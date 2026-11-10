---
paths:
  - 'resources/views/**'
---

# Views

## Nowe listy buduj z kompozycji komponentów Blade
Nowe widoki list oraz przebudowy istniejących list buduj z małych, współdzielonych komponentów Blade: oprawa listy, panel filtrów, pasek narzędzi, tabela i paginacja. Przed tworzeniem sprawdź istniejące komponenty. Wspólny układ przekazuj przez sloty, dane i konfigurację przez jawne props, a dodatkowe atrybuty przez attribute bag. Pola filtrów, zawartość komórek i akcje pozostaw właściwe dla danej listy; unikaj jednego komponentu z wieloma warunkami zależnymi od rodzaju listy. Komponenty widoku nie pobierają danych z bazy. Stosuj ten kierunek przy nowych pracach; zapis reguły nie oznacza migracji wszystkich istniejących widoków.
