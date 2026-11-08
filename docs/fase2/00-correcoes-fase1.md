# Correções à Fase 1 (v1.0 → v2.0)

A Fase 2 obriga a corrigir a Fase 1 sempre que a análise do modelo relacional expõe falhas. Lista das alterações feitas e porquê. Estas alterações devem ser refletidas em `fase1.md` e no diagrama ER antes de fechar a tag `v2.0`.

| # | Problema na v1.0 | Correção | Impacto |
|---|---|---|---|
| C1 | `MARCACAO` não tinha relacionamento com `SERVICO`, apesar de RD05 falar em "um determinado serviço". Sem isto NI02, NI05, NI06 e NI07 não são respondíveis. | Novo relacionamento **N:M** `MARCACAO — inclui — SERVICO`, com atributo `preco_aplicado`. Uma marcação tem 1..N serviços (decisão do grupo: combinações livres). | RD05 reescrito; nova tabela `marcacao_servico` |
| C2 | RN03 (barbeiro habilitado a serviços) não tinha entidade nem relacionamento. | Novo relacionamento **N:M** `BARBEIRO — executa — SERVICO`. Novo RD10. | Nova tabela `barbeiro_servico` |
| C3 | `AVALIACAO` sem relacionamentos (quem avalia, o quê). | `AVALIACAO` passa a incidir numa `MARCACAO` e exatamente num alvo: barbeiro **ou** serviço dessa marcação (RN06). O cliente é o da marcação (não se repete). | FK compostas em `avaliacao` |
| C4 | Cardinalidade de `PAGAMENTO` (0,1):(0,N) não exprimia "exatamente uma origem". | Passa a: `PAGAMENTO` tem 1 origem, que é marcação **XOR** subscrição (RN04). Uma marcação/subscrição pode ter 0..N pagamentos (ex.: tentativa falhada + nova tentativa; mensalidades mensais). | `CHECK num_nonnulls(...) = 1` |
| C5 | `CALENDARIO.dias_trabalho` multivalor (array). Viola 1NF. | Atributo multivalor → entidade fraca `DIA_TRABALHO` (ou atributo multivalor no ER). | Tabela `calendario_dia` |
| C6 | `MARCACAO.hora` era opcional. Sem hora não há como evitar dois clientes no mesmo barbeiro à mesma hora, nem calcular ocupação. | `hora` obrigatória. | `NOT NULL` |
| C7 | Faltava regra de conflito de horário. | **Nova RN07**: um barbeiro não pode ter duas marcações ativas que se sobreponham no tempo. (Fase 2 garante o mesmo instante de início; sobreposição por duração fica para trigger na Fase 3.) | índice único parcial |
| C8 | Faltava regra de papel nas relações. | **Nova RN08**: `MARCACAO.cliente` só pode ser utilizador com papel `cliente`; `MARCACAO.barbeiro` só `barbeiro`; idem para subscrição. | FK composta `(id, papel)` |
| C9 | Faltava regra de unicidade de subscrição. | **Nova RN09**: um cliente tem no máximo uma subscrição `ativa`. | índice único parcial |
| C10 | `fase1.md` usa termos de implementação (`TINYINT`, `Hash`, `ENUM`). O enunciado proíbe decisões de SGBD na Fase 1. | Na Fase 1 ficam domínios conceptuais (inteiro 1..5, texto, valor monetário). Tipos concretos só na Fase 2. | só documentação |
| C11 | `AVALIACAO` sem data própria; consultas temporais (NI01) usam a data da marcação. | Aceite como decisão: a data da avaliação é irrelevante para as NI. Fica registado. | nenhum |

## Requisitos e regras novos/alterados

- **RD05 (alterado):** "...reserva realizada por um cliente com um barbeiro para a prestação de **um ou mais serviços**..."
- **RD10 (novo):** O sistema deve registar quais os serviços que cada barbeiro está habilitado a executar.
- **RN07 (nova):** Um barbeiro não pode ter duas marcações ativas (não canceladas) em sobreposição temporal.
- **RN08 (nova):** O cliente de uma marcação/subscrição tem papel `cliente`; o barbeiro de uma marcação tem papel `barbeiro`.
- **RN09 (nova):** Um cliente tem no máximo uma subscrição no estado `ativa`.
- **RN05 (clarificada):** "Marcações cobertas" = marcações do mês ligadas à subscrição e não canceladas.

## Matriz de relacionamentos corrigida

| Origem | Relação | Destino | Cardinalidade (origem : destino) |
|---|---|---|---|
| UTILIZADOR (barbeiro) | possui | CALENDARIO | (0,1) : (1,1) |
| CALENDARIO | tem | DIA_TRABALHO | (1,1) : (1,N) |
| UTILIZADOR (cliente) | agenda | MARCACAO | (1,1) na marcação : (0,N) no cliente |
| UTILIZADOR (barbeiro) | realiza | MARCACAO | (1,1) na marcação : (0,N) no barbeiro |
| MARCACAO | inclui | SERVICO | N:M, atributo `preco_aplicado`, marcação (1,N) |
| BARBEIRO | executa | SERVICO | N:M, barbeiro (0,N), serviço (0,N) |
| UTILIZADOR (cliente) | contrata | SUBSCRICAO | (1,1) na subscrição : (0,N) |
| PLANO_SUBSCRICAO | é contratado em | SUBSCRICAO | (1,1) na subscrição : (0,N) no plano |
| SUBSCRICAO | cobre | MARCACAO | (0,1) na marcação : (0,N) |
| MARCACAO | origina | PAGAMENTO | marcação (0,N) : pagamento (0,1) |
| SUBSCRICAO | origina | PAGAMENTO | subscrição (0,N) : pagamento (0,1) — pagamento tem exatamente 1 origem |
| MARCACAO | é avaliada em | AVALIACAO | marcação (0,N) : avaliação (1,1) |
| AVALIACAO | incide em | BARBEIRO **ou** SERVICO | exatamente um dos dois |
