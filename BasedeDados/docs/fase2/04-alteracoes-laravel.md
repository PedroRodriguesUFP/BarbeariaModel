# O que alterar no Laravel para ficar compatível com o SQL final

Base: repo `PedroRodriguesUFP/BarbeariaModel`, commit `bdde564`. A lista vem da **leitura** das migrations, models, controllers, policies, views, seeders e testes; **não executei o Laravel** contra esta BD, por isso a lista pode ter omissões — corre `php artisan test` depois de cada passo.

Atalho: as migrations têm de acabar a criar o mesmo que `sql/01_schema.sql`. A forma mais simples é substituir as migrations afetadas (ou acrescentar migrations novas de alteração). O Laravel tem de usar PostgreSQL: o `.env.example` traz `DB_CONNECTION=sqlite`; passar a `pgsql` (host, porta, base, utilizador).

## A. Obrigatórias (a app falha ou a BD recusa dados)

### A1. `appointments.time` passa a NOT NULL
- `database/migrations/2026_10_05_184357_create_appointments_table.php`: `$table->time('time')->nullable()` → `$table->time('time')`.
- Os controllers já validam `time` como `required` (`AppointmentController` linhas ~59 e ~186, `CalendarController` ~117), por isso não mudam. O parâmetro `?string $time = null` em `Appointment::makeAppointment()` e `updateAppointment()` deve passar a `string $time`.

### A2. Walk-in: restrições em falta
- `database/migrations/2026_10_18_184657_make_customer_id_nullable_and_add_walk_in_fields_to_appointments_table.php`: acrescentar, no `up()`:
  ```php
  DB::statement("ALTER TABLE appointments ADD CONSTRAINT ck_appointments_customer_or_walkin CHECK (customer_id IS NOT NULL OR walk_in_name IS NOT NULL)");
  DB::statement("ALTER TABLE appointments ADD CONSTRAINT ck_appointments_subscription_needs_customer CHECK (subscription_id IS NULL OR customer_id IS NOT NULL)");
  ```
  (a segunda só depois de existir `subscription_id`, ver B1).
- `CalendarController` já exige `walk_in_name` quando `client_type = walk_in`; sem alterações.

### A3. Pagamentos: `payer_id` desaparece
O SQL não tem `payer_id` (derivável: `appointments.customer_id`; `02-normalizacao.md` §3).
- `database/migrations/2026_10_05_191000_create_payments_table.php`: remover `payer_id`; `appointment_id` passa a **anulável** (porque o pagamento pode ser de uma subscrição); acrescentar `subscription_id` anulável (ver B1) e o `CHECK`:
  ```php
  DB::statement("ALTER TABLE payments ADD CONSTRAINT ck_payments_single_origin CHECK (num_nonnulls(appointment_id, subscription_id) = 1)");
  ```
- `app/Models/Payment.php`: tirar `payer_id` do `$fillable`; substituir a relação `payer()` por um acessor que devolve `$this->appointment?->customer ?? $this->subscription?->user`; acrescentar `subscription_id` ao `$fillable` e a relação `subscription()`.
- `app/Http/Controllers/PaymentController.php`: remover `'payer_id' => auth()->id()` (linhas ~28, ~105) e a referência de ~200; a verificação «só podes pagar marcações feitas por ti» (`customer_id === auth()->id()`) **mantém-se** e é o que substitui o `payer_id`.
- `app/Policies/PaymentPolicy.php` (`view`, `update`, `delete`): trocar `$user->id === $payment->payer_id` por `$user->id === $payment->appointment?->customer_id` (e, se vierem a existir pagamentos de subscrição, `|| $user->id === $payment->subscription?->user_id`).
- `database/seeders/PaymentSeeder.php` (linha ~22): remover `'payer_id'`.
- `resources/views/payments/index.blade.php` (~52), `show.blade.php` (~60), `edit.blade.php` (~27): trocar `$payment->payer` / `$payment->payer_id` pelo novo acessor.
- `tests/Feature/PaymentTest.php`: retirar as asserções e dados com `payer_id` (linhas ~35–153); o teste «payer_id spoofado» deixa de fazer sentido — substituir por «cliente só paga marcações suas».

### A4. `paid_at` obrigatório quando `status = paid`
A BD recusa `status='paid'` sem `paid_at`; o `PaymentController` aceita `paid_at` como `nullable` (linhas ~80 e ~186).
- No `store()` e `update()`: `'paid_at' => ['nullable','date','required_if:status,paid']`, ou preencher `now()` quando o estado é `paid` e o campo vem vazio.

### A5. Avaliações: `morphs('reviewable')` → `barber_id` / `service_id`
- `database/migrations/2026_10_05_190000_create_reviews_table.php`: substituir `$table->morphs('reviewable')` por `barber_id` (FK `users`, anulável), `service_id` (FK `services`, anulável), e acrescentar:
  ```php
  DB::statement("ALTER TABLE reviews ADD COLUMN barber_role VARCHAR(20) GENERATED ALWAYS AS ('barber') STORED");
  DB::statement("ALTER TABLE reviews ADD CONSTRAINT fk_reviews_barber FOREIGN KEY (barber_id, barber_role) REFERENCES users (id, role) ON DELETE CASCADE");
  DB::statement("ALTER TABLE reviews ADD CONSTRAINT ck_reviews_single_target CHECK (num_nonnulls(barber_id, service_id) = 1)");
  DB::statement("ALTER TABLE reviews ADD CONSTRAINT ck_reviews_rating CHECK (rating BETWEEN 1 AND 5)");
  DB::statement("ALTER TABLE reviews ADD CONSTRAINT ck_reviews_not_self CHECK (barber_id IS NULL OR reviewer_id <> barber_id)");
  ```
  (antes é preciso o `UNIQUE (id, role)` em `users`, ver A6).
- `app/Models/Review.php`: `$fillable` passa de `reviewable_type/reviewable_id` para `barber_id/service_id`; `reviewable()` (MorphTo) → duas `belongsTo` (`barber()`, `service()`); `isAboutBarber()` = `barber_id !== null`; `isAboutService()` = `service_id !== null`.
- `app/Models/User.php` (`reviewsReceived()`, ~linha 140) e `app/Models/Service.php` (~linha 36): trocar `morphMany(Review::class,'reviewable')` por `hasMany(Review::class,'barber_id')` / `hasMany(Review::class,'service_id')`.
- `app/Http/Controllers/ReviewController.php` (`store` e `update`; ~linhas 20–200): o formulário envia `reviewable_type`+`reviewable_id`; mapear para `barber_id` (se `User::class`) ou `service_id` (se `Service::class`), mantendo a validação `isBarber()`. Pode manter-se o mesmo formulário e fazer o mapeamento só no controller.
- `app/Http/Controllers/BarberController.php`, `app/Policies/ReviewPolicy.php`, `resources/views/reviews/{create,index,edit,show}.blade.php`, `resources/views/barber/reviews.blade.php`: substituir `->reviewable` / `reviewable_type` / `reviewable_id` pelas relações novas.
- `database/seeders/ReviewSeeder.php` e `tests/Feature/ReviewTest.php`: idem.

### A6. Papel `barber` garantido pela BD (RN02)
- `database/migrations/2026_10_04_173035_create_users_table.php`: acrescentar `$table->unique(['id','role'])` e `CHECK (role IN ('admin','barber','client'))`.
- `service_user`, `working_hours`, `schedule_blocks`: acrescentar a coluna gerada e a FK composta (exemplo para `working_hours`):
  ```php
  DB::statement("ALTER TABLE working_hours ADD COLUMN user_role VARCHAR(20) GENERATED ALWAYS AS ('barber') STORED");
  DB::statement("ALTER TABLE working_hours ADD CONSTRAINT fk_working_hours_barber FOREIGN KEY (user_id, user_role) REFERENCES users (id, role) ON DELETE CASCADE");
  ```
  Migrations: `2026_10_05_192000_create_service_user_table.php`, `2026_10_14_120939_create_working_hours_table.php`, `2026_10_14_121710_create_schedule_blocks_table.php`.
- Efeito na app: tentar associar horário ou serviço a um utilizador que não é barbeiro passa a dar erro de BD. Hoje `Admin/BarberController` já só trabalha com barbeiros; confirmar nos testes.

### A7. `appointments` → FK composta de habilitação (RN03) e índice único (RN07)
- Na migration de `appointments` (ou nova): 
  ```php
  DB::statement("ALTER TABLE appointments ADD CONSTRAINT fk_appointments_barber_service FOREIGN KEY (barber_id, service_id) REFERENCES service_user (user_id, service_id) ON DELETE RESTRICT");
  DB::statement("CREATE UNIQUE INDEX uq_appointments_barber_slot ON appointments (barber_id, date, time) WHERE status <> 'cancelled'");
  DB::statement("ALTER TABLE appointments ADD CONSTRAINT ck_appointments_customer_not_barber CHECK (customer_id <> barber_id)");
  ```
  O `UNIQUE (user_id, service_id)` que o Laravel já cria em `service_user` serve de alvo à FK.
- `AppointmentController`/`CalendarController`: já verificam disponibilidade com `isAvailableFor()`, mas devem **apanhar** `QueryException` (SQLSTATE 23505/23503) e devolver mensagem amigável («horário já ocupado», «barbeiro não habilitado») em vez de erro 500.

### A8. `ON DELETE` em `appointments`
O Laravel usa `cascadeOnDelete()` em `customer_id`, `barber_id`, `service_id`; o SQL usa `RESTRICT` (não se apagam utilizadores/serviços com marcações; desativa-se com `active`/`is_active`). Mudar para `->restrictOnDelete()` ou documentar a diferença. Idem em `payments.appointment_id` (SQL: `RESTRICT`).

## B. Subscrições (não existem no Laravel)

### B1. Migrations e models novos
- `create_subscription_plans_table`: `name` (único), `description?`, `monthly_price` (decimal 8,2, `CHECK >= 0`), `cuts_per_month` (`CHECK > 0`), `active`, timestamps.
- `create_subscriptions_table`: `user_id` (FK `users`), `subscription_plan_id` (FK, restrict), `start_date`, `end_date?`, `status` (`CHECK IN ('active','suspended','cancelled','expired')`), `CHECK (end_date IS NULL OR end_date >= start_date)`, `UNIQUE (id, user_id)`, e o índice parcial `UNIQUE (user_id) WHERE status = 'active'`.
- `add_subscription_id_to_appointments`: `subscription_id` anulável + `FOREIGN KEY (subscription_id, customer_id) REFERENCES subscriptions (id, user_id)`.
- `add_subscription_id_to_payments` (ver A3).
- `app/Enums/SubscriptionStatus.php` (`active`, `suspended`, `cancelled`, `expired`); `app/Models/SubscriptionPlan.php`, `app/Models/Subscription.php`; relações em `User` (`subscriptions()`), `Appointment` (`subscription()`), `Payment` (`subscription()`).
- Não há controllers/views para subscrições: só são precisas se a app as vier a usar. A BD já funciona sem eles.

## C. Diferenças de framework (não precisam de alterar nada)

- `service_user.id` e `service_user.timestamps` do Laravel vs. PK composta do SQL (`UNIQUE (user_id, service_id)` já existe, a app funciona igual).
- `users.google_id`, `email_verified_at`, `remember_token`; tabelas `sessions`, `password_reset_tokens`, `migrations`, `cache`, `jobs`: ficam só no Laravel.
- `created_at/updated_at` existem nas duas; `NOT NULL DEFAULT CURRENT_TIMESTAMP` no SQL.
- `users.password` anulável: já feito pela migration `make_password_nullable_on_users_table`.
- Enums: os valores já coincidem (`UserRole`, `Status`, `Category`); métodos de pagamento e estados de pagamento estão validados no `PaymentController` com os mesmos valores do `CHECK`.
- Tipos: o Laravel usa `bigint unsigned`/`string(255)`; em PostgreSQL ficam `bigint`/`varchar(255)`.
