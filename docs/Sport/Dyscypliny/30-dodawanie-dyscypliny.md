---
tags:
  - sport
---

# Dodawanie dyscypliny w panelu administratora

## Cel i zgłoszenie

Etap dotyczy [issue #22 — formularz tworzenia dyscypliny](https://github.com/SebMagacy/sportowiada/issues/22). Administrator ma dodawać dyscypliny z panelu, bez dopisywania ich przez seeder. Poprzedni etap opisano w [rozdziale 29](29-widok-dyscyplin.md).

## Stan wyjściowy

- Istnieje lista dyscyplin pobieranych z bazy przez `DisciplineController::index`.
- Tabela `disciplines` zawiera `id`, `name`, unikalny `alias` i znaczniki czasu.
- Nazwy są polskim tekstem w bazie; alias jest stały. Wielojęzyczne dane i adresy odłożono.
- Listy użytkowników i dyscyplin korzystają ze wspólnych komponentów `lists/panel` i `lists/table`.
- Kod poprzedniego etapu opublikowano w `e08607b`, dokumentację w `ad8e5ea`; użytkownik potwierdził push obu commitów.
- Poprzednia weryfikacja: 219 testów PHP / 1188 asercji oraz testy frontendowe, PHPStan, Pint i build przeszły. Te wyniki nie potwierdzają jeszcze nowego formularza.

## Planowany zakres

1. Uzgodnić format aliasu.
2. Dodać chronione trasy wyświetlenia formularza i zapisu oraz odpowiednie metody kontrolera.
3. Dodać przycisk „Dodaj dyscyplinę” na liście i formularz z polami „Nazwa” oraz „Alias”.
4. Zapewnić walidację, wyświetlenie błędów i zachowanie wpisanych wartości.
5. Po zapisie wrócić do listy z komunikatem sukcesu; zapewnić także powrót bez zapisu.
6. Dodać tłumaczenia interfejsu PL/EN, testy i sprawdzenie wyglądu na szerokim oraz wąskim ekranie.

Wiążące kryteria akceptacji znajdują się w issue #22. Dostęp do formularza i zapisu jest wyłącznie dla administratora. Formularz wymaga CSRF, jawnie dozwolonych pól i bezpiecznego wyświetlania wartości.

## Uzgodnione zachowanie aliasu

Użytkownik zatwierdził automatyczne podpowiadanie aliasu z nazwy, z możliwością ręcznej zmiany. Przykład: `Piłka nożna` daje `pilka-nozna`. Podpowiedź aktualizuje się podczas wpisywania nazwy do pierwszej ręcznej zmiany aliasu; późniejsze zmiany nazwy nie nadpisują ręcznie zmienionego aliasu.

Obowiązuje format: małe litery `a–z`, cyfry, pojedyncze myślniki między członami, bez myślnika na początku lub końcu, maksymalnie 255 znaków. Przy zapisie sprawdzamy format i unikalność. Zajęty alias powoduje błąd przy polu; nie dodajemy automatycznie numerów. To uzgodnienie, jeszcze bez implementacji.

## Krok 1 — trasa formularza

Użytkownik dodał w `routes/web.php`:

```php
Route::get('/admin/disciplines/create', [DisciplineController::class, 'create'])
    ->middleware(['auth', 'can:access-admin'])
    ->name('admin.disciplines.create');
```

`Route::get` rejestruje adres formularza i wskazuje metodę `create` kontrolera. Middleware wymagają zalogowania i uprawnień administratora. Nazwa trasy pozwoli generować odnośniki przez `route('admin.disciplines.create')`.

Użytkownik uruchomił `php artisan route:list --path=admin/disciplines -v`: wynik pokazuje trasę listy i nową trasę formularza, obie z `web`, `auth` i `can:access-admin`. Asystent potwierdził zapis trasy w kodzie. To potwierdza rejestrację i przypisanie middleware, ale nie wykonanie kontroli dostępu w żądaniu HTTP. Metoda `create` i widok jeszcze nie istnieją.

## Krok 2 — metoda kontrolera

Użytkownik dodał w `app/Http/Controllers/Admin/DisciplineController.php`, wewnątrz klasy obok `index`:

```php
public function create(): View
{
    return view('admin.disciplines.create');
}
```

Publiczna metoda `create` jest akcją wskazaną przez nową trasę. Typ zwracany `View` korzysta z istniejącego importu `Illuminate\View\View`. Funkcja `view` wskazuje plik `resources/views/admin/disciplines/create.blade.php`; kropki oddzielają katalogi i nazwę widoku.

Użytkownik potwierdził wykonanie kroku, a asystent sprawdził metodę w kodzie. Nie potwierdzono jeszcze wyświetlenia strony: plik widoku pozostaje do utworzenia.

## Krok 3 — tłumaczenie nagłówka

W tablicach tłumaczeń użytkownik dodał klucz `create_title`: w `lang/pl/disciplines.php` wartość `Dodaj dyscyplinę`, a w `lang/en/disciplines.php` wartość `Add discipline`. Dotychczasowe wpisy pozostały zachowane.

Wywołanie `__('disciplines.create_title')` pobiera tekst dla bieżącego języka aplikacji: pierwszy człon wskazuje plik, drugi klucz tablicy. Użytkownik potwierdził wykonanie, a asystent zweryfikował oba pliki. Wyświetlenie nagłówka w przeglądarce pozostaje do sprawdzenia po utworzeniu widoku.

## Krok 4 — widok i ikona dodawania

Użytkownik utworzył `resources/views/admin/disciplines/create.blade.php`:

```blade
<x-layouts.admin>
    <x-slot:heading>
        <x-ui.page-heading>
            <x-slot:icon>
                <x-ui.icon name="add-circle" class="size-full" />
            </x-slot:icon>

            {{ __('disciplines.create_title') }}
        </x-ui.page-heading>
    </x-slot:heading>
</x-layouts.admin>
```

`x-layouts.admin` zapewnia istniejący układ panelu. Slot `heading` przekazuje nagłówek do paska, `x-ui.page-heading` tworzy wspólny nagłówek, a jego slot `icon` przyjmuje ikonę. Tekst pobierany jest z wcześniej dodanego tłumaczenia. Pola formularza nie są jeszcze wdrożone.

Użytkownik wybrał ikonę Material Symbols Outlined `add_circle`. Lokalny plik to `resources/svg/add_circle.svg`, z `fill="currentColor"`, dzięki czemu dziedziczy kolor tekstu. W `resources/views/components/ui/icon.blade.php` dodano mapowanie:

```php
'add-circle' => resource_path('svg/add_circle.svg'),
```

Nazwa komponentu `add-circle` nie musi być identyczna z nazwą pliku; wskazana ścieżka musi jednak odpowiadać plikowi na dysku. Początkowo ścieżka zawierała myślnik zamiast podkreślenia i powodowała wyjątek o brakującym SVG. Po poprawieniu użytkownik potwierdził, że strona działa. Asystent sprawdził widok, mapowanie i kolor SVG w kodzie. Nie potwierdzono jeszcze widoku na wąskim ekranie ani działania formularza.

Podczas błędu brakującej ikony wystąpił także brak uprawnień procesu aplikacji do zapisu `storage/logs/laravel.log`. Nie zmieniano uprawnień; poprawne wyświetlenie strony po naprawie ścieżki nie potwierdza naprawienia zapisu logów.

## Krok 5 — tłumaczenia pola i przycisków

Użytkownik dodał w `lang/pl/disciplines.php` klucze `alias` (`Alias`), `save` (`Zapisz`) i `cancel` (`Anuluj`). Odpowiedniki w `lang/en/disciplines.php` to `Alias`, `Save` i `Cancel`. Nazwa pola korzysta z istniejącego `disciplines.name`.

Klucze przygotowują teksty do użycia przez `__('disciplines.alias')`, `__('disciplines.save')` i `__('disciplines.cancel')`. Użytkownik potwierdził wykonanie; asystent sprawdził oba pliki. Pola i przyciski nie są jeszcze dodane do widoku.

## Krok 6 — pola nazwy i aliasu

Użytkownik dodał do widoku `create.blade.php` dwa pola tekstowe z etykietami, zgodnie ze wzorcem formularza edycji użytkownika. Istotna część pola nazwy:

```blade
<label for="name" class="mb-2 block text-sm font-medium text-gray-700">
    {{ __('disciplines.name') }}
</label>

<input type="text" id="name" name="name" maxlength="255" required
    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
```

Pole aliasu ma analogiczną strukturę, z `id="alias"`, `name="alias"` i tłumaczeniem `disciplines.alias`. `for` etykiety odpowiada `id` pola. Atrybut `name` określi klucz przesyłanych danych po dodaniu formularza. `required` i `maxlength` przygotowują ograniczenia przeglądarki; nie zastępują walidacji serwera.

Kontener używa `grid grid-cols-1 md:grid-cols-2`: domyślnie pola są w jednej kolumnie, od progu `md` w dwóch. `max-w-3xl` ogranicza szerokość, `gap-6` zapewnia odstęp, a klasy `focus-visible:*` określają obrys fokusu.

Użytkownik zaakceptował krok, a asystent potwierdził oba pola w kodzie. Nie otrzymano osobnego wyniku sprawdzenia fokusu ani wąskiego ekranu. Nie ma jeszcze elementu `form`, zapisu, obsługi błędów ani automatycznego aliasu.

## Krok 7 — anulowanie

Pod kontenerem pól użytkownik dodał:

```blade
<div class="mt-6 flex flex-wrap items-center gap-3">
    <a href="{{ route('admin.disciplines.index') }}"
        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
        {{ __('disciplines.cancel') }}
    </a>
</div>
```

`route` generuje adres istniejącej listy. Odnośnik wykonuje nawigację bez przesyłania pól i bez zapisu. Kontener pozwala później umieścić obok przycisk zapisu; `flex-wrap` pozwala zawijać elementy przy braku miejsca. Klasy `focus-visible` zapewniają obrys przy nawigacji klawiaturą.

Użytkownik potwierdził sukces po poleceniu sprawdzenia powrotu na listę przez „Anuluj”; asystent zweryfikował odnośnik w kodzie. Zapis danych nadal nie jest wdrożony.

## Krok 8 — trasa zapisu

Użytkownik dodał w `routes/web.php`:

```php
Route::post('/admin/disciplines', [DisciplineController::class, 'store'])
    ->middleware(['auth', 'can:access-admin'])
    ->name('admin.disciplines.store');
```

Metoda HTTP rozróżnia operacje pod tym samym adresem: GET wyświetla listę, POST będzie tworzyć rekord. Trasa wskazuje akcję `store`, wymaga logowania i uprawnień administratora, a jej nazwa posłuży do wygenerowania adresu formularza.

Użytkownik potwierdził dodanie trasy; asystent sprawdził zapis w pliku. Nie otrzymano wyniku `route:list` dla tego kroku i nie sprawdzono jeszcze żądania POST. Metoda `store` pozostaje do dodania.

## Krok 9 — walidacja i zapis w kontrolerze

Użytkownik dodał importy `Illuminate\Http\RedirectResponse`, `Illuminate\Http\Request` i `Illuminate\Validation\Rule` oraz metodę:

```php
public function store(Request $request): RedirectResponse
{
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'alias' => [
            'bail',
            'required',
            'string',
            'max:255',
            'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/',
            Rule::unique('disciplines', 'alias'),
        ],
    ]);

    $discipline = new Discipline;
    $discipline->name = $validated['name'];
    $discipline->alias = $validated['alias'];
    $discipline->save();

    return redirect()->route('admin.disciplines.index');
}
```

`Request` udostępnia dane żądania, a `RedirectResponse` określa typ odpowiedzi. `validate` wymaga dwóch pól tekstowych do 255 znaków i przy błędzie przerywa wykonanie przed zapisem. Dla aliasu `bail` zatrzymuje dalsze reguły po pierwszym błędzie. Wyrażenie regularne sprawdza cały tekst (`\A` i `\z`): co najmniej jedną literę `a–z` lub cyfrę, opcjonalnie kolejne człony poprzedzone pojedynczym myślnikiem. Reguła `unique` sprawdza alias w tabeli `disciplines`; baza ma także unikalny indeks.

Nowa instancja `Discipline` otrzymuje wyłącznie jawnie przypisane, zwalidowane `name` i `alias`. `save` zapisuje rekord, a `redirect()->route` kieruje do listy. Automatyczna podpowiedź aliasu będzie osobną funkcją interfejsu; serwer sprawdza otrzymany alias i nie zastępuje go innym.

Użytkownik potwierdził dodanie metody; asystent sprawdził kod. Nie wykonano jeszcze żądania zapisu ani testów nowej metody. Formularz nie jest jeszcze podłączony, nie wyświetla błędów i nie zachowuje danych po błędzie. Komunikat sukcesu pozostaje do dodania. Formatowanie Pint będzie wymagane przy weryfikacji zmian PHP.

## Krok 10 — podłączenie formularza i rozpoznanie błędu walidacji

Użytkownik objął pola i przyciski elementem formularza:

```blade
<form method="POST" action="{{ route('admin.disciplines.store') }}">
    @csrf
    {{-- Pola i przyciski znajdują się wewnątrz formularza. --}}
</form>
```

`method="POST"` wybiera trasę zapisu, `action` wskazuje ją przez nazwę, a `@csrf` dodaje token ochronny. Dodany przycisk `type="submit"` wysyła formularz i korzysta z tłumaczenia `disciplines.save`. Asystent potwierdził strukturę w kodzie.

Pierwsza próba użytkownika nie utworzyła rekordu. Polecenie `php artisan debugbar:find --issues --max=10` zwróciło `No issues found in 10 scanned requests`; ten wynik nie dowodzi poprawnego zapisu. Następnie użytkownik dodał obsługę błędu aliasu:

```blade
@error('alias')
    <p role="alert" class="mt-2 text-sm text-red-600">
        {{ $message }}
    </p>
@enderror
```

Dyrektywa pokazuje komunikat walidacji konkretnego pola; `{{ $message }}` escapuje jego treść. Zrzut ekranu użytkownika potwierdza komunikat „Format pola alias jest nieprawidłowy.” i puste pola po powrocie. Żądanie dotarło więc do walidacji, która odrzuciła alias. Nie znamy wysłanej wartości. Poprawny zapis nadal nie jest potwierdzony. Blok błędu znajduje się obecnie między etykietą a polem; docelowo umieścimy go pod polem. Odtwarzanie wartości i błąd nazwy pozostają do dodania.

## Krok 11 — zachowanie wartości po błędzie

Użytkownik dodał do pola nazwy atrybut:

```blade
value="{{ is_string(old('name')) ? old('name') : '' }}"
```

Pole aliasu otrzymało analogiczny atrybut z `old('alias')`. Laravel zachowuje dane w sesji po nieudanej walidacji; `old` pobiera je przy ponownym wyświetleniu formularza. `is_string` odrzuca nietekstową wartość, a składnia `{{ }}` escapuje tekst w atrybucie HTML.

Użytkownik przeniósł także blok `@error('alias')` pod pole aliasu. Asystent potwierdził obie zmiany w kodzie. Zrzut ekranu użytkownika pokazuje zachowane `Piłka nożna 2` oraz `pilka_nozna_2` i błąd formatu pod polem. Potwierdza to zachowanie wartości i odrzucenie podkreśleń, które nie należą do uzgodnionego formatu aliasu. Poprawny zapis pozostaje do sprawdzenia z aliasem używającym myślników.

## Krok 12 — potwierdzony poprawny zapis

Po zmianie aliasu z `pilka_nozna_2` na `pilka-nozna-2` użytkownik potwierdził sukces próby: zapis i powrót do listy z nową dyscypliną „Piłka nożna 2”. To ręczne potwierdzenie poprawnej ścieżki dla jednego rekordu, pochodzące od użytkownika. Nie zastępuje testów automatycznych, sprawdzenia zajętego aliasu ani odmowy dostępu do GET i POST.

Automatyczna podpowiedź aliasu i komunikat sukcesu nadal pozostają do wdrożenia.

## Krok 13 — tłumaczenie komunikatu sukcesu

Użytkownik dodał klucz `created` w obu plikach tłumaczeń dyscyplin: `Dyscyplina została dodana.` w PL oraz `Discipline added successfully.` w EN. Asystent potwierdził wartości w kodzie. Wywołanie `__('disciplines.created')` pozwoli przekazać odpowiedni tekst po zapisie. Samo przekazanie i wyświetlenie komunikatu nie jest jeszcze wdrożone.

## Krok 14 — przekazanie statusu i wspólny komponent

Użytkownik zmienił końcowe przekierowanie metody `store`:

```php
return redirect()->route('admin.disciplines.index')
    ->with('status', __('disciplines.created'));
```

`with` przekazuje przetłumaczony komunikat jako dane flash sesji na następne żądanie. Asystent potwierdził kod; wyświetlenie komunikatu i jego zniknięcie po odświeżeniu nie są jeszcze sprawdzone.

Ponieważ lista użytkowników miała już własny blok statusu, uzgodniono wydzielenie wspólnego komponentu zamiast kopiowania HTML. Użytkownik utworzył `resources/views/components/ui/status-message.blade.php`:

```blade
@if (session('status'))
    <div role="status"
        {{ $attributes->class(['mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800']) }}>
        {{ session('status') }}
    </div>
@endif
```

Warunek pomija komunikat przy braku statusu. `role="status"` oznacza informację o stanie operacji. Attribute bag pozwala przekazać dodatkowe atrybuty i klasy, a `{{ session('status') }}` escapuje tekst. Użytkownik potwierdził utworzenie pliku, a asystent sprawdził jego treść. Podłączenie komponentu do obu list jest następnym krokiem; migracja wspólnego wzorca nie jest jeszcze ukończona.

## Krok 15 — wspólny status w obu listach

Użytkownik zastąpił dotychczasowy blok statusu na liście użytkowników i dodał go na liście dyscyplin przez wspólne wywołanie, po slocie nagłówka, przed panelem listy:

```blade
<x-ui.status-message />
```

Asystent potwierdził wywołanie w `resources/views/admin/users/index.blade.php` i `resources/views/admin/disciplines/index.blade.php`. Obie listy korzystają z tego samego komponentu, a właściwy tekst nadal przekazuje ich kontroler.

Zrzut ekranu użytkownika potwierdza wyświetlenie zielonego komunikatu „Dyscyplina została dodana.” na liście dyscyplin oraz widoczność rekordu „Piłka nożna 3”. Zniknięcie komunikatu po odświeżeniu i otwarcie listy użytkowników po migracji komponentu pozostają do potwierdzenia.

### Dokończenie ręcznego sprawdzenia statusu

Użytkownik potwierdził oba pozostałe sprawdzenia: komunikat znika po odświeżeniu listy dyscyplin, a lista użytkowników otwiera się bez błędu po podłączeniu wspólnego komponentu. Nie sprawdzono jeszcze ponownego wyświetlenia komunikatu po edycji użytkownika.

## Krok 16 — oznaczenie formularza dla JavaScript

Użytkownik potwierdził dodanie atrybutu `data-discipline-form` do otwierającego znacznika formularza. Będzie on selektorem kontenera dla modułu podpowiedzi aliasu. Pola będą wyszukiwane wewnątrz formularza, zgodnie z konwencją modułów panelu, bez opierania inicjalizacji na globalnych identyfikatorach. Sam atrybut nie uruchamia jeszcze automatycznego aliasu.

## Krok 17 — moduł podpowiedzi aliasu

Użytkownik utworzył `resources/js/discipline-form.js` z dwiema eksportowanymi funkcjami. Asystent sprawdził kod:

```js
export function generateDisciplineAlias(name) {
    return name
        .toLowerCase()
        .replace(/ł/g, 'l')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .slice(0, 255)
        .replace(/-+$/g, '');
}

export function initializeDisciplineForms(root = document) {
    root.querySelectorAll('[data-discipline-form]').forEach((form) => {
        const nameInput = form.querySelector('input[name="name"]');
        const aliasInput = form.querySelector('input[name="alias"]');

        if (!nameInput || !aliasInput) {
            return;
        }

        let isAliasManuallyEdited = aliasInput.value !== '';

        aliasInput.addEventListener('input', () => {
            isAliasManuallyEdited = true;
        });

        nameInput.addEventListener('input', () => {
            if (isAliasManuallyEdited) {
                return;
            }

            aliasInput.value = generateDisciplineAlias(nameInput.value);
        });
    });
}
```

Generator zmienia litery na małe, osobno zamienia `ł`, rozkłada znaki przez `normalize('NFD')` i usuwa znaki diakrytyczne. Ciągi spoza `a–z` i cyfr zastępuje jednym myślnikiem. Usuwa myślniki brzegowe, ogranicza wynik do 255 znaków i ponownie usuwa ewentualny końcowy myślnik powstały po skróceniu. Nazwa bez liter łacińskich i cyfr może dać pusty alias; użytkownik musi wtedy wpisać poprawny alias, a serwer nadal wymaga niepustej wartości.

Inicjalizator wyszukuje oznaczone formularze i ich pola, pomija formularz bez wymaganych pól, a na stronach bez selektora nie wykonuje operacji. Zdarzenie `input` aliasu ustawia flagę ręcznej edycji. Zmiana nazwy aktualizuje alias tylko przy nieustawionej fladze. Niepusty alias odtworzony po walidacji jest od początku chroniony przed nadpisaniem. Programowe przypisanie `value` nie wywołuje zdarzenia `input`.

Użytkownik potwierdził utworzenie modułu. Import i inicjalizacja w `admin.js`, sprawdzenie w przeglądarce i testy pozostają do wykonania.

## Krok 18 — uruchomienie i ręczne sprawdzenie podpowiedzi

W `resources/js/admin.js` użytkownik dodał import obok pozostałych modułów panelu oraz wywołanie inicjalizatora:

```js
import { initializeDisciplineForms } from './discipline-form.js';

initializeDisciplineForms();
```

Import udostępnia funkcję z osobnego modułu, a wywołanie podpina obsługę zdarzeń do oznaczonych formularzy. Asystent potwierdził obie linie w kodzie. Nie zmieniano punktów wejścia Vite ani zależności.

Użytkownik uruchomił `npm run build`: Vite 8.3.5 zakończył budowanie pomyślnie (17 modułów). Pojawiło się ostrzeżenie o opcjonalnym pakiecie `fontaine` dla optymalizacji fallbacków fontów; nie zatrzymało budowania i nie instalowano pakietu.

Użytkownik potwierdził ręczne sprawdzenie na pustym formularzu: nazwa `Piłka nożna 4` daje `pilka-nozna-4`; po ręcznej zmianie aliasu na `moja-dyscyplina` późniejsza zmiana nazwy nie nadpisuje aliasu. Próba nie wymagała zapisu. Testy automatyczne generatora, zachowanie po błędzie walidacji przy włączonym module i pozostałe kryteria issue nadal pozostają do sprawdzenia.

## Architektura i sposób pracy

Przed zmianami przeczytać `AGENTS.md`, `.ai/rules/index.md` oraz wszystkie pasujące reguły, sprawdzić Git i istniejący kod. Wykorzystać aktualne komponenty i konwencje; stosować odpowiednie skille i dokumentację wersji pakietów.

Zmianę wspólnego wzorca architektury wdrażać we wszystkich objętych nim widokach w tym samym etapie. Zachować funkcje właściwe poszczególnym widokom. Bez nowych zależności, Filament, nowej gałęzi i worktree.

Prowadzić po polsku, po jednej małej zmianie: cel, mechanizm, konkretna zmiana, kryterium sukcesu, następnie czekać na wynik. Użytkownik wykonuje edycje, polecenia i Git, chyba że wyraźnie zleci je asystentowi. Asystent uzupełnia ten rozdział po potwierdzonych krokach, z kodem i objaśnieniem fragment po fragmencie, odróżniając plany od wykonania i weryfikacji.

## Poza zakresem

Edycja i usuwanie dyscyplin, wyszukiwanie, filtry, paginacja, operacje zbiorcze, tabele tłumaczeń i publiczne adresy. [Issue #21](https://github.com/SebMagacy/sportowiada/issues/21) pozostaje osobnym, otwartym zadaniem analizy domeny sportowej.

## Stan wykonania

13 listopada 2026 roku na prośbę użytkownika utworzono ten rozdział. Issue #22 i osobny czat „Issue #22 — Dodawanie dyscypliny” zostały przygotowane. Formularz i jego testy nie zostały jeszcze wdrożone. Rozdział jest lokalny i niecommitowany.
