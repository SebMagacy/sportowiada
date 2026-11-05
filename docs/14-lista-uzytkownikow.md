# Lista użytkowników w panelu administratora

## 1. Punkt wyjścia

### 1.1. Zakończony poprzedni etap

Etap opisany w [[13-widok-uzytkownikow]] zapisano w commicie `b53bf55` — „Dodaj widok użytkowników w panelu administratora”. Użytkownik potwierdził udany push tego commitu na `origin/feature/admin-users`. Gałąź nie została scalona do `main`.

Istnieją:

- `app/Http/Controllers/Admin/UserController.php` z metodą `index(): View`, która zwraca `view('admin.users.index')`;
- `resources/views/admin/users/index.blade.php` z komponentem `<x-admin-layout>` i nagłówkiem „Użytkownicy”;
- trasa GET `/admin/users` o nazwie `admin.users.index`, wywołująca `UserController::index()` i chroniona przez `auth` oraz `can:access-admin`;
- odnośnik menu korzystający z `route('admin.users.index')`.

Użytkownik wykonał formatowanie Pint. PHPStan uruchomiony przez GrumPHP podczas commitu zakończył się poprawnie. Polecenie `route:list` potwierdziło jedną trasę i przypisane middleware. Wcześniejsze ręczne sprawdzenia dostępu opisuje notatka 13; nie są to wyniki testów automatycznych.

### 1.2. Stan Git przed rozpoczęciem notatki

Odczyt 05.11.2026 przed utworzeniem tego pliku pokazał czysty katalog roboczy na `feature/admin-users`, bez różnicy względem lokalnego refu `origin/feature/admin-users`. Utworzenie niniejszej notatki wprowadza nowy, nieśledzony plik. Nie wykonano jej commitu ani pushu.

## 2. Cel etapu i sposób pracy

### 2.1. Początkowy plan

Na początku zaplanowano pobranie użytkowników z bazy w kontrolerze, przekazanie danych do widoku i wyświetlenie listy. Szczegóły zapytania i prezentacji ustalano podczas pracy. Wykonane kroki opisują kolejne rozdziały.

### 2.2. Sposób pracy

Pracujemy po polsku. Użytkownik sam wykonuje polecenia i edycje kodu. Asystent podaje jeden mały krok, wyjaśnia poszczególne fragmenty kodu i kryterium sukcesu, następnie czeka na wynik. Dokumentację aktualizujemy wyłącznie na podstawie wykonanych kroków; plany oznaczamy osobno. Testy automatyczne pozostają odłożone.

Przed implementacją odczytano instrukcje repozytorium, Git i potrzebne pliki. Wynik `composer show laravel/framework` przekazany przez użytkownika potwierdził Laravel 13.38.0. Korzystano z dokumentacji przez Boost oraz właściwych skills. Katalog `.ai/rules` nie istniał podczas odczytu. Nie tworzymy nowego worktree, nie przełączamy gałęzi i nie zmieniamy zależności. Historyczny stan Git w [[00-kontekst-projektu]] nie zastępuje aktualnego odczytu ani powyższych wyników.

## 3. Pobranie danych w kontrolerze

### 3.1. Import modelu

Plik: `app/Http/Controllers/Admin/UserController.php`

Do istniejących importów dodano:

```php
use App\Models\User;
```

Import pozwala używać krótkiej nazwy `User` w kontrolerze. Sam nie wykonuje zapytania do bazy. Wykorzystano istniejący model; nie tworzono modelu ani migracji.

### 3.2. Zapytanie i przekazanie danych

W metodzie `index()` przed `return` dodano pobranie kont, a następnie rozszerzono wywołanie `view()` o tablicę danych. Pierwsza wersja metody, przed dodaniem paginacji (rozdział 6):

```php
public function index(): View
{
    $users = User::query()->orderBy('id')->get();

    return view('admin.users.index', [
        'users' => $users,
    ]);
}
```

- `User::query()` rozpoczyna budowanie zapytania dla modelu użytkownika.
- `orderBy('id')` ustala kolejność rosnącą według identyfikatora.
- `get()` wykonuje zapytanie i zwraca kolekcję modeli. W tej pierwszej wersji pobierano wszystkie konta i wszystkie kolumny. Później zastąpiono `get()` paginacją; wybór kolumn nadal nie jest ograniczony.
- `$users` przechowuje wynik zapytania.
- Klucz `'users'` w drugim argumencie `view()` udostępnia tę kolekcję w Blade jako `$users`.
- Typ zwracany `View` pozostaje bez zmian: metoda zwraca widok, a nie samą kolekcję.

## 4. Pierwsza lista w Blade

### 4.1. Pętla i pusta kolekcja

Plik: `resources/views/admin/users/index.blade.php`

Najpierw pod nagłówkiem dodano prostą listę:

```html
<ul>
    @forelse ($users as $user)
        <li>{{ $user->name }} — {{ $user->email }}</li>
    @empty
        <li>Brak użytkowników.</li>
    @endforelse
</ul>
```

`@forelse` przechodzi przez kolekcję, przypisując kolejne konto do `$user`. Właściwości `name` i `email` zawierają dane konta. Zapis `{{ ... }}` wyświetla tekst z escapowaniem HTML. Blok `@empty` jest przeznaczony dla pustej kolekcji, a `@endforelse` kończy konstrukcję.

Użytkownik pokazał zrzut z dwoma kontami. Potwierdza to wykonanie kontrolera i wyświetlenie przekazanych danych. Brak wypunktowania wynikał z resetu stylów Tailwind. Listę następnie zastąpiono tabelą.

## 5. Tabela użytkowników

### 5.1. Struktura i dane

Najpierw dodano tabelę z nagłówkami ID, Imię, E-mail i Typ konta, pozostawiając chwilowo listę pod spodem. Następnie przeniesiono pętlę do `tbody`, zamieniono elementy listy na wiersze `tr` z komórkami `td` i usunięto blok `ul`.

- `thead` grupuje nagłówki, a `tbody` wiersze danych.
- `th scope="col"` oznacza nagłówek opisujący kolumnę.
- Każdy użytkownik zajmuje jeden wiersz i cztery komórki.
- `$user->is_admin ? 'Administrator' : 'Użytkownik'` wybiera etykietę na podstawie wartości logicznej `is_admin`.
- `colspan="4"` rozciąga komunikat pustej kolekcji na cztery kolumny.

Zrzut użytkownika pokazał dwa wiersze z ID oraz różnymi etykietami typu konta. ID jest identyfikatorem rekordu, nie numerem porządkowym listy; nie musi tworzyć ciągłej numeracji od 1.

### 5.2. Wygląd inspirowany Filament

Użytkownik wskazał zrzut listy rekordów Filament jako wzór wizualny. Wykorzystano jasną kartę, subtelne obramowanie, szare tło nagłówków, odstępy i poziome linie. Nie instalowano Filament ani innych zależności. Wyszukiwanie, filtry, sortowanie przez nagłówki i przyciski działań widoczne na wzorze nie zostały zaimplementowane.

Zmiany wprowadzano kolejno: rama i szerokość tabeli → nagłówki → komórki i linie → tytuł strony → podświetlenie wiersza. Zawartość widoku po stylowaniu tabeli, przed dodaniem stopki z rozdziału 6:

```html
<x-admin-layout>
    <h1 class="mb-6 text-2xl font-bold tracking-tight text-gray-950">
        Użytkownicy
    </h1>

    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left font-semibold text-gray-950">ID</th>
                    <th scope="col" class="px-6 py-3 text-left font-semibold text-gray-950">Imię</th>
                    <th scope="col" class="px-6 py-3 text-left font-semibold text-gray-950">E-mail</th>
                    <th scope="col" class="px-6 py-3 text-left font-semibold text-gray-950">Typ konta</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($users as $user)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 text-gray-500">{{ $user->id }}</td>
                        <td class="px-6 py-3 font-medium text-gray-950">{{ $user->name }}</td>
                        <td class="px-6 py-3 text-gray-700">{{ $user->email }}</td>
                        <td class="px-6 py-3 text-gray-700">{{ $user->is_admin ? 'Administrator' : 'Użytkownik' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                            Brak użytkowników.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</x-admin-layout>
```

Wyjaśnienie zastosowanych klas Tailwind CSS 4:

| Klasy | Znaczenie |
| --- | --- |
| `mb-6 text-2xl font-bold tracking-tight` | Odstęp pod tytułem, większy pogrubiony tekst i ciaśniejsze odstępy między literami |
| `overflow-x-auto` | Możliwość przewijania szerokiej tabeli w poziomie |
| `rounded-xl border border-gray-200 bg-white shadow-sm` | Zaokrąglona biała karta z jasną ramką i subtelnym cieniem |
| `w-full text-left text-sm` | Pełna szerokość tabeli, wyrównanie do lewej i mniejszy tekst |
| `border-b border-gray-200 bg-gray-50` | Linia pod nagłówkiem i bardzo jasne szare tło |
| `px-6 py-3` | Odstępy poziome i pionowe w komórkach |
| `font-semibold`, `font-medium` | Umiarkowane pogrubienie nagłówków i delikatne wyróżnienie imienia |
| `text-gray-950`, `text-gray-700`, `text-gray-500` | Hierarchia od ciemnego tekstu do mniej wyróżnionych wartości |
| `divide-y divide-gray-200` | Poziome linie między wierszami danych |
| `py-8 text-center` | Większy odstęp pionowy i wyśrodkowanie komunikatu pustej tabeli |
| `hover:bg-gray-50` | Delikatne podświetlenie wiersza po najechaniu kursorem |

Zrzuty potwierdziły zastosowanie ramy, nagłówków, odstępów i linii. Po zmianie tytułu użytkownik potwierdził wykonanie kroku, a po dodaniu podświetlenia odpowiedział „ok”. Końcowy odczyt pliku potwierdził obecność obu zmian. Nie przeprowadzono osobnego sprawdzenia przewijania na wąskim ekranie. Nie potwierdzono uruchomienia `npm run dev` ani builda w tym etapie; wskazano je jedynie jako rozwiązanie w razie niewidocznych zmian stylów.

## 6. Paginacja i licznik wyników

### 6.1. Pobieranie po 10 kont

Plik: `app/Http/Controllers/Admin/UserController.php`

Wywołanie `get()` zastąpiono `paginate(10)`. Metoda zapisana w etapie 14 (późniejsze ponowne użycie testowego limitu 1 opisuje punkt 6.6):

```php
public function index(): View
{
    $users = User::query()->orderBy('id')->paginate(10);

    return view('admin.users.index', [
        'users' => $users,
    ]);
}
```

`paginate(10)` pobiera maksymalnie 10 kont dla bieżącej strony i ustala łączną liczbę wyników. Laravel odczytuje numer strony z parametru `page` żądania. Wynik jest obiektem `LengthAwarePaginator`, po którym dotychczasowa pętla `@forelse` nadal może przechodzić. Zachowano rosnącą kolejność według `id`.

### 6.2. Nawigacja i licznik

Plik: `resources/views/admin/users/index.blade.php`

Po `</table>`, wewnątrz karty, dodano najpierw warunkową nawigację, a następnie własny licznik przed nią. Pierwsza wersja stopki, przed późniejszym dostosowaniem z punktów 6.5–6.6:

```html
<div class="border-t border-gray-200 px-6 py-4 text-sm text-gray-600">
    @if ($users->total() > 0)
        Wyświetlono {{ $users->firstItem() }}–{{ $users->lastItem() }}
        z {{ $users->total() }} użytkowników.
    @else
        Brak użytkowników.
    @endif
</div>

@if ($users->hasPages())
    <div class="border-t border-gray-200 px-6 py-4">
        {{ $users->links() }}
    </div>
@endif
```

- `total()` zwraca liczbę wszystkich wyników zapytania.
- `firstItem()` i `lastItem()` wskazują pozycje wyników na bieżącej stronie, a nie identyfikatory kont.
- Warunek `total() > 0` obsługuje brak wyników bez wyświetlania pustego zakresu.
- `hasPages()` decyduje, czy potrzebna jest nawigacja między stronami.
- `links()` renderuje wbudowany widok paginacji Laravel z odnośnikami zawierającymi parametr `page`.
- `border-t border-gray-200` oddziela stopkę linią, a `px-6 py-4` zapewnia odstępy.

Wbudowane `links()` zawiera także własny licznik. Przy wielu stronach ta pierwsza wersja pokazywała więc dwa liczniki: własny polski i wbudowany angielski. Zrzut użytkownika potwierdził ten stan. Zaproponowano warunkowe ukrycie własnego licznika przez `@class` i `hidden`, ale aktualny odczyt pliku nie potwierdza wdrożenia tej poprawki. Nie przedstawiamy jej jako wykonanej. Przy jednej stronie nawigacja wraz z wbudowanym licznikiem jest ukryta.

### 6.3. Ręczne sprawdzenie na dwóch kontach

Aby sprawdzić nawigację bez dodawania rekordów, tymczasowo zmieniono limit na `paginate(1)`. Użytkownik potwierdził działanie przechodzenia między stronami. Zrzut pokazał jeden rekord, własny zakres „1–1 z 2” i dwa numery stron w nawigacji. Zauważono angielskie teksty oraz ciemny wygląd przycisków, różniący się od wzoru Filament.

Następnie zalecono powrót do `paginate(10)`. Aktualny odczyt kontrolera potwierdził przywrócony limit 10. Nie otrzymano osobnego zrzutu ani jednoznacznego potwierdzenia wyglądu strony po tym przywróceniu. Nie dodawano przykładowych kont przez fabrykę ani seeder.

### 6.4. Granica etapu 14 i tłumaczeń

Podczas sprawdzania angielskiej paginacji użytkownik pokazał początkowe ustawienia językowe z `.env`. Po zmianie języka przekazał wynik:

```text
Configuration cache cleared successfully.
app.locale ... pl
```

Potwierdza to wykonanie `php artisan config:clear --no-interaction` i aktywny język `pl` odczytany przez `php artisan config:show app.locale --no-interaction`. Omówiono także możliwość użycia `APP_FAKER_LOCALE=pl_PL`; nie potwierdzono osobno aktywnej konfiguracji Fakera. Ustawienia lokalnego `.env` nie są treścią commitu.

Sama zmiana języka nie dostarcza polskich tłumaczeń. Polecenie `lang:publish` zaproponowano, lecz nie potwierdzono jego wykonania. Użytkownik ostatecznie wybrał dodanie tłumaczeń jako kolejny etap 15, z osobną dokumentacją i commitem. Pierwszą wersję etapu 14 zakończono z działającą paginacją i angielskimi tekstami wbudowanego widoku. Późniejsze tłumaczenia opisuje [[15-polskie-tlumaczenia]], a zmiany szablonu i stopki — poniższe punkty.

### 6.5. Publikacja i dostosowanie widoków paginacji

Poniższe prace wykonano później, podczas etapu tłumaczeń, na gałęzi `codex/polish-translations`. Opis znajduje się tutaj ze względu na temat: budowę i dostosowanie paginacji. Nie są to zmiany zawarte w pierwotnym commicie listy użytkowników `8e6c699`.

Na prośbę użytkownika opublikowano widoki:

```bash
php artisan vendor:publish --tag=laravel-pagination --no-interaction
```

Polecenie potwierdziło skopiowanie szablonów do `resources/views/vendor/pagination`. Wywołanie `{{ $users->links() }}` bez argumentu korzysta z domyślnego widoku `tailwind.blade.php`, a po publikacji — z jego lokalnej kopii. Publikacja dostarczyła również inne warianty szablonów. Nie instalowano Filament ani Bootstrap.

W opublikowanych szablonach zastąpiono pełne teksty, np. `__('Showing')`, kluczami `pagination.*`. Umożliwiło to przeniesienie tłumaczeń do plików `pagination.php` dla obu języków. Same wpisy językowe opisuje [[15-polskie-tlumaczenia#8.2. Wszystkie teksty paginacji w `pagination.php`]]. Początkowo pozostawiono strukturę i style szablonów.

Nie edytowano plików w głównym katalogu `vendor`. Zwykła aktualizacja zależności nie nadpisuje lokalnych kopii widoków, ale nie przenosi też do nich automatycznie poprawek frameworka. Przy aktualizacji należy uwzględniać różnice względem szablonu pakietu. Ponowna publikacja z `--force` może nadpisać lokalne modyfikacje.

Po publikacji sprawdzono składnię plików językowych i formatowanie Pint. Polecenia `php artisan view:cache --no-interaction` oraz `php artisan view:clear --no-interaction` zakończyły się poprawnie. Próbne renderowanie paginacji z dwoma wynikami i limitem 1 w kontekście aplikacji potwierdziło tekst licznika i etykietę odnośnika do strony 2 w językach `pl` i `en`, bez nierozwiązanych kluczy. Kontrole te poprzedzały późniejsze usunięcie licznika; nie stanowią sprawdzenia końcowego układu stopki.

### 6.6. Jeden licznik — wykonane zmiany i dalszy plan

Podczas dalszych sprawdzeń ponownie ustawiono `paginate(1)`. Po zakończeniu sprawdzania przywrócono docelowy limit `paginate(10)`. Odczyt kontrolera oraz wynik `git status` potwierdziły powrót do wersji zapisanej w Git.

Początkowo zastosowano warunkową klasę `sm:hidden`, aby ukrywać własny licznik na większych ekranach przy wielu stronach. Następnie zrezygnowano z tego rozwiązania na rzecz jednego licznika:

- z lokalnego `resources/views/vendor/pagination/tailwind.blade.php` usunięto blok licznika, pozostawiając nawigację;
- własny licznik w widoku użytkowników pozostawiono widoczny bez warunkowej klasy ukrywania;
- jego tekst zastąpiono jednym tłumaczeniem z parametrami:

```blade
{{ __('pagination.overview', [
    'first' => $users->firstItem(),
    'last' => $users->lastItem(),
    'total' => $users->total(),
]) }}
```

Warunek `total() > 0` oraz komunikat `__('users.empty')` dla braku wyników pozostają w widoku. Zrzut użytkownika potwierdził pojedynczy licznik „Showing 1 to 1 of 2 results” i widoczne przyciski stron. Nie potwierdzono ponownie przechodzenia między stronami po tej konkretnej zmianie ani zachowania na wąskim ekranie.

Porównanie z komponentem Filament 5 było inspiracją do pojedynczego licznika i jednego tłumaczenia całego zdania. Mechanizm pobierania danych i generowania odnośników nadal zapewnia Laravel; nie wykorzystano pakietu Filament.

Następnie połączono licznik i nawigację w jeden kontener stopki w `resources/views/admin/users/index.blade.php`:

```blade
<div class="flex flex-col gap-4 border-t border-gray-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
    <div class="text-sm text-gray-600">
        @if ($users->total() > 0)
            {{ __('pagination.overview', [
                'first' => $users->firstItem(),
                'last' => $users->lastItem(),
                'total' => $users->total(),
            ]) }}
        @else
            {{ __('users.empty') }}
        @endif
    </div>

    @if ($users->hasPages())
        <div>
            {{ $users->links() }}
        </div>
    @endif
</div>
```

`flex-col` i `gap-4` ustawiają elementy pionowo z odstępem na wąskim ekranie. Od progu `sm` elementy znajdują się w jednym rzędzie: licznik po lewej, przyciski po prawej. `border-t` występuje na wspólnym kontenerze, więc nie ma dodatkowej linii między licznikiem a nawigacją. Nawigacja nadal zależy od `hasPages()`. Nie wydzielono osobnego komponentu Blade stopki.

Na potrzeby sprawdzenia ponownie użyto tymczasowego `paginate(1)`. Zrzut użytkownika potwierdził wspólny rząd z polskim licznikiem po lewej i przyciskami po prawej. Na tym zrzucie przyciski miały jeszcze ciemne tło.

### 6.7. Jasny wygląd przycisków i końcowe sprawdzenie listy

Lokalny szablon `resources/views/vendor/pagination/tailwind.blade.php` zawierał klasy `dark:*`. Przy domyślnym sposobie obsługi ciemnego motywu w Tailwind reagują one na preferencję schematu kolorów przeglądarki lub systemu. Powodowało to ciemny wygląd przycisków na tle jasnej tabeli.

Na wyraźną prośbę użytkownika usunięto z tego szablonu 55 klas z prefiksem `dark:`, zachowując jasne style, m.in. `bg-white`, `bg-gray-200`, `border-gray-300` i `hover:bg-gray-100`. Zmiana dotyczy lokalnego widoku paginacji, nie globalnej konfiguracji motywu aplikacji. Użytkownik potwierdził wykonanie sprawdzenia po odświeżeniu strony.

Po tej zmianie poprawnie wykonano `php artisan view:cache --no-interaction`, a następnie `php artisan view:clear --no-interaction`. `git diff --check` nie zgłosił problemów. Nie uruchamiano ponownie PHPUnit ani PHPStan dla tej zmiany wyglądu.

Na zakończenie przywrócono `paginate(10)`. Użytkownik potwierdził wyświetlanie obu kont, jednego licznika z zakresem 1–2 i brak przycisków przy jednej stronie wyników. Aktualny odczyt kontrolera również potwierdza limit 10, a odczyt widoku — wspólny kontener stopki. Parametr `?page=1` oznacza pierwszą stronę i nie zmienia limitu rekordów.

**Pozostałe sprawdzenia:** zachowanie układu na wąskim ekranie, wariant pustej listy oraz ponowne przechodzenie między stronami po końcowych zmianach wyglądu. Nie przedstawiamy tych scenariuszy jako zweryfikowanych.


## 7. Weryfikacja i stan przy zakończeniu pierwszej wersji

Poniższe wyniki i stan Git dotyczą pierwotnego etapu. Jego późniejszy lokalny commit `8e6c699` odnotowano w [[15-polskie-tlumaczenia#1.1. Zapisany etap listy użytkowników]]. Dalsze dostosowanie paginacji dokumentują punkty 6.5–6.7.

### 7.1. Potwierdzone wyniki

| Sprawdzenie | Wynik i zakres |
| --- | --- |
| Wynik Composera przekazany w pliku przez użytkownika | Laravel 13.38.0 |
| Pierwsza lista w przeglądarce | Zrzut pokazał dwa konta pobrane przez kontroler i przekazane do widoku |
| Tabela w przeglądarce | Zrzuty pokazały ID, imię, e-mail i typ konta oraz kolejne zmiany wyglądu |
| `vendor/bin/pint --dirty --format agent` | Użytkownik przekazał `{"tool":"pint","result":"passed"}` po dodaniu pierwszej listy, przed jej zamianą na tabelę |
| Przegląd `git diff` | Sprawdzono import, zapytanie, przekazanie danych i pierwszą pętlę listy, przed rozbudową tabeli |
| `git diff --check` wykonane przez użytkownika | Brak komunikatów; kontrola dotyczyła zmian sprzed rozbudowy tabeli |
| Paginacja z tymczasowym limitem 1 | Użytkownik potwierdził przechodzenie między stronami; zrzut pokazał nawigację oraz powielony licznik |
| Odczyt plików przy najnowszej aktualizacji dokumentacji | Potwierdzono `paginate(10)`, tabelę, własny licznik i warunkowe `links()`; brak proponowanego ukrywania własnego licznika |

Nie sprawdzono w przeglądarce wariantu pustej kolekcji. Nie ponawiano w tym etapie scenariuszy gościa i zwykłego użytkownika; wcześniejsze wyniki i ich ograniczenia opisuje [[13-widok-uzytkownikow]]. Wyświetlenie listy w tym etapie potwierdza już wykonanie akcji kontrolera dla konta korzystającego z panelu. Testów automatycznych nie tworzono ani nie uruchamiano, zgodnie z decyzją użytkownika. Nie uruchamiano PHPStan dla aktualnych zmian; wcześniejszy wynik GrumPHP dotyczy commitu etapu 13.

### 7.2. Stan Git przy aktualizacji 05.11.2026

Bieżąca gałąź to `feature/admin-users`, zgodna z lokalnym refem `origin/feature/admin-users` w zakresie zapisanych commitów. Poza commitami pozostają:

- zmieniony `app/Http/Controllers/Admin/UserController.php`;
- zmieniony `resources/views/admin/users/index.blade.php`;
- nieśledzony `docs/14-lista-uzytkownikow.md`.

Użytkownik zdecydował pozostawić notatkę roboczą do dalszego uzupełniania; nie wykonał proponowanego `git add` ani osobnego commitu startowego. Nie wykonano commitu, pushu ani scalenia aktualnego etapu. Aktualizacja dokumentacji nie zmienia tego stanu publikacji.

### 7.3. Pozostałe prace i osobny etap tłumaczeń

Paginacja i licznik zostały wdrożone. Pozostają ręczne sprawdzenia pustej listy i zachowania na wąskim ekranie oraz końcowe kontrole jakości i zapis etapu w Git. Tłumaczenia użytkownik zdecydował wykonać jako osobny etap, opisany w notatce 15 i zapisany w osobnym commicie. Nie utworzono jeszcze tej notatki ani plików polskich tłumaczeń. Wyszukiwanie, filtry, interaktywne sortowanie oraz operacje dodawania, edycji i usuwania kont również pozostają poza obecną implementacją; sam wzór Filament nie stanowi decyzji o ich wykonaniu. Dalszy zakres należy ustalać w kolejnych małych krokach.
