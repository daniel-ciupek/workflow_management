# Workflow Management

Aplikacja do zarządzania zadaniami dla zespołów. Administratorzy tworzą i przypisują zadania pracownikom. Pracownicy logują się wspólnym PINem, wybierają swoją tożsamość i zarządzają przydzielonymi zadaniami.

---

## Spis treści

- [Stos technologiczny](#stos-technologiczny)
- [Wymagania](#wymagania)
- [Instalacja i uruchomienie](#instalacja-i-uruchomienie)
- [Domyślne dane logowania](#domyślne-dane-logowania)
- [Struktura ról](#struktura-ról)
- [Konfiguracja produkcyjna](#konfiguracja-produkcyjna)
- [Testy](#testy)
- [Docker Hub](#docker-hub)

---

## Stos technologiczny

| Warstwa | Technologia |
|---|---|
| Backend | Laravel 13.x (PHP 8.4) |
| Komponenty UI | Livewire 3 + Volt |
| Interaktywność | Alpine.js |
| Stylizacja | Tailwind CSS 3 + DaisyUI 4 |
| Baza danych | PostgreSQL 16.2 |
| Build tool | Vite 8 |
| Web server | Nginx 1.27 |
| Środowisko | Docker (PHP 8.4-fpm-alpine, Node 22-alpine) |

---

## Wymagania

- [Docker](https://docs.docker.com/get-docker/) i Docker Compose
- Git

Nie potrzebujesz lokalnie PHP, Node ani Composer — wszystko działa w kontenerach.

---

## Instalacja i uruchomienie

### 1. Sklonuj repozytorium

```bash
git clone https://github.com/daniel-ciupek/workflow_management.git
cd workflow_management
```

### 2. Skopiuj plik środowiskowy

```bash
cp .env.example .env
```

Domyślna konfiguracja działa od razu lokalnie — nie musisz nic zmieniać.

### 3. Uruchom kontenery

```bash
docker compose up -d
```

Przy pierwszym uruchomieniu Docker pobierze obrazy. Może to zająć kilka minut.

### 4. Wygeneruj klucz aplikacji

```bash
docker compose exec app php artisan key:generate
```

### 5. Uruchom migracje i seeder

```bash
docker compose exec app php artisan migrate --seed
```

Seeder tworzy konto Super Admina oraz ustawia domyślny PIN pracowników.

### 6. Zbuduj assety frontendowe

```bash
docker compose exec node npm run build
```

> Kontener `node` automatycznie uruchamia też Vite dev server (`npm run dev`) przy starcie — assety są dostępne na bieżąco bez ręcznego budowania podczas developmentu.

### 7. Otwórz aplikację

```
http://localhost:8000
```

---

## Domyślne dane logowania

### Administrator (Super Admin)

| Pole | Wartość |
|---|---|
| PIN | `000000` |
| Długość | 6 cyfr |

Po zalogowaniu zostaniesz przekierowany do panelu admina.

> Zmień domyślny PIN po pierwszym logowaniu: **Settings → Admin PIN**.

### Pracownicy

| Pole | Wartość |
|---|---|
| PIN | `1234` |
| Długość | 4 cyfry (wspólny dla wszystkich pracowników) |

Po zalogowaniu PINem pracownika pojawia się ekran wyboru tożsamości — wybierz swoje imię z listy.

> PIN pracowników może zmienić wyłącznie Super Admin: **Settings → Employee PIN**.

---

## Struktura ról

### Super Admin
- Zarządza wszystkimi administratorami (tworzenie, usuwanie)
- Przypisuje pracowników do dowolnego admina
- Zmienia globalny PIN pracowników
- Ma dostęp do wszystkich danych w systemie

### Admin
- Tworzy i zarządza zadaniami dla swoich pracowników
- Widzi tylko pracowników przypisanych do siebie
- Może zmieniać swój własny PIN (6 cyfr)

### Pracownik
- Loguje się wspólnym 4-cyfrowym PINem
- Wybiera swoją tożsamość z listy po zalogowaniu
- Widzi przypisane aktywne zadania i oznacza je jako wykonane
- Ma dostęp do historii zakończonych zadań (max 5 ostatnich)

---

## Automatyczna archiwizacja zadań

Zadania są automatycznie archiwizowane po 24 godzinach od utworzenia przez dedykowany serwis schedulera (`php artisan schedule:work`) działający jako osobny kontener Docker. Na każdego pracownika przechowywanych jest maksymalnie 5 ostatnich zarchiwizowanych zadań — starsze są usuwane automatycznie wraz z załącznikami.

---

## Konfiguracja produkcyjna

Przed wdrożeniem na serwer ustaw w pliku `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://twoja-domena.pl

LOG_LEVEL=warning

SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true   # wymaga HTTPS
```

Po deploymencie uruchom:

```bash
docker compose exec app php artisan migrate --force
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
docker compose exec node npm run build
```

---

## Testy

Uruchom pełny zestaw testów (76 testów, 151 asercji):

```bash
docker compose exec app php artisan test
```

### Zakres testów

| Obszar | Liczba testów |
|---|---|
| Autentykacja PIN (admin + pracownik, rate limiting) | 11 |
| Middleware dostępu (IsAdmin, IsEmployee) | 6 |
| Zarządzanie pracownikami (CRUD, cascade delete) | 8 |
| Zarządzanie zadaniami (CRUD, archiwizacja) | 8 |
| Historia zadań admina | 3 |
| Dashboard pracownika | 3 |
| Zmiana PINów (walidacja, weryfikacja) | 8 |
| Obserwator zadań (czyszczenie, pruning) | 5 |
| Komenda archiwizacji (logika 24h) | 4 |
| Uprawnienia admina (super vs regularny) | 7 |
| Inne | 3 |

---

## Docker Hub

Obraz aplikacji dostępny publicznie:

```
danielciupek/workflow-management:1.1.0
```

```bash
docker pull danielciupek/workflow-management:1.1.0
```

---

## Porty

| Port | Usługa |
|---|---|
| `8000` / `80` | Aplikacja (Nginx) |
| `5173` | Vite HMR (dev server) |
| `5432` | PostgreSQL |
