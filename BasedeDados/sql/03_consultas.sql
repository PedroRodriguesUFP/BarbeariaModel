-- =====================================================================
-- BarbeariaModel — BDAD 2026/27 (UFP) — Fase 2 (v2.0)
-- 03_consultas.sql — início das consultas (NI01..NI08 da Fase 1 = Q01..Q08)
--
-- As datas estão fixas para o resultado ser reproduzível com 02_seed.sql.
-- "Hoje" nos dados de teste = 2026-10-31.
-- Uso:  psql -d <bd> -f sql/03_consultas.sql
-- =====================================================================

-- ---------------------------------------------------------------------
-- Q01 (NI01) — Barbeiros mais bem avaliados no último trimestre (jul–set 2026):
-- média, nº de avaliações e nº de comentários.
-- A data da avaliação é reviews.created_at.
-- ---------------------------------------------------------------------
SELECT u.id,
       u.name                          AS barbeiro,
       round(avg(r.rating), 2)         AS media,
       count(*)                        AS n_avaliacoes,
       count(r.comment)                AS n_comentarios
FROM reviews r
JOIN users u ON u.id = r.barber_id
WHERE r.barber_id IS NOT NULL
  AND r.created_at >= TIMESTAMP '2026-07-01' AND r.created_at < TIMESTAMP '2026-10-01'
GROUP BY u.id, u.name
ORDER BY media DESC, n_avaliacoes DESC, u.name;

-- ---------------------------------------------------------------------
-- Q02 (NI02) — Serviços concluídos e horas trabalhadas por barbeiro no mês
-- transato (setembro 2026). Horas = soma das durações dos serviços concluídos.
-- ---------------------------------------------------------------------
SELECT u.id,
       u.name                                   AS barbeiro,
       count(*)                                 AS servicos_concluidos,
       round(sum(s.duration_minutes) / 60.0, 2) AS horas_trabalhadas
FROM appointments a
JOIN users    u ON u.id = a.barber_id
JOIN services s ON s.id = a.service_id
WHERE a.status = 'completed'
  AND a.date >= DATE '2026-09-01' AND a.date < DATE '2026-10-01'
GROUP BY u.id, u.name
ORDER BY horas_trabalhadas DESC, u.name;

-- ---------------------------------------------------------------------
-- Q03 (NI03) — Clientes com subscrição ativa e plano com mais adesões.
-- ---------------------------------------------------------------------
SELECT p.name                                                  AS plano,
       count(*)                                                AS adesoes_ativas,
       (SELECT count(*) FROM subscriptions WHERE status = 'active') AS total_subscricoes_ativas
FROM subscriptions s
JOIN subscription_plans p ON p.id = s.subscription_plan_id
WHERE s.status = 'active'
GROUP BY p.id, p.name
ORDER BY adesoes_ativas DESC, p.name;

-- ---------------------------------------------------------------------
-- Q04 (NI04) — Saldo de cortes no mês corrente (outubro 2026) por cliente com
-- subscrição ativa (RN05). Marcações canceladas não consomem quota.
-- O LEFT JOIN mantém clientes sem marcações no mês (saldo = quota inteira).
-- ---------------------------------------------------------------------
SELECT c.id,
       c.name                              AS cliente,
       p.name                              AS plano,
       p.cuts_per_month                    AS quota,
       count(a.id)                         AS cortes_usados,
       p.cuts_per_month - count(a.id)      AS cortes_disponiveis
FROM subscriptions s
JOIN users c               ON c.id = s.user_id
JOIN subscription_plans p  ON p.id = s.subscription_plan_id
LEFT JOIN appointments a   ON a.subscription_id = s.id
                          AND a.status <> 'cancelled'
                          AND a.date >= DATE '2026-10-01' AND a.date < DATE '2026-11-01'
WHERE s.status = 'active'
GROUP BY c.id, c.name, p.name, p.cuts_per_month
ORDER BY cortes_disponiveis, c.name;

-- ---------------------------------------------------------------------
-- Q05 (NI05) — Receita de serviços avulsos vs mensalidades em setembro 2026.
-- Só pagamentos com estado 'paid' contam como receita.
-- ---------------------------------------------------------------------
SELECT CASE WHEN appointment_id IS NOT NULL THEN 'avulso' ELSE 'mensalidade' END AS origem,
       count(*)      AS n_pagamentos,
       sum(amount)   AS receita
FROM payments
WHERE status = 'paid'
  AND paid_at >= TIMESTAMP '2026-09-01' AND paid_at < TIMESTAMP '2026-10-01'
GROUP BY (appointment_id IS NOT NULL)
ORDER BY origem;

-- ---------------------------------------------------------------------
-- Q06 (NI06) — Serviços mais requisitados por categoria no último ano
-- (marcações não canceladas, 2025-11-01 a 2026-10-31).
-- ---------------------------------------------------------------------
SELECT s.category                AS categoria,
       s.name                    AS servico,
       count(*)                  AS n_requisicoes
FROM appointments a
JOIN services s ON s.id = a.service_id
WHERE a.status <> 'cancelled'
  AND a.date >= DATE '2025-11-01' AND a.date <= DATE '2026-10-31'
GROUP BY s.category, s.id, s.name
ORDER BY s.category, n_requisicoes DESC, s.name;

-- ---------------------------------------------------------------------
-- Q07 (NI07) — Agenda pendente de confirmação para hoje (2026-10-31),
-- por barbeiro e hora. Clientes walk-in aparecem com o nome indicado.
-- ---------------------------------------------------------------------
SELECT b.name                                        AS barbeiro,
       a.time                                        AS hora,
       COALESCE(c.name, a.walk_in_name || ' (sem conta)') AS cliente,
       s.name                                        AS servico
FROM appointments a
JOIN users    b ON b.id = a.barber_id
JOIN services s ON s.id = a.service_id
LEFT JOIN users c ON c.id = a.customer_id
WHERE a.status = 'pending'
  AND a.date = DATE '2026-10-31'
ORDER BY b.name, a.time;

-- ---------------------------------------------------------------------
-- Q08 (NI08) — Taxa de cancelamento por mês: nº absoluto e percentagem.
-- ---------------------------------------------------------------------
SELECT to_char(date_trunc('month', date), 'YYYY-MM')              AS mes,
       count(*)                                                    AS total_marcacoes,
       count(*) FILTER (WHERE status = 'cancelled')                AS canceladas,
       round(100.0 * count(*) FILTER (WHERE status = 'cancelled')
                   / count(*), 1)                                  AS taxa_cancelamento_pct
FROM appointments
GROUP BY date_trunc('month', date)
ORDER BY mes;
