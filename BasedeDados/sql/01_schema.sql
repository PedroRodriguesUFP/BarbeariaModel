-- =========================================================
-- BARBEARIA MODEL
-- Fase 2 - Schema Relacional PostgreSQL
-- Ficheiro: sql/01_schema.sql
-- =========================================================

BEGIN;

-- =========================================================
-- 1. USERS
-- =========================================================

CREATE TABLE users (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,

    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(30),
    bio TEXT,

    password VARCHAR(255) NOT NULL,

    role VARCHAR(20) NOT NULL
        CHECK (role IN ('admin', 'barber', 'client')),

    active BOOLEAN NOT NULL DEFAULT TRUE,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);


-- =========================================================
-- 2. SERVICES
-- =========================================================

CREATE TABLE services (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,

    name VARCHAR(255) NOT NULL,
    description TEXT,

    price NUMERIC(8, 2) NOT NULL
        CHECK (price >= 0),

    duration_minutes INTEGER NOT NULL
        CHECK (duration_minutes > 0),

    category VARCHAR(30) NOT NULL
        CHECK (
            category IN (
                'hair',
                'beard',
                'hair and beard',
                'fade',
                'coloring'
            )
        ),

    is_active BOOLEAN NOT NULL DEFAULT TRUE,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);


-- =========================================================
-- 3. SERVICE_USER
-- Serviços que cada barbeiro está habilitado a realizar.
-- Relação N:M entre users e services.
-- =========================================================

CREATE TABLE service_user (
    user_id BIGINT NOT NULL,
    service_id BIGINT NOT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (user_id, service_id),

    CONSTRAINT fk_service_user_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_service_user_service
        FOREIGN KEY (service_id)
        REFERENCES services(id)
        ON DELETE CASCADE
);


-- =========================================================
-- 4. WORKING_HOURS
-- Horário semanal normal de cada barbeiro.
--
-- day_of_week:
-- 1 = segunda
-- 2 = terça
-- ...
-- 7 = domingo
-- =========================================================

CREATE TABLE working_hours (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,

    user_id BIGINT NOT NULL,

    day_of_week SMALLINT NOT NULL
        CHECK (day_of_week BETWEEN 1 AND 7),

    start_time TIME NOT NULL,
    end_time TIME NOT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT chk_working_hours_interval
        CHECK (start_time < end_time),

    CONSTRAINT fk_working_hours_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT uq_working_hours_period
        UNIQUE (
            user_id,
            day_of_week,
            start_time,
            end_time
        )
);

CREATE INDEX idx_working_hours_user_day
    ON working_hours(user_id, day_of_week);


-- =========================================================
-- 5. SCHEDULE_BLOCKS
-- Exceções ao horário semanal:
-- férias, consultas, ausências, etc.
-- =========================================================

CREATE TABLE schedule_blocks (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,

    user_id BIGINT NOT NULL,

    block_date DATE NOT NULL,

    start_time TIME,
    end_time TIME,

    all_day BOOLEAN NOT NULL DEFAULT FALSE,

    reason VARCHAR(255),

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_schedule_blocks_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT chk_schedule_block_times
        CHECK (
            (
                all_day = TRUE
                AND start_time IS NULL
                AND end_time IS NULL
            )
            OR
            (
                all_day = FALSE
                AND start_time IS NOT NULL
                AND end_time IS NOT NULL
                AND start_time < end_time
            )
        )
);

CREATE INDEX idx_schedule_blocks_user_date
    ON schedule_blocks(user_id, block_date);


-- =========================================================
-- 6. SUBSCRIPTION_PLANS
-- Planos mensais disponíveis.
-- =========================================================

CREATE TABLE subscription_plans (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,

    name VARCHAR(255) NOT NULL UNIQUE,

    description TEXT,

    monthly_price NUMERIC(8, 2) NOT NULL
        CHECK (monthly_price >= 0),

    cuts_per_month INTEGER NOT NULL
        CHECK (cuts_per_month > 0),

    active BOOLEAN NOT NULL DEFAULT TRUE,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);


-- =========================================================
-- 7. SUBSCRIPTIONS
-- Adesão de um utilizador a um plano mensal.
-- =========================================================

CREATE TABLE subscriptions (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,

    user_id BIGINT NOT NULL,
    subscription_plan_id BIGINT NOT NULL,

    start_date DATE NOT NULL,
    end_date DATE,

    status VARCHAR(20) NOT NULL
        DEFAULT 'active'
        CHECK (
            status IN (
                'active',
                'suspended',
                'cancelled',
                'expired'
            )
        ),

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_subscriptions_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_subscriptions_plan
        FOREIGN KEY (subscription_plan_id)
        REFERENCES subscription_plans(id)
        ON DELETE RESTRICT,

    CONSTRAINT chk_subscription_dates
        CHECK (
            end_date IS NULL
            OR end_date >= start_date
        ),

    -- Necessário para permitir à appointment confirmar
    -- que a subscrição pertence ao customer.
    CONSTRAINT uq_subscription_id_user
        UNIQUE (id, user_id)
);


-- Um utilizador só pode ter uma subscrição marcada
-- como ativa em simultâneo.
CREATE UNIQUE INDEX uq_one_active_subscription_per_user
    ON subscriptions(user_id)
    WHERE status = 'active';


-- =========================================================
-- 8. APPOINTMENTS
-- Marcações entre um utilizador e um barbeiro.
-- =========================================================

CREATE TABLE appointments (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,

    customer_id BIGINT NOT NULL,
    barber_id BIGINT NOT NULL,
    service_id BIGINT NOT NULL,

    -- NULL = serviço avulso.
    -- preenchido = serviço coberto por subscrição.
    subscription_id BIGINT,

    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,

    status VARCHAR(20) NOT NULL
        DEFAULT 'pending'
        CHECK (
            status IN (
                'pending',
                'confirmed',
                'cancelled',
                'completed'
            )
        ),

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_appointments_customer
        FOREIGN KEY (customer_id)
        REFERENCES users(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_appointments_barber
        FOREIGN KEY (barber_id)
        REFERENCES users(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_appointments_service
        FOREIGN KEY (service_id)
        REFERENCES services(id)
        ON DELETE RESTRICT,

    -- Garante RN03:
    -- o barbeiro só pode receber uma marcação para
    -- um serviço associado a ele em service_user.
    CONSTRAINT fk_appointments_barber_service
        FOREIGN KEY (barber_id, service_id)
        REFERENCES service_user(user_id, service_id)
        ON DELETE RESTRICT,

    -- Se a marcação usar uma subscrição, garante que
    -- essa subscrição pertence ao próprio cliente.
    CONSTRAINT fk_appointments_subscription_customer
        FOREIGN KEY (subscription_id, customer_id)
        REFERENCES subscriptions(id, user_id)
        ON DELETE RESTRICT,

    CONSTRAINT chk_customer_not_barber
        CHECK (customer_id <> barber_id)
);

CREATE INDEX idx_appointments_barber_date
    ON appointments(barber_id, appointment_date);

CREATE INDEX idx_appointments_customer_date
    ON appointments(customer_id, appointment_date);

CREATE INDEX idx_appointments_status
    ON appointments(status);


-- =========================================================
-- 9. PAYMENTS
--
-- RN04:
-- Um pagamento pertence exatamente a:
--   OU uma appointment
--   OU uma subscription
-- Nunca às duas.
-- =========================================================

CREATE TABLE payments (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,

    appointment_id BIGINT,
    subscription_id BIGINT,

    amount NUMERIC(8, 2) NOT NULL
        CHECK (amount >= 0),

    method VARCHAR(20) NOT NULL
        CHECK (
            method IN (
                'cash',
                'card',
                'mbway',
                'transfer'
            )
        ),

    status VARCHAR(20) NOT NULL
        DEFAULT 'pending'
        CHECK (
            status IN (
                'pending',
                'paid',
                'failed',
                'refunded'
            )
        ),

    paid_at TIMESTAMP,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_payments_appointment
        FOREIGN KEY (appointment_id)
        REFERENCES appointments(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_payments_subscription
        FOREIGN KEY (subscription_id)
        REFERENCES subscriptions(id)
        ON DELETE RESTRICT,

    CONSTRAINT chk_payment_single_origin
        CHECK (
            (appointment_id IS NOT NULL)
            <>
            (subscription_id IS NOT NULL)
        ),

    CONSTRAINT chk_payment_paid_date
        CHECK (
            status <> 'paid'
            OR paid_at IS NOT NULL
        )
);

CREATE INDEX idx_payments_appointment
    ON payments(appointment_id);

CREATE INDEX idx_payments_subscription
    ON payments(subscription_id);

CREATE INDEX idx_payments_status
    ON payments(status);


-- =========================================================
-- 10. REVIEWS
--
-- RN06:
-- Uma avaliação é exatamente sobre:
--   OU um barbeiro
--   OU um serviço.
-- =========================================================

CREATE TABLE reviews (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,

    reviewer_id BIGINT NOT NULL,

    barber_id BIGINT,
    service_id BIGINT,

    rating SMALLINT NOT NULL
        CHECK (rating BETWEEN 1 AND 5),

    comment TEXT,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_reviews_reviewer
        FOREIGN KEY (reviewer_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_reviews_barber
        FOREIGN KEY (barber_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_reviews_service
        FOREIGN KEY (service_id)
        REFERENCES services(id)
        ON DELETE CASCADE,

    CONSTRAINT chk_review_single_target
        CHECK (
            (barber_id IS NOT NULL)
            <>
            (service_id IS NOT NULL)
        ),

    CONSTRAINT chk_reviewer_not_same_barber
        CHECK (
            barber_id IS NULL
            OR reviewer_id <> barber_id
        )
);

CREATE INDEX idx_reviews_barber
    ON reviews(barber_id);

CREATE INDEX idx_reviews_service
    ON reviews(service_id);

CREATE INDEX idx_reviews_reviewer
    ON reviews(reviewer_id);


COMMIT;