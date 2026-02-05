# 🚀 Quick Start Guide

Szybki start dla projektu WDPAI 2025 - System Zarządzania Wydarzeniami

---

## ⚡ Uruchomienie w 3 krokach

### Krok 1: Sklonuj repozytorium
```bash
git clone https://github.com/twoj-uzytkownik/wdpai-2025.git
cd wdpai-2025
```

### Krok 2: Uruchom aplikację
```bash
docker-compose up -d
```

### Krok 3: Otwórz przeglądarkę
```
http://localhost:8080
```

**To wszystko! Aplikacja jest gotowa do użycia.** 🎉

---

## 🔑 Testowe konta

### Administrator
- **Email:** `admin@event.io`
- **Hasło:** `admin`
- **Uprawnienia:** Pełny dostęp do panelu administracyjnego

### Użytkownik
- **Email:** `user@event.io`
- **Hasło:** `user`
- **Uprawnienia:** Standardowe funkcje użytkownika


### Dostęp do pgAdmin:
- **Email:** `admin@example.com`
- **Hasło:** `admin`

### Połączenie z bazą (pgAdmin):
1. Otwórz pgAdmin → Add New Server
2. Nazwa: `wdpai-db`
3. Host: `db` (lub `localhost` z hosta)
4. Port: `5432` (wewnętrzny) lub `5433` (z hosta)
5. Database: `wdpai_db`
6. User: `postgres`
7. Password: `postgres`

---

## 📁 Struktura projektu

```
wdpai-2025/
├── src/                    # Kod źródłowy PHP
│   ├── controllers/        # Kontrolery MVC
│   ├── models/            # Modele danych
│   ├── repository/        # Warstwa dostępu do danych
│   ├── services/          # Serwisy pomocnicze
│   └── database/          # Konfiguracja bazy danych
├── public/                # Pliki publiczne
│   ├── views/             # Widoki HTML
│   ├── styles/            # Style CSS
│   └── scripts/           # Skrypty JavaScript
├── database/              # Skrypty SQL
│   └── 01_create_tables.sql
├── docker/                # Konfiguracja Docker
│   ├── nginx/
│   ├── php/
│   └── db/
├── docker-compose.yaml    # Orkiestracja kontenerów
├── README.md              # Pełna dokumentacja
└── .env.example           # Przykładowa konfiguracja
```

## 🛠️ Przydatne komendy Docker

### Podstawowe operacje:
```bash
# Uruchomienie wszystkich kontenerów
docker-compose up -d

# Zatrzymanie kontenerów
docker-compose down

# Restart aplikacji
docker-compose restart

# Podgląd logów
docker-compose logs -f

# Status kontenerów
docker-compose ps
```

### Zarządzanie danymi:
```bash
# Restart z czyszczeniem wolumenów (USUWA DANE!)
docker-compose down -v
docker-compose up -d

# Ponowne zbudowanie obrazów
docker-compose build --no-cache
docker-compose up -d
```

### Debugging:
```bash
# Wejście do kontenera PHP
docker exec -it wdpai-php bash

# Wejście do kontenera bazy danych
docker exec -it wdpai-db psql -U postgres -d wdpai_db

# Sprawdzenie logów konkretnego kontenera
docker-compose logs php
docker-compose logs db
docker-compose logs web
```

---

## 🧪 Szybki test funkcjonalności

### Test 1: Logowanie
1. Otwórz http://localhost:8080
2. Zaloguj się jako `admin@event.io` / `admin`
3. Sprawdź czy widzisz panel administratora

### Test 2: CRUD wydarzeń
1. Przejdź do "Panel administratora" → "Wydarzenia"
2. Dodaj nowe wydarzenie
3. Edytuj wydarzenie
4. Usuń wydarzenie

### Test 3: Role użytkowników
1. Wyloguj się
2. Zaloguj jako `user@event.io` / `user`
3. Spróbuj otworzyć http://localhost:8080/admin/users
4. Powinieneś zobaczyć błąd 403 (brak dostępu)

### Test 4: Baza danych
1. Otwórz pgAdmin: http://localhost:5050
2. Połącz się z serwerem (dane powyżej)
3. Otwórz widok `v_event_statistics`
4. Sprawdź dane

---

## 🔧 Troubleshooting

### Problem: Port 8080 zajęty
```bash
# Zatrzymaj aplikację
docker-compose down

# Zmień port w docker-compose.yaml
# Zmień "8080:80" na np. "8081:80"

# Uruchom ponownie
docker-compose up -d
```

### Problem: Kontenery nie startują
```bash
# Sprawdź logi
docker-compose logs

# Wyczyść i uruchom ponownie
docker-compose down -v
docker-compose up -d --build
```

### Problem: Błąd połączenia z bazą danych
```bash
# Sprawdź czy kontener bazy działa
docker-compose ps

# Sprawdź logi bazy
docker-compose logs db

# Restart bazy
docker-compose restart db
```

### Problem: Brak danych w bazie
```bash
# Wejdź do kontenera bazy
docker exec -it wdpai-db psql -U postgres -d wdpai_db

# Sprawdź czy tabele istnieją
\dt

# Jeśli nie - zaimportuj ręcznie
\i /docker-entrypoint-initdb.d/01_create_tables.sql
```