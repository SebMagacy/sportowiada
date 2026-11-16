---
tags:
  - sport
---

# 38. Relacje ligi

## Cel i zakres

Trzecia część [issue #25](https://github.com/SebMagacy/sportowiada/issues/25) obejmuje relacje Eloquent ligi z dyscypliną i właścicielem oraz relacje odwrotne. Tabela i klucze obce zostały przygotowane w [części 2](37-model-i-tabela-lig.md), opublikowanej w commicie `d4e0109`.

Klucz obcy chroni spójność w bazie, a metoda relacji pozwala pobierać powiązany model w kodzie aplikacji. Dodanie metod relacji nie wymaga nowej migracji.

## 1. Dyscyplina ligi — krok do wykonania

Plik: `app/Models/League.php`.

Planowany import:

```php
use Illuminate\Database\Eloquent\Relations\BelongsTo;
```

Planowana metoda w klasie:

```php
/** @return BelongsTo<Discipline, $this> */
public function discipline(): BelongsTo
{
    return $this->belongsTo(Discipline::class);
}
```

Nazwa metody wskazuje konwencyjny klucz `discipline_id`. Po dodaniu relacji `$league->discipline` pozwoli pobrać dyscyplinę ligi. PHPDoc opisuje typ powiązanego modelu dla analizy statycznej. Składnię sprawdzono przez Boost dla Laravel 13.

Przed podaniem kroku asystent odczytał model: metoda nie była jeszcze dodana. Wykonanie i działanie relacji nie zostały potwierdzone. Kryterium najbliższego kroku: import i metoda zapisane w modelu przez użytkownika. Testy relacji oraz kontrole części 3 pozostają do wykonania.
