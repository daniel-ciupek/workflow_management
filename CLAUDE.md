# Wytyczne Projektu: Workflow management v2026

## 🛠 Tech Stack & Standardy
- **Framework:** Laravel 13.x (PHP 8.4), Livewire 3, Alpine.js.
- **UI:** Tailwind CSS + DaisyUI (Motyw: Modern/Soft).
- **Baza:** PostgreSQL 16.2.
- **Docker:** Laravel Sail (ZAKAZ używania tagów `latest` w docker-compose.yml).
- **Logika:** Indywidualne zamykanie zadań w tabeli pivot (`task_user`).

## 🌐 Language & Localization
- **Communication:** Konwersacja z użytkownikiem odbywa się w języku **polskim**.
- **Project UI:** Wszystkie elementy interfejsu (przyciski, etykiety, komunikaty, nazwy menu) muszą być w języku **angielskim**.
- **Codebase:** Nazewnictwo zmiennych, klas, tabel w bazie oraz komentarze w kodzie muszą być w języku **angielskim**.

## 🔌 Agent Plugins & Tools
1. **Plugin:** `npx claudepluginhub anthropics/claude-plugins-official --plugin context7`
2. **Database Access:** Agent musi skonfigurować `.env` tak, aby umożliwić dostęp narzędziom zewnętrznym (DBeaver, VS Code devDb) przez port 5432 na localhost.

## 🐳 Docker & Deployment Strategy
1. **Base Image:** PHP 8.4-fpm-alpine.
2. **Dockerfile:** Instalacja `pdo_pgsql`, `zip`, `gd` i Composera.
3. **Network & Ports:** `docker-compose.yml` MUSI wystawiać:
   - **80 / 8000:** Web Server.
   - **5173:** Vite HMR.
   - **5432:** PostgreSQL (mapowanie 5432:5432 na hosta).
4. **Data Persistence:** Obowiązkowe użycie nazwanych wolumenów dla bazy danych.
5. **Workflow:** Budowa lokalna -> Push na Docker Hub -> Pull na produkcji/testach.

## 🔐 Autoryzacja & Dostęp (Zasada PIN)
- **Employee PIN:** Dokładnie 4 cyfry (string).
- **Admin PIN:** Dokładnie 6 cyfr (string).
- **Logowanie:** Automatyczne rozpoznawanie roli po długości i dopasowaniu PIN-u.
- **Rejestracja:** Brak publicznej rejestracji. Konta tworzy wyłącznie Administrator.

## ⚠️ Zasada Współpracy (Kluczowe!)
- **Praca Etapowa:** AI musi realizować projekt zgodnie z kolejnością w `.claude/roadmap.md`.
- **Komunikacja:** Przy każdym podsumowaniu etapu odwołuj się do ID zadań (np. [1.1], [1.2]).
- **ZAKAZ AUTOMATYZACJI:** Po zakończeniu każdego Etapu AI **MUSI** się zatrzymać, podsumować prace i poprosić o akceptację przed przejściem dalej.

## 🏗 Reguły Architektoniczne & Security
1. **Pivot Table:** Status `done` i `completed_at` znajdują się w `task_user`.
2. **Karta Zadania:** Wyświetla datę utworzenia (`created_at`), brak miniatur zdjęć na głównej liście.
3. **Automatyzacja:** `TaskObserver` czyści stare zadania (max 10 zakończonych na pracownika).
4. **Bezpieczeństwo:** Rygorystyczny Rate Limiting na logowanie PIN-em. Walidacja plików: JPG/PDF, max 15MB.
5. **Git Hygiene:** Obowiązkowe `.gitignore` i `.dockerignore`.