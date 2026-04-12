# Tests TODO

## Status: 76/76 testów przechodzi ✅

Pokryte: PIN auth, middleware, task CRUD, archiwizacja, observer, modele, employee dashboard, super admin vs admin izolacja, zarządzanie pracownikami, zmiana PIN, historia tasków.

---

## Pliki testów

| Plik | Zakres |
|------|--------|
| `tests/Unit/TaskObserverTest.php` | Observer: usuwanie załączników, prune limit 5 |
| `tests/Unit/TaskTest.php` | Model Task: scope active/archived, relacje |
| `tests/Unit/UserTest.php` | Model User: role, relacje |
| `tests/Feature/PinAuth/PinLoginTest.php` | Logowanie PIN (admin/employee), rate limiting |
| `tests/Feature/Middleware/IsAdminTest.php` | Middleware isAdmin |
| `tests/Feature/Middleware/IsEmployeeTest.php` | Middleware isEmployee |
| `tests/Feature/Admin/TaskTest.php` | CRUD tasków admina |
| `tests/Feature/Admin/TaskHistoryTest.php` | Historia tasków admina (izolacja) |
| `tests/Feature/Admin/AdminAccessTest.php` | Super admin vs zwykły admin, uprawnienia |
| `tests/Feature/Admin/EmployeeManagementTest.php` | Tworzenie/edycja/usuwanie pracowników |
| `tests/Feature/Admin/ChangePinTest.php` | Zmiana PIN admina i pracownika |
| `tests/Feature/Employee/DashboardTest.php` | Dashboard pracownika, markDone, redirect bez employee_id |
| `tests/Feature/Commands/ArchiveOldTasksTest.php` | Komenda archiwizacji |

---

## Uwagi

- Testy uruchamiać przez: `docker compose exec app php artisan test`
- Pliki testów: `tests/Feature/` i `tests/Unit/`
- Stary scaffolding (email/password) usunięty — nie przywracać
- Pracownicy wybierają tożsamość po zalogowaniu (ekran select) — sesja `employee_id`
