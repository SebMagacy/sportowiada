# JavaScript, Vite i Vue na stronie publicznej

Opis podłączenia zasobów i Vue do strony publicznej. Fragmenty pokazują stan na tym etapie. Późniejsze warunkowe montowanie Vue opisano w [[08-uklad-panelu-administratora]].

## 1. Stan początkowy

### 1.1. Widok przed podłączeniem zasobów

Po przygotowaniu trasy i widoku z rozdziału 06 adres `/` zwracał
`resources/views/home.blade.php`. Widok zawierał podstawowy dokument
HTML, ale nie ładował jeszcze zasobów Vite. W projekcie były już
`resources/css/app.css`, `resources/js/app.js` i konfiguracja Vite.
Plik CSS importował Tailwind CSS, a konfiguracja Vite wskazywała oba
pliki jako zasoby wejściowe.

## 2. Podłączenie zasobów do widoku

### 2.1. Dyrektywa Vite

Plik: `resources/views/home.blade.php`

W sekcji `<head>` dodaliśmy:

```html
@vite(['resources/css/app.css', 'resources/js/app.js'])
```

Dyrektywa ładuje arkusz stylów i plik JavaScript na stronie głównej.
Sama konfiguracja plików wejściowych w `vite.config.js` nie powodowała
jeszcze ich załadowania przez ten widok.

`@vite` wybiera zasoby z działającego serwera deweloperskiego albo odczytuje manifest zbudowanych plików. Sam `php artisan serve` ani uruchomienie Apache nie budują JavaScript i CSS. Zależności npm muszą być zainstalowane, a zasoby dostępne przez jeden z tych dwóch sposobów. [Integracja Vite z Laravel](https://laravel.com/docs/13.x/vite#loading-your-scripts-and-styles).

## 3. Dodanie Vue

### 3.1. Pakiety i wtyczka Vite

Pliki: `package.json`, `vite.config.js`

W `package.json` dodaliśmy zależność `vue` oraz wtyczkę
`@vitejs/plugin-vue`. Konfiguracja Vite włącza tę wtyczkę przez
`vue()`. Wtyczka pozwala Vite przetwarzać komponenty w plikach `.vue`. Ten zapis opisuje wykonaną integrację, nie ponowną instalację zależności.

W konfiguracji zapisanej w commicie `96eaf73` dodano import:

```javascript
import vue from '@vitejs/plugin-vue';
```

Następnie wewnątrz istniejącej tablicy `plugins`, obok pozostałych wtyczek, dodano:

```javascript
vue(),
```

Import udostępnia funkcję wtyczki, a jej wywołanie rejestruje obsługę Vue w Vite. Zachowujemy istniejące wejścia Laravel i konfigurację stylów.

### 3.2. Element docelowy Vue

Plik: `resources/views/home.blade.php`

W widoku dodaliśmy element, w którym Vue wyświetla stronę:

```html
<div id="app"></div>
```

### 3.3. Uruchomienie komponentu

Plik: `resources/js/app.js`

Plik importuje `createApp` z Vue oraz komponent
`App.vue`, a następnie montuje go w elemencie `#app`:

```js
import { createApp } from 'vue';
import App from './App.vue';

createApp(App).mount('#app');
```

`createApp(App)` tworzy aplikację Vue z komponentem głównym `App`, a `mount('#app')` umieszcza ją w elemencie o identyfikatorze `app`. Importy muszą być dostępne przed wywołaniem tego kodu.

Plik: `resources/js/App.vue`

Komponent początkowo wyświetlał nagłówek
„Sportowiada”. Dodanie tych plików i zależności jest zapisane w commicie
`96eaf73` (`Dodaj Vue do publicznej strony`).

## 4. Sprawdzenie stylów podczas pracy

### 4.1. Próba z klasami i serwerem Vite

Pliki: `resources/views/home.blade.php`, `resources/js/App.vue`

Na próbę dodaliśmy do `<body>` w `home.blade.php` klasę Tailwind
`bg-sky-100`. Początkowo kolor tła się nie zmienił: serwer Vite nie był
uruchomiony, a wcześniej zbudowany arkusz nie zawierał tej nowej klasy.
Po uruchomieniu `npm run dev` użytkownik potwierdził zmianę tła.
Sprawdziliśmy też stylowanie nagłówka w `App.vue` klasami Tailwind.

`npm run dev` uruchamia skrypt deweloperski Vite zdefiniowany w `package.json`. Proces udostępnia bieżące zasoby podczas pracy.

Przy stronie uruchomionej przez HTTPS przeglądarka musi też móc połączyć się z serwerem Vite. Certyfikat ustawiony dla Apache nie jest automatycznie konfiguracją TLS Vite. Samo `npm run dev` nie gwarantuje poprawnego połączenia w dowolnej konfiguracji HTTPS; w razie problemów należy sprawdzić adres serwera zasobów i błędy przeglądarki. W tym rozdziale zapisano historycznie potwierdzoną próbę, nie wykonano dodatkowej konfiguracji TLS Vite. [Bezpieczny serwer deweloperski Vite](https://laravel.com/docs/13.x/vite#working-with-a-secure-development-server).

### 4.2. Wynik i zakres próby

Te klasy służyły do sprawdzenia połączenia widoku, Vite i stylów.
W plikach `home.blade.php` oraz `App.vue` sprawdzonych podczas dokumentowania tego etapu nie było już klas
użytych w tych próbach. Arkusz `app.css` nadal importuje Tailwind CSS.
Własne reguły CSS można dodawać w tym samym pliku; próba z kolorem
tekstu elementu `body` nie zmieniała nagłówka, któremu klasą Tailwind
nadano własny kolor.

## 5. Sprawdzenie budowania

### 5.1. Zbudowanie zasobów

W katalogu projektu używamy polecenia:

```bash
npm run build
```

Skrypt tworzy zbudowane zasoby do użycia bez działającego serwera deweloperskiego Vite.

Uruchomiony serwer Vite zapisuje znacznik `public/hot`, przez który `@vite` wybiera tryb deweloperski. Sam build nie przełącza strony na manifest, jeśli aplikacja nadal widzi ten znacznik. Zatrzymanie Vite powinno go usunąć; przy awaryjnym zakończeniu procesu może pozostać nieaktualny plik. To rozróżnienie jest istotne przy diagnozowaniu niedostępnych zasobów.

Użytkownik uruchomił `npm run build` i przesłał wynik z komunikatem
`✓ built in 325ms`. Vite utworzył m.in. arkusz CSS i plik JavaScript
w `public/build`. Pojawiło się ostrzeżenie dotyczące opcjonalnego
pakietu `fontaine`, ale nie zatrzymało budowania.

### 5.2. Stan po etapie

Na tym etapie projekt był przygotowany do wyświetlania komponentu Vue
na stronie publicznej. Przełącznik motywu jasnego i ciemnego oraz
podział na widok użytkownika i administratora były omawiane jako
następne kroki; nie stanowią części prac opisanych w tym rozdziale.
