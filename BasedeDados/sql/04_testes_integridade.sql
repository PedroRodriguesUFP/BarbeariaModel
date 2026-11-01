-- =====================================================================
-- BarbeariaModel — BDAD 2026/27 (UFP) — Fase 2 (v2.0)
-- 04_testes_integridade.sql — testes das restrições declarativas
--
-- Cada teste tenta uma operação e compara o resultado com o esperado:
--   * testes NEGATIVOS: a operação tem de ser recusada com o SQLSTATE e a
--     restrição indicados (assim garante-se que falha pela razão certa);
--   * testes POSITIVOS ('aceite'): a operação válida tem de ser aceite.
-- Tudo corre numa transação revertida (ROLLBACK): não altera os dados.
-- Pressupõe 01_schema.sql + 02_seed.sql carregados.
-- Uso:  psql -d <bd> -f sql/04_testes_integridade.sql
-- =====================================================================

BEGIN;

CREATE TEMP TABLE res (
    n         serial,
    regra     text,
    teste     text,
    esperado  text,
    observado text,
    ok        boolean
);

CREATE FUNCTION pg_temp.t(p_regra text, p_teste text, p_sql text, p_esperado text)
RETURNS void LANGUAGE plpgsql AS $$
DECLARE
    v_obs   text;
    v_state text;
    v_cons  text;
    v_col   text;
BEGIN
    BEGIN
        EXECUTE p_sql;
        v_obs := 'aceite';
        RAISE EXCEPTION '__desfazer__';          -- desfaz o efeito do teste
    EXCEPTION WHEN OTHERS THEN
        IF SQLERRM <> '__desfazer__' THEN
            GET STACKED DIAGNOSTICS v_state = RETURNED_SQLSTATE,
                                    v_cons  = CONSTRAINT_NAME,
                                    v_col   = COLUMN_NAME;
            v_obs := v_state || ' ' || COALESCE(NULLIF(v_cons, ''), NULLIF(v_col, ''), '-');
        END IF;
    END;
    INSERT INTO res (regra, teste, esperado, observado, ok)
    VALUES (p_regra, p_teste, p_esperado, v_obs, v_obs = p_esperado);
END $$;

\o /dev/null

-- ===================== USERS =====================
SELECT pg_temp.t('RN01', 'e-mail duplicado',
  $q$INSERT INTO users (name, email, role) VALUES ('X', 'admin@barbeariamodel.pt', 'client')$q$,
  '23505 uq_users_email');
SELECT pg_temp.t('RD01', 'role inválido',
  $q$INSERT INTO users (name, email, role) VALUES ('X', 'x1@x.pt', 'visitante')$q$,
  '23514 ck_users_role');
SELECT pg_temp.t('RD01', 'role em português (enum é o do Laravel)',
  $q$INSERT INTO users (name, email, role) VALUES ('X', 'x2@x.pt', 'cliente')$q$,
  '23514 ck_users_role');
SELECT pg_temp.t('RD01', 'name NULL',
  $q$INSERT INTO users (name, email, role) VALUES (NULL, 'x3@x.pt', 'client')$q$,
  '23502 name');
SELECT pg_temp.t('RD01', 'e-mail sem formato válido',
  $q$INSERT INTO users (name, email, role) VALUES ('X', 'sem-arroba', 'client')$q$,
  '23514 ck_users_email');

-- ===================== SERVICES =====================
SELECT pg_temp.t('RD04', 'preço negativo',
  $q$INSERT INTO services (name, price, duration_minutes, category) VALUES ('T', -1, 10, 'hair')$q$,
  '23514 ck_services_price');
SELECT pg_temp.t('RD04', 'duração 0',
  $q$INSERT INTO services (name, price, duration_minutes, category) VALUES ('T', 1, 0, 'hair')$q$,
  '23514 ck_services_duration');
SELECT pg_temp.t('RD04', 'categoria inexistente',
  $q$INSERT INTO services (name, price, duration_minutes, category) VALUES ('T', 1, 10, 'massagem')$q$,
  '23514 ck_services_category');
SELECT pg_temp.t('RD04', 'categoria em português',
  $q$INSERT INTO services (name, price, duration_minutes, category) VALUES ('T', 1, 10, 'cabelo')$q$,
  '23514 ck_services_category');

-- ===================== RN02: só barbeiros =====================
SELECT pg_temp.t('RN02', 'horário semanal de um cliente',
  $q$INSERT INTO working_hours (user_id, day_of_week, start_time, end_time) VALUES (5, 1, '09:00', '17:00')$q$,
  '23503 fk_working_hours_barber');
SELECT pg_temp.t('RN02', 'bloqueio de agenda de um cliente',
  $q$INSERT INTO schedule_blocks (user_id, date, all_day) VALUES (5, '2026-11-20', TRUE)$q$,
  '23503 fk_schedule_blocks_barber');
SELECT pg_temp.t('RN02', 'habilitação de serviço para um cliente',
  $q$INSERT INTO service_user (user_id, service_id) VALUES (5, 1)$q$,
  '23503 fk_service_user_barber');
SELECT pg_temp.t('RN02', 'horário semanal do administrador',
  $q$INSERT INTO working_hours (user_id, day_of_week, start_time, end_time) VALUES (1, 1, '09:00', '17:00')$q$,
  '23503 fk_working_hours_barber');

-- ===================== RD03: horários =====================
SELECT pg_temp.t('RD03', 'turno com fim antes do início',
  $q$INSERT INTO working_hours (user_id, day_of_week, start_time, end_time) VALUES (2, 1, '18:00', '10:00')$q$,
  '23514 ck_working_hours_interval');
SELECT pg_temp.t('RD03', 'dia da semana 8',
  $q$INSERT INTO working_hours (user_id, day_of_week, start_time, end_time) VALUES (2, 8, '09:00', '17:00')$q$,
  '23514 ck_working_hours_day');
SELECT pg_temp.t('RD03', 'período de horário duplicado',
  $q$INSERT INTO working_hours (user_id, day_of_week, start_time, end_time) VALUES (2, 2, '10:00', '19:00')$q$,
  '23505 uq_working_hours_period');
SELECT pg_temp.t('RD03', 'bloqueio de dia inteiro com horas',
  $q$INSERT INTO schedule_blocks (user_id, date, start_time, end_time, all_day) VALUES (2, '2026-11-20', '09:00', '10:00', TRUE)$q$,
  '23514 ck_schedule_block_times');
SELECT pg_temp.t('RD03', 'bloqueio parcial sem hora de fim',
  $q$INSERT INTO schedule_blocks (user_id, date, start_time, all_day) VALUES (2, '2026-11-20', '09:00', FALSE)$q$,
  '23514 ck_schedule_block_times');

-- ===================== RN03: habilitação (sem trigger) =====================
SELECT pg_temp.t('RN03', 'barbeiro não habilitado (Miguel não faz Fade)',
  $q$INSERT INTO appointments (customer_id, barber_id, service_id, date, time) VALUES (9, 3, 2, '2026-11-10', '10:00')$q$,
  '23503 fk_appointments_barber_service');
SELECT pg_temp.t('RN03', 'serviço sem barbeiros habilitados (serviço 6)',
  $q$INSERT INTO appointments (customer_id, barber_id, service_id, date, time) VALUES (9, 2, 6, '2026-11-10', '10:00')$q$,
  '23503 fk_appointments_barber_service');
SELECT pg_temp.t('RN03', 'cliente a "executar" a marcação (não é barbeiro)',
  $q$INSERT INTO appointments (customer_id, barber_id, service_id, date, time) VALUES (9, 6, 1, '2026-11-10', '10:00')$q$,
  '23503 fk_appointments_barber_service');

-- ===================== RD05 / RN07 / RN09: marcações =====================
SELECT pg_temp.t('RD05', 'estado inválido',
  $q$INSERT INTO appointments (customer_id, barber_id, service_id, date, time, status) VALUES (9, 2, 1, '2026-11-10', '10:00', 'adiada')$q$,
  '23514 ck_appointments_status');
SELECT pg_temp.t('RD05', 'estado em português',
  $q$INSERT INTO appointments (customer_id, barber_id, service_id, date, time, status) VALUES (9, 2, 1, '2026-11-10', '10:00', 'pendente')$q$,
  '23514 ck_appointments_status');
SELECT pg_temp.t('RD05', 'hora NULL',
  $q$INSERT INTO appointments (customer_id, barber_id, service_id, date, time) VALUES (9, 2, 1, '2026-11-10', NULL)$q$,
  '23502 time');
SELECT pg_temp.t('RD05', 'sem cliente e sem nome walk-in',
  $q$INSERT INTO appointments (barber_id, service_id, date, time) VALUES (2, 1, '2026-11-10', '10:00')$q$,
  '23514 ck_appointments_customer_or_walkin');
SELECT pg_temp.t('RN07', 'mesmo barbeiro, mesma hora, marcação ativa',
  $q$INSERT INTO appointments (customer_id, barber_id, service_id, date, time) VALUES (9, 2, 1, '2026-10-31', '10:00')$q$,
  '23505 uq_appointments_barber_slot');
SELECT pg_temp.t('RN09', 'barbeiro a marcar consigo próprio',
  $q$INSERT INTO appointments (customer_id, barber_id, service_id, date, time) VALUES (2, 2, 1, '2026-11-10', '10:00')$q$,
  '23514 ck_appointments_customer_not_barber');
SELECT pg_temp.t('RN05', 'subscrição de outro cliente',
  $q$INSERT INTO appointments (customer_id, barber_id, service_id, subscription_id, date, time) VALUES (8, 2, 1, 1, '2026-11-10', '10:00')$q$,
  '23503 fk_appointments_subscription_customer');
SELECT pg_temp.t('RN05', 'marcação walk-in coberta por subscrição',
  $q$INSERT INTO appointments (walk_in_name, barber_id, service_id, subscription_id, date, time) VALUES ('X', 2, 1, 1, '2026-11-10', '10:00')$q$,
  '23514 ck_appointments_subscription_needs_customer');

-- ===================== RD06 / RD07 / RN08: subscrições =====================
SELECT pg_temp.t('RD06', 'plano com 0 cortes por mês',
  $q$INSERT INTO subscription_plans (name, monthly_price, cuts_per_month) VALUES ('T', 10, 0)$q$,
  '23514 ck_plans_cuts');
SELECT pg_temp.t('RD07', 'estado de subscrição inválido',
  $q$INSERT INTO subscriptions (user_id, subscription_plan_id, start_date, status) VALUES (9, 1, '2026-10-01', 'pausada')$q$,
  '23514 ck_subscriptions_status');
SELECT pg_temp.t('RD07', 'data de fim anterior ao início',
  $q$INSERT INTO subscriptions (user_id, subscription_plan_id, start_date, end_date, status) VALUES (9, 1, '2026-10-10', '2026-10-01', 'cancelled')$q$,
  '23514 ck_subscriptions_dates');
SELECT pg_temp.t('RN08', '2.ª subscrição ativa do mesmo utilizador',
  $q$INSERT INTO subscriptions (user_id, subscription_plan_id, start_date, status) VALUES (5, 1, '2026-10-01', 'active')$q$,
  '23505 uq_one_active_subscription_per_user');

-- ===================== RN04 / RD08: pagamentos =====================
SELECT pg_temp.t('RN04', 'pagamento com marcação E subscrição',
  $q$INSERT INTO payments (appointment_id, subscription_id, amount, method, status, paid_at) VALUES (4, 1, 10, 'card', 'paid', now())$q$,
  '23514 ck_payments_single_origin');
SELECT pg_temp.t('RN04', 'pagamento sem origem',
  $q$INSERT INTO payments (amount, method, status) VALUES (10, 'card', 'pending')$q$,
  '23514 ck_payments_single_origin');
SELECT pg_temp.t('RD08', 'estado "paid" sem paid_at',
  $q$INSERT INTO payments (appointment_id, amount, method, status) VALUES (4, 10, 'card', 'paid')$q$,
  '23514 ck_payments_paid_at');
SELECT pg_temp.t('RD08', 'método em português',
  $q$INSERT INTO payments (appointment_id, amount, method) VALUES (4, 10, 'numerario')$q$,
  '23514 ck_payments_method');
SELECT pg_temp.t('RD08', 'montante negativo',
  $q$INSERT INTO payments (appointment_id, amount, method) VALUES (4, -5, 'cash')$q$,
  '23514 ck_payments_amount');

-- ===================== RN06 / RD09: avaliações =====================
SELECT pg_temp.t('RN06', 'avaliação a barbeiro E serviço',
  $q$INSERT INTO reviews (reviewer_id, barber_id, service_id, rating) VALUES (5, 3, 1, 5)$q$,
  '23514 ck_reviews_single_target');
SELECT pg_temp.t('RN06', 'avaliação sem alvo',
  $q$INSERT INTO reviews (reviewer_id, rating) VALUES (5, 5)$q$,
  '23514 ck_reviews_single_target');
SELECT pg_temp.t('RD09', 'classificação 6',
  $q$INSERT INTO reviews (reviewer_id, barber_id, rating) VALUES (5, 3, 6)$q$,
  '23514 ck_reviews_rating');
SELECT pg_temp.t('RN06', 'avaliar como "barbeiro" um cliente',
  $q$INSERT INTO reviews (reviewer_id, barber_id, rating) VALUES (5, 6, 4)$q$,
  '23503 fk_reviews_barber');
SELECT pg_temp.t('RN06', 'barbeiro a avaliar-se a si próprio',
  $q$INSERT INTO reviews (reviewer_id, barber_id, rating) VALUES (2, 2, 5)$q$,
  '23514 ck_reviews_not_self');

-- ===================== TESTES POSITIVOS =====================
SELECT pg_temp.t('RD05', 'marcação válida (barbeiro habilitado)',
  $q$INSERT INTO appointments (customer_id, barber_id, service_id, date, time) VALUES (9, 4, 1, '2026-11-10', '10:00')$q$,
  'aceite');
SELECT pg_temp.t('RN09', 'barbeiro como cliente de outro barbeiro',
  $q$INSERT INTO appointments (customer_id, barber_id, service_id, date, time) VALUES (2, 4, 2, '2026-11-11', '10:00')$q$,
  'aceite');
SELECT pg_temp.t('RD05', 'marcação walk-in sem conta',
  $q$INSERT INTO appointments (walk_in_name, barber_id, service_id, date, time) VALUES ('Cliente de passagem', 2, 1, '2026-11-12', '10:00')$q$,
  'aceite');
SELECT pg_temp.t('RN05', 'marcação coberta pela subscrição do próprio cliente',
  $q$INSERT INTO appointments (customer_id, barber_id, service_id, subscription_id, date, time) VALUES (5, 2, 1, 1, '2026-11-13', '10:00')$q$,
  'aceite');
SELECT pg_temp.t('RN07', 'reutilizar o slot de uma marcação cancelada',
  $q$INSERT INTO appointments (customer_id, barber_id, service_id, date, time) VALUES (9, 3, 1, '2026-09-18', '10:00')$q$,
  'aceite');
SELECT pg_temp.t('RN06', 'avaliação válida a barbeiro',
  $q$INSERT INTO reviews (reviewer_id, barber_id, rating, comment) VALUES (5, 3, 4, 'ok')$q$,
  'aceite');
SELECT pg_temp.t('RN06', 'avaliação válida a serviço',
  $q$INSERT INTO reviews (reviewer_id, service_id, rating) VALUES (5, 2, 5)$q$,
  'aceite');
SELECT pg_temp.t('RN04', 'pagamento válido de subscrição',
  $q$INSERT INTO payments (subscription_id, amount, method, status, paid_at) VALUES (2, 19.90, 'mbway', 'paid', now())$q$,
  'aceite');
SELECT pg_temp.t('RD01', 'conta sem password (login externo)',
  $q$INSERT INTO users (name, email, role) VALUES ('Google', 'g@x.pt', 'client')$q$,
  'aceite');

\o

SELECT n, regra, teste, esperado, observado, CASE WHEN ok THEN 'PASSOU' ELSE 'FALHOU' END AS resultado
FROM res ORDER BY n;

SELECT count(*)                          AS total,
       count(*) FILTER (WHERE ok)        AS passaram,
       count(*) FILTER (WHERE NOT ok)    AS falharam,
       count(*) FILTER (WHERE esperado <> 'aceite') AS negativos,
       count(*) FILTER (WHERE esperado = 'aceite')  AS positivos
FROM res;

ROLLBACK;
