# 07. JavaScript, Vite i Vue na stronie publicznej

## Stan początkowy

Po przygotowaniu trasy i widoku z rozdziału 06 adres `/` zwracał
`resources/views/home.blade.php`. Widok zawierał podstawowy dokument
HTML, ale nie ładował jeszcze zasobów Vite. W projekcie były już
`resources/css/app.css`, `resources/js/app.js` i konfiguracja Vite.
Plik CSS importował Tailwind CSS, a konfiguracja Vite wskazywała oba
pliki jako zasoby wejściowe.

## Podłączenie zasobów do widoku

W sekcji `<head>` pliku `home.blade.php` dodaliśmy:

```blade
@vite(['resources/css/app.css', 'resources/js/app.js'])
```

Dyrektywa ładuje arkusz stylów i plik JavaScript na stronie głównej.
Sama konfiguracja plików wejściowych w `vite.config.js` nie powodowała
jeszcze ich załadowania przez ten widok.

## Dodanie Vue

W `package.json` dodaliśmy zależność `vue` oraz wtyczkę
`@vitejs/plugin-vue`. Konfiguracja Vite włącza tę wtyczkę przez
`vue()`. W widoku dodaliśmy element, w którym Vue wyświetla stronę:

```html
<div id="app"></div>
```

Plik `resources/js/app.js` importuje `createApp` z Vue oraz komponent
`App.vue`, a następnie montuje go w elemencie `#app`:

```js
createApp(App).mount('#app');
```

Komponent `resources/js/App.vue` początkowo wyświetlał nagłówek
„Sportowiada”. Dodanie tych plików i zależności jest zapisane w commicie
`96eaf73` (`Dodaj Vue do publicznej strony`).

## Sprawdzenie stylów podczas pracy

Na próbę dodaliśmy do `<body>` w `home.blade.php` klasę Tailwind
`bg-sky-100`. Początkowo kolor tła się nie zmienił: serwer Vite nie był
uruchomiony, a wcześniej zbudowany arkusz nie zawierał tej nowej klasy.
Po uruchomieniu `npm run dev` użytkownik potwierdził zmianę tła.
Sprawdziliśmy też stylowanie nagłówka w `App.vue` klasami Tailwind.

Te klasy służyły do sprawdzenia połączenia widoku, Vite i stylów.
W bieżących plikach `home.blade.php` oraz `App.vue` nie ma już klas
użytych w tych próbach. Arkusz `app.css` nadal importuje Tailwind CSS.
Własne reguły CSS można dodawać w tym samym pliku; próba z kolorem
tekstu elementu `body` nie zmieniała nagłówka, któremu klasą Tailwind
nadano własny kolor.

## Sprawdzenie budowania

Użytkownik uruchomił `npm run build` i przesłał wynik z komunikatem
`✓ built in 325ms`. Vite utworzył m.in. arkusz CSS i plik JavaScript
w `public/build`. Pojawiło się ostrzeżenie dotyczące opcjonalnego
pakietu `fontaine`, ale nie zatrzymało budowania.

Na tym etapie projekt był przygotowany do wyświetlania komponentu Vue
na stronie publicznej. Przełącznik motywu jasnego i ciemnego oraz
podział na widok użytkownika i administratora były omawiane jako
następne kroki; nie stanowią części prac opisanych w tym rozdziale.
