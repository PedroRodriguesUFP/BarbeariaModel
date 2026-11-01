# Modelo relacional e mapeamento ER → relacional

Notação: **PK** chave primária, *FK* chave estrangeira, `UK` chave candidata única. Esquema físico em `sql/01_schema.sql` (PostgreSQL 16, esquema `public`). Nomes e valores são os do Laravel (ver `00-correcoes-fase1.md` §1).

## 1. Relações

**users**(**id**, name, email `UK`, phone?, bio?, password?, role, active, created_at, updated_at)
  `UK (id, role)` — alvo das FK compostas que validam o papel `barber`.

**services**(**id**, name, description?, price, duration_minutes, category, is_active, created_at, updated_at)

**service_user**(***user_id***, ***service_id***, user_role *(gerada = 'barber')*, created_at)
  FK `(user_id, user_role)` → users `(id, role)`; FK `service_id` → services

**working_hours**(**id**, *user_id*, user_role *(gerada)*, day_of_week, start_time, end_time, created_at, updated_at)
  FK `(user_id, user_role)` → users; `UK (user_id, day_of_week, start_time, end_time)`

**schedule_blocks**(**id**, *user_id*, user_role *(gerada)*, date, start_time?, end_time?, all_day, reason?, created_at, updated_at)
  FK `(user_id, user_role)` → users

**subscription_plans**(**id**, name `UK`, description?, monthly_price, cuts_per_month, active, created_at, updated_at)

**subscriptions**(**id**, *user_id*, *subscription_plan_id*, start_date, end_date?, status, created_at, updated_at)
  `UK (id, user_id)` (alvo da FK composta de appointments); índice único parcial `(user_id) WHERE status = 'active'`

**appointments**(**id**, *customer_id*?, walk_in_name?, walk_in_phone?, *barber_id*, *service_id*, *subscription_id*?, date, time, status, created_at, updated_at)
  FK `customer_id` → users; FK `barber_id` → users; FK `service_id` → services;
  FK `(barber_id, service_id)` → service_user `(user_id, service_id)`;
  FK `(subscription_id, customer_id)` → subscriptions `(id, user_id)`;
  índice único parcial `(barber_id, date, time) WHERE status <> 'cancelled'`

**payments**(**id**, *appointment_id*?, *subscription_id*?, amount, method, status, paid_at?, created_at, updated_at)
  FK → appointments; FK → subscriptions; `CHECK num_nonnulls(appointment_id, subscription_id) = 1`

**reviews**(**id**, *reviewer_id*, *barber_id*?, barber_role *(gerada)*, *service_id*?, rating, comment?, created_at, updated_at)
  FK `reviewer_id` → users; FK `(barber_id, barber_role)` → users `(id, role)`; FK `service_id` → services;
  `CHECK num_nonnulls(barber_id, service_id) = 1`

## 2. Decisões de mapeamento

| # | Decisão | Alternativa rejeitada | Porquê |
|---|---|---|---|
| M1 | **Generalização → uma só tabela `users`** com `role`. O Chen tem CLIENTE e BARBEIRO como entidades; ambas colapsam em `users`. | Uma tabela por subtipo. | O Laravel já tem `users.role`. Os subtipos só diferem em colunas opcionais (`bio`) e um barbeiro **pode ser cliente** (README do repo), o que uma partição disjunta em tabelas impediria sem duplicar contas. |
| M2 | **Papel `barber` garantido por FK composta** `(user_id, user_role) → users(id, role)`, em que `user_role` é coluna gerada constante `'barber'`. Aplica-se a `service_user`, `working_hours`, `schedule_blocks` e ao alvo de `reviews.barber_id` (RN02, RN06). | Trigger; ou confiar na app. | O enunciado prefere restrições declarativas. A coluna gerada só existe para a FK; não guarda informação. Em `appointments` não é preciso: `barber_id` tem de existir em `service_user`, logo é barbeiro (transitivamente). |
| M3 | **BARBEIRO executa SERVIÇO (N:M)** → `service_user` com PK composta. | Atributo em `users`. | RN03/RD10. A PK composta impede duplicados e é alvo de FK. |
| M4 | **RN03 sem trigger:** `FOREIGN KEY (barber_id, service_id) → service_user(user_id, service_id)` em `appointments`. | Trigger a validar. | Declarativo (melhoria do Pedro). Testes 19–21. |
| M5 | **Horário semanal** (`working_hours`): uma linha por barbeiro × dia × período. | Array `dias_trabalho` (viola 1NF); `calendario` + `calendario_dia` do zip. | É o que o repo implementa e permite horário diferente por dia e períodos partidos (o Rui tem manhã e tarde). |
| M6 | **Exceções** (`schedule_blocks`): dia inteiro **ou** intervalo, por `CHECK` que liga `all_day` à presença de horas. | Duas tabelas. | Mesma natureza; o CHECK evita estados contraditórios (testes 17–18). |
| M7 | **SERVIÇO 1 : N MARCAÇÃO** → `appointments.service_id` (Opção A). | Tabela `appointment_service` (Opção B). | Ver `00-correcoes-fase1.md` A1. |
| M8 | **Cliente walk-in:** `customer_id` anulável + `walk_in_name/phone` na própria marcação, com `CHECK (customer_id IS NOT NULL OR walk_in_name IS NOT NULL)`. | Tabela `walk_in_customer`. | O Laravel já guarda os campos na marcação; são 1:1 com a marcação, por isso separar seria uma decomposição artificial. |
| M9 | **Subscrição cobre marcação (0,1 : N):** `appointments.subscription_id` anulável, com FK composta `(subscription_id, customer_id) → subscriptions(id, user_id)` (RN05). | FK simples + trigger. | Garante declarativamente que a subscrição é do cliente. Com `customer_id` nulo a FK não é verificada, por isso há um CHECK extra (teste 29). A redundância `customer_id`/`subscription_id` é deliberada (ver `02-normalizacao.md` §2). |
| M10 | **Pagamento: origem XOR** com duas FK anuláveis e `num_nonnulls(...) = 1` (RN04). | Duas tabelas de pagamento. | Mesmos atributos; Q05 fica numa só tabela. |
| M11 | **Sem `payer_id`:** o pagador é `appointments.customer_id` ou `subscriptions.user_id`. | Manter `payer_id` como o Laravel. | Seria dependência transitiva (`02-normalizacao.md` §3) e em marcações walk-in não existe pagador com conta. |
| M12 | **Avaliação: alvo XOR** `barber_id`/`service_id` com `num_nonnulls = 1` (RN06), sem ligação à marcação (decisão em `00-correcoes-fase1.md` §4). Substitui o `morphs('reviewable')`, que não permite FK. | `morphs`; ligação à marcação. | FK reais para os dois alvos. |
| M13 | **Enums → `CHECK (col IN (...))`** com os valores do repo. | `ENUM` do PostgreSQL; tabelas de domínio. | Alterar valores é um `ALTER TABLE`; tabelas de domínio seriam exagero para listas fechadas e estáveis. |
| M14 | **Índices únicos parciais** para RN07 (`barber_id, date, time` onde `status <> 'cancelled'`) e RN08 (um `active` por utilizador). | Trigger. | Um slot cancelado liberta-se sem apagar histórico (teste 48). |
| M15 | **Chaves substitutas** `bigint identity` (BY DEFAULT); chaves naturais (`email`, `plan.name`) em `UNIQUE`. | Email como PK. | O e-mail pode mudar; é o que o Laravel faz. |

## 3. Domínios

| Atributo | Tipo | Restrição |
|---|---|---|
| email | `varchar(255)` | formato `x@y.z`, único |
| role | `varchar(20)` | `admin`, `barber`, `client` |
| price, amount, monthly_price | `numeric(8,2)` | ≥ 0 (nunca `float` para dinheiro) |
| duration_minutes, cuts_per_month | `integer` | > 0 |
| day_of_week | `smallint` | 1..7 (1 = segunda) |
| start_time < end_time | `time` | CHECK |
| date, time (marcação) | `date`, `time` | ambos `NOT NULL` |
| rating | `smallint` | 1..5 |
| paid_at | `timestamp` | obrigatório se `status = 'paid'` |

## 4. Rastreabilidade regra → mecanismo → teste

Os números de teste são os de `04_testes_integridade.sql` (execução real em `execucao_postgres16.txt`: 52 testes, 43 negativos, 9 positivos, 52 passam).

| Regra | Mecanismo | Testes |
|---|---|---|
| RN01 e-mail único | `UNIQUE (email)` | 1 |
| RD01 domínio de utilizador | `CHECK role`, `NOT NULL name`, `CHECK email` | 2–5 |
| RD04 catálogo | `CHECK price, duration, category` | 6–9 |
| RN02 só barbeiros | FK composta `(user_id, user_role)` | 10–13 |
| RD03 horários | `CHECK` intervalo, dia, `UNIQUE`, `CHECK all_day` | 14–18 |
| RN03 habilitação | FK composta `(barber_id, service_id) → service_user` | 19–21 |
| RD05 marcação | `CHECK status`, `NOT NULL time`, `CHECK customer_or_walkin` | 22–25, 44, 46 |
| RN07 sem conflito | índice único parcial | 26, 48 |
| RN09 ninguém marca consigo | `CHECK customer_id <> barber_id` | 27 (neg.), 45 (pos.) |
| RN05 subscrição do cliente | FK composta `(subscription_id, customer_id)` + CHECK | 28, 29, 47 |
| RD06/RD07 planos e subscrições | `CHECK` cortes, estado, datas | 30–32 |
| RN08 uma subscrição ativa | índice único parcial | 33 |
| RN04 origem única de pagamento | `CHECK num_nonnulls = 1` | 34, 35, 51 |
| RD08 pagamentos | `CHECK paid_at`, `method`, `amount` | 36–38 |
| RN06 alvo único da avaliação | `CHECK num_nonnulls = 1`, FK composta (alvo é barbeiro) | 39, 40, 42, 49, 50 |
| RD09 avaliações | `CHECK rating`; `CHECK` não se auto-avalia | 41, 43 |
| RD01 login externo | `password` anulável | 52 |

### Regras que o SQL declarativo **não** cobre (ficam para a Fase 3)

| Regra | Porquê não é declarativa | Fase 3 |
|---|---|---|
| RN05 quota: nº de marcações cobertas ≤ `cuts_per_month` no mês | Depende de contar linhas de outra tabela por mês. Fase 2 só a **calcula** (Q04). | trigger |
| RN07 sobreposição por duração (ex.: 10:00 + 40 min vs 10:20) | O índice só apanha o mesmo instante de início. | trigger ou `EXCLUDE` |
| Marcação dentro de `working_hours` e fora de `schedule_blocks` | Compara com intervalos noutras tabelas. | trigger |
| Intervalos de `working_hours` do mesmo dia a sobrepor-se | Só o `UNIQUE` do período exato. | `EXCLUDE` |
| Transições válidas de estado (`pending → confirmed → completed`) | Depende do estado anterior. | trigger |
