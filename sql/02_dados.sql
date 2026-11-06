-- =====================================================================
-- BarbeariaModel — BDAD 2026/27 (UFP) — Fase 2 (v2.0)
-- 02_dados.sql — povoamento (dados normais + casos limite)
--
-- Uso:  psql -d <bd> -v ON_ERROR_STOP=1 -f sql/02_dados.sql
-- Casos limite incluídos (ver docs/fase2/04-dados.md):
--   * marcação com vários serviços (m4, m11)
--   * marcação coberta por subscrição vs avulsa
--   * cliente com subscrição expirada e depois nova ativa (cliente 7)
--   * cliente com quota de subscrição esgotada no mês (cliente 7, outubro)
--   * marcação cancelada que liberta o slot de outra (m22 vs m19)
--   * pagamento falhado e pagamento reembolsado
--   * avaliação a barbeiro e avaliação a serviço (RN06)
--   * serviço inativo e sem barbeiro habilitado; plano inativo
--   * preco_aplicado diferente do preço atual do serviço (m1)
-- =====================================================================

SET search_path TO barbearia;

BEGIN;

-- ---------------------------------------------------------------------
-- UTILIZADORES (1 admin, 3 barbeiros, 8 clientes)
-- Hashes são valores fictícios no formato bcrypt.
-- ---------------------------------------------------------------------
INSERT INTO utilizador (id, nome, email, palavra_passe_hash, papel, telefone, nif, biografia, ativo) VALUES
 (1,  'Ana Gestora',     'ana.gestora@barbearia.pt',  '$2y$10$hashfalso0000000000000000000001', 'admin',    NULL,        NULL,        NULL, true),
 (2,  'Rui Barbosa',     'rui@barbearia.pt',          '$2y$10$hashfalso0000000000000000000002', 'barbeiro', NULL,        NULL,        'Especialista em cortes clássicos, 12 anos de experiência.', true),
 (3,  'Tiago Navalha',   'tiago@barbearia.pt',        '$2y$10$hashfalso0000000000000000000003', 'barbeiro', NULL,        NULL,        'Mestre em degradés e coloração.', true),
 (4,  'Miguel Tesoura',  'miguel@barbearia.pt',       '$2y$10$hashfalso0000000000000000000004', 'barbeiro', NULL,        NULL,        'Barba e tratamento premium.', true),
 (5,  'Joana Silva',     'joana.silva@email.pt',      '$2y$10$hashfalso0000000000000000000005', 'cliente',  '912000005', '234567890', NULL, true),
 (6,  'Pedro Costa',     'pedro.costa@email.pt',      '$2y$10$hashfalso0000000000000000000006', 'cliente',  '912000006', '234567891', NULL, true),
 (7,  'Marta Ferreira',  'marta.ferreira@email.pt',   '$2y$10$hashfalso0000000000000000000007', 'cliente',  '912000007', NULL,        NULL, true),
 (8,  'Hugo Santos',     'hugo.santos@email.pt',      '$2y$10$hashfalso0000000000000000000008', 'cliente',  '912000008', '234567893', NULL, true),
 (9,  'Sofia Almeida',   'sofia.almeida@email.pt',    '$2y$10$hashfalso0000000000000000000009', 'cliente',  NULL,        NULL,        NULL, true),
 (10, 'Bruno Pereira',   'bruno.pereira@email.pt',    '$2y$10$hashfalso0000000000000000000010', 'cliente',  '912000010', '234567895', NULL, true),
 (11, 'Carla Mendes',    'carla.mendes@email.pt',     '$2y$10$hashfalso0000000000000000000011', 'cliente',  '912000011', NULL,        NULL, true),
 (12, 'Diogo Rocha',     'diogo.rocha@email.pt',      '$2y$10$hashfalso0000000000000000000012', 'cliente',  '912000012', '234567897', NULL, false);

-- ---------------------------------------------------------------------
-- CALENDÁRIOS (só barbeiros) + dias de trabalho (ISO 1=seg ... 7=dom)
-- ---------------------------------------------------------------------
INSERT INTO calendario (id, barbeiro_id, nome, hora_inicio, hora_fim) VALUES
 (1, 2, 'Turno Rui',    '09:00', '18:00'),
 (2, 3, 'Turno Tiago',  '10:00', '19:00'),
 (3, 4, 'Turno Miguel', '09:00', '13:00');

INSERT INTO calendario_dia (calendario_id, dia_semana) VALUES
 (1,1),(1,2),(1,3),(1,4),(1,5),
 (2,2),(2,3),(2,4),(2,5),(2,6),
 (3,1),(3,3),(3,5),(3,6);

-- ---------------------------------------------------------------------
-- SERVIÇOS (7 e 8: caso combo; 8 inativo e sem barbeiro habilitado)
-- ---------------------------------------------------------------------
INSERT INTO servico (id, nome, descricao, preco, duracao_minutos, categoria, ativo) VALUES
 (1, 'Corte Clássico',          'Corte à tesoura e máquina',                    15.00, 30, 'cabelo',         true),
 (2, 'Corte Degradé',           'Degradé com transição suave',                  18.00, 40, 'degradee',       true),
 (3, 'Barba',                   'Aparar e modelar barba com toalha quente',     10.00, 20, 'barba',          true),
 (4, 'Corte + Barba',           'Pacote corte clássico e barba',                22.00, 50, 'cabelo e barba', true),
 (5, 'Coloração',               'Coloração completa',                           35.00, 60, 'coloracao',      true),
 (6, 'Corte Infantil',          'Corte para menores de 12 anos',                12.00, 25, 'cabelo',         true),
 (7, 'Corte + Barba Premium',   'Pacote completo com tratamento facial',        30.00, 60, 'cabelo e barba', true),
 (8, 'Tratamento Capilar',      'Serviço descontinuado',                        20.00, 30, 'cabelo',         false);

-- RN03: habilitações
INSERT INTO barbeiro_servico (barbeiro_id, servico_id) VALUES
 (2,1),(2,2),(2,3),(2,4),(2,6),
 (3,1),(3,2),(3,3),(3,4),(3,5),
 (4,1),(4,3),(4,4),(4,5),(4,6),(4,7);

-- ---------------------------------------------------------------------
-- PLANOS E SUBSCRIÇÕES
-- ---------------------------------------------------------------------
INSERT INTO plano_subscricao (id, nome, preco_mensal, cortes_por_mes, ativo) VALUES
 (1, 'Básico',  25.00,  2, true),
 (2, 'Premium', 45.00,  4, true),
 (3, 'VIP',     80.00, 10, false);

INSERT INTO subscricao (id, cliente_id, plano_id, data_inicio, data_fim, estado) VALUES
 (1, 5, 2, '2026-09-01', NULL,         'ativa'),
 (2, 6, 1, '2026-09-15', NULL,         'ativa'),
 (3, 7, 2, '2026-06-01', '2026-08-31', 'expirada'),
 (4, 8, 1, '2026-07-01', '2026-09-30', 'cancelada'),
 (5, 7, 1, '2026-10-01', NULL,         'ativa');

-- ---------------------------------------------------------------------
-- MARCAÇÕES
-- (id, cliente, barbeiro, subscrição, data, hora, estado)
-- ---------------------------------------------------------------------
INSERT INTO marcacao (id, cliente_id, barbeiro_id, subscricao_id, data, hora, estado) VALUES
 (1,  5,  2, 1,    '2026-09-03', '10:00', 'concluida'),
 (2,  5,  2, 1,    '2026-09-17', '10:00', 'concluida'),
 (3,  6,  3, 2,    '2026-09-22', '11:00', 'concluida'),
 (4,  7,  3, NULL, '2026-09-08', '15:00', 'concluida'),
 (5,  8,  4, NULL, '2026-09-04', '09:30', 'concluida'),
 (6,  9,  2, NULL, '2026-09-10', '14:00', 'concluida'),
 (7,  10, 3, NULL, '2026-09-15', '16:00', 'cancelada'),
 (8,  11, 4, NULL, '2026-09-19', '10:00', 'concluida'),
 (9,  5,  3, 1,    '2026-10-06', '12:00', 'concluida'),
 (10, 6,  2, 2,    '2026-10-09', '09:00', 'concluida'),
 (11, 12, 3, NULL, '2026-10-13', '17:00', 'concluida'),
 (12, 9,  4, NULL, '2026-10-16', '11:00', 'concluida'),
 (13, 10, 2, NULL, '2026-10-20', '15:00', 'cancelada'),
 (14, 7,  2, 5,    '2026-10-22', '10:30', 'concluida'),
 (15, 8,  3, NULL, '2026-10-24', '13:00', 'concluida'),
 (16, 11, 2, NULL, '2026-10-29', '16:00', 'confirmada'),
 (17, 5,  3, 1,    '2026-10-31', '10:00', 'pendente'),
 (18, 6,  4, 2,    '2026-10-31', '09:00', 'pendente'),
 (19, 12, 3, NULL, '2026-10-31', '11:00', 'pendente'),
 (20, 9,  3, NULL, '2026-10-31', '15:00', 'confirmada'),
 (21, 7,  3, 5,    '2026-10-31', '14:00', 'pendente'),
 (22, 10, 3, NULL, '2026-10-31', '11:00', 'cancelada');   -- mesmo slot que m19: permitido por estar cancelada

-- ---------------------------------------------------------------------
-- SERVIÇOS DAS MARCAÇÕES (preço aplicado no momento da marcação)
-- ---------------------------------------------------------------------
INSERT INTO marcacao_servico (marcacao_id, servico_id, preco_aplicado) VALUES
 (1,  1, 14.00),               -- preço antigo (hoje o serviço custa 15.00)
 (2,  4, 22.00),
 (3,  2, 18.00),
 (4,  1, 15.00), (4, 3, 10.00),    -- vários serviços
 (5,  5, 35.00),
 (6,  2, 18.00),
 (7,  1, 15.00),
 (8,  7, 30.00),
 (9,  3, 10.00),
 (10, 1, 15.00),
 (11, 2, 18.00), (11, 3, 10.00),   -- vários serviços
 (12, 4, 22.00),
 (13, 2, 18.00),
 (14, 1, 15.00),
 (15, 5, 35.00),
 (16, 2, 18.00),
 (17, 1, 15.00),
 (18, 3, 10.00),
 (19, 4, 22.00),
 (20, 2, 18.00),
 (21, 1, 15.00),
 (22, 4, 22.00);

-- ---------------------------------------------------------------------
-- PAGAMENTOS
-- Avulsos (marcacao_id) e mensalidades (subscricao_id). Nunca ambos (RN04).
-- Marcações cobertas por subscrição (m1,m2,m3,m9,m10,m14,...) não têm pagamento avulso.
-- ---------------------------------------------------------------------
INSERT INTO pagamento (marcacao_id, subscricao_id, montante, metodo, estado, data_pagamento) VALUES
 -- avulsos
 (4,  NULL, 25.00, 'mbway',         'pago',        '2026-09-08 15:50'),
 (5,  NULL, 35.00, 'cartao',        'pago',        '2026-09-04 10:40'),
 (6,  NULL, 18.00, 'numerario',     'pago',        '2026-09-10 14:45'),
 (8,  NULL, 30.00, 'cartao',        'pago',        '2026-09-19 11:05'),
 (11, NULL, 28.00, 'mbway',         'pago',        '2026-10-13 17:50'),
 (12, NULL, 22.00, 'cartao',        'pago',        '2026-10-16 11:55'),
 (13, NULL, 18.00, 'cartao',        'reembolsado', '2026-10-20 12:00'),   -- cancelada e reembolsada
 (15, NULL, 35.00, 'numerario',     'pago',        '2026-10-24 14:05'),
 (16, NULL, 18.00, 'cartao',        'falhado',     NULL),                 -- tentativa falhada
 -- mensalidades
 (NULL, 1, 45.00, 'cartao',         'pago', '2026-09-01 09:00'),
 (NULL, 1, 45.00, 'cartao',         'pago', '2026-10-01 09:00'),
 (NULL, 2, 25.00, 'mbway',          'pago', '2026-09-15 09:00'),
 (NULL, 2, 25.00, 'mbway',          'pago', '2026-10-15 09:00'),
 (NULL, 3, 45.00, 'transferencia',  'pago', '2026-06-01 09:00'),
 (NULL, 3, 45.00, 'transferencia',  'pago', '2026-07-01 09:00'),
 (NULL, 3, 45.00, 'transferencia',  'pago', '2026-08-01 09:00'),
 (NULL, 4, 25.00, 'cartao',         'pago', '2026-07-01 09:00'),
 (NULL, 4, 25.00, 'cartao',         'pago', '2026-08-01 09:00'),
 (NULL, 4, 25.00, 'cartao',         'pago', '2026-09-01 09:00'),
 (NULL, 5, 25.00, 'mbway',          'pago', '2026-10-01 09:00');

-- ---------------------------------------------------------------------
-- AVALIAÇÕES (RN06: ou barbeiro ou serviço)
-- Só sobre marcações concluídas (regra a reforçar por trigger na Fase 3).
-- ---------------------------------------------------------------------
INSERT INTO avaliacao (marcacao_id, barbeiro_id, servico_id, classificacao, comentario) VALUES
 (1,  2, NULL, 5, 'Excelente atendimento.'),
 (2,  NULL, 4, 4, 'Bom pacote, preço justo.'),
 (3,  3, NULL, 5, 'Degradé perfeito.'),
 (4,  3, NULL, 4, 'Muito simpático.'),
 (4,  NULL, 3, 3, NULL),
 (5,  4, NULL, 5, 'Coloração impecável.'),
 (6,  2, NULL, 3, NULL),
 (8,  4, NULL, 4, 'Muito bom, um pouco demorado.'),
 (9,  NULL, 3, 5, 'Barba top.'),
 (10, 2, NULL, 4, NULL),
 (11, 3, NULL, 2, 'Esperei 20 minutos.'),
 (12, 4, NULL, 5, 'Recomendo.'),
 (14, 2, NULL, 5, NULL),
 (15, 3, NULL, 4, 'Bom resultado.');

-- Sincronizar sequências das colunas identity (inserimos ids explícitos)
SELECT setval(pg_get_serial_sequence('utilizador','id'),       (SELECT max(id) FROM utilizador));
SELECT setval(pg_get_serial_sequence('calendario','id'),       (SELECT max(id) FROM calendario));
SELECT setval(pg_get_serial_sequence('servico','id'),          (SELECT max(id) FROM servico));
SELECT setval(pg_get_serial_sequence('plano_subscricao','id'), (SELECT max(id) FROM plano_subscricao));
SELECT setval(pg_get_serial_sequence('subscricao','id'),       (SELECT max(id) FROM subscricao));
SELECT setval(pg_get_serial_sequence('marcacao','id'),         (SELECT max(id) FROM marcacao));
SELECT setval(pg_get_serial_sequence('pagamento','id'),        (SELECT max(id) FROM pagamento));
SELECT setval(pg_get_serial_sequence('avaliacao','id'),        (SELECT max(id) FROM avaliacao));

COMMIT;
