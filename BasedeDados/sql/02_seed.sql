-- =====================================================================
-- BarbeariaModel — BDAD 2026/27 (UFP) — Fase 2 (v2.0)
-- 02_seed.sql — dados de teste (normais + casos limite)
--
-- Executar depois de 01_schema.sql:
--   psql -d <bd> -v ON_ERROR_STOP=1 -f sql/02_seed.sql
-- "Hoje" nos dados de teste = sábado 2026-10-31 (as consultas usam datas fixas).
-- Palavra-passe de todas as contas com login local: "password"
-- (hash bcrypt do seed do Laravel). Os IDs são fixos para os testes serem
-- reproduzíveis; no fim as sequências são realinhadas.
--
-- Casos limite (ver docs/fase2/03-dados-e-consultas.md):
--   * marcação walk-in (sem conta)                         -> appointments 8, 24
--   * barbeiro a marcar como cliente de outro barbeiro      -> appointments 12, 32
--   * subscrição expirada + nova ativa do mesmo utilizador  -> subscriptions 3 e 4
--   * quota de subscrição esgotada no mês                   -> João (sub 1), Rafael (sub 2)
--   * slot libertado por cancelamento e reocupado           -> appointments 22 e 23
--   * pagamento falhado + nova tentativa, reembolsado, pendente
--   * subscrição suspensa e cancelada; plano inativo
--   * serviço inativo sem barbeiros habilitados             -> service 6
--   * horário partido (2 períodos no mesmo dia)             -> working_hours do Rui
--   * conta sem password (login Google)                     -> user 12
--   * avaliações a barbeiro e a serviço; avaliação fora do trimestre
-- =====================================================================

BEGIN;

-- ---------------------------------------------------------------------
-- USERS: 1 admin, 3 barbeiros, 8 clientes (1 inativo, 1 sem telefone, 1 sem password)
-- ---------------------------------------------------------------------
INSERT INTO users (id, name, email, phone, bio, password, role, active) VALUES
 (1,  'Administrador',  'admin@barbeariamodel.pt',          '910000001', 'Conta de administração para testes.', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin',  TRUE),
 (2,  'Tiago Costa',    'tiago.barber@barbeariamodel.pt',   '910000002', 'Barbeiro especializado em fades.',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'barber', TRUE),
 (3,  'Miguel Sousa',   'miguel.barber@barbeariamodel.pt',  '910000003', 'Barbeiro especializado em barba e cabelo.', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'barber', TRUE),
 (4,  'Rui Barbosa',    'rui.barber@barbeariamodel.pt',     '910000004', 'Cortes clássicos, 12 anos de experiência.', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'barber', TRUE),
 (5,  'João Silva',     'joao.cliente@barbeariamodel.pt',   '910000005', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', TRUE),
 (6,  'Rafael Ferreira','rafael.cliente@barbeariamodel.pt', '910000006', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', TRUE),
 (7,  'André Martins',  'andre.cliente@barbeariamodel.pt',  '910000007', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', TRUE),
 (8,  'Marta Ferreira', 'marta.cliente@barbeariamodel.pt',  '910000008', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', TRUE),
 (9,  'Sofia Almeida',  'sofia.cliente@barbeariamodel.pt',  NULL,        NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', TRUE),
 (10, 'Carla Mendes',   'carla.cliente@barbeariamodel.pt',  '910000010', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', TRUE),
 (11, 'Diogo Rocha',    'diogo.cliente@barbeariamodel.pt',  '910000011', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', FALSE),
 (12, 'Inês Lopes',     'ines.google@barbeariamodel.pt',    '910000012', NULL, NULL,                                                              'client', TRUE);

-- ---------------------------------------------------------------------
-- SERVICES (catálogo do seed do Pedro + 1 serviço inativo sem barbeiros)
-- ---------------------------------------------------------------------
INSERT INTO services (id, name, description, price, duration_minutes, category, is_active) VALUES
 (1, 'Corte clássico',    'Corte tradicional à tesoura ou máquina.',  12.00, 30, 'hair',           TRUE),
 (2, 'Fade',              'Degradê com acabamento detalhado.',        15.00, 40, 'fade',           TRUE),
 (3, 'Corte e barba',     'Corte de cabelo e tratamento da barba.',   20.00, 50, 'hair and beard', TRUE),
 (4, 'Aparar barba',      'Aparar e alinhar a barba.',                 8.00, 20, 'beard',          TRUE),
 (5, 'Coloração',         'Serviço de coloração capilar.',            25.00, 60, 'coloring',       TRUE),
 (6, 'Tratamento facial', 'Serviço descontinuado (sem barbeiros).',   18.00, 30, 'beard',          FALSE);

-- ---------------------------------------------------------------------
-- SERVICE_USER: habilitações (RN03) — cada barbeiro com um conjunto diferente
-- ---------------------------------------------------------------------
INSERT INTO service_user (user_id, service_id) VALUES
 (2, 1), (2, 2), (2, 3), (2, 5),        -- Tiago: sem "Aparar barba"
 (3, 1), (3, 3), (3, 4),                -- Miguel: sem Fade nem Coloração
 (4, 1), (4, 2), (4, 4);                -- Rui: sem Corte e barba nem Coloração

-- ---------------------------------------------------------------------
-- WORKING_HOURS (1=segunda ... 7=domingo)
-- Tiago: ter-sáb 10-19 | Miguel: seg, qua, sex, sáb 09-13 | Rui: seg-sex, 09-13 e 14-18
-- ---------------------------------------------------------------------
INSERT INTO working_hours (user_id, day_of_week, start_time, end_time) VALUES
 (2, 2, '10:00', '19:00'), (2, 3, '10:00', '19:00'), (2, 4, '10:00', '19:00'), (2, 5, '10:00', '19:00'), (2, 6, '10:00', '19:00'),
 (3, 1, '09:00', '13:00'), (3, 3, '09:00', '13:00'), (3, 5, '09:00', '13:00'), (3, 6, '09:00', '13:00'),
 (4, 1, '09:00', '13:00'), (4, 1, '14:00', '18:00'), (4, 2, '09:00', '13:00'), (4, 2, '14:00', '18:00'),
 (4, 3, '09:00', '13:00'), (4, 3, '14:00', '18:00'), (4, 4, '09:00', '13:00'), (4, 4, '14:00', '18:00'),
 (4, 5, '09:00', '13:00'), (4, 5, '14:00', '18:00');

-- ---------------------------------------------------------------------
-- SCHEDULE_BLOCKS: um dia inteiro (férias) e um bloqueio parcial
-- ---------------------------------------------------------------------
INSERT INTO schedule_blocks (user_id, date, start_time, end_time, all_day, reason) VALUES
 (2, '2026-11-14', NULL,    NULL,    TRUE,  'Férias'),
 (3, '2026-11-02', '09:00', '11:00', FALSE, 'Consulta médica');

-- ---------------------------------------------------------------------
-- SUBSCRIPTION_PLANS (2 do seed do Pedro + 1 inativo)
-- ---------------------------------------------------------------------
INSERT INTO subscription_plans (id, name, description, monthly_price, cuts_per_month, active) VALUES
 (1, 'Plano Essencial', 'Inclui dois cortes por mês.',    19.90, 2, TRUE),
 (2, 'Plano Premium',   'Inclui quatro cortes por mês.',  34.90, 4, TRUE),
 (3, 'Plano Legado',    'Plano descontinuado.',            9.90, 1, FALSE);

-- ---------------------------------------------------------------------
-- SUBSCRIPTIONS
--   1 João   Premium    ativa
--   2 Rafael Essencial  ativa
--   3 André  Essencial  expirada (mai–ago)   } mesmo utilizador: a antiga
--   4 André  Essencial  ativa (desde set)    } expirou e há uma nova ativa
--   5 Marta  Essencial  cancelada
--   6 Carla  Premium    suspensa
--   7 Inês   Essencial  ativa (desde 15 out)
-- ---------------------------------------------------------------------
INSERT INTO subscriptions (id, user_id, subscription_plan_id, start_date, end_date, status) VALUES
 (1, 5,  2, '2026-09-01', NULL,         'active'),
 (2, 6,  1, '2026-09-01', NULL,         'active'),
 (3, 7,  1, '2026-05-01', '2026-08-31', 'expired'),
 (4, 7,  1, '2026-09-01', NULL,         'active'),
 (5, 8,  1, '2026-06-01', '2026-07-15', 'cancelled'),
 (6, 10, 2, '2026-10-01', NULL,         'suspended'),
 (7, 12, 1, '2026-10-15', NULL,         'active');

-- ---------------------------------------------------------------------
-- APPOINTMENTS (um serviço por marcação — Opção A)
-- colunas: id, customer_id, walk_in_name, walk_in_phone, barber_id, service_id,
--          subscription_id, date, time, status
-- ---------------------------------------------------------------------
INSERT INTO appointments (id, customer_id, walk_in_name, walk_in_phone, barber_id, service_id, subscription_id, date, time, status) VALUES
 -- setembro 2026
 (1,  5,    NULL,                NULL,        2, 2, 1,    '2026-09-03', '10:00', 'completed'),
 (2,  6,    NULL,                NULL,        3, 3, 2,    '2026-09-04', '09:00', 'completed'),
 (3,  7,    NULL,                NULL,        4, 1, 4,    '2026-09-07', '10:00', 'completed'),
 (4,  8,    NULL,                NULL,        2, 5, NULL, '2026-09-08', '11:00', 'completed'),
 (5,  9,    NULL,                NULL,        4, 4, NULL, '2026-09-09', '09:30', 'completed'),
 (6,  10,   NULL,                NULL,        3, 1, NULL, '2026-09-12', '10:00', 'completed'),
 (7,  5,    NULL,                NULL,        4, 2, 1,    '2026-09-14', '15:00', 'completed'),
 (8,  NULL, 'Cliente de passagem','912345678', 3, 4, NULL, '2026-09-16', '11:00', 'completed'),   -- walk-in
 (9,  8,    NULL,                NULL,        3, 1, NULL, '2026-09-18', '10:00', 'cancelled'),
 (10, 9,    NULL,                NULL,        2, 3, NULL, '2026-09-19', '14:00', 'completed'),
 (11, 6,    NULL,                NULL,        2, 1, 2,    '2026-09-22', '10:00', 'completed'),
 (12, 4,    NULL,                NULL,        2, 2, NULL, '2026-09-25', '12:00', 'completed'),   -- Rui (barbeiro) como cliente
 -- outubro 2026
 (13, 5,    NULL,                NULL,        2, 1, 1,    '2026-10-02', '10:00', 'completed'),
 (14, 5,    NULL,                NULL,        3, 3, 1,    '2026-10-09', '09:00', 'completed'),
 (15, 5,    NULL,                NULL,        4, 2, 1,    '2026-10-16', '10:00', 'completed'),
 (16, 5,    NULL,                NULL,        2, 2, 1,    '2026-10-23', '10:00', 'confirmed'),   -- 4.º corte: quota Premium esgotada
 (17, 6,    NULL,                NULL,        3, 1, 2,    '2026-10-03', '10:00', 'completed'),
 (18, 6,    NULL,                NULL,        4, 2, 2,    '2026-10-12', '10:00', 'completed'),   -- 2.º corte: quota Essencial esgotada
 (19, 7,    NULL,                NULL,        4, 1, 4,    '2026-10-13', '10:00', 'completed'),
 (20, 7,    NULL,                NULL,        4, 4, 4,    '2026-10-20', '10:00', 'cancelled'),   -- cancelada: não consome quota
 (21, 8,    NULL,                NULL,        2, 5, NULL, '2026-10-10', '11:00', 'completed'),
 (22, 9,    NULL,                NULL,        3, 4, NULL, '2026-10-17', '10:00', 'cancelled'),   -- slot libertado...
 (23, 9,    NULL,                NULL,        3, 4, NULL, '2026-10-17', '10:00', 'confirmed'),   -- ...e reocupado (mesmo barbeiro/hora)
 (24, NULL, 'Sr. Armando',       NULL,        2, 1, NULL, '2026-10-24', '15:00', 'completed'),   -- walk-in sem telefone
 (25, 10,   NULL,                NULL,        4, 1, NULL, '2026-10-26', '10:00', 'completed'),
 -- hoje: sábado 2026-10-31
 (26, 8,    NULL,                NULL,        2, 2, NULL, '2026-10-31', '10:00', 'pending'),
 (27, 10,   NULL,                NULL,        2, 1, NULL, '2026-10-31', '11:00', 'pending'),
 (28, 12,   NULL,                NULL,        2, 3, NULL, '2026-10-31', '15:00', 'pending'),
 (29, 9,    NULL,                NULL,        3, 4, NULL, '2026-10-31', '10:00', 'pending'),
 (30, 7,    NULL,                NULL,        3, 1, NULL, '2026-10-31', '11:00', 'confirmed'),   -- confirmada: não aparece em Q07
 -- futuro
 (31, 5,    NULL,                NULL,        2, 1, 1,    '2026-11-06', '10:00', 'pending'),
 (32, 4,    NULL,                NULL,        3, 4, NULL, '2026-11-04', '10:00', 'confirmed');   -- Rui (barbeiro) como cliente

-- ---------------------------------------------------------------------
-- PAYMENTS (origem: marcação XOR subscrição)
-- Marcações cobertas por subscrição não têm pagamento avulso (RN05).
-- ---------------------------------------------------------------------
INSERT INTO payments (id, appointment_id, subscription_id, amount, method, status, paid_at) VALUES
 -- avulsos
 (1,  4,    NULL, 25.00, 'card',     'paid',     '2026-09-08 12:10'),
 (2,  5,    NULL,  8.00, 'cash',     'paid',     '2026-09-09 10:00'),
 (3,  6,    NULL, 12.00, 'mbway',    'failed',   NULL),                    -- tentativa falhada...
 (4,  6,    NULL, 12.00, 'card',     'paid',     '2026-09-12 10:45'),      -- ...e nova tentativa
 (5,  8,    NULL,  8.00, 'cash',     'paid',     '2026-09-16 11:30'),      -- walk-in
 (6,  10,   NULL, 20.00, 'transfer', 'paid',     '2026-09-19 14:50'),
 (7,  12,   NULL, 15.00, 'card',     'paid',     '2026-09-25 12:45'),      -- barbeiro-cliente
 (8,  9,    NULL, 12.00, 'card',     'refunded', '2026-09-18 09:00'),      -- marcação cancelada
 (9,  21,   NULL, 25.00, 'card',     'paid',     '2026-10-10 12:05'),
 (10, 24,   NULL, 12.00, 'cash',     'paid',     '2026-10-24 15:40'),      -- walk-in
 (11, 25,   NULL, 12.00, 'mbway',    'pending',  NULL),
 -- mensalidades
 (12, NULL, 3,    19.90, 'card',     'paid',     '2026-05-01 09:00'),      -- subscrição agora expirada
 (13, NULL, 1,    34.90, 'card',     'paid',     '2026-09-01 09:00'),
 (14, NULL, 2,    19.90, 'mbway',    'paid',     '2026-09-01 09:30'),
 (15, NULL, 4,    19.90, 'card',     'paid',     '2026-09-02 10:00'),
 (16, NULL, 1,    34.90, 'card',     'paid',     '2026-10-01 09:00'),
 (17, NULL, 2,    19.90, 'mbway',    'paid',     '2026-10-01 09:30'),
 (18, NULL, 4,    19.90, 'card',     'pending',  NULL),
 (19, NULL, 7,    19.90, 'card',     'paid',     '2026-10-15 11:00');

-- ---------------------------------------------------------------------
-- REVIEWS (barbeiro XOR serviço). created_at = data da avaliação.
-- ---------------------------------------------------------------------
INSERT INTO reviews (id, reviewer_id, barber_id, service_id, rating, comment, created_at) VALUES
 (1,  5, 2,    NULL, 5, 'Excelente fade.',            '2026-09-04 12:00'),
 (2,  8, 2,    NULL, 4, NULL,                          '2026-09-09 12:00'),
 (3,  6, 3,    NULL, 5, 'Muito atencioso.',            '2026-09-05 12:00'),
 (4,  10, 3,   NULL, 4, NULL,                          '2026-09-13 12:00'),
 (5,  7, 4,    NULL, 4, 'Bom corte.',                  '2026-09-08 12:00'),
 (6,  9, 4,    NULL, 3, NULL,                          '2026-09-10 12:00'),
 (7,  5, 4,    NULL, 2, 'Demorou mais do que o previsto.', '2026-10-17 12:00'),  -- fora do trimestre
 (8,  9, 2,    NULL, 5, 'Ótimo atendimento.',          '2026-07-20 12:00'),
 (9,  4, 2,    NULL, 5, NULL,                          '2026-09-26 12:00'),       -- barbeiro a avaliar barbeiro
 (10, 5, NULL, 2,    5, 'Melhor fade da cidade.',      '2026-09-04 12:00'),
 (11, 8, NULL, 5,    4, NULL,                          '2026-09-09 12:00'),
 (12, 6, NULL, 3,    5, NULL,                          '2026-09-05 12:00'),
 (13, 9, NULL, 4,    3, 'Poderia ser mais rápido.',    '2026-09-10 12:00'),
 (14, 7, NULL, 1,    4, NULL,                          '2026-10-14 12:00');

-- ---------------------------------------------------------------------
-- Realinhar as sequências (os IDs acima foram inseridos explicitamente)
-- ---------------------------------------------------------------------
SELECT setval(pg_get_serial_sequence('users', 'id'),              (SELECT max(id) FROM users));
SELECT setval(pg_get_serial_sequence('services', 'id'),           (SELECT max(id) FROM services));
SELECT setval(pg_get_serial_sequence('working_hours', 'id'),      (SELECT coalesce(max(id), 1) FROM working_hours));
SELECT setval(pg_get_serial_sequence('schedule_blocks', 'id'),    (SELECT max(id) FROM schedule_blocks));
SELECT setval(pg_get_serial_sequence('subscription_plans', 'id'), (SELECT max(id) FROM subscription_plans));
SELECT setval(pg_get_serial_sequence('subscriptions', 'id'),      (SELECT max(id) FROM subscriptions));
SELECT setval(pg_get_serial_sequence('appointments', 'id'),       (SELECT max(id) FROM appointments));
SELECT setval(pg_get_serial_sequence('payments', 'id'),           (SELECT max(id) FROM payments));
SELECT setval(pg_get_serial_sequence('reviews', 'id'),            (SELECT max(id) FROM reviews));

COMMIT;
