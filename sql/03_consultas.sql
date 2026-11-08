-- =====================================================================
-- BarbeariaModel — BDAD 2026/27 (UFP) — Fase 2 (v2.0)
-- 03_consultas.sql — início das consultas (NI01..NI08 da Fase 1 = Q01..Q08)
--
-- As datas estão fixas para o resultado ser reproduzível com 02_dados.sql.
-- "Hoje" nos dados de teste = 2026-10-31.
-- Uso:  psql -d <bd> -f sql/03_consultas.sql
-- =====================================================================

SET search_path TO barbearia;

-- ---------------------------------------------------------------------
-- Q01 (NI01) — Barbeiros mais bem avaliados no último trimestre
-- (jul–set 2026): média, nº de avaliações e nº de comentários.
-- A avaliação não guarda data própria: usa-se a data da marcação.
-- ---------------------------------------------------------------------
SELECT u.id,
       u.nome                                   AS barbeiro,
       round(avg(a.classificacao), 2)           AS media,
       count(*)                                 AS n_avaliacoes,
       count(a.comentario)                      AS n_comentarios
FROM avaliacao a
JOIN marcacao  m ON m.id = a.marcacao_id
JOIN utilizador u ON u.id = a.barbeiro_id
WHERE a.barbeiro_id IS NOT NULL
  AND m.data >= DATE '2026-07-01' AND m.data < DATE '2026-10-01'
GROUP BY u.id, u.nome
ORDER BY media DESC, n_avaliacoes DESC;

-- ---------------------------------------------------------------------
-- Q02 (NI02) — Serviços concluídos e horas trabalhadas por barbeiro
-- no mês transato (setembro 2026).
-- Conta marcações concluídas e soma as durações dos serviços de cada uma.
-- ---------------------------------------------------------------------
SELECT u.id,
       u.nome                                       AS barbeiro,
       count(DISTINCT m.id)                         AS marcacoes_concluidas,
       count(*)                                     AS servicos_realizados,
       round(sum(s.duracao_minutos) / 60.0, 2)      AS horas_trabalhadas
FROM marcacao m
JOIN utilizador       u  ON u.id = m.barbeiro_id
JOIN marcacao_servico ms ON ms.marcacao_id = m.id
JOIN servico          s  ON s.id = ms.servico_id
WHERE m.estado = 'concluida'
  AND m.data >= DATE '2026-09-01' AND m.data < DATE '2026-10-01'
GROUP BY u.id, u.nome
ORDER BY horas_trabalhadas DESC;

-- ---------------------------------------------------------------------
-- Q03 (NI03) — Clientes com subscrição ativa e plano com mais adesões.
-- A coluna total repete o nº total de clientes com subscrição ativa.
-- ---------------------------------------------------------------------
SELECT p.nome                                   AS plano,
       count(*)                                 AS adesoes_ativas,
       (SELECT count(*) FROM subscricao WHERE estado = 'ativa') AS total_clientes_ativos
FROM subscricao sb
JOIN plano_subscricao p ON p.id = sb.plano_id
WHERE sb.estado = 'ativa'
GROUP BY p.id, p.nome
ORDER BY adesoes_ativas DESC, p.nome;

-- ---------------------------------------------------------------------
-- Q04 (NI04) — Saldo de cortes no mês corrente (outubro 2026) por
-- cliente com subscrição ativa (RN05).
-- Marcações canceladas não consomem quota. LEFT JOIN mantém clientes
-- sem marcações no mês (saldo = quota inteira).
-- ---------------------------------------------------------------------
SELECT c.id,
       c.nome                                                   AS cliente,
       p.nome                                                   AS plano,
       p.cortes_por_mes                                         AS quota,
       count(m.id)                                              AS cortes_usados,
       p.cortes_por_mes - count(m.id)                           AS cortes_disponiveis
FROM subscricao sb
JOIN utilizador c          ON c.id = sb.cliente_id
JOIN plano_subscricao p    ON p.id = sb.plano_id
LEFT JOIN marcacao m       ON m.subscricao_id = sb.id
                          AND m.estado <> 'cancelada'
                          AND m.data >= DATE '2026-10-01' AND m.data < DATE '2026-11-01'
WHERE sb.estado = 'ativa'
GROUP BY c.id, c.nome, p.nome, p.cortes_por_mes
ORDER BY cortes_disponiveis, c.nome;

-- ---------------------------------------------------------------------
-- Q05 (NI05) — Receita de serviços avulsos vs mensalidades em setembro 2026.
-- Só pagamentos com estado 'pago' contam como receita.
-- ---------------------------------------------------------------------
SELECT CASE WHEN marcacao_id IS NOT NULL THEN 'avulso' ELSE 'mensalidade' END AS origem,
       count(*)        AS n_pagamentos,
       sum(montante)   AS receita
FROM pagamento
WHERE estado = 'pago'
  AND data_pagamento >= TIMESTAMP '2026-09-01' AND data_pagamento < TIMESTAMP '2026-10-01'
GROUP BY (marcacao_id IS NOT NULL)
ORDER BY origem;

-- ---------------------------------------------------------------------
-- Q06 (NI06) — Serviços mais requisitados por categoria no último ano
-- (marcações não canceladas).
-- ---------------------------------------------------------------------
SELECT s.categoria,
       s.nome                AS servico,
       count(*)              AS n_requisicoes
FROM marcacao_servico ms
JOIN marcacao m ON m.id = ms.marcacao_id
JOIN servico  s ON s.id = ms.servico_id
WHERE m.estado <> 'cancelada'
  AND m.data >= DATE '2025-11-01' AND m.data <= DATE '2026-10-31'
GROUP BY s.categoria, s.id, s.nome
ORDER BY s.categoria, n_requisicoes DESC, s.nome;

-- ---------------------------------------------------------------------
-- Q07 (NI07) — Agenda pendente de confirmação para hoje (2026-10-31),
-- por barbeiro e hora, com a lista de serviços de cada marcação.
-- ---------------------------------------------------------------------
SELECT b.nome                                     AS barbeiro,
       m.hora,
       c.nome                                     AS cliente,
       string_agg(s.nome, ' + ' ORDER BY s.nome)  AS servicos
FROM marcacao m
JOIN utilizador       b  ON b.id = m.barbeiro_id
JOIN utilizador       c  ON c.id = m.cliente_id
JOIN marcacao_servico ms ON ms.marcacao_id = m.id
JOIN servico          s  ON s.id = ms.servico_id
WHERE m.estado = 'pendente'
  AND m.data = DATE '2026-10-31'
GROUP BY b.nome, m.hora, c.nome, m.id
ORDER BY b.nome, m.hora;

-- ---------------------------------------------------------------------
-- Q08 (NI08) — Taxa de cancelamento por mês: nº absoluto e percentagem.
-- ---------------------------------------------------------------------
SELECT to_char(date_trunc('month', data), 'YYYY-MM')              AS mes,
       count(*)                                                    AS total_marcacoes,
       count(*) FILTER (WHERE estado = 'cancelada')                AS canceladas,
       round(100.0 * count(*) FILTER (WHERE estado = 'cancelada')
                   / count(*), 1)                                  AS taxa_cancelamento_pct
FROM marcacao
GROUP BY date_trunc('month', data)
ORDER BY mes;
