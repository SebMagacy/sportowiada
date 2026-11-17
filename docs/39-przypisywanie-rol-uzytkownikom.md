# 39. Przypisywanie ról użytkownikom — mechanizm formularza

## Cel i stan etapu

Część [issue #26 — uprawnienia i Filament Shield](https://github.com/SebMagacy/sportowiada/issues/26).

Wyjaśnienie dotyczy kodu przygotowanego w `app/Filament/Actions/AssignUserRolesAction.php` oraz metody `assignRoles()` w `app/Policies/UserPolicy.php`. Asystent potwierdził ich zawartość przez odczyt plików 17 listopada 2026 r.

Akcja została dodana do tabeli w `UserResource`. Użytkownik potwierdził ręcznie otwarcie formularza przy Ryśku z zaznaczoną obecną rolą `panel_user`, a następnie odebranie tej roli przez zapis pustego wyboru. Testy automatyczne akcji dodano podczas porządkowania legacy-admin; aktualne wyniki opisuje [rozdział 40](40-usuniecie-legacy-admin.md).

## Cały mechanizm prostymi słowami

Wyobraź sobie, że na liście użytkowników klikasz „Role” przy Ryśku.

1. **Sprawdzamy, czy możesz zmieniać role.** `authorize('assignRoles')` korzysta z Policy użytkownika. Metoda `assignRoles()` pozwala na tę czynność tylko osobie z rolą `super_admin`.
2. **Odczytujemy obecne role Ryśka.** `fillForm()` pobiera je z bazy i zaznacza w formularzu. Widzisz stan, od którego zaczynasz edycję.
3. **Pokazujemy dostępne role.** `options()` pobiera role z guardem `web`, uporządkowane według nazwy. Dostępne możliwości i aktualnie zaznaczone wartości to dwie osobne rzeczy.
4. **Zmieniasz wybór.** Zaznaczasz lub odznaczasz role. Zmienia się tylko formularz; baza pozostaje bez zmian.
5. **Klikasz „Zapisz”.** Wybrane wartości trafiają do `$data['roles']`. `Gate::authorize('assignRoles', $record)` ponownie sprawdza uprawnienie przed zapisem. Następnie `syncRoles()` zastępuje dotychczasowy zestaw ról wybranym zestawem.
6. **Pojawia się potwierdzenie.** Po wykonaniu zapisu kod wysyła powiadomienie „Role użytkownika zostały zapisane”.

Zamknięcie formularza bez zapisywania nie zmienia ról użytkownika.

## Jak działa `fillForm()`

```php
->fillForm(fn (User $record): array => [
    'roles' => $record->roles()->pluck('name')->all(),
])
```

`$record` to użytkownik z wiersza tabeli, przy którym otwarto akcję, np. Rysiek. Nie jest to administrator wykonujący zmianę.

- `roles()` tworzy zapytanie o role przypisane temu użytkownikowi.
- `pluck('name')` pobiera same nazwy ról.
- `all()` zamienia kolekcję wyników na zwykłą tablicę PHP.

Dla Ryśka z rolą `panel_user` otrzymujemy:

```php
[
    'roles' => ['panel_user'],
]
```

Klucz `roles` odpowiada nazwie pola `Select::make('roles')`. Dlatego po otwarciu okna ta rola jest już zaznaczona. Użytkownik bez ról otrzyma pusty wybór.

`fillForm()` niczego nie zapisuje w bazie. Ustawia początkowe wartości formularza.

Bez `fillForm()` w tej własnej akcji pole nie wczytywałoby obecnych przypisań i otwierałoby się puste. Nie jest to zasada dotycząca wszystkich formularzy Filamentu: standardowy formularz edycji zasobu wczytuje dane rekordu automatycznie.

## Dostępne role a zaznaczone role

`options()` odpowiada na pytanie: „Jakie role można wybrać?”. `fillForm()` odpowiada na pytanie: „Które role ma już ten użytkownik?”.

Przykład: lista może zawierać `super_admin` i `panel_user`, ale u Ryśka zaznaczone będzie tylko `panel_user`.

## Co dokładnie zapisuje `syncRoles()`

```php
$record->syncRoles($data['roles'] ?? []);
```

Po zapisie użytkownik ma dokładnie role zaznaczone w formularzu:

| Wybór w formularzu | Wynik zapisu |
| --- | --- |
| `panel_user` | Użytkownik ma tylko `panel_user`. |
| `panel_user` i `super_admin` | Użytkownik ma obie role. |
| Pusty wybór | Wszystkie przypisane role zostają odebrane. |

`?? []` oznacza użycie pustej tablicy, jeśli wartości `roles` brakuje lub jest ona `null`. `syncRoles()` nie dopisuje wyłącznie nowych ról: usuwa również przypisania, których nie ma w przekazanym zestawie. Dlatego poprawne wczytanie aktualnych ról przez `fillForm()` jest istotne — pusty formularz zapisany bez zmian odebrałby dotychczasowe role.

## Uprawnienie do przypisywania ról

W `UserPolicy` przygotowano:

```php
public function assignRoles(User $authUser, User $user): bool
{
    return $authUser->hasRole('super_admin');
}
```

Oba parametry używają modelu `App\Models\User`, który korzysta z traita `HasRoles`. `$authUser` to osoba wykonująca zmianę, a `$user` to osoba, której role zmieniamy. Obecna reguła zależy od roli wykonującego zmianę, więc drugi parametr nie jest używany w jej treści.

Samo uprawnienie do zwykłej edycji użytkownika nie daje prawa do przypisywania ról. Jest to osobna czynność kontrolowana przez `assignRoles`.

## Podłączenie do tabeli i potwierdzone sprawdzenie

W `app/Filament/Resources/Users/UserResource.php` dodano import `App\Filament\Actions\AssignUserRolesAction` oraz wywołanie po `defaultSort('id')`:

```php
->recordActions([
    AssignUserRolesAction::make(),
]);
```

Asystent potwierdził import i rejestrację akcji przez odczyt kodu. Użytkownik odpowiedzią „ok. sukces” potwierdził ręcznie, że na koncie `super_admin` przycisk „Role” przy Ryśku otwiera formularz z zaznaczoną rolą `panel_user`. Ten krok dotyczył otwarcia i wypełnienia formularza, bez zapisywania zmian.

## Ręczne sprawdzenie odebrania roli

Użytkownik na koncie `super_admin` usunął zaznaczenie `panel_user` przy Ryśku i zapisał formularz. Odpowiedzią „ok. Sukces” potwierdził komunikat powodzenia oraz pusty wybór ról po ponownym otwarciu formularza. Potwierdza to odebranie roli i ponowne wczytanie zapisanego stanu. Nie potwierdza jeszcze zachowania sesji Ryśka po odebraniu dostępu.

## Ręczne sprawdzenie nadania roli

Użytkownik na koncie `super_admin` ponownie zaznaczył `panel_user` przy Ryśku i zapisał formularz. Odpowiedzią „ok. sukces” potwierdził, że po ponownym otwarciu formularza rola nadal jest zaznaczona. Ręcznie sprawdzono zatem zarówno odebranie, jak i nadanie roli przez akcję.

## Następny krok

Testy zapisu i odmowy wykonania akcji dodano w ramach prac opisanych w [rozdziale 40](40-usuniecie-legacy-admin.md). Całe issue #26 nadal wymaga końcowej oceny kryteriów i publikacji.
