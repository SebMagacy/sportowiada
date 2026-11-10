---
paths:
  - 'resources/js/**'
---

# Js

## Współdzielone interakcje list i oddzielne zasoby panelu
Przy nowych modułach i przebudowie list oddzielaj interakcje administracyjne od publicznego kodu Vue; nie ładuj publicznego interfejsu Vue wyłącznie dla obsługi panelu. Wspólne interakcje list umieszczaj w osobnych modułach i inicjalizuj względem kontenera komponentu, obsługując wiele instancji bez stałych globalnych identyfikatorów. Zachowuj unikalne powiązania aria-controls, synchronizację aria-expanded i obsługę stron bez komponentu. Zmiany punktów wejścia uzgadniaj z konfiguracją Vite i layoutami; nie zmieniaj zależności bez zgody użytkownika.
