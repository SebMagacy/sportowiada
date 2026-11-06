# Słownik pojęć

Pojęcia używane w projekcie Sportowiada, wyjaśnione na przykładach z aplikacji.

## Dokumentacja projektu

### Dokumentacja projektu

**Dokumentacja projektu** to uporządkowany zbiór materiałów opisujących cel projektu, jego budowę, decyzje, sposób uruchomienia oraz wykonane prace i ich sprawdzenia. Pomaga zrozumieć aplikację i kontynuować jej rozwój.

> [!example]- Przykład w Sportowiadzie
> Katalog `docs` zawiera rozdziały opisujące kolejne etapy budowy Sportowiady, kontekst projektu, słownik pojęć i spis treści w `README.md`. Plany oznaczamy osobno od działań wykonanych i potwierdzonych.

### Dokument pomocniczy

**Dokument pomocniczy** to materiał uzupełniający główne rozdziały dokumentacji, np. słownik pojęć lub podsumowanie kontekstu. Porządkuje wiedzę przydatną w wielu etapach pracy i może być aktualizowany niezależnie od ich kolejności. Jest to przyjęte określenie roli dokumentu, a nie wymagany format pliku.

> [!example]- Przykład w Sportowiadzie
> `slownik-pojec.md` wyjaśnia terminy, a `00-kontekst-projektu.md` zbiera kontekst i ustalenia. Ich rola różni się od opisu pojedynczego etapu implementacji. Przed poleganiem na zapisanym stanie projektu sprawdzamy aktualne pliki i Git.

### Rozdział dokumentacji

**Rozdział dokumentacji** to wyodrębniona tematycznie część dokumentacji. W przyjętym układzie Sportowiady numerowane rozdziały opisują kolejne etapy pracy, a każdy z nich jest zapisany w osobnym pliku Markdown. Rozdział może zawierać punkt wyjścia, wykonane kroki, wyjaśnienia, wyniki sprawdzeń i osobno oznaczone plany.

> [!example]- Przykład w Sportowiadzie
> `14-lista-uzytkownikow.md` to rozdział 14, opisujący pobieranie kont, tabelę i paginację. `15-polskie-tlumaczenia.md` rozpoczyna osobny etap dotyczący tłumaczeń; obecność tego rozdziału sama nie oznacza zakończenia zaplanowanych prac.

### Spis treści

**Spis treści** to uporządkowana lista części dokumentacji z odnośnikami, które pomagają znaleźć właściwy temat. Pokazuje strukturę materiałów; nie zastępuje ich treści ani szczegółowego opisu stanu prac.

> [!example]- Przykład w Sportowiadzie
> `docs/README.md` zawiera numerowaną listę rozdziałów oraz odnośnik do słownika pojęć. Po dodaniu nowego rozdziału uzupełniamy spis treści, aby dokument był łatwy do odnalezienia.

## Części aplikacji

### Witryna publiczna

**Witryna publiczna** to część aplikacji przeznaczona dla odwiedzających. Obejmuje stronę główną i pozostałe publicznie dostępne strony. W dokumentacji Sportowiady używamy tego określenia dla odróżnienia jej od panelu administratora.

Witryna może w przyszłości udostępniać także funkcje wymagające zalogowania. Dostęp do takich funkcji ustala się osobno; określenie „witryna publiczna” nie oznacza, że każda funkcja aplikacji jest dostępna bez ograniczeń.

> [!example]- Przykład w Sportowiadzie
> Obecnym punktem wejścia do witryny publicznej jest strona główna pod `/`, dostępna również dla gościa. Jej widok `home.blade.php` zawiera miejsce montowania publicznego komponentu Vue i odnośnik do logowania.
>
> Panel administratora jest odrębną częścią aplikacji. Trasy `/admin` i `/admin/users` wymagają zalogowania oraz uprawnienia `access-admin`.
>
> Wydarzenia, aktualności i inne treści sportowe mogą być przykładami przyszłych stron witryny, ale nie są obecnie zaimplementowanymi modułami ani ustalonym zakresem tego hasła.

### Strona główna

**Strona główna** to główna strona witryny, dostępna pod jej podstawowym adresem, czyli ścieżką `/`. Zwykle stanowi punkt wejścia i pomaga przejść do pozostałych treści. Jest jedną ze stron witryny, a nie nazwą całej jej publicznej części.

> [!example]- Przykład w Sportowiadzie
> W `routes/web.php` trasa GET `/` zwraca widok `home`, zapisany w `resources/views/home.blade.php`.
>
> Widok ładuje zasoby przez Vite, zawiera element `#app` dla publicznego komponentu Vue oraz odnośnik „Zaloguj się”. Trasa strony głównej nie wymaga zalogowania.

Przebieg tworzenia opisują [[06-trasa-i-widok-strony-glownej]] i [[07-javascript-vite-vue-strona-publiczna]].

### Pulpit administratora (dashboard)

**Pulpit administratora** (ang. *dashboard*) to główna strona panelu administratora, stanowiąca punkt wejścia do jego funkcji. Może prezentować podsumowania, statystyki, powiadomienia i skróty do najważniejszych działań. Sama nazwa „pulpit” nie oznacza, że wszystkie te elementy są już zaimplementowane.

> [!example]- Przykład w Sportowiadzie
> Pulpit jest dostępny pod ścieżką `/admin`. Trasa nosi nazwę `admin.dashboard` i wyświetla widok `resources/views/admin/dashboard.blade.php`.
>
> Odnośnik w menu panelu ma etykietę „Pulpit” i korzysta z `route('admin.dashboard')`. Dostęp do strony chronią middleware `auth` i `can:access-admin`.
>
> Pulpit jest jedną ze stron panelu administratora. Lista użytkowników to osobna strona tego samego panelu, a strona główna witryny publicznej pod `/` należy do innej części aplikacji.

## Rekordy i ich widoki

### Lista rekordów

**Lista rekordów** to widok przedstawiający zestaw danych, np. użytkowników, wydarzeń lub klubów. Rekord oznacza pojedynczy element tego zestawu. Lista może mieć postać tabeli, kart lub prostego zestawienia.

Wariant z wierszami i kolumnami nazywamy **tabelą danych** (ang. *data table*). Każdy wiersz przedstawia jeden rekord, a kolumny pokazują jego wybrane pola, np. imię i e-mail użytkownika. Lista może udostępniać paginację, wyszukiwanie lub sortowanie, ale te funkcje wymagają osobnej implementacji. Sama obecność listy nie oznacza możliwości dodawania, edytowania ani usuwania rekordów.

> [!example]- Przykład w Sportowiadzie
> Lista w panelu administratora pokazuje ID, imię, e-mail i typ konta: „Administrator” albo „Użytkownik”.
>
> Dane przygotowuje metoda `index()` w `app/Http/Controllers/Admin/UserController.php`:
>
> ```php
> $users = User::query()->orderBy('id')->paginate(10);
>
> return view('admin.users.index', [
>     'users' => $users,
> ]);
> ```
>
> Kontroler pobiera stronę wyników i przekazuje ją do widoku jako `$users`. Plik `resources/views/admin/users/index.blade.php` przechodzi przez wyniki pętlą `@forelse`, tworząc po jednym wierszu tabeli dla każdego konta. Przy braku wyników wyświetla komunikat „Brak użytkowników.”.
>
> Dostęp do listy chronią middleware `auth` i `can:access-admin`. Pierwsze wymaga zalogowania, a drugie sprawdza uprawnienie do panelu. Samo ukrycie odnośnika w menu nie zastępuje tych zabezpieczeń.
>
> Lista korzysta z paginacji po 10 kont na stronę. Nie zawiera jeszcze wyszukiwania, interaktywnego sortowania ani operacji dodawania, edycji i usuwania kont.

Przebieg implementacji opisują [[13-widok-uzytkownikow]] i [[14-lista-uzytkownikow]].

### Widok listy rekordów

**Widok listy rekordów** to strona lub fragment interfejsu prezentujący wiele rekordów danego rodzaju, np. użytkowników. Może przedstawiać je jako tabelę, karty albo prostą listę. W standardowym nazewnictwie kontrolerów zasobów Laravel odpowiada mu akcja `index`.

> [!example]- Przykład w Sportowiadzie
> Metoda `UserController::index()` pobiera stronę kont i zwraca widok `admin.users.index`. Widok przedstawia tabelę użytkowników z paginacją. Szczegóły zawiera hasło „Lista rekordów” i [[14-lista-uzytkownikow]].

### Widok szczegółów rekordu

**Widok szczegółów rekordu** to strona lub fragment interfejsu pokazujący dane jednego istniejącego rekordu. Służy do ich odczytu; nie musi zawierać formularza zmiany danych. W standardowym nazewnictwie kontrolerów zasobów Laravel odpowiada mu akcja `show`.

> [!example]- Przykład możliwego zastosowania w Sportowiadzie
> Osobna strona konta mogłaby pokazywać jego imię, e-mail i typ. Jest to przykład zastosowania pojęcia, a nie opis gotowej funkcji ani zatwierdzony plan. Widok szczegółów użytkownika nie został dotąd zaimplementowany w omawianym module.

### Widok tworzenia rekordu

**Widok tworzenia rekordu** to strona lub fragment interfejsu zawierający formularz dodawania nowego rekordu. W standardowym nazewnictwie kontrolerów zasobów Laravel akcja `create` wyświetla formularz, a osobna akcja `store` obsługuje przesłane dane i zapis. Samo otwarcie formularza nie tworzy rekordu.

> [!example]- Przykład możliwego zastosowania w Sportowiadzie
> Formularz „Dodaj użytkownika” w panelu mógłby umożliwiać administratorowi utworzenie konta. Takiego widoku w module administracyjnym jeszcze nie zaimplementowano; przykład nie ustala jego przyszłego zakresu. Istniejący publiczny formularz rejestracji jest odrębnym przepływem.

### Widok edycji rekordu

**Widok edycji rekordu** to strona lub fragment interfejsu zawierający formularz zmiany danych istniejącego rekordu. Formularz zwykle jest wypełniony jego aktualnymi wartościami. W standardowym nazewnictwie kontrolerów zasobów Laravel akcja `edit` wyświetla formularz, a osobna akcja `update` obsługuje przesłane zmiany i zapis. Wyświetlenie formularza nie jest jeszcze aktualizacją rekordu.

> [!example]- Przykład możliwego zastosowania w Sportowiadzie
> Formularz edycji konta mógłby wyświetlać obecne imię i e-mail, pozwalając zmienić dozwolone dane. Widoku ani zapisu edycji użytkownika jeszcze nie zaimplementowano. To przykład pojęcia; zakres pól i uprawnień wymaga osobnego ustalenia.

## Prezentacja wyników

### Pole wyszukiwania użytkowników

**Pole wyszukiwania użytkowników** to pole tekstowe, w którym wpisuje się imię, adres e-mail lub ich fragment, aby wyświetlić na liście pasujące konta.

> [!example]- Planowany przykład w Sportowiadzie
> Administrator wpisze `example-user-25` w polu nad tabelą i naciśnie Enter. Na liście wyników zobaczy konto z adresem `example-user-25@example.com`. Plan wyszukiwania opisuje [[18-wyszukiwanie-uzytkownikow]].

### Paginacja

**Paginacja** to podział wyników na strony. Zamiast pobierać i wyświetlać wszystkie rekordy naraz, aplikacja pobiera część przypadającą na wybraną stronę.

> [!example]- Przykład w Sportowiadzie
> Plik: `app/Http/Controllers/Admin/UserController.php`
>
> ```php
> $users = User::query()->orderBy('id')->paginate(10);
> ```
>
> `orderBy('id')` ustala kolejność rosnącą według identyfikatora konta. `paginate(10)` pobiera maksymalnie 10 użytkowników na stronę i ustala łączną liczbę wyników. Laravel odczytuje numer strony z parametru `page` w żądaniu, np. `?page=2` oznacza drugą stronę.
>
> Przy 24 kontach pierwsza strona zawiera pozycje 1–10, druga 11–20, a trzecia 21–24. Są to **pozycje na liście, a nie identyfikatory kont** — wartości `id` mogą mieć przerwy.
>
> Wynik `paginate()` jest obiektem paginacji, po którym można przechodzić pętlą `@forelse`. Udostępnia też metody:
>
> - `total()` — liczba wszystkich wyników zapytania;
> - `firstItem()` i `lastItem()` — pozycje pierwszego i ostatniego wyniku na bieżącej stronie;
> - `hasPages()` — informacja, czy wyniki zajmują więcej niż jedną stronę;
> - `links()` — wygenerowanie nawigacji między stronami w widoku Blade.
>
> Przy dwóch kontach i limicie 10 wystarcza jedna strona, więc nawigacja nie jest potrzebna. Działanie kilku stron sprawdziliśmy tymczasowo przez `paginate(1)`, a następnie przywróciliśmy limit 10.

Przebieg implementacji opisuje [[14-lista-uzytkownikow]].
