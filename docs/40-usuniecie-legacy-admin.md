# 40. Usunięcie legacy-admin i przejście na role

## Zakres i źródła

Część [issue #26](https://github.com/SebMagacy/sportowiada/issues/26). Użytkownik 17 listopada 2026 r. zlecił asystentowi samodzielne usunięcie całego legacy-admin, zastąpienie „Typu konta” rolami i udokumentowanie zmian. Jest to wyjątek od dotychczasowego prowadzenia użytkownika po jednym kroku.

Przegląd objął dokumentację dotychczasowego panelu, w szczególności rozdział 24 o edycji kont, rozdział 31 o rozdzieleniu adresów Filamentu i legacy-admin, bieżący kontekst, reguły projektu, kod i odwołania do komponentów. Dokumentację porównano z rzeczywistym kodem — historyczne fragmenty nie były poleceniami ponownego wdrożenia.

## Co usunięto

- Wszystkie trasy `/legacy-admin` i nazwy tras `admin.*` z `routes/web.php`.
- Kontrolery `app/Http/Controllers/Admin/UserController.php` i `DisciplineController.php`.
- Widoki `resources/views/admin/`, własny layout i komponenty list, filtrów oraz nagłówków w `resources/views/components/`. W chwili przeglądu korzystał z nich wyłącznie usuwany panel.
- Nadpisania widoków paginacji przygotowane dla tego panelu.
- `resources/css/admin.css`, `resources/js/admin.js` i moduły obsługujące stare filtry, wyszukiwanie, wybór liczby wyników, zaznaczanie wierszy i formularz dyscypliny.
- Inicjalizację paska bocznego `public/js/admin-sidebar-init.js` oraz nieużywane już ikony SVG. Zachowano `resources/svg/disciplines.svg`, używane przez Filament, i plik licencji.
- Pliki tłumaczeń `users.php` oraz `dashboard.php` w PL/EN, których odbiorcą był stary panel. Zachowano tłumaczenia logowania, walidacji i katalogów używane przez aplikację.
- Testy kontrolerów legacy-admin, starego Gate, pulpitu i komponentów oraz testy JavaScriptu usuniętych interakcji. Nie usuwano testów zachowanych funkcji publicznych i katalogów.

Z konfiguracji Vite usunięto punkty wejścia `admin.css` i `admin.js`. Publiczne Vue, jego zasoby oraz CSS Filamentu pozostały.

## Role zamiast typu konta

W `UserResource` kolumnę `is_admin` zastąpiono:

```php
TextColumn::make('roles.name')
    ->label('Role')
    ->badge()
    ->placeholder('Brak ról'),
```

Kolumna pokazuje faktycznie przypisane role, a konto bez przypisania otrzymuje „Brak ról”. Nie wyciągamy już uprawnień z dawnej flagi administratora. Akcja przypisywania ról pozostaje podłączona do tabeli; jej działanie opisuje [rozdział 39](39-przypisywanie-rol-uzytkownikom.md).

Dostęp do panelu nadal określa `User::canAccessPanel()` na podstawie `super_admin` albo `panel_user`. Policies określają dostęp do zasobów; `assignRoles()` jest zarezerwowane dla `super_admin`.

## Wycofanie `is_admin` i `access-admin`

Usunięto definicję Gate `access-admin` i jego nieużywane importy w `AppServiceProvider`, cast `is_admin` w modelu użytkownika oraz wpis tej flagi w `ExampleUsersSeeder`.

Przed usunięciem kolumny odczyt przez Boost potwierdził role istniejących kont: ID 4 miało `super_admin`, a ID 31 miało `panel_user`. Nie przekształcano wszystkich dawnych `is_admin = 1` automatycznie w `super_admin`: uprawnienia wynikają z wcześniej nadanych ról.

Dodano nową migrację `database/migrations/2026_11_17_121413_drop_is_admin_from_users_table.php`. `up()` usuwa kolumnę `users.is_admin`. Pierwotną migrację dodającą flagę pozostawiono jako historię schematu, aby nie zmieniać wykonanych migracji.

Asystent wykonał nową migrację na lokalnej bazie MySQL; wynik `DONE`. Następny odczyt schematu przez Boost potwierdził brak kolumny `is_admin`. Nie usuwano kont, katalogów ani przypisań ról.

**Ograniczenie cofania:** `down()` odtwarza kolumnę z domyślnym `false`, ale nie odtwarza dawnych wartości flagi. Role Spatie pozostają osobnymi danymi.

## Logowanie i rejestracja

Po poprawnym logowaniu przez publiczny formularz `LoginController` pyta `canAccessPanel()` o dostęp do panelu. Konta z odpowiednią rolą trafiają na nazwaną trasę `filament.panel.pages.dashboard`; pozostałe na stronę główną. Nie ma już przekierowania do usuniętego `admin.dashboard`.

Rejestracja i wylogowanie pozostają dostępne. Test rejestracji sprawdza teraz, że przesłanie dodatkowych `roles` i `permissions` nie nadaje kontu uprawnień. Seeder przykładowych użytkowników nie nadaje ról i zachowuje istniejące konta oraz ich role.

## Weryfikacja

Zaktualizowano dotychczasowe testy Filamentu, logowania, wylogowania, rejestracji i seedera, zastępując przygotowanie kont przez `is_admin` rzeczywistymi rolami i uprawnieniami. Wspólny pomocnik `createPanelAdministrator()` w `tests/TestCase.php` tworzy testowe konto z rolą i uprawnieniami; nie omija Policies przez zastępowanie Gate.

Dodano:

- `LegacyAdminRemovalTest`: 404 dla usuniętych adresów GET/POST/PATCH, 403 dla zasobów i formularzy przy `panel_user`, dostęp tej roli do pulpitu bez nawigacji zasobów.
- `AssignUserRolesTest`: przypisanie i odebranie roli, wczytanie obecnego wyboru, powiadomienie, prezentacja kolumny ról oraz odmowa akcji osobie z prawem przeglądania i edycji użytkowników, lecz bez `super_admin`. Próba bezpośredniego zamontowania niedostępnej akcji nie otwiera formularza i nie zmienia ról.

Asystent wykonał końcowe kontrole:

- `php artisan test --compact`: **134 testy, 615 asercji, wszystkie poprawne**.
- `vendor/bin/pint --dirty --format agent` oraz Pint dla nowych plików: poprawne po formatowaniu.
- `vendor/bin/phpstan analyse --no-progress --debug`: **0 błędów**.
- `npm run build`: poprawny build po usunięciu starych punktów wejścia.
- `git diff --check`: bez błędów białych znaków.
- `php artisan route:list --except-vendor --no-interaction`: brak tras legacy-admin.

Testy używają SQLite w pamięci; nie zmieniają lokalnych kont. Mniejsza liczba testów niż historycznie wynika z usunięcia testów wycofanego panelu, a nie z potwierdzenia wszystkich dawnych funkcji w Filamencie. Filament nie otrzymał automatycznie funkcji dawnego CRUD kont, filtrów czy sortowania.

Po porządkowaniu i publikacji commita użytkownik potwierdził dwa nowe sprawdzenia ręczne:

- Konto `super_admin`: logowanie przez publiczny `/login` kieruje do pulpitu Filamentu; lista użytkowników pokazuje kolumnę „Role” i akcję przypisywania ról (odpowiedź „ok. działa”).
- Rysiek z rolą `panel_user`: logowanie przez publiczny `/login` w oknie prywatnym kieruje do pulpitu Filamentu; w menu nie ma ról, użytkowników, dyscyplin ani sezonów (odpowiedź „ok. sukces”).

Wcześniejsze ręczne próby przypisywania ról opisuje rozdział 39. Nie powtarzano po porządkowaniu wszystkich prób bezpośrednich adresów w przeglądarce; odmowy dostępu obejmują testy automatyczne opisane powyżej.

## Dokumentacja i status

Historyczne rozdziały zachowano z oznaczeniem archiwalnych odwołań do legacy-admin. Rozdział 31 otrzymał informację o zmianie, a na początku bieżącego kontekstu zapisano aktualny stan mający pierwszeństwo przed dawnymi wpisami. Usunięto z instrukcji kontynuacji wymóg zachowania starego panelu.

Issue #26 pozostaje otwarte. Status commita i publikacji należy sprawdzać w historii Git; wyniki kontroli powyżej dotyczą lokalnej implementacji. Ta zmiana nie oznacza implementacji uprawnień przyszłych lig i projektów ani ukończenia wszystkich kryteriów całego etapu.

## Commit i publikacja

Utworzono commit `447ea47` — „Wdroż role i uprawnienia Filamentu oraz usuń legacy-admin”, z odniesieniem `Refs #26`. Kontrola PHPStan uruchomiona przez hook przed commitem zakończyła się poprawnie; po commicie katalog roboczy był czysty.

Użytkownik potwierdził publikację wynikiem `git push origin main`: `11f024e..447ea47 main -> main`. Potwierdza to push tego commita, nie zamknięcie issue #26. Niniejszy dopisek dokumentacyjny powstał po publikacji i wymaga osobnego commita oraz pushu.
