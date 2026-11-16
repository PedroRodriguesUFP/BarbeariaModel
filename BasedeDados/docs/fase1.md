# ESPECIFICAÇÃO COMPLETA DE BASE DE DADOS E GLOSSÁRIO
## Sistema Integrado de Gestão de Barbearia — Modelo Entidade-Associação (EA)

> Versão corrigida na Fase 2 (v2.0): ver `fase2/00-correcoes-fase1.md` para a lista de alterações e porquê. O modelo relacional, a normalização e o SQL ficam em `fase2/`; este documento mantém-se ao nível conceptual.

---

## ÍNDICE
1. Glossário e Dicionário de Dados
   1.1. Descrição Geral do Sistema
   1.2. Especificação de Entidades e Atributos
   1.3. Dicionário Sintético de Atributos
   1.4. Matriz de Relacionamentos e Cardinalidades
2. Requisitos, Regras e Necessidades de Acesso
   2.1. Requisitos de Dados (RD01 a RD10)
   2.2. Regras de Negócio (RN01 a RN10)
   2.3. Atores e Necessidades de Acesso
   2.4. Necessidades de Informação (Q01 a Q08)
   2.5. Rastreabilidade

---

# CAPÍTULO 1: GLOSSÁRIO E DICIONÁRIO DE DADOS

## 1.1. Descrição Geral do Sistema
O sistema visa a gestão operacional e financeira de uma barbearia multi-serviço. A arquitetura de dados assenta no modelo Entidade-Associação, cobrindo o ciclo de vida dos utilizadores (clientes, barbeiros e administradores), catálogo de serviços, habilitações dos barbeiros, horários e exceções de agenda, agendamentos (com ou sem conta), pagamentos avulsos, planos de subscrição mensal e avaliações de satisfação.

---

## 1.2. Especificação de Entidades e Atributos

### UTILIZADOR
Entidade central unificada que representa qualquer pessoa com conta registada no sistema. Modela Clientes, Barbeiros e Administradores através de um atributo de papel. Um barbeiro pode também marcar um serviço com outro barbeiro — nessa situação atua como cliente.
- nome: texto (obrigatório). Nome completo do utilizador.
- email: texto (obrigatório, identificador natural único). Endereço de correio eletrónico de acesso.
- palavra_passe: texto encriptado (opcional — contas com login externo, ex. Google, não têm palavra-passe local).
- papel: um de "admin", "barbeiro", "cliente" (obrigatório). Define o tipo e as permissões do utilizador.
- telefone: texto (opcional, qualquer papel). Contacto telefónico.
- biografia: texto (opcional, tipicamente barbeiros). Apresentação profissional.
- ativo: booleano (obrigatório). Indica se a conta está ativa.

### HORARIO_TRABALHO
Período semanal de disponibilidade de um barbeiro. Entidade fraca, dependente de um utilizador com papel "barbeiro". Um barbeiro pode ter vários períodos no mesmo dia (horário partido).
- dia_semana: inteiro, 1 (segunda) a 7 (domingo) (obrigatório).
- hora_inicio: hora (obrigatório).
- hora_fim: hora (obrigatório, posterior a hora_inicio).

### BLOQUEIO_AGENDA
Exceção pontual ao horário de um barbeiro (férias, folga, ausência). Entidade fraca, dependente de um utilizador com papel "barbeiro".
- data: data (obrigatório).
- hora_inicio: hora (opcional — ausente quando o bloqueio é de dia inteiro).
- hora_fim: hora (opcional — ausente quando o bloqueio é de dia inteiro).
- dia_inteiro: booleano (obrigatório).
- motivo: texto (opcional).

### SERVICO
Catálogo de tratamentos e procedimentos disponibilizados pela barbearia.
- nome: texto (obrigatório). Denominação do serviço.
- descricao: texto (opcional). Detalhes e especificações.
- preco: decimal (obrigatório). Valor monetário unitário.
- duracao_minutos: inteiro (obrigatório). Tempo estimado em minutos.
- categoria: um de "cabelo", "barba", "cabelo e barba", "degradé", "coloração" (obrigatório).
- ativo: booleano (obrigatório). Indicador de disponibilidade no catálogo.

### MARCACAO
Registo de reserva de um serviço efetuado por um cliente — com conta ou sem conta (cliente de balcão) — com um barbeiro, para um determinado dia e hora.
- data: data (obrigatório). Dia do agendamento.
- hora: hora (obrigatório). Horário do agendamento.
- estado: um de "pendente", "confirmada", "cancelada", "concluída" (obrigatório). Ciclo de vida da reserva.
- nome_cliente_balcao: texto (opcional). Nome do cliente, só quando este não tem conta.
- telefone_cliente_balcao: texto (opcional). Contacto do cliente de balcão.

### PAGAMENTO
Registo financeiro da transação monetária. Origina-se de forma exclusiva a partir de uma Marcação individual ou de uma Subscrição mensal; não tem atributo nem relação própria com o pagador — este deriva sempre da marcação (o seu cliente) ou da subscrição (o seu utilizador).
- montante: decimal (obrigatório). Valor financeiro cobrado.
- metodo: texto (obrigatório). Meio de pagamento utilizado.
- estado: um de "pendente", "pago", "falhado", "reembolsado" (obrigatório).
- data_pagamento: data e hora (obrigatório quando estado = "pago").

### PLANO_SUBSCRICAO
Pacote comercial de avença mensal oferecido aos clientes.
- nome: texto (obrigatório, único). Nome do plano.
- descricao: texto (opcional).
- preco_mensal: decimal (obrigatório). Valor cobrado mensalmente.
- cortes_por_mes: inteiro (obrigatório). Quota máxima de cortes/serviços incluídos por mês.
- ativo: booleano (obrigatório). Indicador de comercialização.

### SUBSCRICAO
Adesão de um cliente a um plano de subscrição, num dado período.
- data_inicio: data (obrigatório). Início do contrato.
- data_fim: data (opcional). Fim do contrato (vazio = em curso).
- estado: um de "ativa", "suspensa", "cancelada", "expirada" (obrigatório).

### AVALIACAO
Registo de satisfação e opinião submetido por um utilizador, dirigido a um barbeiro ou a um serviço — nunca aos dois ao mesmo tempo.
- data: data e hora (obrigatório). Momento da avaliação.
- classificacao: inteiro, 1 a 5 (obrigatório). Nota numérica de avaliação.
- comentario: texto (opcional). Parecer textual do autor.

---

## 1.3. Dicionário Sintético de Atributos

| Entidade | Atributo | Domínio | Obrigatoriedade | Notas de Campo |
| :--- | :--- | :--- | :--- | :--- |
| UTILIZADOR | email | texto | Obrigatório, único | Chave natural de acesso |
| UTILIZADOR | papel | "admin" / "barbeiro" / "cliente" | Obrigatório | Define o tipo de utilizador |
| UTILIZADOR | ativo | booleano | Obrigatório | Conta ativa ou desativada |
| HORARIO_TRABALHO | dia_semana | 1–7 | Obrigatório | 1 = segunda |
| SERVICO | categoria | "cabelo" / "barba" / "cabelo e barba" / "degradé" / "coloração" | Obrigatório | Catálogo fechado |
| MARCACAO | estado | "pendente" / "confirmada" / "cancelada" / "concluída" | Obrigatório | Ciclo de vida da reserva |
| PLANO_SUBSCRICAO | cortes_por_mes | inteiro > 0 | Obrigatório | Quota limite mensal do plano |
| AVALIACAO | classificacao | 1–5 | Obrigatório | Nota de satisfação quantitativa |

---

## 1.4. Matriz de Relacionamentos e Cardinalidades

| Entidade Origem | Relação | Entidade Destino | Cardinalidade | Descrição Funcional |
| :--- | :--- | :--- | :--- | :--- |
| UTILIZADOR (barbeiro) | tem | HORARIO_TRABALHO | (0,1) : (0,N) | Horário semanal do barbeiro |
| UTILIZADOR (barbeiro) | tem | BLOQUEIO_AGENDA | (0,1) : (0,N) | Exceções ao horário |
| UTILIZADOR (barbeiro) | executa | SERVICO | (0,N) : (0,N) | Habilitação do barbeiro para o serviço |
| UTILIZADOR (como cliente) | agenda | MARCACAO | (0,1) : (0,N) | Nulo do lado da marcação = cliente de balcão, sem conta |
| UTILIZADOR (barbeiro) | realiza | MARCACAO | (1,1) : (0,N) | Todo barbeiro de uma marcação é obrigatório |
| SERVICO | é objeto de | MARCACAO | (1,1) : (0,N) | Uma marcação tem exatamente um serviço |
| MARCACAO | usa o par "executa" | UTILIZADOR (barbeiro) + SERVICO | — | O par (barbeiro, serviço) tem de existir em "executa" (RN03) |
| UTILIZADOR (cliente) | contrata | SUBSCRICAO | (1,1) : (0,N) | Toda subscrição pertence a um cliente |
| PLANO_SUBSCRICAO | é contratado em | SUBSCRICAO | (1,1) : (0,N) | Toda subscrição segue um plano |
| SUBSCRICAO | cobre | MARCACAO | (0,1) : (0,N) | A subscrição que cobre a marcação é do mesmo cliente (RN05) |
| MARCACAO | origina | PAGAMENTO | (0,N) : (0,1) | Cada pagamento tem exatamente uma origem: marcação OU subscrição |
| SUBSCRICAO | origina | PAGAMENTO | (0,N) : (0,1) | idem |
| UTILIZADOR | escreve | AVALIACAO | (1,1) : (0,N) | Qualquer utilizador pode avaliar (há casos de barbeiro a avaliar barbeiro) |
| AVALIACAO | incide em | BARBEIRO **ou** SERVICO | exatamente um dos dois | RN06 |
| AVALIACAO | refere-se a | MARCACAO | (0,1) : (0,N) | Nulo em dados antigos; RN10 |

---

# CAPÍTULO 2: ESPECIFICAÇÃO DE REQUISITOS, REGRAS E ACESSOS

## 2.1. Requisitos de Dados (RD)

- RD01 — Identificação de Utilizadores e Perfis: O sistema deve registar os utilizadores com o seu nome completo, endereço de correio eletrónico (identificador natural único), palavra-passe (quando aplicável) e o papel desempenhado na plataforma (Cliente, Barbeiro ou Administrador), bem como o estado ativo/inativo da conta.
- RD02 — Dados de Perfil e Contacto Específicos: Deve ser possível armazenar informação suplementar do utilizador, nomeadamente número de contacto telefónico (qualquer papel) e biografia profissional/apresentação para barbeiros.
- RD03 — Horários e Agenda dos Barbeiros: O sistema deve representar a disponibilidade dos barbeiros através de um horário semanal por dia da semana (um ou mais períodos por dia) e de bloqueios pontuais (férias, folgas, ausências) que suspendem essa disponibilidade.
- RD04 — Catálogo de Serviços e Categorias: A base de dados deve manter o catálogo de serviços oferecidos pela barbearia, contendo nome, descrição detalhada, preço monetário, duração estimada em minutos, categoria e indicação do estado ativo/inativo.
- RD05 — Marcações e Ciclo de Vida do Agendamento: Deve ser registada a reserva de **um** serviço por um cliente — com conta ou sem conta (cliente de balcão) — com um barbeiro, armazenando data, hora e o estado do agendamento.
- RD06 — Planos de Subscrição Recorrente: O sistema deve representar os planos de avença mensal disponíveis para contratação, com nome, preço fixo mensal, número máximo de cortes/serviços incluídos por mês e estado ativo.
- RD07 — Contratos de Subscrição de Clientes: Deve ser mantido o histórico e estado das subscrições dos clientes, incluindo data de início, data de fim/renovação e situação atual.
- RD08 — Transações Financeiras e Pagamentos: A base de dados deve registar todas as transações efetuadas, contendo o montante, método de pagamento, data e hora de processamento e estado financeiro.
- RD09 — Avaliações e Satisfação: O sistema deve guardar o feedback deixado pelos utilizadores, composto por uma pontuação de 1 a 5, um comentário textual opcional e a data da avaliação, podendo opcionalmente referir-se à marcação que a originou.
- RD10 — Habilitações dos Barbeiros: O sistema deve registar quais os serviços que cada barbeiro está habilitado a executar.

---

## 2.2. Regras de Negócio (RN)

- RN01 — Unicidade de Utilizador: O e-mail é o identificador único do utilizador no sistema.
- RN02 — Exclusividade do Barbeiro: Apenas utilizadores com o papel "barbeiro" têm horário semanal, bloqueios de agenda e habilitações.
- RN03 — Habilitação de Serviço: Um barbeiro só pode ser associado a marcações de serviços para os quais esteja habilitado; a marcação só é válida para o par (barbeiro, serviço) habilitado.
- RN04 — Origem Única de Pagamento: Cada pagamento associa-se de forma estritamente exclusiva a UMA marcação OU a UMA subscrição.
- RN05 — Cobertura e Quota de Subscrição: Marcações cobertas por uma subscrição ativa não originam pagamento avulso, desde que não canceladas; a subscrição que cobre a marcação pertence sempre ao cliente da própria marcação. Os cortes disponíveis no mês resultam de:
  $\text{Cortes\_Disponíveis} = \text{PLANO\_SUBSCRICAO.cortes\_por\_mes} - \text{Marcacoes\_Cobertas\_e\_Não\_Canceladas\_No\_Mes}$
- RN06 — Exclusividade de Alvo em Avaliações: Uma avaliação incide exatamente sobre UM Barbeiro (o alvo tem de ter papel "barbeiro") OU sobre UM Serviço.
- RN07 — Sem Conflito de Horário: Um barbeiro não tem duas marcações ativas (não canceladas) à mesma data e hora de início.
- RN08 — Uma Subscrição Ativa: Um utilizador tem, no máximo, uma subscrição ativa em simultâneo.
- RN09 — Ninguém Marca Consigo Próprio: Um barbeiro não pode agendar uma marcação em que é simultaneamente cliente e barbeiro; pode, no entanto, ser cliente de **outro** barbeiro.
- RN10 — Uma Avaliação por Alvo e por Marcação: Quando uma avaliação se refere a uma marcação, essa marcação tem, no máximo, uma avaliação ao barbeiro e uma ao serviço.

---

## 2.3. Atores e Necessidades de Acesso

### Cliente
- Consultar o catálogo de serviços, preços, duração e biografia dos barbeiros.
- Consultar a disponibilidade de horários dos barbeiros e efetuar/cancelar marcações.
- Consultar, contratar e gerir os seus planos de subscrição e histórico de pagamentos.
- Registar avaliações aos serviços usufruídos ou barbeiros atendentes.

### Barbeiro
- Consultar a sua agenda diária e semanal de marcações e serviços previstos.
- Configurar e atualizar o seu horário semanal e os seus bloqueios de agenda.
- Atualizar o estado das suas marcações (confirmar agendamentos e assinalar conclusões).
- Consultar o seu histórico de serviços realizados e as avaliações recebidas.
- Agendar uma marcação como cliente, inclusive com outro barbeiro.

### Gestor / Admin
- Registar, atualizar e desativar contas de utilizadores (clientes e barbeiros).
- Gerir o catálogo de serviços, as habilitações dos barbeiros e os planos de subscrição.
- Consultar relatórios operacionais globais, receitas financeiras e taxas de ocupação.
- Auditar todas as transações, cancelamentos e métricas de satisfação da barbearia.

---

## 2.4. Necessidades de Informação (Q01 a Q08)

- Q01 — Barbeiros Mais Bem Avaliados: Quais são os barbeiros com maior classificação média de avaliação no último trimestre e quantos comentários receberam?
- Q02 — Ocupação e Volume por Barbeiro: Qual o número de serviços concluídos e o volume de horas trabalhadas por cada barbeiro no mês transato?
- Q03 — Adesão a Subscrições: Quantos clientes têm atualmente uma subscrição ativa e qual o plano com maior número de adesões?
- Q04 — Saldo de Cortes de Cliente: Quantos cortes estão ainda disponíveis no mês corrente para um cliente com subscrição ativa?
- Q05 — Receita por Origem: Qual o faturamento gerado por serviços avulsos comparado com a receita de mensalidades, num dado mês?
- Q06 — Serviços Mais Requisitados: Quais os serviços mais procurados, por categoria, ao longo do último ano?
- Q07 — Agenda Pendente do Dia: Quais as marcações em estado "pendente" para a data de hoje, por barbeiro?
- Q08 — Taxa de Cancelamentos: Qual a percentagem e o número absoluto de marcações canceladas por mês?

Estas questões são concretizadas em SQL em `fase2/sql/03_consultas.sql` (Q01–Q08, resultados documentados em `fase2/03-dados-e-consultas.md`).

## 2.5. Rastreabilidade

A matriz Requisito → Ator → Regra está refletida na Matriz de Relacionamentos (§1.4) e nos Atores (§2.3). A rastreabilidade Regra → Mecanismo SQL → Teste, que só faz sentido a partir da implementação, está em `fase2/01-mapeamento-relacional.md` §4.
