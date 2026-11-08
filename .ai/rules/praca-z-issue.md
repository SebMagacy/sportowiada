# Praca ze zgłoszeniami GitHub

Jedno issue traktujemy jako jeden etap pracy. Domyślnie realizujemy jedno zgłoszenie naraz, wybrane przez użytkownika.

1. Przeczytaj aktualny opis issue i kryteria akceptacji. Sprawdź instrukcje projektu, status Git, kod i wersje zależności potrzebnych do zadania. Zachowaj istniejące zmiany.
2. Prowadź po polsku, po jednym małym kroku: cel, wyjaśnienie mechanizmu, konkretna zmiana lub polecenie i kryterium sukcesu. Następnie poczekaj na wynik użytkownika.
3. Użytkownik wykonuje edycje aplikacji, polecenia i operacje Git. Asystent wykonuje je tylko na wyraźną prośbę. Odczyt i przegląd kodu może wykonywać samodzielnie.
4. Uzupełniaj istniejącą dokumentację etapu po potwierdzonych krokach zgodnie z [regułą dokumentowania](dokumentowanie-krokow.md). Nowy rozdział twórz na wyraźną prośbę użytkownika; wpisz w nim numer i odnośnik do issue. Issue określa cel i warunki ukończenia, a dokumentacja wyjaśnia kod i zapisuje wyniki.
5. Przed ukończeniem porównaj rezultat z każdym kryterium akceptacji. Checklistę aktualizuj wyłącznie na podstawie wykonanych zmian i potwierdzonych sprawdzeń; samo przejście niezwiązanych testów nie wystarcza.
6. Wykonaj uzgodnioną weryfikację, przegląd zmian i wymagane kontrole projektu. Następnie poprowadź użytkownika przez commit i push. Powiąż commit ze zgłoszeniem, np. przez `Refs #5`.
7. Po spełnieniu kryteriów i potwierdzeniu publikacji zmian zamknijcie issue z krótkim podsumowaniem, wynikiem weryfikacji i numerem commita. Nie oznaczaj zgłoszenia jako ukończonego, jeśli wymagane sprawdzenia lub zmiany pozostają niewykonane. Zmiany w GitHub wykonuj w zakresie zlecenia użytkownika; nie twierdź, że checklistę lub stan issue zmieniono, jeśli operacja nie została potwierdzona.

Problemy i pomysły wykraczające poza zakres zaproponuj jako osobne issue, zamiast rozszerzać bieżący etap bez uzgodnienia. Nie zmieniaj zależności ani nie instaluj Filament tylko dlatego, że służy jako wzór wizualny.
