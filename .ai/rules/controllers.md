---
paths:
  - 'app/Http/Controllers/**'
---

# Controllers

## Oddzielaj dane listy od uniwersalnego widoku
Przy budowie list pozostaw autoryzację, normalizację dozwolonych parametrów, zapytanie Eloquent i paginację poza komponentami Blade. Przekazuj widokom jawne dane i konfigurację konkretnej listy. Zachowuj dozwolone kolumny sortowania, stabilny porządek oraz parametry wyszukiwania i filtrów w linkach. Wspólne operacje wydzielaj, gdy mają rzeczywiste zastosowanie w kolejnych listach; nie twórz uniwersalnego silnika zapytań ani warstwy repozytoriów jedynie dla ujednolicenia widoków.

## Przygotowuj dane list panelu według wspólnego wzorca
Listy panelu buduj według podziału odpowiedzialności listy użytkowników: metoda index kontrolera przygotowuje dane i konfigurację kolumn oraz przekazuje je jawnie do widoku. Dotyczy to także list ze stałymi danymi bez bazy; nie umieszczaj przygotowania danych w komponentach Blade. Wdrażaj tylko uzgodnione funkcje listy, bez kopiowania niepotrzebnej logiki innych list.
