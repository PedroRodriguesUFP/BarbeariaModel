# O que alterar no diagrama ER (Chen)

Ficheiro revisto: `BasedeDados/modelo/modelo_chen_barbearia.drawio.png`. Só vi o Chen (o EA e o UML da mesma pasta não foram revistos). O ficheiro `.drawio` fonte não está no repo (só PNGs), por isso a edição tem de ser feita a partir de uma cópia do draw.io que tenham localmente.

## Entidades

| # | Alteração |
|---|---|
| E1 | **UTILIZADOR**: acrescentar `ativo`; `palavra_passe` passa a elipse tracejada (opcional, login externo); acrescentar `telefone` (opcional) e `biografia` (opcional) aqui ou mantê-los nas especializações, mas coerentes com o SQL (`users.phone`, `users.bio`). |
| E2 | **CLIENTE**: remover `nif`. `id_cliente` e `id_barbeiro` são identificadores de especialização: no relacional ambos são `users.id`. |
| E3 | **MARCAÇÃO**: `hora` deixa de ser tracejada (obrigatória). Acrescentar `nome_cliente_balcao` e `telefone_cliente_balcao` (ambos tracejados). |
| E4 | **PAGAMENTO**: sem alterações de atributos. |
| E5 | **AVALIAÇÃO**: acrescentar o atributo `data` (→ `created_at`). |
| E6 | **Nova entidade HORÁRIO_TRABALHO** (entidade fraca de BARBEIRO): `dia_semana`, `hora_inicio`, `hora_fim`. Cardinalidade BARBEIRO (0,1) — «tem» — (0,N). |
| E7 | **Nova entidade BLOQUEIO_AGENDA** (fraca de BARBEIRO): `data`, `hora_inicio` (tracejado), `hora_fim` (tracejado), `dia_inteiro`, `motivo` (tracejado). |
| E8 | **Nova entidade PLANO_SUBSCRIÇÃO**: `id`, `nome`, `descricao` (trac.), `preco_mensal`, `cortes_por_mes`, `ativo`. |
| E9 | **Nova entidade SUBSCRIÇÃO**: `id`, `data_inicio`, `data_fim` (trac.), `estado`. |

## Relações

| # | Alteração |
|---|---|
| R1 | **CLIENTE — agenda — MARCAÇÃO**: do lado da marcação passa a **0..1** (nulo = cliente walk-in, sem conta). |
| R2 | **Especialização**: hoje CLIENTE e BARBEIRO são `0..1` a partir de UTILIZADOR, mas a regra RN08 antiga foi removida e **um barbeiro pode ser cliente de outro barbeiro**. Mostrar que a especialização é **sobreposta** (um utilizador pode agir como cliente e como barbeiro), ou ligar «agenda» diretamente a UTILIZADOR. No SQL, `appointments.customer_id` aceita qualquer utilizador. |
| R3 | **MARCAÇÃO — é objeto de — SERVIÇO**: mantém-se **N:1** (cada marcação, exatamente um serviço): marcação **(1,1)**, serviço (0,N). Está correto para a Opção A; confirmar que o lado da marcação é obrigatório. |
| R4 | **BARBEIRO — executa — SERVIÇO (N:M)**: mantém-se. Acrescentar nota: «a marcação só é válida para um par (barbeiro, serviço) existente em *executa*» (RN03). |
| R5 | **Apagar PAGAMENTO — efetua — CLIENTE** e **PAGAMENTO — recebe — BARBEIRO**: o pagador e o barbeiro derivam da marcação (dependência transitiva, `02-normalizacao.md` §3). |
| R6 | **MARCAÇÃO — origina — PAGAMENTO**: o lado da marcação passa de `1` para **(0,1)** no pagamento (um pagamento pode ter origem numa subscrição). |
| R7 | **Nova relação SUBSCRIÇÃO — origina — PAGAMENTO** (subscrição (0,N), pagamento (0,1)) com nota **XOR**: «cada pagamento tem exatamente uma origem: marcação OU subscrição» (RN04). |
| R8 | **Nova relação SUBSCRIÇÃO — cobre — MARCAÇÃO** (subscrição (0,N), marcação (0,1)) com nota: «a subscrição pertence ao cliente da marcação» (RN05). |
| R9 | **Novas relações** UTILIZADOR (cliente) — contrata — SUBSCRIÇÃO (1,1):(0,N) e PLANO_SUBSCRIÇÃO — é contratado em — SUBSCRIÇÃO (1,1):(0,N). |
| R10 | **AVALIAÇÃO**: as relações «escreve» (CLIENTE→avaliação) e «é avaliado em» (barbeiro e serviço) estão corretas e **não** se liga à marcação (decisão em `00-correcoes-fase1.md` §4). Garantir que a nota XOR («barbeiro OU serviço») se mantém. «escreve» deve partir de UTILIZADOR se os barbeiros puderem avaliar (o seed tem uma avaliação de barbeiro a barbeiro). |

## Notas (caixas amarelas)

Acrescentar: «Uma subscrição ativa por utilizador (RN08)»; «Ninguém marca consigo próprio (RN09)»; «Um barbeiro não tem duas marcações ativas à mesma hora (RN07)»; «Só barbeiros têm horário, bloqueios e habilitações (RN02)». A nota «Barbeiro–Serviço é N:M (tabela de junção barber_service…)» deve usar o nome físico `service_user`.
