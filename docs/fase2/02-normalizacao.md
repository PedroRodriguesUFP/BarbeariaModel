# Normalização (1NF, 2NF, 3NF)

Para cada relação: dependências funcionais (DF) relevantes, chaves candidatas e forma normal. Nenhuma decomposição foi feita só para "mostrar o processo"; as únicas decomposições estão em §2.

## 1. Análise por relação

| Relação | Chaves candidatas | DF relevantes | Forma normal |
|---|---|---|---|
| utilizador | `id`; `email`; `nif` (quando existe) | `id → nome, email, palavra_passe_hash, papel, telefone, nif, biografia, ativo`; `email → id`; `nif → id` | **3NF** (e BCNF). Todos os determinantes são chaves candidatas. |
| calendario | `id`; `barbeiro_id` | `id → barbeiro_id, nome, hora_inicio, hora_fim`; `barbeiro_id → id` | **BCNF** |
| calendario_dia | `(calendario_id, dia_semana)` | sem atributos não-chave | **BCNF** (relação "toda chave") |
| servico | `id`; `nome` | `id → nome, descricao, preco, duracao_minutos, categoria, ativo`; `nome → id` | **BCNF** |
| barbeiro_servico | `(barbeiro_id, servico_id)` | sem atributos não-chave | **BCNF** |
| plano_subscricao | `id`; `nome` | `id → nome, preco_mensal, cortes_por_mes, ativo` | **BCNF** |
| subscricao | `id` | `id → cliente_id, plano_id, data_inicio, data_fim, estado` | **BCNF**. `plano_id` não determina `cortes_por_mes` aqui (está em `plano_subscricao`). |
| marcacao | `id`; `(barbeiro_id, data, hora)` só entre ativas (índice parcial, não é chave candidata geral) | `id → cliente_id, barbeiro_id, subscricao_id, data, hora, estado` | **BCNF** |
| marcacao_servico | `(marcacao_id, servico_id)` | `(marcacao_id, servico_id) → preco_aplicado` | **BCNF**. Ver §3: `preco_aplicado` não é redundância. |
| pagamento | `id` | `id → marcacao_id, subscricao_id, montante, metodo, estado, data_pagamento` | **BCNF** |
| avaliacao | `id`; `(marcacao_id, barbeiro_id)`; `(marcacao_id, servico_id)` | `id → marcacao_id, barbeiro_id, servico_id, classificacao, comentario` | **BCNF** |

## 2. Decomposições efetuadas (justificadas)

### 2.1 `dias_trabalho` (multivalor) — 1NF
**Problema:** `calendario(id, barbeiro_id, dias_trabalho = {1,2,3,4,5}, hora_inicio, hora_fim)` tem um atributo não atómico.
**Decomposição:** `calendario_dia(calendario_id, dia_semana)`.
**Ganho:** consultas por dia sem processar texto/array; PK composta impede o mesmo dia duas vezes.

### 2.2 Serviços da marcação — 1NF
**Problema:** a decisão do grupo é "uma marcação tem lista de serviços". Guardar a lista numa coluna (`servicos = {1,3}`) viola 1NF e impede JOIN com `servico`.
**Decomposição:** `marcacao_servico(marcacao_id, servico_id, preco_aplicado)`.

## 3. Redundâncias aparentes que **não** são violação

| Atributo | Parece redundante porque... | Porque é aceitável |
|---|---|---|
| `marcacao_servico.preco_aplicado` | duplica `servico.preco` | Não depende só de `servico_id`: depende de `(marcacao_id, servico_id)`, é o preço no momento da marcação. `servico.preco` pode mudar. É um facto histórico. |
| `cliente_papel`, `barbeiro_papel` (colunas geradas constantes) | duplicam `utilizador.papel` | São constantes geradas (`'cliente'`, `'barbeiro'`), sem valor próprio: só existem para a FK composta validar o papel. Não podem ficar inconsistentes. |
| `pagamento.montante` | pode ser calculável a partir de `marcacao_servico` / `plano.preco_mensal` | Pode haver descontos, reembolsos parciais ou preço de plano alterado. O montante cobrado é um facto financeiro independente e tem de ficar fixo. |

## 4. Dependências que **não existem** (e porque deixam de ser problema)

- `cortes_disponiveis` **não é guardado**: é derivável (`cortes_por_mes − marcações cobertas no mês`, RN05). Guardá-lo criaria dependência calculável e risco de inconsistência. É calculado em Q04.
- `avaliacao.cliente_id` **não existe**: o cliente é o da marcação (`marcacao_id → cliente_id`). Evita dependência transitiva (`avaliacao → marcacao → cliente`), ou seja, 3NF.
- A duração total e o preço total da marcação **não são guardados**: derivam de `marcacao_servico`.

## 5. Conclusão

Após as duas decomposições da §2, todas as relações estão em **3NF**, e na prática em **BCNF**. Não houve decomposição artificial.
