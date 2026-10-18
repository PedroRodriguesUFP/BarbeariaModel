-- =========================================================
-- BARBEARIA MODEL
-- Fase 2 - Seed de dados de teste
-- Ficheiro: BasedeDados/02_seed.sql
--
-- Nota: este ficheiro pressupoe que 01_schema.sql ja foi executado.
-- Os dados sao ficticios. A password de teste para todas as contas
-- e "password" (hash bcrypt compatível com Laravel).
-- =========================================================

BEGIN;

-- =========================================================
-- 1. USERS
-- Roles permitidos: admin, barber, client
-- =========================================================

INSERT INTO users (name, email, phone, bio, password, role, active)
VALUES
    ('Administrador', 'admin@barbeariamodel.pt', '910000001', 'Conta de administração para testes.',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', TRUE),
    ('Tiago Costa', 'tiago.barber@barbeariamodel.pt', '910000002', 'Barbeiro especializado em fades.',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'barber', TRUE),
    ('Miguel Sousa', 'miguel.barber@barbeariamodel.pt', '910000003', 'Barbeiro especializado em barba e cabelo.',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'barber', TRUE),
    ('João Silva', 'joao.cliente@barbeariamodel.pt', '910000004', NULL,
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', TRUE),
    ('Rafael Ferreira', 'rafael.cliente@barbeariamodel.pt', '910000005', NULL,
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', TRUE),
    ('André Martins', 'andre.cliente@barbeariamodel.pt', '910000006', NULL,
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', TRUE),
    ('Diogo Ribeiro', 'diogo.cliente@barbeariamodel.pt', '910000007', NULL,
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', TRUE)
ON CONFLICT (email) DO NOTHING;

-- =========================================================
-- 2. SERVICES
-- Categories allowed: hair, beard, hair and beard, fade, coloring
-- =========================================================

INSERT INTO services (name, description, price, duration_minutes, category, is_active)
SELECT v.name, v.description, v.price, v.duration_minutes, v.category, TRUE
FROM (VALUES
    ('Corte clássico', 'Corte tradicional à tesoura ou máquina.', 12.00::NUMERIC, 30, 'hair'),
    ('Fade', 'Degradê com acabamento detalhado.', 15.00::NUMERIC, 40, 'fade'),
    ('Corte e barba', 'Corte de cabelo e tratamento da barba.', 20.00::NUMERIC, 50, 'hair and beard'),
    ('Aparar barba', 'Aparar e alinhar a barba.', 8.00::NUMERIC, 20, 'beard'),
    ('Coloração', 'Serviço de coloração capilar.', 25.00::NUMERIC, 60, 'coloring')
) AS v(name, description, price, duration_minutes, category)
WHERE NOT EXISTS (
    SELECT 1 FROM services s WHERE s.name = v.name
);

-- =========================================================
-- 3. SERVICE_USER
-- Associação dos barbeiros aos serviços que podem realizar.
-- =========================================================

INSERT INTO service_user (user_id, service_id)
SELECT u.id, s.id
FROM users u
JOIN services s ON
    (u.email = 'tiago.barber@barbeariamodel.pt'
        AND s.name IN ('Corte clássico', 'Fade', 'Corte e barba'))
    OR
    (u.email = 'miguel.barber@barbeariamodel.pt'
        AND s.name IN ('Corte clássico', 'Corte e barba', 'Aparar barba', 'Coloração'))
WHERE u.role = 'barber'
ON CONFLICT (user_id, service_id) DO NOTHING;

-- =========================================================
-- 4. WORKING_HOURS
-- day_of_week: 1 = segunda ... 7 = domingo
-- =========================================================

INSERT INTO working_hours (user_id, day_of_week, start_time, end_time)
SELECT u.id, v.day_of_week, v.start_time::TIME, v.end_time::TIME
FROM users u
CROSS JOIN (VALUES
    (1, '09:00', '18:00'),
    (2, '09:00', '18:00'),
    (3, '09:00', '18:00'),
    (4, '09:00', '18:00'),
    (5, '09:00', '18:00')
) AS v(day_of_week, start_time, end_time)
WHERE u.email IN ('tiago.barber@barbeariamodel.pt', 'miguel.barber@barbeariamodel.pt')
ON CONFLICT (user_id, day_of_week, start_time, end_time) DO NOTHING;

-- =========================================================
-- 5. SCHEDULE_BLOCKS
-- Exemplo de uma ausência durante um dia inteiro.
-- =========================================================

INSERT INTO schedule_blocks (user_id, block_date, start_time, end_time, all_day, reason)
SELECT u.id, CURRENT_DATE + 14, NULL, NULL, TRUE, 'Ausência para teste'
FROM users u