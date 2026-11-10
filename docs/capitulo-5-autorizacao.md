# Capítulo 5: Autenticação e autorização

## 5.1 Como se entra

| Forma | Como funciona |
|---|---|
| Email e palavra-passe | `POST /login`. O Laravel compara o hash guardado (cast `'hashed'` no modelo `User`). Só entram contas com `active = true`. Depois de entrar, a sessão é regenerada (impede a fixação de sessão). |
| Google | `GET /auth/google` leva ao Google (Socialite). No regresso procura o utilizador por `google_id`, depois por email, e se não existir cria um **cliente**. |
| Registo | `POST /registar`. Cria sempre um **cliente**. O papel nunca vem do formulário. |
| Recuperar palavra-passe | Resposta igual exista ou não a conta (não revela quem tem conta). Ao repor, as sessões abertas dessa conta são apagadas. |

Defesas na entrada: token CSRF em todos os formulários, limite de 5 tentativas de login por email e IP (`LoginRequest`) e limite de 6 registos por minuto.

## 5.2 Perfis

| Perfil | Valor em `users.role` | Como se cria |
|---|---|---|
| Cliente | `client` | Registo, Google, ou pelo admin |
| Barbeiro | `barber` | Só pelo admin |
| Administrador | `admin` | Só pelo admin / seeder |

O papel é um enum (`UserRole`), não texto livre.

## 5.3 Quem pode o quê

Legenda: ✔ pode, ✘ não pode, (cond.) só com a condição indicada.

| Recurso e ação | Visitante | Cliente | Barbeiro | Admin |
|---|---|---|---|---|
| Ver serviços (lista e detalhe) | ✔ | ✔ | ✔ | ✔ |
| Criar, editar, apagar serviços | ✘ | ✘ | ✘ | ✔ |
| Listar marcações | ✘ | ✔ só as suas | ✔ só as suas | ✔ todas |
| Ver uma marcação | ✘ | (cond.) se for o cliente | (cond.) se for o barbeiro | ✔ |
| Criar marcação | ✘ | ✔ | ✘ | ✔ (escolhe o cliente) |
| Editar / apagar marcação | ✘ | (cond.) se for a dele **e estiver pendente ou confirmada** | (cond.) se for o barbeiro | ✔ |
| Confirmar / concluir marcação | ✘ | ✘ | (cond.) se for o barbeiro | ✔ |
| Cancelar marcação | ✘ | (cond.) se for a dele | (cond.) se for o barbeiro | ✔ |
| Registar cliente sem conta (walk-in) | ✘ | ✘ | ✔ | ✘ |
| Pagar uma marcação com cartão | ✘ | (cond.) se for a dele, não cancelada e ainda por pagar | ✘ | ✘ |
| Ver pagamentos | ✘ | ✔ só os seus | ✔ só os seus | ✔ todos |
| Registar, editar, apagar pagamento à mão | ✘ | ✘ | ✘ | ✔ |
| Criar avaliação | ✘ | (cond.) marcação sua **concluída** e ainda não avaliada | ✘ | ✘ |
| Editar / apagar avaliação | ✘ | (cond.) se for o autor | ✘ | ✔ |
| Agenda e disponibilidade (`/calendar`, `/barber/*`) | ✘ | ✘ | ✔ a sua | ✘ |
| Painel, barbeiros e utilizadores (`/admin/*`) | ✘ | ✘ | ✘ | ✔ |

## 5.4 Onde cada regra é verificada

As regras são verificadas no servidor. Esconder um botão não protege nada.

| Camada | Ferramenta | O que decide | Exemplo |
|---|---|---|---|
| Rota | middleware `auth`, `guest`, `role:...` | Tens sessão? Tens este papel? | `/admin/*` só com `role:admin` |
| Form request | `authorize()` | Podes criar ou editar isto? (chama a policy) | `GuardarMarcacaoRequest` |
| Controller | `$this->authorize(...)` | Podes ver, editar ou apagar **este** registo? | `AppointmentController::show` |
| Modelo | métodos de regra de negócio | O estado permite a operação? | `canBeCancelled()`, `canBePaidBy()` |
| Vista | `@can` | O que mostrar. É cortesia, não defesa. | botões Editar e Eliminar |

O administrador passa em todas as policies através de um único `Gate::before` (`AppServiceProvider`).

### Regras que dependem do estado do registo

- O cliente só edita uma marcação enquanto está **pendente ou confirmada** (`AppointmentPolicy::update`).
- Só se confirma o que está pendente e só se conclui o que está confirmado (`confirmAppointment`, `completeAppointment`).
- Só se paga uma marcação que não está cancelada nem já paga (`Appointment::canBePaidBy`).
- Só se avalia uma marcação **concluída** (`ReviewController::store`).

### Resposta a um recurso de outra pessoa

Um utilizador autenticado que abre a marcação de outro recebe **403** (existe, mas não pode). Um id que não existe dá **404**. Foi uma escolha: o 403 diz claramente porque falhou, e os testes exigem-no.

## 5.5 Registos (logs)

O canal `auth` escreve em `storage/logs/auth.log`:

| Evento | Mensagem | Nível |
|---|---|---|
| Entrada com sucesso (email ou Google) | `Sessão iniciada` | info |
| Palavra-passe errada | `Login falhado` (só o email, **nunca** a palavra-passe) | warning |
| Excesso de tentativas | `Login bloqueado por excesso de tentativas` | warning |
| Saída | `Sessão terminada` | info |
| Registo | `Conta criada` | info |
| Palavra-passe reposta | `Password reposta` | info |
| Resposta 403 a um utilizador com sessão | `Acesso recusado` (utilizador, método, URL) | notice |

Cada linha leva o IP. O ficheiro `docs/fail2ban.md` explica como um servidor Linux pode bloquear IPs que falham muitas vezes.

## 5.6 Como se testou

Testes em `tests/Feature`: `RegistrationTest`, `AuthLogTest`, `AppointmentListingTest`, `FormRequestsTest`, `PaymentTest`, `ReviewTest`, `AppointmentTest`. Os testes de contorno abrem a URL à mão com um perfil sem permissão e exigem **403**, por exemplo `the url typed by hand is still refused even when the button is hidden`.
