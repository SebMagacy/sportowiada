# Trasa i widok strony głównej

Opis zastąpienia strony powitalnej własnym widokiem. Pusty widok jest stanem pośrednim tego etapu; zasoby Vite i Vue dodajemy w [[07-javascript-vite-vue-strona-publiczna]].

## 1. Stan początkowy

### 1.1. Domyślna strona Laravel

Po utworzeniu projektu trasa `/` w `routes/web.php` zwracała widok
`welcome`. Laravel odczytywał go z pliku
`resources/views/welcome.blade.php`. W rozdziale 01 sprawdziliśmy
działanie tej domyślnej strony przez `php artisan serve`.

## 2. Własny widok `home`

### 2.1. Przygotowanie dokumentu HTML

Plik: `resources/views/home.blade.php`

Utworzyliśmy plik widoku z podstawowym
szkieletem HTML. Ustawiliśmy język dokumentu na polski i tytuł
`Sportowiada`. Element `<body>` jest na razie pusty. Nie dodawaliśmy
jeszcze zawartości strony ani kodu JavaScript.

Szkielet zapisany na tym etapie w commicie `2db6b84`:

```html
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sportowiada</title>
</head>
<body>

</body>
</html>
```

- `<!DOCTYPE html>` — określa dokument HTML.
- `lang="pl"` — wskazuje język treści, wykorzystywany m.in. przez czytniki ekranu.
- `charset="UTF-8"` — określa kodowanie znaków.
- `viewport` — dostosowuje szerokość obszaru strony do szerokości urządzenia.
- `<title>` — ustawia tytuł karty przeglądarki.
- `<body>` — jest miejscem na widoczną treść strony, którą rozwiniemy później.

## 3. Zmiana trasy

### 3.1. Połączenie adresu z widokiem

Plik: `routes/web.php`

Zmieniliśmy widok zwracany dla żądania `GET /`:

Na początku istniejącego pliku pozostawiamy import fasady tras:

```php
use Illuminate\Support\Facades\Route;
```

Definicja trasy:

```php
Route::get('/', function () {
    return view('home');
});
```

- `Route::get()` — rejestruje trasę dla żądania GET.
- `/` — oznacza główną ścieżkę witryny.
- Funkcja anonimowa — jest wykonywana po dopasowaniu trasy.
- `return view('home')` — zwraca odpowiedź z wyrenderowanym widokiem.

Nazwa `home` wskazuje plik `resources/views/home.blade.php`. To trasa
wybiera widok dla adresu `/`; sama nazwa pliku nie czyni go automatycznie
stroną główną.

Laravel rejestruje dla tej definicji także HEAD, dlatego lista tras pokazuje `GET|HEAD`. Przykład jest fragmentem istniejącego `routes/web.php`, a nie pełną zawartością pliku.

### 3.2. Sprawdzenie trasy i strony

Po zmianie sprawdziliśmy, że Laravel rejestruje trasę `GET|HEAD /`,
a plik `home.blade.php` istnieje. Użytkownik potwierdził w przeglądarce,
że strona otwiera się pod skonfigurowaną domeną lokalną.

## 4. Usunięcie nieużywanego widoku

### 4.1. Usunięcie widoku powitalnego

Plik: `resources/views/welcome.blade.php`

Po przełączeniu trasy usunęliśmy dawny plik
`resources/views/welcome.blade.php` poleceniem:

```bash
git rm resources/views/welcome.blade.php
```

`git rm` usuwa śledzony plik i przygotowuje jego usunięcie do commitu. Nie tworzy jeszcze commitu.

Git zarejestrował jego usunięcie jako zmianę do zatwierdzenia.

## 5. Kontrole

### 5.1. Składnia i formatowanie

Sprawdzenie składni `routes/web.php` przez `php -l` nie zgłosiło błędu.
Uruchomiony po zmianie pliku PHP Laravel Pint zwrócił `passed` i nie
zmienił przygotowanych plików.

`php -l` sprawdza składnię PHP, a Pint formatowanie. Żadna z tych kontroli nie wysyła żądania do aplikacji; działanie strony potwierdzono osobno w przeglądarce.
