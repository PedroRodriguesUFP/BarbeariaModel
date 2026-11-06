# Modelo relacional e mapeamento ER → relacional

Notação: **PK** chave primária, *FK* chave estrangeira, `UK` chave candidata única.
Esquema PostgreSQL: `barbearia` (ver `sql/01_schema.sql`).

## 1. Relações

**utilizador**(**id**, nome, email `UK`, palavra_passe_hash, papel, telefone, nif `UK parcial`, biografia, ativo)
  `UK (id, papel)` — usada como alvo de FK compostas.

**calendario**(**id**, *barbeiro_id* `UK`, barbeiro_papel, nome, hora_inicio, hora_fim)
  FK `(barbeiro_id, barbeiro_papel)` → utilizador `(id, papel)`

**calendario_dia**(***calendario_id***, **dia_semana**)
  FK `calendario_id` → calendario

**servico**(**id**, nome `UK`, descricao, preco, duracao_minutos, categoria, ativo)

**barbeiro_servico**(***barbeiro_id***, ***servico_id***, barbeiro_papel)
  FK `(barbeiro_id, barbeiro_papel)` → utilizador; FK `servico_id` → servico

**plano_subscricao**(**id**, nome `UK`, preco_mensal, cortes_por_mes, ativo)

**subscricao**(**id**, *cliente_id*, cliente_papel, *plano_id*, data_inicio, data_fim, estado)
  FK `(cliente_id, cliente_papel)` → utilizador; FK `plano_id` → plano_subscricao

**marcacao**(**id**, *cliente_id*, cliente_papel, *barbeiro_id*, barbeiro_papel, *subscricao_id*?, data, hora, estado)
  FK `(cliente_id, cliente_papel)` e `(barbeiro_id, barbeiro_papel)` → utilizador; FK `subscricao_id` → subscricao
  `UK (id, barbeiro_id)`; índice único parcial `(barbeiro_id, data, hora) WHERE estado <> 'cancelada'`

**marcacao_servico**(***marcacao_id***, ***servico_id***, preco_aplicado)
  FK `marcacao_id` → marcacao; FK `servico_id` → servico

**pagamento**(**id**, *marcacao_id*?, *subscricao_id*?, montante, metodo, estado, data_pagamento?)
  FK → marcacao; FK → subscricao; `CHECK num_nonnulls(marcacao_id, subscricao_id) = 1`

**avaliacao**(**id**, marcacao_id, barbeiro_id?, servico_id?, classificacao, comentario?)
  FK `(marcacao_id, barbeiro_id)` → marcacao `(id, barbeiro_id)`
  FK `(marcacao_id, servico_id)` → marcacao_servico `(marcacao_id, servico_id)`
  `CHECK num_nonnulls(barbeiro_id, servico_id) = 1`

## 2. Decisões de mapeamento (justificação)

| # | Decisão | Alternativa rejeitada | Porquê |
|---|---|---|---|
| M1 | **Uma tabela `utilizador`** com `papel` em vez de subtabelas `cliente`/`barbeiro`/`admin` (generalização "tabela única"). | Tabela por subtipo; ou uma por subtipo com herança. | Os três tipos partilham quase todos os atributos; os específicos são 3 colunas opcionais (`telefone`, `nif`, `biografia`). Evita JOINs em todas as consultas. Os campos exclusivos de cada papel são protegidos por `CHECK` (RD02). |
| M2 | **FK composta `(id, papel)`** com coluna constante gerada (`cliente_papel = 'cliente'`) para garantir o papel nas relações (RN02, RN08). | Trigger a validar o papel. | Restrição declarativa é preferida pelo enunciado. Fica reservado trigger para regras que o declarativo não consegue (Fase 3). |
| M3 | **Relacionamento 1:1 utilizador–calendario**: FK `barbeiro_id` com `UNIQUE` na tabela `calendario`. | Colocar o calendário em `utilizador`. | A participação é parcial do lado do utilizador (só barbeiros) → evitaria NULLs em clientes/admins. |
| M4 | **Atributo multivalor `dias_trabalho`** → relação `calendario_dia (calendario_id, dia_semana)`. | Array ou texto "1,2,3". | 1NF. Permite consultar "quem trabalha à quarta" com um simples `WHERE`. |
| M5 | **N:M marcação–serviço** → `marcacao_servico` com atributo do relacionamento `preco_aplicado`. | Uma coluna `servico_id` em `marcacao`. | Uma marcação tem vários serviços (combinações). O preço aplicado preserva o histórico mesmo se `servico.preco` mudar. |
| M6 | **N:M barbeiro–serviço** → `barbeiro_servico`. | Atributo em `utilizador`. | RN03. PK composta evita duplicados. |
| M7 | **1:N plano–subscrição, cliente–subscrição, subscrição–marcação**: FK no lado N. `marcacao.subscricao_id` nulo = marcação avulsa. | Tabela associativa `marcacao_coberta`. | Cada marcação é coberta por no máximo uma subscrição (cardinalidade (0,1)). FK direta é suficiente e mais simples. |
| M8 | **Pagamento com duas FK anuláveis + `CHECK` XOR** (RN04). | Duas tabelas `pagamento_marcacao` e `pagamento_subscricao`; ou supertipo `origem_pagamento`. | Mesmos atributos nos dois tipos de pagamento; consultas de receita (Q05) ficam numa só tabela. O `CHECK` garante exatamente uma origem. |
| M9 | **Avaliação XOR (barbeiro, serviço)** com FK compostas para a marcação. | Duas tabelas `avaliacao_barbeiro` e `avaliacao_servico`. | RN06 num só `CHECK`; as FK compostas garantem que o barbeiro/serviço avaliado esteve mesmo na marcação, sem trigger. O cliente avaliador é derivado da marcação (sem coluna redundante). |
| M10 | **Enumerações como `CHECK (... IN (...))`** em `varchar`. | Tipo `ENUM` do PostgreSQL ou tabelas de domínio. | Alterar valores é um simples `ALTER TABLE`. `ENUM` do PG é rígido para remover valores. Tabelas de domínio seriam sobreengenharia para 4 valores estáveis. |
| M11 | **Chaves substitutas (`identity`)** em todas as entidades; chaves naturais (`email`, `nome`) mantidas como `UNIQUE`. | Email como PK. | Email pode mudar; PK inteira é mais estável e leve para FK. |
| M12 | **`UNIQUE` parcial** para "no máximo uma subscrição ativa por cliente" (RN09) e "um slot por barbeiro" (RN07). | Trigger. | Índices parciais declarativos exprimem estas regras diretamente. |

## 3. Domínios

| Atributo | Tipo PostgreSQL | Domínio / restrição |
|---|---|---|
| email | `varchar(254)` | formato `x@y.z`, único |
| nif | `char(9)` | 9 dígitos, único se existir |
| papel | `varchar(10)` | `admin`, `barbeiro`, `cliente` |
| preco, montante, preco_mensal, preco_aplicado | `numeric(8,2)` | ≥ 0 (montante > 0) — nunca `float` para dinheiro |
| duracao_minutos, cortes_por_mes | `integer` | > 0 |
| dia_semana | `smallint` | 1..7 (ISO, 1 = segunda) |
| hora_inicio, hora_fim, hora | `time` | `hora_fim > hora_inicio` |
| data_pagamento | `timestamp` | obrigatório se estado `pago`/`reembolsado` |
| classificacao | `smallint` | 1..5 |
| estado (marcação / subscrição / pagamento) | `varchar` | listas fechadas por `CHECK` |
| metodo (pagamento) | `varchar(15)` | `numerario`, `cartao`, `mbway`, `transferencia` |

## 4. Rastreabilidade regra → mecanismo (Fase 2)

| Regra | Mecanismo | Teste (`sql/04_testes_integridade.sql`) |
|---|---|---|
| RN01 e-mail único | `UNIQUE (email)` | RN01 / email duplicado |
| RN02 só barbeiros têm calendário | FK composta `(barbeiro_id, barbeiro_papel)` | RN02 / calendário de cliente |
| RN03 habilitação | tabela `barbeiro_servico` + FK composta (papel). **Falta**: marcar só serviços habilitados → trigger na Fase 3 | RN03 / habilitação de cliente |
| RN04 origem única de pagamento | `CHECK num_nonnulls = 1` | RN04 (2 testes) |
| RN05 quota de subscrição | tabela `marcacao.subscricao_id` + consulta Q04. **Falta**: bloquear marcação acima da quota → trigger na Fase 3 | Q04 |
| RN06 alvo único da avaliação | `CHECK num_nonnulls = 1` + FK compostas | RN06 (2 testes), RD09 (2 testes) |
| RN07 sem conflito de horário | índice único parcial (mesmo início). **Falta**: sobreposição por duração → trigger na Fase 3 | RD05 / slot duplicado |
| RN08 papéis nas relações | FK compostas `(id, papel)` | RD05 (2 testes) |
| RN09 uma subscrição ativa | índice único parcial | RD07 / 2ª subscrição ativa |
