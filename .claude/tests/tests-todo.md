# Tests TODO

## Status: 50/50 testów przechodzi ✅

Pokryte: PIN auth, middleware, task CRUD, archiwizacja, observer, modele, employee dashboard.

---

## Do zrobienia

### Priorytet wysoki

**Super admin vs zwykły admin — izolacja uprawnień**
- Super admin może wejść w `/admin/admins`, zwykły admin dostaje 403
- Zwykły admin widzi TYLKO swoich pracowników (nie wszystkich)
- Super admin widzi wszystkich pracowników

**Zarządzanie pracownikami (`admin.employees`)**
- Tworzenie pracownika (name required, widoczność dla admina)
- Edycja pracownika — zmiana przypisanych adminów
- Usuwanie pracownika — cascade na task_user
- Super admin może przypisać dowolnego admina, zwykły — tylko siebie

**Zmiana PIN-u (`admin.change-pin`)**
- Zła wartość "Current PIN" blokuje zmianę
- Nowy PIN musi mieć 6 cyfr, potwierdzenie musi się zgadzać
- Zmiana PIN-u pracownika (4 cyfry) — Setting::set('employee_pin')

### Priorytet niski

**Historia tasków**
- Admin widzi tylko swoje zarchiwizowane taski (filtr `created_by`)
- Pracownik widzi historię zakończonych przez siebie

**Livewire komponenty (integracyjne)**
- `admin.task-form` — render, walidacja, redirect po save
- `admin.tasks` — paginacja (10 na stronę)
- `admin.admins` — tylko super admin może tworzyć/usuwać adminów

---

## Uwagi

- Testy uruchamiać przez: `docker exec workflow_app php artisan test`
- Pliki testów: `tests/Feature/` i `tests/Unit/`
- Stary scaffolding (email/password) usunięty — nie przywracać
- Pracownicy widzą wszystkie taski celowo (jeden wspólny PIN) — nie testować izolacji
