# Dados de teste e consultas

Tudo o que está abaixo foi **executado** em PostgreSQL 16.15 com `./run_all.sh` (BD criada do zero). O log completo está em `execucao_postgres16.txt`. «Hoje» nos dados = sábado 2026-10-31.

## 1. Dados (`sql/02_seed.sql`)

| Tabela | Linhas | Notas |
|---|---|---|
| users | 12 | 1 admin, 3 barbeiros, 8 clientes (1 inativo, 1 sem telefone, 1 sem password = login Google) |
| services | 6 | 5 do seed do Pedro + 1 inativo sem barbeiros habilitados |
| service_user | 10 | Tiago 4, Miguel 3, Rui 3 (habilitações diferentes) |
| working_hours | 19 | Tiago ter–sáb 10–19; Miguel seg/qua/sex/sáb 9–13; Rui seg–sex 9–13 e 14–18 (horário partido) |
| schedule_blocks | 2 | férias (dia inteiro) e consulta (parcial) |
| subscription_plans | 3 | 1 inativo |
| subscriptions | 7 | ativas, expirada + nova ativa, cancelada, suspensa |
| appointments | 32 | 20 completed, 4 confirmed, 5 pending, 3 cancelled; 2 walk-in; 14 cobertas por subscrição |
| payments | 19 | 11 avulsos e 8 mensalidades |
| reviews | 14 | 9 a barbeiros, 5 a serviços |

### Casos limite

| Caso | Onde | Regra |
|---|---|---|
| Marcação sem conta (walk-in), com e sem telefone | appointments 8, 24 | RD05 |
| Barbeiro como cliente de outro barbeiro | appointments 12, 32 (Rui → Tiago/Miguel) | RN09 |
| Subscrição expirada + nova ativa, mesmo utilizador | subscriptions 3 e 4 (André) | RN08 |
| Subscrição cancelada, suspensa | subscriptions 5, 6 | RD07 |
| Quota esgotada no mês | João (4/4 Premium), Rafael (2/2 Essencial) em outubro | RN05, Q04 |
| Marcação cancelada não consome quota | appointment 20 (André) | RN05, Q04 |
| Slot libertado por cancelamento e reocupado | appointments 22 (cancelled) e 23 (confirmed), mesmo barbeiro/dia/hora | RN07 |
| Pagamento falhado + nova tentativa | payments 3 e 4 (appointment 6) | RD08 |
| Pagamento reembolsado (marcação cancelada) e pendente | payments 8, 11, 18 | RD08 |
| Serviço inativo sem barbeiros | service 6 | RN03 (teste 20) |
| Plano inativo | plan 3 | RD06 |
| Conta sem password | user 12 | RD01 |
| Avaliação fora do trimestre de Q01 | review 7 (out) | NI01 |
| Barbeiro a avaliar outro barbeiro | review 9 | RN06 |

## 2. Consultas (`sql/03_consultas.sql`) e resultados obtidos

Numeração igual à Fase 1 (NI01 = Q01, …).

**Q01 — barbeiros mais bem avaliados (jul–set 2026)**

| barbeiro | média | n_avaliacoes | n_comentarios |
|---|---|---|---|
| Tiago Costa | 4.75 | 4 | 2 |
| Miguel Sousa | 4.50 | 2 | 1 |
| Rui Barbosa | 3.50 | 2 | 1 |

(A avaliação de 17 out. do Rui fica de fora do trimestre.)

**Q02 — serviços concluídos e horas por barbeiro (setembro)**

| barbeiro | servicos_concluidos | horas_trabalhadas |
|---|---|---|
| Tiago Costa | 5 | 3.67 |
| Miguel Sousa | 3 | 1.67 |
| Rui Barbosa | 3 | 1.50 |

Conferido à mão: Tiago = 40 + 60 + 50 + 30 + 40 min = 220 min.

**Q03 — adesão a subscrições**

| plano | adesoes_ativas | total_subscricoes_ativas |
|---|---|---|
| Plano Essencial | 3 | 4 |
| Plano Premium | 1 | 4 |

**Q04 — saldo de cortes em outubro**

| cliente | plano | quota | cortes_usados | cortes_disponiveis |
|---|---|---|---|---|
| João Silva | Premium | 4 | 4 | 0 |
| Rafael Ferreira | Essencial | 2 | 2 | 0 |
| André Martins | Essencial | 2 | 1 | 1 |
| Inês Lopes | Essencial | 2 | 0 | 2 |

**Q05 — receita em setembro (só `paid`)**

| origem | n_pagamentos | receita |
|---|---|---|
| avulso | 6 | 88.00 |
| mensalidade | 3 | 74.70 |

O falhado (3) e o reembolsado (8) não contam.

**Q06 — serviços mais requisitados por categoria (12 meses, sem canceladas)**

| categoria | servico | n_requisicoes |
|---|---|---|
| beard | Aparar barba | 4 |
| coloring | Coloração | 2 |
| fade | Fade | 7 |
| hair | Corte clássico | 10 |
| hair and beard | Corte e barba | 4 |

**Q07 — pendentes hoje (2026-10-31)**

| barbeiro | hora | cliente | servico |
|---|---|---|---|
| Miguel Sousa | 10:00 | Sofia Almeida | Aparar barba |
| Tiago Costa | 10:00 | Marta Ferreira | Fade |
| Tiago Costa | 11:00 | Carla Mendes | Corte clássico |
| Tiago Costa | 15:00 | Inês Lopes | Corte e barba |

A marcação 30 (confirmed) não aparece. Um walk-in pendente apareceria como «Nome (sem conta)».

**Q08 — taxa de cancelamento por mês**

| mes | total_marcacoes | canceladas | taxa_cancelamento_pct |
|---|---|---|---|
| 2026-09 | 12 | 1 | 8.3 |
| 2026-10 | 18 | 2 | 11.1 |
| 2026-11 | 2 | 0 | 0.0 |

## 3. Testes de integridade (`sql/04_testes_integridade.sql`)

52 testes: **43 negativos** (a operação tem de ser recusada com o SQLSTATE **e** a restrição esperados) e **9 positivos** (a operação válida tem de ser aceite). Resultado da execução: `total 52 | passaram 52 | falharam 0`. Corre numa transação revertida e confirmei que a BD fica intacta (32 marcações, 14 avaliações, 19 pagamentos, 12 utilizadores).

**Teste de mutação** (para ter a certeza de que os testes não são vazios): removi `fk_appointments_barber_service` e `ck_appointments_subscription_needs_customer` e voltei a correr; falharam exatamente os testes 19, 20, 21 e 29 (48 passaram, 4 falharam). A BD foi recriada do zero depois disso.

## 4. Limites do que foi testado

- Testado: PostgreSQL 16.15, carga do zero (duas vezes seguidas), consultas, testes, sequências realinhadas.
- **Não** testado: PostgreSQL 14/15 (uso `num_nonnulls`, identity e colunas geradas `STORED`, que exigem PostgreSQL 12+; não corri); execução das migrations/app Laravel contra esta BD.
- Datas fixas nas consultas: o resultado só é reproduzível com este seed.
