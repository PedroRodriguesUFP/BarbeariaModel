-- =====================================================================
-- BarbeariaModel — BDAD 2026/27 (UFP)
-- 04_testes_integridade.sql — testes negativos das restrições declarativas
--
-- Cada teste tenta uma operação que DEVE ser recusada e imprime
-- OK (recusada, como esperado) ou FALHA (foi aceite!).
-- Tudo corre dentro de uma transação revertida: não altera os dados.
-- Uso:  psql -d <bd> -f sql/04_testes_integridade.sql
-- =====================================================================

SET search_path TO barbearia;

BEGIN;

CREATE TEMP TABLE resultado_testes (regra text, teste text, esperado text, observado text);

CREATE OR REPLACE FUNCTION pg_temp.tenta(p_regra text, p_teste text, p_sql text, p_esperado text)
RETURNS void LANGUAGE plpgsql AS $$
DECLARE
    v_obs text;
BEGIN
    BEGIN
        EXECUTE p_sql;
        RAISE EXCEPTION 'ACEITE_SEM_ERRO';          -- força rollback do sub-bloco
    EXCEPTION WHEN OTHERS THEN
        IF SQLERRM = 'ACEITE_SEM_ERRO' THEN
            v_obs := 'FALHA: operação aceite';
        ELSE
            v_obs := 'OK: ' || SQLSTATE || ' ' || split_part(SQLERRM, E'\n', 1);
        END IF;
    END;
    INSERT INTO resultado_testes VALUES (p_regra, p_teste, p_esperado, v_obs);
END $$;

\o /dev/null

-- RN01 — e-mail duplicado (UNIQUE)
SELECT pg_temp.tenta('RN01','email duplicado',
 $q$INSERT INTO utilizador (nome,email,palavra_passe_hash,papel) VALUES ('X','rui@barbearia.pt','h','cliente')$q$,
 'unique_violation 23505');

-- RD01 — papel inválido (CHECK)
SELECT pg_temp.tenta('RD01','papel inválido',
 $q$INSERT INTO utilizador (nome,email,palavra_passe_hash,papel) VALUES ('X','x@x.pt','h','visitante')$q$,
 'check_violation 23514');

-- RD01 — nome NULL (NOT NULL)
SELECT pg_temp.tenta('RD01','nome NULL',
 $q$INSERT INTO utilizador (nome,email,palavra_passe_hash,papel) VALUES (NULL,'y@y.pt','h','cliente')$q$,
 'not_null_violation 23502');

-- RD02 — barbeiro com NIF (campo exclusivo de cliente)
SELECT pg_temp.tenta('RD02','barbeiro com NIF',
 $q$INSERT INTO utilizador (nome,email,palavra_passe_hash,papel,nif) VALUES ('X','z@z.pt','h','barbeiro','123456789')$q$,
 'check_violation 23514');

-- RN02 — calendário para um cliente (FK composta com papel)
SELECT pg_temp.tenta('RN02','calendário de cliente',
 $q$INSERT INTO calendario (barbeiro_id,hora_inicio,hora_fim) VALUES (5,'09:00','17:00')$q$,
 'foreign_key_violation 23503');

-- RD03 — hora_fim antes de hora_inicio
SELECT pg_temp.tenta('RD03','turno invertido',
 $q$UPDATE calendario SET hora_fim = '08:00' WHERE id = 1$q$,
 'check_violation 23514');

-- RN03 (parcial) — habilitação para barbeiro inexistente / não barbeiro
SELECT pg_temp.tenta('RN03','habilitação de cliente',
 $q$INSERT INTO barbeiro_servico (barbeiro_id,servico_id) VALUES (5,1)$q$,
 'foreign_key_violation 23503');

-- RD04 — preço negativo
SELECT pg_temp.tenta('RD04','preço negativo',
 $q$INSERT INTO servico (nome,preco,duracao_minutos,categoria) VALUES ('Teste',-1,10,'cabelo')$q$,
 'check_violation 23514');

-- RD04 — categoria inválida
SELECT pg_temp.tenta('RD04','categoria inválida',
 $q$INSERT INTO servico (nome,preco,duracao_minutos,categoria) VALUES ('Teste',1,10,'massagem')$q$,
 'check_violation 23514');

-- RD05 — marcação com cliente que é barbeiro
SELECT pg_temp.tenta('RD05','cliente com papel barbeiro',
 $q$INSERT INTO marcacao (cliente_id,barbeiro_id,data,hora) VALUES (2,3,'2026-11-02','10:00')$q$,
 'foreign_key_violation 23503');

-- RD05 — barbeiro com papel cliente
SELECT pg_temp.tenta('RD05','barbeiro com papel cliente',
 $q$INSERT INTO marcacao (cliente_id,barbeiro_id,data,hora) VALUES (5,6,'2026-11-02','10:00')$q$,
 'foreign_key_violation 23503');

-- RD05 — mesmo barbeiro, mesmo instante (marcação ativa)
SELECT pg_temp.tenta('RD05','slot duplicado',
 $q$INSERT INTO marcacao (cliente_id,barbeiro_id,data,hora,estado) VALUES (9,3,'2026-10-31','11:00','pendente')$q$,
 'unique_violation 23505');

-- RD05 — estado inválido
SELECT pg_temp.tenta('RD05','estado inválido',
 $q$UPDATE marcacao SET estado = 'adiada' WHERE id = 1$q$,
 'check_violation 23514');

-- RD07 — duas subscrições ativas para o mesmo cliente
SELECT pg_temp.tenta('RD07','2ª subscrição ativa',
 $q$INSERT INTO subscricao (cliente_id,plano_id,data_inicio,estado) VALUES (5,1,'2026-10-01','ativa')$q$,
 'unique_violation 23505');

-- RD07 — data_fim anterior a data_inicio
SELECT pg_temp.tenta('RD07','datas invertidas',
 $q$INSERT INTO subscricao (cliente_id,plano_id,data_inicio,data_fim,estado) VALUES (9,1,'2026-10-10','2026-10-01','cancelada')$q$,
 'check_violation 23514');

-- RN04 — pagamento com marcação E subscrição
SELECT pg_temp.tenta('RN04','pagamento com 2 origens',
 $q$INSERT INTO pagamento (marcacao_id,subscricao_id,montante,metodo,estado,data_pagamento) VALUES (4,1,10,'cartao','pago',now())$q$,
 'check_violation 23514');

-- RN04 — pagamento sem origem
SELECT pg_temp.tenta('RN04','pagamento sem origem',
 $q$INSERT INTO pagamento (montante,metodo,estado) VALUES (10,'cartao','pendente')$q$,
 'check_violation 23514');

-- RD08 — pago sem data
SELECT pg_temp.tenta('RD08','pago sem data_pagamento',
 $q$INSERT INTO pagamento (marcacao_id,montante,metodo,estado) VALUES (4,10,'cartao','pago')$q$,
 'check_violation 23514');

-- RN06 — avaliação com barbeiro E serviço
SELECT pg_temp.tenta('RN06','avaliação com 2 alvos',
 $q$INSERT INTO avaliacao (marcacao_id,barbeiro_id,servico_id,classificacao) VALUES (4,3,1,5)$q$,
 'check_violation 23514');

-- RN06 — avaliação sem alvo
SELECT pg_temp.tenta('RN06','avaliação sem alvo',
 $q$INSERT INTO avaliacao (marcacao_id,classificacao) VALUES (4,5)$q$,
 'check_violation 23514');

-- RD09 — classificação fora de 1..5
SELECT pg_temp.tenta('RD09','classificação 6',
 $q$INSERT INTO avaliacao (marcacao_id,barbeiro_id,classificacao) VALUES (6,2,6)$q$,
 'check_violation 23514');

-- RD09 — avaliar barbeiro que não atendeu a marcação
SELECT pg_temp.tenta('RD09','avaliar barbeiro errado',
 $q$INSERT INTO avaliacao (marcacao_id,barbeiro_id,classificacao) VALUES (6,3,4)$q$,
 'foreign_key_violation 23503');

-- RD09 — avaliar serviço que não fez parte da marcação
SELECT pg_temp.tenta('RD09','avaliar serviço errado',
 $q$INSERT INTO avaliacao (marcacao_id,servico_id,classificacao) VALUES (6,5,4)$q$,
 'foreign_key_violation 23503');

-- RD09 — avaliação duplicada (mesma marcação, mesmo barbeiro)
SELECT pg_temp.tenta('RD09','avaliação duplicada',
 $q$INSERT INTO avaliacao (marcacao_id,barbeiro_id,classificacao) VALUES (1,2,3)$q$,
 'unique_violation 23505');

-- Teste positivo: operação válida deve ser ACEITE
DO $$
BEGIN
    INSERT INTO marcacao (cliente_id,barbeiro_id,data,hora) VALUES (9,2,'2026-11-02','10:00');
    INSERT INTO resultado_testes VALUES ('RD05','marcação válida','aceite','OK: aceite');
EXCEPTION WHEN OTHERS THEN
    INSERT INTO resultado_testes VALUES ('RD05','marcação válida','aceite','FALHA: ' || SQLERRM);
END $$;

\o

SELECT regra, teste, esperado, observado FROM resultado_testes ORDER BY regra, teste;

ROLLBACK;
