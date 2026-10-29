# 06. Trasa i widok strony głównej

## Stan początkowy

Po utworzeniu projektu trasa `/` w `routes/web.php` zwracała widok
`welcome`. Laravel odczytywał go z pliku
`resources/views/welcome.blade.php`. W rozdziale 01 sprawdziliśmy
działanie tej domyślnej strony przez `php artisan serve`.

## Własny widok `home`

Utworzyliśmy plik `resources/views/home.blade.php` z podstawowym
szkieletem HTML. Ustawiliśmy język dokumentu na polski i tytuł
`Sportowiada`. Element `<body>` jest na razie pusty. Nie dodawaliśmy
jeszcze zawartości strony ani kodu JavaScript.

## Zmiana trasy

W `routes/web.php` zmieniliśmy widok zwracany dla żądania `GET /`:

```php
Route::get('/', function () {
    return view('home');
});
```

Nazwa `home` wskazuje plik `resources/views/home.blade.php`. To trasa
wybiera widok dla adresu `/`; sama nazwa pliku nie czyni go automatycznie
stroną główną.

Po zmianie sprawdziliśmy, że Laravel rejestruje trasę `GET|HEAD /`,
a plik `home.blade.php` istnieje. Użytkownik potwierdził w przeglądarce,
że strona otwiera się pod skonfigurowaną domeną lokalną.

## Usunięcie nieużywanego widoku

Po przełączeniu trasy usunęliśmy dawny plik
`resources/views/welcome.blade.php` poleceniem:

```bash
git rm resources/views/welcome.blade.php
```

Git zarejestrował jego usunięcie jako zmianę do zatwierdzenia.

## Kontrole

Sprawdzenie składni `routes/web.php` przez `php -l` nie zgłosiło błędu.
Uruchomiony po zmianie pliku PHP Laravel Pint zwrócił `passed` i nie
zmienił przygotowanych plików.
