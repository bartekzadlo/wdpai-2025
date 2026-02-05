-- ============================================================================
-- WDPAI 2025 - Przykładowe testy SQL
-- ============================================================================
-- 
-- Ten plik zawiera testy SQL, które można wykonać w pgAdmin aby sprawdzić
-- działanie widoków, funkcji, triggerów i relacji CASCADE.
--
-- Jak używać:
-- 1. Otwórz pgAdmin (http://localhost:5050)
-- 2. Połącz się z bazą wdpai_db
-- 3. Otwórz Query Tool
-- 4. Skopiuj i wykonaj poszczególne testy
-- ============================================================================

-- ============================================================================
-- SEKCJA 1: TESTY WIDOKÓW
-- ============================================================================

-- TEST 1.1: Widok v_event_statistics
-- Powinien zwrócić wszystkie wydarzenia z liczbą zainteresowanych i kategoriami
SELECT * FROM v_event_statistics;

-- TEST 1.2: Widok v_event_statistics - filtrowanie po statusie
SELECT 
    title, 
    location, 
    date, 
    total_interested_users, 
    categories 
FROM v_event_statistics 
WHERE status = 'active'
ORDER BY total_interested_users DESC;

-- TEST 1.3: Widok v_category_statistics
-- Powinien zwrócić wszystkie kategorie z liczbą wydarzeń
SELECT * FROM v_category_statistics
ORDER BY total_events DESC;

-- TEST 1.4: Top 5 najpopularniejszych wydarzeń
SELECT 
    title,
    location,
    date,
    total_interested_users as popularity,
    categories
FROM v_event_statistics
WHERE status = 'active'
ORDER BY total_interested_users DESC
LIMIT 5;

-- ============================================================================
-- SEKCJA 2: TESTY FUNKCJI
-- ============================================================================

-- TEST 2.1: Funkcja get_user_interested_events
-- Powinien zwrócić wydarzenia, którymi zainteresowany jest user_1
SELECT * FROM get_user_interested_events('user_1');

-- TEST 2.2: Funkcja get_user_interested_events dla admina
SELECT * FROM get_user_interested_events('admin_1');

-- TEST 2.3: Sprawdzenie czy funkcja zwraca poprawną liczbę zainteresowanych
SELECT 
    event_title,
    total_interested,
    categories
FROM get_user_interested_events('user_1')
ORDER BY total_interested DESC;

-- ============================================================================
-- SEKCJA 3: TESTY TRIGGERÓW
-- ============================================================================

-- TEST 3.1: Trigger trg_validate_event_date
-- Ten INSERT powinien zakończyć się błędem, bo data jest w przeszłości
BEGIN;
INSERT INTO events (id, title, location, date, description, status)
VALUES ('test_past_date', 'Wydarzenie w przeszłości', 'Warszawa', '2020-01-01', 'Test', 'active');
ROLLBACK; -- Rollback bo spodziewamy się błędu

-- TEST 3.2: Trigger trg_validate_event_date - poprawna data
-- Ten INSERT powinien się powieść
BEGIN;
INSERT INTO events (id, title, location, date, description, status)
VALUES ('test_future_date', 'Wydarzenie w przyszłości', 'Warszawa', '2026-12-31', 'Test', 'active');
COMMIT;

-- Sprawdź czy wydarzenie zostało dodane
SELECT * FROM events WHERE id = 'test_future_date';

-- Usuń testowe wydarzenie
DELETE FROM events WHERE id = 'test_future_date';

-- TEST 3.3: Trigger trg_update_users_updated_at
-- Sprawdź aktualny updated_at
SELECT id, email, updated_at FROM users WHERE email = 'user@event.io';

-- Aktualizuj dane użytkownika
UPDATE users SET city = 'Gdańsk' WHERE email = 'user@event.io';

-- Sprawdź czy updated_at się zmienił (powinien być nowszy)
SELECT id, email, city, updated_at FROM users WHERE email = 'user@event.io';

-- Przywróć poprzednią wartość
UPDATE users SET city = 'Kraków' WHERE email = 'user@event.io';

-- TEST 3.4: Trigger trg_update_user_login (tworzenie profilu)
-- Dodaj nowego użytkownika
BEGIN;
INSERT INTO users (id, email, password, role, name, surname)
VALUES ('test_trigger_user', 'triggertest@example.com', crypt('test123', gen_salt('bf')), 'user', 'Trigger', 'Test');

-- Sprawdź czy profil został automatycznie utworzony
SELECT * FROM user_profiles WHERE user_id = 'test_trigger_user';

-- Usuń testowego użytkownika (CASCADE usunie też profil)
DELETE FROM users WHERE id = 'test_trigger_user';

-- Sprawdź czy profil został automatycznie usunięty
SELECT * FROM user_profiles WHERE user_id = 'test_trigger_user';
COMMIT;

-- TEST 3.5: Trigger trg_update_events_updated_at
-- Sprawdź aktualny updated_at wydarzenia
SELECT id, title, updated_at FROM events WHERE id = 'event_1';

-- Aktualizuj wydarzenie
UPDATE events SET description = 'Zaktualizowany opis wydarzenia' WHERE id = 'event_1';

-- Sprawdź czy updated_at się zmienił
SELECT id, title, description, updated_at FROM events WHERE id = 'event_1';

-- ============================================================================
-- SEKCJA 4: TESTY CASCADE
-- ============================================================================

-- TEST 4.1: CASCADE DELETE - usunięcie użytkownika usuwa profil (1:1)
BEGIN;
-- Utwórz testowego użytkownika
INSERT INTO users (id, email, password, role, name, surname)
VALUES ('cascade_test_user', 'cascadetest@example.com', crypt('test', gen_salt('bf')), 'user', 'Cascade', 'Test');

-- Sprawdź czy profil został utworzony
SELECT * FROM user_profiles WHERE user_id = 'cascade_test_user';

-- Usuń użytkownika
DELETE FROM users WHERE id = 'cascade_test_user';

-- Sprawdź czy profil został automatycznie usunięty (powinno być 0 wyników)
SELECT COUNT(*) as should_be_zero FROM user_profiles WHERE user_id = 'cascade_test_user';
COMMIT;

-- TEST 4.2: CASCADE DELETE - usunięcie wydarzenia usuwa powiązania (N:M)
BEGIN;
-- Utwórz testowe wydarzenie
INSERT INTO events (id, title, location, date, description, status)
VALUES ('cascade_test_event', 'Test CASCADE', 'Warszawa', '2026-12-31', 'Test', 'active');

-- Dodaj kategorie
INSERT INTO event_categories (event_id, category_id)
VALUES ('cascade_test_event', 1), ('cascade_test_event', 2);

-- Dodaj zainteresowania
INSERT INTO user_event_interests (user_id, event_id)
VALUES ('user_1', 'cascade_test_event'), ('user_2', 'cascade_test_event');

-- Sprawdź powiązania przed usunięciem
SELECT 'event_categories' as table_name, COUNT(*) as count FROM event_categories WHERE event_id = 'cascade_test_event'
UNION ALL
SELECT 'user_event_interests', COUNT(*) FROM user_event_interests WHERE event_id = 'cascade_test_event';

-- Usuń wydarzenie
DELETE FROM events WHERE id = 'cascade_test_event';

-- Sprawdź czy powiązania zostały automatycznie usunięte (powinno być 0 wszędzie)
SELECT 'event_categories' as table_name, COUNT(*) as should_be_zero FROM event_categories WHERE event_id = 'cascade_test_event'
UNION ALL
SELECT 'user_event_interests', COUNT(*) FROM user_event_interests WHERE event_id = 'cascade_test_event';
COMMIT;

-- TEST 4.3: CASCADE DELETE - usunięcie kategorii usuwa powiązania
BEGIN;
-- Utwórz testową kategorię
INSERT INTO categories (name, description)
VALUES ('Test CASCADE Category', 'Kategoria testowa dla CASCADE');

-- Pobierz ID nowo utworzonej kategorii
DO $$
DECLARE
    test_category_id INTEGER;
BEGIN
    SELECT id INTO test_category_id FROM categories WHERE name = 'Test CASCADE Category';
    
    -- Przypisz kategorię do wydarzenia
    INSERT INTO event_categories (event_id, category_id)
    VALUES ('event_1', test_category_id);
    
    -- Sprawdź powiązanie
    RAISE NOTICE 'Powiązania przed usunięciem: %', (SELECT COUNT(*) FROM event_categories WHERE category_id = test_category_id);
    
    -- Usuń kategorię
    DELETE FROM categories WHERE id = test_category_id;
    
    -- Sprawdź czy powiązanie zostało usunięte
    RAISE NOTICE 'Powiązania po usunięciu: %', (SELECT COUNT(*) FROM event_categories WHERE category_id = test_category_id);
END $$;
COMMIT;

-- ============================================================================
-- SEKCJA 5: TESTY RELACJI I JOINÓW
-- ============================================================================

-- TEST 5.1: INNER JOIN - wydarzenia z kategoriami
SELECT 
    e.title,
    e.location,
    e.date,
    c.name as category_name
FROM events e
INNER JOIN event_categories ec ON e.id = ec.event_id
INNER JOIN categories c ON ec.category_id = c.id
ORDER BY e.title, c.name;

-- TEST 5.2: LEFT JOIN - wszystkie wydarzenia, nawet bez kategorii
SELECT 
    e.title,
    e.location,
    COUNT(ec.category_id) as category_count
FROM events e
LEFT JOIN event_categories ec ON e.id = ec.event_id
GROUP BY e.id, e.title, e.location
ORDER BY category_count DESC;

-- TEST 5.3: JOIN z agregacją - użytkownicy z liczbą zainteresowań
SELECT 
    u.email,
    u.name,
    u.surname,
    COUNT(uei.event_id) as interested_events_count
FROM users u
LEFT JOIN user_event_interests uei ON u.id = uei.user_id
GROUP BY u.id, u.email, u.name, u.surname
ORDER BY interested_events_count DESC;

-- TEST 5.4: Kompleksowy JOIN - wydarzenia z kategoriami i zainteresowanymi
SELECT 
    e.title as event_title,
    e.date,
    STRING_AGG(DISTINCT c.name, ', ') as categories,
    COUNT(DISTINCT uei.user_id) as interested_users,
    STRING_AGG(DISTINCT u.name || ' ' || u.surname, ', ') as user_names
FROM events e
LEFT JOIN event_categories ec ON e.id = ec.event_id
LEFT JOIN categories c ON ec.category_id = c.id
LEFT JOIN user_event_interests uei ON e.id = uei.event_id
LEFT JOIN users u ON uei.user_id = u.id
GROUP BY e.id, e.title, e.date
ORDER BY interested_users DESC;

-- ============================================================================
-- SEKCJA 6: TESTY NORMALIZACJI
-- ============================================================================

-- TEST 6.1: Sprawdzenie 1NF - wszystkie wartości są atomowe
-- Sprawdź czy nie ma wartości wielokrotnych w pojedynczych kolumnach
SELECT 
    'users' as table_name,
    COUNT(*) as row_count,
    COUNT(DISTINCT id) as unique_ids
FROM users
UNION ALL
SELECT 'events', COUNT(*), COUNT(DISTINCT id) FROM events
UNION ALL
SELECT 'categories', COUNT(*), COUNT(DISTINCT id) FROM categories;

-- TEST 6.2: Sprawdzenie 2NF - pełna zależność od klucza głównego
-- W tabelach łączących (N:M) sprawdź czy wszystkie kolumny zależą od klucza
SELECT 
    ec.event_id,
    ec.category_id,
    e.title as event_title,
    c.name as category_name
FROM event_categories ec
INNER JOIN events e ON ec.event_id = e.id
INNER JOIN categories c ON ec.category_id = c.id
LIMIT 10;

-- TEST 6.3: Sprawdzenie 3NF - brak zależności przechodnich
-- Sprawdź czy user_profiles zawiera tylko dane zależne od user_id
SELECT 
    u.id,
    u.email,
    u.name,
    up.bio,
    up.login_count
FROM users u
INNER JOIN user_profiles up ON u.id = up.user_id
LIMIT 10;

-- TEST 6.4: Sprawdzenie redundancji - czy dane nie są duplikowane
-- Sprawdź czy nazwy kategorii są unikalne
SELECT 
    name,
    COUNT(*) as occurrences
FROM categories
GROUP BY name
HAVING COUNT(*) > 1;

-- Sprawdź czy emaile użytkowników są unikalne
SELECT 
    email,
    COUNT(*) as occurrences
FROM users
GROUP BY email
HAVING COUNT(*) > 1;

-- ============================================================================
-- SEKCJA 7: TESTY TRANSAKCJI
-- ============================================================================

-- TEST 7.1: Transakcja z ROLLBACK
BEGIN;
-- Dodaj wydarzenie
INSERT INTO events (id, title, location, date, description, status)
VALUES ('transaction_test', 'Test transakcji', 'Warszawa', '2026-12-31', 'Test', 'active');

-- Sprawdź czy wydarzenie zostało dodane
SELECT COUNT(*) as should_be_1 FROM events WHERE id = 'transaction_test';

-- Wycofaj zmiany
ROLLBACK;

-- Sprawdź czy wydarzenie NIE zostało dodane (powinno być 0)
SELECT COUNT(*) as should_be_0 FROM events WHERE id = 'transaction_test';

-- TEST 7.2: Transakcja z COMMIT
BEGIN;
-- Dodaj wydarzenie
INSERT INTO events (id, title, location, date, description, status)
VALUES ('transaction_test_2', 'Test transakcji 2', 'Kraków', '2026-12-31', 'Test', 'active');

-- Zatwierdź zmiany
COMMIT;

-- Sprawdź czy wydarzenie zostało dodane (powinno być 1)
SELECT COUNT(*) as should_be_1 FROM events WHERE id = 'transaction_test_2';

-- Usuń testowe wydarzenie
DELETE FROM events WHERE id = 'transaction_test_2';

-- TEST 7.3: Transakcja z wieloma operacjami
BEGIN;
-- Dodaj użytkownika
INSERT INTO users (id, email, password, role, name, surname)
VALUES ('trans_user', 'transtest@example.com', crypt('test', gen_salt('bf')), 'user', 'Trans', 'Test');

-- Dodaj wydarzenie
INSERT INTO events (id, title, location, date, description, status)
VALUES ('trans_event', 'Trans Event', 'Warszawa', '2026-12-31', 'Test', 'active');

-- Dodaj zainteresowanie
INSERT INTO user_event_interests (user_id, event_id)
VALUES ('trans_user', 'trans_event');

-- Sprawdź czy wszystko zostało dodane
SELECT 
    (SELECT COUNT(*) FROM users WHERE id = 'trans_user') as user_count,
    (SELECT COUNT(*) FROM events WHERE id = 'trans_event') as event_count,
    (SELECT COUNT(*) FROM user_event_interests WHERE user_id = 'trans_user' AND event_id = 'trans_event') as interest_count;

-- Wycofaj wszystkie zmiany
ROLLBACK;

-- Sprawdź czy wszystko zostało wycofane (wszystkie powinny być 0)
SELECT 
    (SELECT COUNT(*) FROM users WHERE id = 'trans_user') as should_be_0,
    (SELECT COUNT(*) FROM events WHERE id = 'trans_event') as should_be_0,
    (SELECT COUNT(*) FROM user_event_interests WHERE user_id = 'trans_user' AND event_id = 'trans_event') as should_be_0;

-- ============================================================================
-- SEKCJA 8: TESTY WYDAJNOŚCI I INDEKSÓW
-- ============================================================================

-- TEST 8.1: Sprawdzenie czy indeksy istnieją
SELECT 
    schemaname,
    tablename,
    indexname,
    indexdef
FROM pg_indexes
WHERE schemaname = 'public'
ORDER BY tablename, indexname;

-- TEST 8.2: Test wydajności wyszukiwania po indeksie (email)
EXPLAIN ANALYZE
SELECT * FROM users WHERE email = 'user@event.io';

-- TEST 8.3: Test wydajności JOIN z indeksami
EXPLAIN ANALYZE
SELECT 
    e.title,
    COUNT(uei.user_id) as interested_count
FROM events e
LEFT JOIN user_event_interests uei ON e.id = uei.event_id
GROUP BY e.id, e.title
ORDER BY interested_count DESC;

-- ============================================================================
-- PODSUMOWANIE TESTÓW
-- ============================================================================

-- Podsumowanie stanu bazy danych
SELECT 
    'Użytkownicy' as entity,
    COUNT(*) as count
FROM users
UNION ALL
SELECT 'Profile użytkowników', COUNT(*) FROM user_profiles
UNION ALL
SELECT 'Wydarzenia', COUNT(*) FROM events
UNION ALL
SELECT 'Kategorie', COUNT(*) FROM categories
UNION ALL
SELECT 'Powiązania wydarzenie-kategoria', COUNT(*) FROM event_categories
UNION ALL
SELECT 'Zainteresowania użytkowników', COUNT(*) FROM user_event_interests;

-- ============================================================================
-- KONIEC TESTÓW
-- ============================================================================
