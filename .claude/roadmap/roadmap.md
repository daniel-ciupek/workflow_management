# Roadmapa Budowy Workflow management v2026

> **UWAGA:** Po każdym zakończonym etapie zatrzymaj się i skonsultuj postępy!

## 🟢 Etap 1: Inicjalizacja Środowiska i Docker Hub Workflow
- [ ] **[1.1]** Analiza wytycznych `CLAUDE.md` i przygotowanie struktury folderów.
- [ ] **[1.2]** Konfiguracja plików `.gitignore` i `.dockerignore` (izolacja danych).
- [ ] **[1.3]** Stworzenie `Dockerfile` oraz `docker-compose.yml` (Postgres 16.2 + Port 5432 dla zewnętrznych narzędzi).
- [ ] **[1.4]** Instalacja Laravela 13 i Breeze (Livewire) wewnątrz kontenera.
- [ ] **[1.5]** Komendy do budowy, tagowania i Push obrazu na Docker Hub.
- [ ] **[1.6]** Aktualizacja `docker-compose.yml` do trybu Pull z Docker Hub.
- [ ] *STOP: Podsumowanie (1.1-1.6) i prośba o akceptację.*

## 🟡 Etap 2: Fundamenty Bazy Danych i Modele
- [ ] **[2.1]** Modyfikacja `users`: pole `pin` (unikalny), pole `role`, usunięcie zbędnych pól domyślnych.
- [ ] **[2.2]** Migracja `tasks` i tabeli pivot `task_user` (status, completed_at).
- [ ] **[2.3]** Konfiguracja modeli i relacji (BelongsToMany).
- [ ] **[2.4]** Konfiguracja Spatie MediaLibrary w modelu `Task`.
- [ ] *STOP: Podsumowanie (2.1-2.4) i prośba o akceptację.*

## 🟡 Etap 3: Autoryzacja PIN i Główny Layout
- [ ] **[3.1]** Całkowite wyłączenie publicznej rejestracji.
- [ ] **[3.2]** Customowy Login: walidacja PIN (4/6 cyfr) z rygorystycznym Rate Limitingiem (UI w j. angielskim).
- [ ] **[3.3]** Stworzenie Layoutu (Navbar, DaisyUI Modern/Soft Theme).
- [ ] **[3.4]** Middleware ról (admin/employee) i logika przekierowań.
- [ ] *STOP: Podsumowanie (3.1-3.4) i prośba o akceptację.*

## 🟡 Etap 4: Panel Admina (Zarządzanie)
- [ ] **[4.1]** CRUD pracowników (Admin tworzy konto, nadaje Name i PIN).
- [ ] **[4.2]** Formularz zadań (Title, Description, Attachments, Employees multiselect).
- [ ] **[4.3]** Widok listy zadań dla Admina (podgląd statusu realizacji przez zespół).
- [ ] *STOP: Podsumowanie (4.1-4.3) i prośba o akceptację.*

## 🟡 Etap 5: Dashboard Pracownika & Zadania
- [ ] **[5.1]** Widok kafelkowy zadań (grid, brak miniatur, tylko date i title).
- [ ] **[5.2]** Logika przycisku "Done" (aktualizacja tylko w pivocie `task_user`).
- [ ] **[5.3]** Sekcja historyczna: lista 10 ostatnich zakończonych zadań (zwinięta).
- [ ] *STOP: Podsumowanie (5.1-5.3) i prośba o akceptację.*

## 🟡 Etap 6: Automatyzacja i Sprzątanie
- [ ] **[6.1]** Utworzenie `TaskObserver`.
- [ ] **[6.2]** Logika usuwania: kasowanie rekordów i plików z dysku po osiągnięciu limitów (10 zadań).
- [ ] *STOP: Podsumowanie (6.1-6.2) i prośba o akceptację.*

## 🔴 Etap 7: Szlify i Security
- [ ] **[7.1]** Optymalizacja zapytań (Eager Loading).
- [ ] **[7.2]** Finalne testy uprawnień (Policies) i walidacji plików.
- [ ] **[7.3]** Ostatnie poprawki wizualne UI (Soft UI).
- [ ] *KONIEC PROJEKTU.*