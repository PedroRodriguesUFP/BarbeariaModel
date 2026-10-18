-- =========================================================
-- BARBEARIA MODEL
-- Fase 2 - Seed de dados de teste
-- Ficheiro: BasedeDados/sql/02_seed.sql
--
-- Executar depois de 01_schema.sql.
-- Password de todas as contas: password
-- =========================================================

BEGIN;

-- =========================================================
-- 1. USERS
-- =========================================================

INSERT INTO users (
    name,
    email,
    phone,
    bio,
    password,
    role,
    active
)
VALUES
    (
        'Administrador',
        'admin@barbeariamodel.pt',
        '910000001',
        'Conta de administração para testes.',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'admin',
        TRUE
    ),
    (
        'Tiago Costa',
        'tiago.barber@barbeariamodel.pt',
        '910000002',
        'Barbeiro especializado em fades.',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'barber',
        TRUE
    ),
    (
        'Miguel Sousa',
        'miguel.barber@barbeariamodel.pt',
        '910000003',
        'Barbeiro especializado em barba e cabelo.',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'barber',
        TRUE
    ),
    (
        'João Silva',
        'joao.cliente@barbeariamodel.pt',
        '910000004',
        NULL,
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'client',
        TRUE
    ),
    (
        'Rafael Ferreira',
        'rafael.cliente@barbeariamodel.pt',
        '910000005',
        NULL,
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'client',
        TRUE
    ),
    (
        'André Martins',
        'andre.cliente@barbeariamodel.pt',
        '910000006',
        NULL,
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'client',
        TRUE
    ),
    (
        'Diogo Ribeiro',
        'diogo.cliente@barbeariamodel.pt',
        '910000007',
        NULL,
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'client',
        TRUE
    )
ON CONFLICT (email) DO NOTHING;


-- =========================================================
-- 2. SERVICES
-- =========================================================

INSERT INTO services (
    name,
    description,
    price,
    duration_minutes,
    category,
    is_active
)
SELECT
    v.name,
    v.description,
    v.price,
    v.duration_minutes,
    v.category,
    TRUE
FROM (
    VALUES
        (
            'Corte clássico',
            'Corte tradicional à tesoura ou máquina.',
            12.00::NUMERIC,
            30,
            'hair'
        ),
        (
            'Fade',
            'Degradê com acabamento detalhado.',
            15.00::NUMERIC,
            40,
            'fade'
        ),
        (
            'Corte e barba',
            'Corte de cabelo e tratamento da barba.',
            20.00::NUMERIC,
            50,
            'hair and beard'
        ),
        (
            'Aparar barba',
            'Aparar e alinhar a barba.',
            8.00::NUMERIC,
            20,
            'beard'
        ),
        (
            'Coloração',
            'Serviço de coloração capilar.',
            25.00::NUMERIC,
            60,
            'coloring'
        )
) AS v(
    name,
    description,
    price,
    duration_minutes,
    category
)
WHERE NOT EXISTS (
    SELECT 1
    FROM services s
    WHERE s.name = v.name
);


-- =========================================================
-- 3. SERVICE_USER
-- =========================================================

INSERT INTO service_user (
    user_id,
    service_id
)
SELECT
    u.id,
    s.id
FROM users u
JOIN services s
    ON (
        u.email = 'tiago.barber@barbeariamodel.pt'
        AND s.name IN (
            'Corte clássico',
            'Fade',
            'Corte e barba'
        )
    )
    OR (
        u.email = 'miguel.barber@barbeariamodel.pt'
        AND s.name IN (
            'Corte clássico',
            'Corte e barba',
            'Aparar barba',
            'Coloração'
        )
    )
WHERE u.role = 'barber'
ON CONFLICT (user_id, service_id) DO NOTHING;


-- =========================================================
-- 4. WORKING_HOURS
-- 1 = segunda ... 7 = domingo
-- =========================================================

INSERT INTO working_hours (
    user_id,
    day_of_week,
    start_time,
    end_time
)
SELECT
    u.id,
    v.day_of_week,
    v.start_time::TIME,
    v.end_time::TIME
FROM users u
CROSS JOIN (
    VALUES
        (1, '09:00', '18:00'),
        (2, '09:00', '18:00'),
        (3, '09:00', '18:00'),
        (4, '09:00', '18:00'),
        (5, '09:00', '18:00')
) AS v(
    day_of_week,
    start_time,
    end_time
)
WHERE u.email IN (
    'tiago.barber@barbeariamodel.pt',
    'miguel.barber@barbeariamodel.pt'
)
ON CONFLICT (
    user_id,
    day_of_week,
    start_time,
    end_time
) DO NOTHING;


-- =========================================================
-- 5. SCHEDULE_BLOCKS
-- =========================================================

INSERT INTO schedule_blocks (
    user_id,
    block_date,
    start_time,
    end_time,
    all_day,
    reason
)
SELECT
    u.id,
    CURRENT_DATE + 14,
    NULL,
    NULL,
    TRUE,
    'Ausência para teste'
FROM users u
WHERE
    u.email = 'tiago.barber@barbeariamodel.pt'
    AND NOT EXISTS (
        SELECT 1
        FROM schedule_blocks sb
        WHERE
            sb.user_id = u.id
            AND sb.block_date = CURRENT_DATE + 14
            AND sb.all_day = TRUE
    );


-- =========================================================
-- 6. SUBSCRIPTION_PLANS
-- =========================================================

INSERT INTO subscription_plans (
    name,
    description,
    monthly_price,
    cuts_per_month,
    active
)
SELECT
    v.name,
    v.description,
    v.monthly_price,
    v.cuts_per_month,
    TRUE
FROM (
    VALUES
        (
            'Plano Essencial',
            'Inclui dois cortes por mês.',
            19.90::NUMERIC,
            2
        ),
        (
            'Plano Premium',
            'Inclui quatro cortes por mês.',
            34.90::NUMERIC,
            4
        )
) AS v(
    name,
    description,
    monthly_price,
    cuts_per_month
)
WHERE NOT EXISTS (
    SELECT 1
    FROM subscription_plans sp
    WHERE sp.name = v.name
);


-- =========================================================
-- 7. SUBSCRIPTIONS
-- =========================================================

INSERT INTO subscriptions (
    user_id,
    subscription_plan_id,
    start_date,
    end_date,
    status
)
SELECT
    u.id,
    sp.id,
    CURRENT_DATE,
    CURRENT_DATE + 30,
    'active'
FROM users u
JOIN subscription_plans sp
    ON sp.name = 'Plano Essencial'
WHERE
    u.email = 'joao.cliente@barbeariamodel.pt'
    AND NOT EXISTS (
        SELECT 1
        FROM subscriptions s
        WHERE
            s.user_id = u.id
            AND s.status = 'active'
    );


INSERT INTO subscriptions (
    user_id,
    subscription_plan_id,
    start_date,
    end_date,
    status
)
SELECT
    u.id,
    sp.id,
    CURRENT_DATE,
    CURRENT_DATE + 30,
    'active'
FROM users u
JOIN subscription_plans sp
    ON sp.name = 'Plano Premium'
WHERE
    u.email = 'rafael.cliente@barbeariamodel.pt'
    AND NOT EXISTS (
        SELECT 1
        FROM subscriptions s
        WHERE
            s.user_id = u.id
            AND s.status = 'active'
    );


-- =========================================================
-- 8. APPOINTMENTS
-- =========================================================

-- João:
-- marcação futura com Tiago para Corte clássico.
INSERT INTO appointments (
    customer_id,
    barber_id,
    service_id,
    subscription_id,
    appointment_date,
    appointment_time,
    status
)
SELECT
    customer.id,
    barber.id,
    service.id,
    subscription.id,
    date_trunc('week', CURRENT_DATE)::DATE + 7,
    TIME '10:00',
    'confirmed'
FROM users customer
JOIN users barber
    ON barber.email = 'tiago.barber@barbeariamodel.pt'
JOIN services service
    ON service.name = 'Corte clássico'
JOIN subscriptions subscription
    ON subscription.user_id = customer.id
    AND subscription.status = 'active'
WHERE
    customer.email = 'joao.cliente@barbeariamodel.pt'
    AND NOT EXISTS (
        SELECT 1
        FROM appointments a
        WHERE
            a.customer_id = customer.id
            AND a.barber_id = barber.id
            AND a.appointment_date =
                date_trunc('week', CURRENT_DATE)::DATE + 7
            AND a.appointment_time = TIME '10:00'
    );


-- Rafael:
-- marcação já concluída com Miguel.
INSERT INTO appointments (
    customer_id,
    barber_id,
    service_id,
    subscription_id,
    appointment_date,
    appointment_time,
    status
)
SELECT
    customer.id,
    barber.id,
    service.id,
    NULL,
    date_trunc('week', CURRENT_DATE)::DATE - 7,
    TIME '11:00',
    'completed'
FROM users customer
JOIN users barber
    ON barber.email = 'miguel.barber@barbeariamodel.pt'
JOIN services service
    ON service.name = 'Corte e barba'
WHERE
    customer.email = 'rafael.cliente@barbeariamodel.pt'
    AND NOT EXISTS (
        SELECT 1
        FROM appointments a
        WHERE
            a.customer_id = customer.id
            AND a.barber_id = barber.id
            AND a.appointment_date =
                date_trunc('week', CURRENT_DATE)::DATE - 7
            AND a.appointment_time = TIME '11:00'
    );


-- Diogo:
-- marcação pendente para Fade.
INSERT INTO appointments (
    customer_id,
    barber_id,
    service_id,
    subscription_id,
    appointment_date,
    appointment_time,
    status
)
SELECT
    customer.id,
    barber.id,
    service.id,
    NULL,
    date_trunc('week', CURRENT_DATE)::DATE + 8,
    TIME '14:00',
    'pending'
FROM users customer
JOIN users barber
    ON barber.email = 'tiago.barber@barbeariamodel.pt'
JOIN services service
    ON service.name = 'Fade'
WHERE
    customer.email = 'diogo.cliente@barbeariamodel.pt'
    AND NOT EXISTS (
        SELECT 1
        FROM appointments a
        WHERE
            a.customer_id = customer.id
            AND a.barber_id = barber.id
            AND a.appointment_date =
                date_trunc('week', CURRENT_DATE)::DATE + 8
            AND a.appointment_time = TIME '14:00'
    );


-- =========================================================
-- 9. PAYMENTS
-- =========================================================

-- Pagamento de uma marcação.
INSERT INTO payments (
    appointment_id,
    subscription_id,
    amount,
    method,
    status,
    paid_at
)
SELECT
    a.id,
    NULL,
    service.price,
    'card',
    'paid',
    CURRENT_TIMESTAMP
FROM appointments a
JOIN users customer
    ON customer.id = a.customer_id
JOIN services service
    ON service.id = a.service_id
WHERE
    customer.email = 'rafael.cliente@barbeariamodel.pt'
    AND a.status = 'completed'
    AND NOT EXISTS (
        SELECT 1
        FROM payments p
        WHERE p.appointment_id = a.id
    );


-- Pagamento de uma subscrição.
INSERT INTO payments (
    appointment_id,
    subscription_id,
    amount,
    method,
    status,
    paid_at
)
SELECT
    NULL,
    subscription.id,
    plan.monthly_price,
    'mbway',
    'paid',
    CURRENT_TIMESTAMP
FROM subscriptions subscription
JOIN users u
    ON u.id = subscription.user_id
JOIN subscription_plans plan
    ON plan.id = subscription.subscription_plan_id
WHERE
    u.email = 'joao.cliente@barbeariamodel.pt'
    AND subscription.status = 'active'
    AND NOT EXISTS (
        SELECT 1
        FROM payments p
        WHERE p.subscription_id = subscription.id
    );


-- =========================================================
-- 10. REVIEWS
-- =========================================================

-- Avaliação de um barbeiro.
INSERT INTO reviews (
    reviewer_id,
    barber_id,
    service_id,
    rating,
    comment
)
SELECT
    reviewer.id,
    barber.id,
    NULL,
    5,
    'Excelente atendimento e resultado.'
FROM users reviewer
JOIN users barber
    ON barber.email = 'miguel.barber@barbeariamodel.pt'
WHERE
    reviewer.email = 'rafael.cliente@barbeariamodel.pt'
    AND NOT EXISTS (
        SELECT 1
        FROM reviews r
        WHERE
            r.reviewer_id = reviewer.id
            AND r.barber_id = barber.id
    );


-- Avaliação de um serviço.
INSERT INTO reviews (
    reviewer_id,
    barber_id,
    service_id,
    rating,
    comment
)
SELECT
    reviewer.id,
    NULL,
    service.id,
    4,
    'Bom serviço e boa relação qualidade/preço.'
FROM users reviewer
JOIN services service
    ON service.name = 'Corte clássico'
WHERE
    reviewer.email = 'joao.cliente@barbeariamodel.pt'
    AND NOT EXISTS (
        SELECT 1
        FROM reviews r
        WHERE
            r.reviewer_id = reviewer.id
            AND r.service_id = service.id
    );


COMMIT;