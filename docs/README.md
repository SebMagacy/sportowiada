# Sportowiada — tworzenie projektu

Poniżej zapisuję kroki potrzebne do odtworzenia projektu od początku.

## 1. Czysta instalacja Laravela

Pracę rozpoczynam w katalogu nadrzędnym:

```shell
cd /home/sebastian/Development/Laravel
mkdir sportowiada
composer create-project laravel/laravel sportowiada
cd sportowiada
php artisan --version
```

Po instalacji polecenie sprawdzające zwróciło: `Laravel Framework 13.38.0`.

## 2. Inicjalizacja Git

Będąc w katalogu `sportowiada`, tworzę lokalne repozytorium z gałęzią `main`:

```shell
git init -b main
```

## 3. Pierwszy commit

Dodaję pliki Laravela i zapisuję pierwszy stan projektu:

```shell
git add .
git commit -m "Czysta instalacja Laravel"
```

W tym momencie katalog `docs` nie jest jeszcze częścią repozytorium.

## 4. Utworzenie dokumentacji

W katalogu głównym projektu tworzę folder dokumentacji i jego plik początkowy:

```shell
mkdir docs
touch docs/README.md
```

## 5. Zapisanie dokumentacji w Git

Do repozytorium dodaję tylko plik dokumentacji. Lokalne ustawienia Obsidiana w `docs/.obsidian/` pozostają poza tym commitem.

```shell
git add docs/README.md
git commit -m "Dodanie dokumentacji projektu"
```

## 6. Pominięcie lokalnych ustawień Obsidiana

Obsidian zapisuje ustawienia sejfu w katalogu `docs/.obsidian/`. Aby Git ich nie śledził, dodaję do głównego pliku `.gitignore`:

```gitignore
docs/.obsidian/
```

Następnie zapisuję zmianę:

```shell
git add .gitignore
git commit -m "Ignorowanie lokalnych ustawień Obsidiana"
```
