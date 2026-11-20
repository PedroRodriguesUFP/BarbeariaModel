# API REST (v1)

Base URL: `https://<dominio>/api/v1`  
Formato: JSON (pedidos e respostas). Autenticação: **Bearer token**.

## Autenticação

1. `POST /auth/token` com email e password devolve um token (válido 30 dias).
2. Enviar o token em todos os outros pedidos: `Authorization: Bearer <token>`.
3. Na base de dados só é guardado o **hash SHA-256** do token. Se o perder, gere outro.

| Método | Rota | Descrição | Autenticação | Sucesso | Erros |
|---|---|---|---|---|---|
| POST | `/auth/token` | Obter token (`email`, `password`, `device_name`) | — (5 pedidos/min) | 201 | 422, 429 |
| DELETE | `/auth/token` | Revogar o token usado | Bearer | 204 | 401 |
| GET | `/me` | Utilizador autenticado | Bearer | 200 | 401 |

```bash
curl -X POST https://<dominio>/api/v1/auth/token \
  -H "Content-Type: application/json" \
  -d '{"email":"cliente@exemplo.pt","password":"password","device_name":"curl"}'
```

## Serviços (`/services`)

| Método | Rota | Quem pode | Sucesso | Erros |
|---|---|---|---|---|
| GET | `/services` | qualquer utilizador autenticado | 200 (paginado, 15/página) | 401 |
| GET | `/services/{id}` | qualquer utilizador autenticado | 200 | 401, 404 |
| POST | `/services` | só admin | 201 | 401, 403, 422 |
| PUT/PATCH | `/services/{id}` | só admin | 200 | 401, 403, 404, 422 |
| DELETE | `/services/{id}` | só admin | 204 | 401, 403, 404 |

Corpo (POST/PUT): `name` (obrigatório), `description`, `price` (≥ 0), `duration_minutes` (≥ 1),
`category` (`hair`, `beard`, `hair and beard`, `fade`, `coloring`).

## Marcações (`/appointments`)

Regras de acesso iguais às da aplicação web (`AppointmentPolicy`):
cliente vê só as suas, barbeiro as que tem a cargo, admin todas.

| Método | Rota | Quem pode | Sucesso | Erros |
|---|---|---|---|---|
| GET | `/appointments?estado=pending` | autenticado (filtrado por perfil) | 200 (paginado) | 401 |
| GET | `/appointments/{id}` | cliente/barbeiro da marcação, admin | 200 | 401, 403, 404 |
| POST | `/appointments` | cliente (para si) ou admin | 201 | 401, 403, 422 |
| PUT/PATCH | `/appointments/{id}` | envolvidos, enquanto em aberto | 200 | 401, 403, 404, 422 |
| DELETE | `/appointments/{id}` | envolvidos, enquanto em aberto | 204 | 401, 403, 404 |

Corpo (POST/PUT): `date` (hoje ou futuro), `time` (`HH:MM`), `barber_id`, `service_id`;
o admin envia ainda `customer_id` no POST.
Devolve **422** se o barbeiro não faz o serviço, está fora do horário, tem um bloqueio ou já tem marcação nessa hora, ou se o dia é feriado nacional (serviço externo de feriados).

## Códigos de estado

| Código | Significado |
|---|---|
| 200 / 201 / 204 | Sucesso / criado / sem conteúdo |
| 401 | Token em falta, inválido, expirado, ou conta desativada |
| 403 | Autenticado mas sem permissão (Policy) |
| 404 | Recurso não existe |
| 422 | Dados inválidos (`errors` indica os campos) |
| 429 | Limite de pedidos excedido (60/min; 5/min no login) |

## Webhook Stripe

`POST /api/stripe/webhook`: chamado pela Stripe, protegido pela assinatura (`STRIPE_WEBHOOK_SECRET`), idempotente. Não usa token.
