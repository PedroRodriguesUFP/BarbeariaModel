# Normalização (1NF, 2NF, 3NF)

Para cada relação: chaves candidatas, dependências funcionais (DF) e forma normal. Nenhuma decomposição foi feita só para «mostrar o processo».

## 1. Análise por relação

| Relação | Chaves candidatas | DF relevantes | Forma normal |
|---|---|---|---|
| users | `id`; `email` | `id → name, email, phone, bio, password, role, active`; `email → id` | **BCNF**. O `UNIQUE (id, role)` é uma superchave (contém `id`). |
| services | `id` | `id → name, description, price, duration_minutes, category, is_active` | **BCNF**. `name` não é único no repo (a app admite repetidos), logo não é chave. |
| service_user | `(user_id, service_id)` | sem atributos não-chave | **BCNF** (relação «toda chave»; 2NF trivial). |
| working_hours | `id`; `(user_id, day_of_week, start_time, end_time)` | `id → user_id, day_of_week, start_time, end_time` | **BCNF**. |
| schedule_blocks | `id` | `id → user_id, date, start_time, end_time, all_day, reason` | **BCNF**. `all_day` não determina `start_time/end_time`: só restringe os seus valores (CHECK). |
| subscription_plans | `id`; `name` | `id → name, description, monthly_price, cuts_per_month, active` | **BCNF**. |
| subscriptions | `id`; `(id, user_id)` | `id → user_id, subscription_plan_id, start_date, end_date, status` | **BCNF**. `cuts_per_month` fica no plano, não aqui. |
| appointments | `id` | `id → customer_id, walk_in_name, walk_in_phone, barber_id, service_id, subscription_id, date, time, status` | **2NF; viola a 3NF só pela DF `subscription_id → customer_id`** (redundância controlada) — ver §2. |
| payments | `id` | `id → appointment_id, subscription_id, amount, method, status, paid_at` | **BCNF** (depois de retirar `payer_id`, §3). |
| reviews | `id` | `id → reviewer_id, barber_id, service_id, rating, comment, created_at` | **BCNF**. |

**1NF:** todos os atributos são atómicos. O atributo multivalor «dias de trabalho» da Fase 1 não existe: o horário é uma linha por (barbeiro, dia, período) em `working_hours`. Isto é o que o repo já faz, por isso a «decomposição» 1NF coincide com o desenho da app.

**2NF:** só se coloca em relações com chave composta; a única é `service_user`, sem atributos não-chave.

**Decomposições efetuadas:** nenhuma para além da do atributo multivalor acima. `walk_in_name`/`walk_in_phone` dependem só de `appointments.id` e ficam na marcação (separá-los daria uma tabela 1:1 opcional sem ganho).

## 2. Redundância deliberada em `appointments`: `customer_id` e `subscription_id`

Existe a DF **`subscription_id → customer_id`** (uma subscrição pertence a um único utilizador; duas marcações com a mesma `subscription_id` têm o mesmo `customer_id`). `subscription_id` não é chave de `appointments`, e `customer_id` não é atributo primo, por isso, em rigor, isto **viola a 3NF** (dependência transitiva `id → subscription_id → customer_id`).

**Porque se aceita:**

- `customer_id` é um facto próprio da marcação independentemente de haver subscrição (marcações avulsas também têm cliente). Retirá-lo das marcações cobertas e derivá-lo da subscrição tornaria `customer_id` condicional e complicaria todas as consultas por cliente.
- Para garantir **declarativamente** que a subscrição pertence ao cliente da marcação (RN05) é preciso ter `customer_id` ao lado de `subscription_id` e uma FK composta `(subscription_id, customer_id) → subscriptions(id, user_id)`. Qualquer desenho alternativo (tabela `appointment_coverage`, por exemplo) precisaria de repetir `customer_id` pelo mesmo motivo, ou de um trigger.
- A FK composta torna a inconsistência **impossível** (teste 28 rejeita uma subscrição de outro cliente). A redundância não pode dessincronizar.

É uma exceção consciente à 3NF, trocada por integridade declarativa; fica assinalada para a defesa.

## 3. Dependências que **não** existem (e porquê)

| Atributo | Decisão | Razão |
|---|---|---|
| `payments.payer_id` (Laravel) | **Não existe** na BD | Para pagamentos de marcações, `id → appointment_id → customer_id` (o `PaymentController` só deixa pagar marcações do próprio utilizador autenticado, logo `payer_id = customer_id`); para mensalidades, `subscription_id → user_id`. Guardá-lo seria dependência transitiva. Em marcações walk-in nem existe pagador com conta. |
| cortes disponíveis no mês | Não guardado | Deriva de `cuts_per_month` e das marcações cobertas (RN05); calculado em Q04. |
| preço/duração total | Não guardados | Deriva de `services` (um serviço por marcação). |
| `user_role` / `barber_role` (colunas geradas `'barber'`) | Existem fisicamente | São constantes geradas, sem valor próprio; existem só para a FK composta validar o papel. Não podem divergir de `users.role`: a FK recusa o resto. |
| `reviews.reviewer_id` | Mantido | As avaliações não estão ligadas a marcações, logo o autor não se pode derivar de nenhuma outra relação. |

## 4. Redundâncias aparentes que **não** são violação

| Atributo | Parece redundante porque… | Porque é aceitável |
|---|---|---|
| `payments.amount` | pode calcular-se de `services.price` / `subscription_plans.monthly_price` | É o montante efetivamente cobrado: pode haver descontos, reembolsos ou preços que mudam depois. É um facto financeiro. |
| `appointments.status` vs. `payments.status` | ambos têm «estado» | São ciclos de vida diferentes (reserva vs. cobrança). |

## 5. Conclusão

Todas as relações estão em BCNF, **exceto** `appointments`: está em 2NF e só falha a 3NF pela DF `subscription_id → customer_id` de §2, que é uma redundância controlada pela FK composta. Não houve decomposições artificiais.
