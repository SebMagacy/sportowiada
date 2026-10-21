# 02. Narzędzia deweloperskie

## Planowane narzędzia

Do projektu chcemy dodać:

| Pakiet | Przeznaczenie |
|---|---|
| fruitcake/laravel-debugbar | Podgląd zapytań SQL, czasu wykonania i informacji diagnostycznych podczas pracy nad aplikacją. |
| barryvdh/laravel-ide-helper | Generowanie informacji ułatwiających IDE podpowiadanie kodu Laravela. |
| larastan/larastan | Rozszerzenie PHPStan do analizy kodu wykorzystującego Laravel. |
| phpstan/phpstan | Statyczna analiza kodu PHP, pomagająca wykrywać błędy bez uruchamiania aplikacji. |
| phpro/grumphp | Automatyczne uruchamianie kontroli jakości kodu przed commitem. |
| laravel/boost | Wsparcie asystentów AI w pracy nad projektem Laravel. |

## Sprawdzenie obecnych zależności

W katalogu projektu wykonaliśmy:

```shell
composer show --direct
```

Żaden z planowanych pakietów nie znajdował się na liście bezpośrednich
zależności projektu.

## Laravel Debugbar

Debugbar wyświetla pasek diagnostyczny w przeglądarce, który pomaga
sprawdzać m.in. zapytania SQL i czas wykonania żądania.

### Sprawdzenie zgodności zależności

Przed instalacją sprawdziliśmy, czy wymagania nowego pakietu można
pogodzić z wersjami PHP, Laravela i pozostałych bibliotek projektu:

```bash
composer require --dev fruitcake/laravel-debugbar --dry-run
```

Opcja `--dry-run` wykonuje próbę bez instalowania pakietów i zapisywania
zmian w plikach `composer.json` oraz `composer.lock`. Pokazuje, jakie
pakiety zostałyby dodane, zaktualizowane lub usunięte.

W naszym przypadku Composer zaproponował dodanie trzech pakietów,
bez aktualizacji i usuwania istniejących zależności.

Jest to krok opcjonalny — zwykłe `composer require` również sprawdza
zgodność, a następnie przeprowadza instalację.

### Instalacja

Zainstalowaliśmy Debugbar jako zależność deweloperską:

```bash
composer require --dev fruitcake/laravel-debugbar
```

Opcja `--dev` dodaje pakiet do sekcji `require-dev` w `composer.json`.
Dzięki temu można pominąć jego instalację na serwerze produkcyjnym,
używając `composer install --no-dev`.

Composer zainstalował:

- `fruitcake/laravel-debugbar` — 4.4.4,
- `php-debugbar/php-debugbar` — 3.8.0,
- `php-debugbar/symfony-bridge` — 1.1.0.

Laravel automatycznie wykrył pakiet. Nie wprowadzaliśmy dodatkowej
konfiguracji.

### Sprawdzenie działania

Uruchomiliśmy aplikację:

```bash
php artisan serve
```

Po otwarciu strony w przeglądarce potwierdziliśmy obecność paska
Debugbara na dole strony.

![Strona aplikacji z widocznym paskiem Laravel Debugbar](images/02-laravel-debugbar.png)

### Testy po instalacji

Po instalacji uruchomiliśmy testy:

```bash
php artisan test --compact
```

Wynik: 2 testy zakończone powodzeniem, 2 asercje.

### Pominięcie danych diagnostycznych w Git

Debugbar zapisuje dane diagnostyczne w katalogu `storage/debugbar/`.
Nie przechowujemy ich w repozytorium, dlatego dodaliśmy do `.gitignore`:

```gitignore
/storage/debugbar
```
