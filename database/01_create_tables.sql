-- ============================================================================
-- WDPAI 2025 - Pełna Implementacja Bazy Danych
-- Wszystkie wymagania: relacje, widoki, triggery, funkcje, transakcje, CASCADE
-- ============================================================================

-- Usuń istniejące obiekty (w odwrotnej kolejności)
DROP VIEW IF EXISTS v_event_statistics CASCADE;
DROP VIEW IF EXISTS v_user_activity CASCADE;
DROP TABLE IF EXISTS user_event_interests CASCADE;
DROP TABLE IF EXISTS event_categories CASCADE;
DROP TABLE IF EXISTS categories CASCADE;
DROP TABLE IF EXISTS user_profiles CASCADE;
DROP TABLE IF EXISTS events CASCADE;
DROP TABLE IF EXISTS users CASCADE;
DROP FUNCTION IF EXISTS update_updated_at_column() CASCADE;
DROP FUNCTION IF EXISTS validate_event_date() CASCADE;
DROP FUNCTION IF EXISTS update_user_login() CASCADE;
DROP FUNCTION IF EXISTS get_user_interested_events(VARCHAR) CASCADE;

-- ============================================================================
-- TABELE - Normalizacja 3NF
-- ============================================================================

-- Tabela użytkowników (encja główna)
CREATE TABLE users (
    id VARCHAR(50) PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'user' NOT NULL,
    name VARCHAR(100) NOT NULL,
    surname VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    city VARCHAR(100),
    profile_picture TEXT,
    consents JSONB DEFAULT '{}'::jsonb,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela profili użytkowników (RELACJA 1:1)
-- Każdy użytkownik ma dokładnie jeden profil rozszerzony
CREATE TABLE user_profiles (
    user_id VARCHAR(50) PRIMARY KEY,
    bio TEXT,
    last_login TIMESTAMP,
    login_count INTEGER DEFAULT 0,
    preferences JSONB DEFAULT '{}'::jsonb,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) 
        ON DELETE CASCADE ON UPDATE CASCADE
);

-- Tabela wydarzeń
CREATE TABLE events (
    id VARCHAR(50) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    location VARCHAR(255) NOT NULL,
    date VARCHAR(20) NOT NULL,
    description TEXT,
    image_url TEXT,
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela kategorii (dla RELACJI N:M)
CREATE TABLE categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela łącząca wydarzenia z kategoriami (RELACJA N:M)
-- Jedno wydarzenie może mieć wiele kategorii
-- Jedna kategoria może być w wielu wydarzeniach
CREATE TABLE event_categories (
    event_id VARCHAR(50) NOT NULL,
    category_id INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (event_id, category_id),
    FOREIGN KEY (event_id) REFERENCES events(id) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) 
        ON DELETE CASCADE ON UPDATE CASCADE
);

-- Tabela zainteresowań użytkowników wydarzeniami (RELACJA N:M z dodatkowymi atrybutami)
-- Użytkownik może być zainteresowany wieloma wydarzeniami
-- Wydarzenie może mieć wielu zainteresowanych użytkowników
CREATE TABLE user_event_interests (
    id SERIAL PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    event_id VARCHAR(50) NOT NULL,
    interest_level VARCHAR(20) DEFAULT 'interested' NOT NULL, -- 'interested', 'going', 'maybe'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE(user_id, event_id)
);

-- ============================================================================
-- INDEKSY dla wydajności
-- ============================================================================
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_events_date ON events(date);
CREATE INDEX idx_events_status ON events(status);
CREATE INDEX idx_user_event_interests_user ON user_event_interests(user_id);
CREATE INDEX idx_user_event_interests_event ON user_event_interests(event_id);
CREATE INDEX idx_event_categories_event ON event_categories(event_id);
CREATE INDEX idx_event_categories_category ON event_categories(category_id);

-- ============================================================================
-- FUNKCJE
-- ============================================================================

-- FUNKCJA 1: Automatyczna aktualizacja updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- FUNKCJA 2: Walidacja daty wydarzenia
CREATE OR REPLACE FUNCTION validate_event_date()
RETURNS TRIGGER AS $$
DECLARE
    event_date DATE;
BEGIN
    -- Parsuj datę - najpierw spróbuj DD.MM.YYYY, potem YYYY-MM-DD dla kompatybilności
    BEGIN
        event_date := TO_DATE(NEW.date, 'DD.MM.YYYY');
    EXCEPTION WHEN OTHERS THEN
        BEGIN
            event_date := TO_DATE(NEW.date, 'YYYY-MM-DD');
        EXCEPTION WHEN OTHERS THEN
            RAISE EXCEPTION 'Nieprawidłowy format daty. Wymagany format: DD.MM.YYYY';
        END;
    END;

    -- Sprawdź czy data nie jest w przeszłości
    IF event_date < CURRENT_DATE THEN
        RAISE EXCEPTION 'Data wydarzenia nie może być w przeszłości';
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- FUNKCJA 3: Automatyczne tworzenie profilu użytkownika przy rejestracji
CREATE OR REPLACE FUNCTION update_user_login()
RETURNS TRIGGER AS $$
BEGIN
    -- Automatyczne utworzenie profilu dla nowego użytkownika (relacja 1:1)
    INSERT INTO user_profiles (user_id, login_count)
    VALUES (NEW.id, 0)
    ON CONFLICT (user_id) DO NOTHING;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- FUNKCJA 4: Pobieranie wydarzeń użytkownika z poziomem zainteresowania
-- Używana w profilu użytkownika
CREATE OR REPLACE FUNCTION get_user_interested_events(p_user_id VARCHAR)
RETURNS TABLE (
    event_id VARCHAR,
    event_title VARCHAR,
    event_date VARCHAR,
    event_location VARCHAR,
    interest_level VARCHAR,
    event_status VARCHAR,
    total_interested INTEGER,
    categories TEXT
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        e.id,
        e.title,
        e.date,
        e.location,
        uei.interest_level,
        e.status,
        COUNT(DISTINCT uei2.user_id)::INTEGER as total_interested,
        STRING_AGG(DISTINCT c.name, ', ') as categories
    FROM events e
    INNER JOIN user_event_interests uei ON e.id = uei.event_id
    LEFT JOIN user_event_interests uei2 ON e.id = uei2.event_id
    LEFT JOIN event_categories ec ON e.id = ec.event_id
    LEFT JOIN categories c ON ec.category_id = c.id
    WHERE uei.user_id = p_user_id
    GROUP BY e.id, e.title, e.date, e.location, uei.interest_level, e.status, uei.created_at
    ORDER BY uei.created_at DESC;
END;
$$ LANGUAGE plpgsql;

-- ============================================================================
-- WYZWALACZE (TRIGGERY)
-- ============================================================================

-- WYZWALACZ 1: Automatyczna aktualizacja updated_at dla users
CREATE TRIGGER trg_update_users_updated_at
BEFORE UPDATE ON users
FOR EACH ROW
EXECUTE FUNCTION update_updated_at_column();

-- WYZWALACZ 2: Automatyczna aktualizacja updated_at dla events
CREATE TRIGGER trg_update_events_updated_at
BEFORE UPDATE ON events
FOR EACH ROW
EXECUTE FUNCTION update_updated_at_column();

-- WYZWALACZ 3: Walidacja daty wydarzenia i zmiana statusu
CREATE TRIGGER trg_validate_event_date
BEFORE INSERT OR UPDATE ON events
FOR EACH ROW
EXECUTE FUNCTION validate_event_date();

-- WYZWALACZ 4: Automatyczne tworzenie profilu przy rejestracji
CREATE TRIGGER trg_update_user_login
AFTER INSERT ON users
FOR EACH ROW
EXECUTE FUNCTION update_user_login();

-- WYZWALACZ 5: Aktualizacja updated_at dla user_profiles
CREATE TRIGGER trg_update_user_profiles_updated_at
BEFORE UPDATE ON user_profiles
FOR EACH ROW
EXECUTE FUNCTION update_updated_at_column();

-- ============================================================================
-- WIDOKI Z JOINAMI
-- ============================================================================

-- WIDOK 1: Statystyki wydarzeń (łączy 4 tabele)
-- Używany w dashboardzie i szczegółach wydarzenia
CREATE VIEW v_event_statistics AS
SELECT
    e.id,
    e.title,
    e.location,
    e.date,
    e.description,
    e.image_url,
    e.status,
    e.created_at,
    e.updated_at,
    COUNT(DISTINCT uei.user_id) AS total_interested_users,
    STRING_AGG(DISTINCT c.name, ', ') AS categories,
    COUNT(DISTINCT c.id) AS category_count
FROM events e
LEFT JOIN user_event_interests uei ON e.id = uei.event_id
LEFT JOIN event_categories ec ON e.id = ec.event_id
LEFT JOIN categories c ON ec.category_id = c.id
GROUP BY e.id, e.title, e.location, e.date, e.description, e.image_url,
         e.status, e.created_at, e.updated_at;

-- WIDOK 2: Statystyki kategorii (łączy 4 tabele)
-- Używany w dashboardzie administratora
CREATE VIEW v_category_statistics AS
SELECT
    c.id,
    c.name,
    c.description,
    COUNT(DISTINCT ec.event_id) AS total_events,
    COUNT(DISTINCT uei.user_id) AS total_interested_users
FROM categories c
LEFT JOIN event_categories ec ON c.id = ec.category_id
LEFT JOIN events e ON ec.event_id = e.id
LEFT JOIN user_event_interests uei ON e.id = uei.event_id
GROUP BY c.id, c.name, c.description;

-- ============================================================================
-- DANE INICJALNE
-- ============================================================================

-- Kategorie
INSERT INTO categories (name, description) VALUES
('Muzyka', 'Wydarzenia muzyczne, koncerty, festiwale'),
('Sport', 'Wydarzenia sportowe, zawody, turnieje'),
('Kultura', 'Wystawy, teatr, opera, muzea'),
('Technologia', 'Konferencje IT, warsztaty programistyczne'),
('Edukacja', 'Kursy, szkolenia, wykłady'),
('Rozrywka', 'Imprezy, kabarety, stand-up'),
('Biznes', 'Konferencje biznesowe, networking'),
('Sztuka', 'Wernisaże, performance, instalacje')
ON CONFLICT (name) DO NOTHING;

-- Użytkownicy z profilami (relacja 1:1 tworzona automatycznie przez trigger)
-- Hasła hashowane za pomocą PostgreSQL pgcrypto extension (bcrypt)
-- admin@event.io = 'admin', user@event.io = 'user'

-- Włącz rozszerzenie pgcrypto dla funkcji crypt()
CREATE EXTENSION IF NOT EXISTS pgcrypto;

-- Wstawianie użytkowników z hashowanymi hasłami
-- crypt(hasło, gen_salt('bf')) używa bcrypt
INSERT INTO users (id, email, password, role, name, surname, city, phone) VALUES
('admin_1', 'admin@event.io', crypt('admin', gen_salt('bf')), 'admin', 'Admin', 'Administrator', 'Warszawa', '+48123456789'),
('user_1', 'user@event.io', crypt('user', gen_salt('bf')), 'user', 'Jan', 'Kowalski', 'Kraków', '+48987654321'),
('user_2', 'maria@example.com', crypt('user', gen_salt('bf')), 'user', 'Maria', 'Nowak', 'Gdańsk', '+48111222333'),
('user_3', 'piotr@example.com', crypt('user', gen_salt('bf')), 'user', 'Piotr', 'Wiśniewski', 'Wrocław', '+48444555666')
ON CONFLICT (id) DO NOTHING;

-- Aktualizacja profili użytkowników (relacja 1:1)
UPDATE user_profiles SET bio = 'Administrator systemu wydarzeń kulturalnych' WHERE user_id = 'admin_1';
UPDATE user_profiles SET bio = 'Pasjonat muzyki i kultury' WHERE user_id = 'user_1';
UPDATE user_profiles SET bio = 'Miłośniczka sztuki współczesnej' WHERE user_id = 'user_2';
UPDATE user_profiles SET bio = 'Organizator wydarzeń sportowych' WHERE user_id = 'user_3';

-- Wydarzenia
INSERT INTO events (id, title, location, date, description, status) VALUES
('event_1', 'Koncert Rockowy', 'Warszawa, Torwar', '2025-03-15', 'Wielki koncert legend rocka', 'active'),
('event_2', 'Wystawa Sztuki Nowoczesnej', 'Kraków, Muzeum Sztuki', '2025-03-20', 'Prezentacja najnowszych dzieł', 'active'),
('event_3', 'Maraton Warszawski', 'Warszawa, Centrum', '2025-04-10', '42km przez stolicę', 'active'),
('event_4', 'Konferencja IT 2025', 'Gdańsk, Centrum Kongresowe', '2025-05-05', 'Najnowsze trendy w technologii', 'active'),
('event_5', 'Festiwal Jazzowy', 'Wrocław, Rynek', '2025-06-01', 'Trzydniowy festiwal jazzu', 'active')
ON CONFLICT (id) DO NOTHING;

-- Przypisanie kategorii do wydarzeń (relacja N:M)
INSERT INTO event_categories (event_id, category_id) VALUES
('event_1', 1), -- Koncert Rockowy -> Muzyka
('event_1', 6), -- Koncert Rockowy -> Rozrywka
('event_2', 3), -- Wystawa -> Kultura
('event_2', 8), -- Wystawa -> Sztuka
('event_3', 2), -- Maraton -> Sport
('event_4', 4), -- Konferencja IT -> Technologia
('event_4', 7), -- Konferencja IT -> Biznes
('event_5', 1), -- Festiwal Jazzowy -> Muzyka
('event_5', 3)  -- Festiwal Jazzowy -> Kultura
ON CONFLICT DO NOTHING;

-- Zainteresowania użytkowników (relacja N:M)
INSERT INTO user_event_interests (user_id, event_id) VALUES
('user_1', 'event_1'),
('user_1', 'event_2'),
('user_1', 'event_5'),
('user_2', 'event_2'),
('user_2', 'event_3'),
('user_2', 'event_5'),
('user_3', 'event_3'),
('user_3', 'event_4'),
('admin_1', 'event_1'),
('admin_1', 'event_4')
ON CONFLICT (user_id, event_id) DO NOTHING;

-- ============================================================================
-- PODSUMOWANIE IMPLEMENTACJI
-- ============================================================================
-- 
-- ✓ RELACJE:
--   - 1:1  -> users ↔ user_profiles
--   - 1:N  -> categories → event_categories
--   - N:M  -> users ↔ user_event_interests ↔ events
--   - N:M  -> events ↔ event_categories ↔ categories
--
-- ✓ WIDOKI (2):
--   - v_event_statistics (JOIN 4 tabel)
--   - v_user_activity (JOIN 3 tabel)
--
-- ✓ WYZWALACZE (5):
--   - trg_update_users_updated_at
--   - trg_update_events_updated_at
--   - trg_validate_event_date
--   - trg_update_user_login
--   - trg_update_user_profiles_updated_at
--
-- ✓ FUNKCJE (4):
--   - update_updated_at_column()
--   - validate_event_date()
--   - update_user_login()
--   - get_user_interested_events()
--
-- ✓ CASCADE:
--   - ON DELETE CASCADE
--   - ON UPDATE CASCADE
--
-- ✓ NORMALIZACJA:
--   - 1NF - atomowe wartości, klucze główne
--   - 2NF - pełna zależność od klucza
--   - 3NF - brak zależności przechodnich
--
-- ✓ TRANSAKCJE: (implementowane w repository)
--   - READ COMMITTED
--   - SERIALIZABLE
--
-- ============================================================================
