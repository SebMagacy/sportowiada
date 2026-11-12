---
paths:
  - 'resources/views/**'
---

# Views

## Zachowuj wspólną architekturę list panelu
Nowe listy panelu, w tym dyscypliny, buduj według wspólnej architektury z listą użytkowników. Wspólną oprawę i tabelę wydzielaj do komponentów Blade używanych przez obie listy, zachowując spójny wygląd; nie twórz równoległych kopii układu. Jest to kierunek nowych prac, a nie stwierdzenie, że istniejąca lista została już przebudowana. Dodawaj tylko funkcje objęte uzgodnionym zakresem; wspólna architektura nie wymaga powielania filtrów, wyszukiwania, zaznaczania ani paginacji w każdej liście.

## Zmiany architektury wdrażaj we wszystkich objętych widokach
Każdą zmianę architektury widoków wdrażaj w ramach tego samego zadania we wszystkich istniejących widokach, których dotyczy zmieniany wzorzec. Dotyczy to także wydzielania wspólnych komponentów list: dostosuj wszystkie korzystające z tego wzorca listy, zamiast pozostawiać równoległe stare i nowe rozwiązania. Zachowuj funkcje właściwe poszczególnym widokom; spójność architektury nie oznacza dodawania wszędzie identycznych funkcji. Ta zasada zastępuje wcześniejsze dopuszczenie odkładania migracji istniejących widoków.

## Nowe listy buduj z kompozycji komponentów Blade
Widoki list buduj z małych, współdzielonych komponentów Blade: oprawa listy, panel filtrów, pasek narzędzi, tabela i paginacja, stosownie do uzgodnionych funkcji. Przed tworzeniem sprawdź istniejące komponenty. Wspólny układ przekazuj przez sloty, dane i konfigurację przez jawne props, a dodatkowe atrybuty przez attribute bag. Pola filtrów, zawartość komórek i akcje pozostaw właściwe dla danej listy; unikaj komponentu z wieloma warunkami zależnymi od rodzaju listy. Komponenty widoku nie pobierają danych z bazy. Zmiany wspólnego wzorca wdrażaj we wszystkich objętych nim widokach w ramach tego samego zadania.
