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

## Laravel IDE Helper

Laravel IDE Helper generuje plik z informacjami, które pomagają
edytorowi podpowiadać metody i typy Laravela.

### Instalacja

Najpierw wykonaliśmy próbę instalacji:

```bash
composer require --dev barryvdh/laravel-ide-helper --dry-run
```

Composer zaproponował cztery nowe pakiety, bez aktualizacji i usuwania
istniejących zależności.

Następnie zainstalowaliśmy narzędzie jako zależność deweloperską:

```bash
composer require --dev barryvdh/laravel-ide-helper
```

Zainstalowana wersja: 3.7.0. Laravel automatycznie wykrył pakiet.

### Generowanie pliku podpowiedzi

Sprawdziliśmy dostępne opcje:

```bash
php artisan ide-helper:generate --help
```

Następnie wygenerowaliśmy plik:

```bash
php artisan ide-helper:generate --no-interaction
```

Powstał plik `_ide_helper.php` w katalogu głównym projektu.
Po dodaniu kolejnych pakietów udostępniających fasady można ponownie
uruchomić to polecenie, aby odświeżyć podpowiedzi.

### Pominięcie wygenerowanego pliku w Git

Sprawdziliśmy regułę ignorowania:

```bash
git check-ignore -v _ide_helper.php
```

Istniejący wpis `_ide_helper.php` w `.gitignore` wyklucza ten plik
z repozytorium. Nie trzeba było zmieniać reguł ignorowania.

### Testy po instalacji

Uruchomiliśmy:

```bash
php artisan test --compact
```

Wynik: 2 testy zakończone powodzeniem, 2 asercje.

## Larastan i PHPStan

PHPStan analizuje kod PHP bez uruchamiania aplikacji, pomagając
wykrywać m.in. nieprawidłowe typy i wywołania nieistniejących metod.
Larastan rozszerza tę analizę o mechanizmy Laravela.

### Instalacja

Najpierw wykonaliśmy próbę instalacji:

```bash
composer require --dev larastan/larastan phpstan/phpstan --dry-run
```

Composer zaproponował trzy nowe pakiety, bez aktualizacji i usuwania
istniejących zależności.

Następnie wykonaliśmy instalację:

```bash
composer require --dev larastan/larastan phpstan/phpstan
```

Zainstalowane wersje:

- `larastan/larastan` — 3.13.0,
- `phpstan/phpstan` — 2.3.1,
- `iamcal/sql-parser` — 0.7.

### Konfiguracja

W katalogu głównym projektu utworzyliśmy plik `phpstan.neon`:

```neon
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    paths:
        - app

    level: 5
```

Sekcja `includes` włącza rozszerzenie Larastan.
Parametr `paths` określa analizowane katalogi.
Poziom `5` określa rygor sprawdzania — wyższe poziomy dodają
kolejne reguły analizy.

### Uruchomienie analizy

Wykonaliśmy:

```bash
vendor/bin/phpstan analyse
```

PHPStan automatycznie odczytał plik `phpstan.neon`.
Przeanalizował trzy pliki z katalogu `app` i nie zgłosił błędów
na poziomie 5.

### Testy po instalacji

Uruchomiliśmy:

```bash
php artisan test --compact
```

Wynik: 2 testy zakończone powodzeniem, 2 asercje.