# ESPECIFICAÇÃO COMPLETA DE BASE DE DADOS E GLOSSÁRIO
## Sistema Integrado de Gestão de Barbearia — Modelo Entidade-Associação (EA)

---

## ÍNDICE
1. Glossário e Dicionário de Dados
   1.1. Descrição Geral do Sistema
   1.2. Especificação de Entidades e Atributos
   1.3. Dicionário Sintético de Atributos
   1.4. Matriz de Relacionamentos e Cardinalidades
2. Requisitos, Regras e Necessidades de Acesso
   2.1. Requisitos de Dados (RD01 a RD09)
   2.2. Regras de Negócio (RN01 a RN06)
   2.3. Atores e Necessidades de Acesso
   2.4. Necessidades de Informação (NI01 a NI08)
   2.5. Matriz de Rastreabilidade

---

# CAPÍTULO 1: GLOSSÁRIO E DICIONÁRIO DE DADOS

## 1.1. Descrição Geral do Sistema
O sistema visa a gestão operacional e financeira de uma barbearia multi-serviço. A arquitetura de dados assenta no modelo Entidade-Associação, cobrindo o ciclo de vida dos utilizadores (clientes, barbeiros e administradores), catálogo de serviços, agendamentos, pagamentos avulsos, planos de subscrição mensal e avaliações de satisfação.

---

## 1.2. Especificação de Entidades e Atributos

### UTILIZADOR
Entidade central unificada que representa qualquer pessoa com conta registada no sistema. Modela Clientes, Barbeiros e Administradores através de polimorfismo baseado no papel.
- nome: Texto (Obrigatório). Nome completo do utilizador.
- email: Texto (Obrigatório, Identificador Natural Único - UK). Endereço de correio eletrónico de acesso.
- palavra_passe: Hash (Obrigatório). Credencial encriptada.
- papel: Enumeração ('admin', 'barbeiro', 'cliente') (Obrigatório). Define o tipo e permissões do utilizador.
- telefone: Texto (Opcional, Clientes). Contacto telefónico.
- nif: Texto (Opcional, Clientes). Número de Identificação Fiscal.
- biografia: Texto (Opcional, Barbeiros). Apresentação profissional.

### CALENDARIO
Define a janela temporal de disponibilidade de trabalho do barbeiro. Existe numa relação exclusiva de 1:1 com utilizadores cujo papel é 'barbeiro'.
- nome: Texto (Opcional). Designação do calendário.
- dias_trabalho: Array/Texto (Obrigatório). Dias da semana de trabalho.
- hora_inicio: Hora (Obrigatório). Horário de início do turno.
- hora_fim: Hora (Obrigatório). Horário de término do turno.

### SERVICO
Catálogo de tratamentos e procedimentos disponibilizados pela barbearia.
- nome: Texto (Obrigatório). Denominação do serviço.
- descricao: Texto (Opcional). Detalhes e especificações.
- preco: Decimal (Obrigatório). Valor monetário unitário.
- duracao_minutos: Inteiro (Obrigatório). Tempo estimado em minutos.
- categoria: Enumeração ('cabelo', 'barba', 'cabelo e barba', 'degradee', 'coloracao') (Obrigatório).
- ativo: Booleano (Obrigatório). Indicador de disponibilidade no catálogo.

### MARCACAO
Registo de reserva de um serviço efetuado por um cliente com um barbeiro para um determinado dia e hora.
- data: Data (Obrigatório). Dia do agendamento.
- hora: Hora (Opcional). Horário do agendamento.
- estado: Enumeração ('pendente', 'confirmada', 'cancelada', 'concluida') (Obrigatório). Ciclo de vida da reserva.

### PAGAMENTO
Registo financeiro da transação monetária. Origina-se de forma exclusiva a partir de uma Marcação individual ou de uma Subscrição mensal.
- montante: Decimal (Obrigatório). Valor financeiro cobrado.
- metodo: Texto (Obrigatório). Meio de pagamento utilizado.
- estado: Enumeração ('pendente', 'pago', 'falhado', 'reembolsado') (Obrigatório).
- data_pagamento: Timestamp (Opcional). Data e hora do processamento.

### PLANO_SUBSCRICAO
Pacote comercial de avença mensal oferecido aos clientes.
- nome: Texto (Obrigatório). Nome do plano.
- preco_mensal: Decimal (Obrigatório). Valor cobrado mensalmente.
- cortes_por_mes: Inteiro (Obrigatório). Quota máxima de cortes/serviços incluídos por mês.
- ativo: Booleano (Obrigatório). Indicador de comercialização.

### SUBSCRICAO
Adesão ativa ou histórico de um cliente a um plano de subscrição.
- data_inicio: Data (Obrigatório). Início do contrato.
- data_fim: Data (Opcional). Expiração/término do contrato.
- estado: Enumeração ('ativa', 'suspensa', 'cancelada', 'expirada') (Obrigatório).

### AVALIACAO
Registo de satisfação e opinião submetido por um cliente.
- classificacao: Inteiro / TinyInt (1 a 5) (Obrigatório). Nota numérica de avaliação.
- comentario: Texto (Opcional). Parecer textual do cliente.

---

## 1.3. Dicionário Sintético de Atributos

| Entidade | Atributo | Tipo / Domínio | Obrigatoriedade | Notas de Campo |
| :--- | :--- | :--- | :--- | :--- |
| UTILIZADOR | email | VARCHAR / Email | Obrigatório (UK) | Chave natural de acesso |
| UTILIZADOR | papel | ENUM | Obrigatório | 'admin', 'barbeiro', 'cliente' |
| SERVICO | categoria | ENUM | Obrigatório | Cabelo, Barba, Degradee, etc. |
| MARCACAO | estado | ENUM | Obrigatório | Pendente, Confirmada, Concluída, Cancelada |
| PLANO_SUBSCRICAO | cortes_por_mes | INTEGER | Obrigatório | Quota limite mensal do plano |
| AVALIACAO | classificacao | TINYINT (1-5) | Obrigatório | Nota de satisfação quantitativa |

---

## 1.4. Matriz de Relacionamentos e Cardinalidades

| Entidade Origem | Relação (Verbo) | Entidade Destino | Cardinalidade | Descrição Funcional |
| :--- | :--- | :--- | :--- | :--- |
| UTILIZADOR | possui | CALENDARIO | 1 : (0,1) | Calendário exclusivo do barbeiro |
| UTILIZADOR | agenda / realiza | MARCACAO | 1 : (0,N) | Cliente agenda; Barbeiro executa |
| SUBSCRICAO | cobre | MARCACAO | (0,1) : (0,N) | Subscrição isenta pagamento direto |
| MARCACAO / SUBSCRICAO | origina | PAGAMENTO | (0,1) : (0,N) | Gera receita monetária única |
| PLANO_SUBSCRICAO | é contratado em | SUBSCRICAO | 1 : (0,N) | Modelo comercial da subscrição |

---

# CAPÍTULO 2: ESPECIFICAÇÃO DE REQUISITOS, REGRAS E ACESSOS

## 2.1. Requisitos de Dados (RD)

- RD01 — Identificação de Utilizadores e Perfis: O sistema deve registar os utilizadores com o seu nome completo, endereço de correio eletrónico (identificador natural único), palavra-passe encriptada e o papel desempenhado na plataforma (Cliente, Barbeiro ou Administrador).
- RD02 — Dados de Perfil e Contacto Específicos: Deve ser possível armazenar informação suplementar associada ao papel do utilizador, nomeadamente número de contacto telefónico e NIF para clientes, e biografia profissional/apresentação para barbeiros.
- RD03 — Horários e Calendário dos Barbeiros: O sistema deve representar a agenda de disponibilidade dos barbeiros, incluindo os dias da semana em que trabalham e o respetivo horário de início e término do turno diário.
- RD04 — Catálogo de Serviços e Categorias: A base de dados deve manter o catálogo de serviços oferecidos pela barbearia, contendo nome, descrição detalhada, preço monetário, duração estimada em minutos, categoria (ex.: cabelo, barba, degradação, coloração) e indicação do estado ativo/inativo.
- RD05 — Marcações e Ciclo de Vida do Agendamento: Deve ser registada cada reserva realizada por um cliente com um barbeiro para a prestação de um determinado serviço, armazenando a data, hora agendada e o estado do agendamento (pendente, confirmada, cancelada ou concluída).
- RD06 — Planos de Subscrição Recorrente: O sistema deve representar os planos de avença mensal disponíveis para contratação, com indicação do nome do plano, preço fixo mensal, número máximo de cortes/serviços incluídos por mês e estado ativo.
- RD07 — Contratos de Subscrição de Clientes: Deve ser mantido o histórico e estado das subscrições ativas dos clientes, incluindo a data de início do contrato, data de fim/renovação e situação atual (ativa, suspensa, cancelada, expirada).
- RD08 — Transações Financeiras e Pagamentos: A base de dados deve registar todas as transações efetuadas, contendo o montante, método de pagamento, data e hora de processamento e estado financeiro (pendente, pago, falhado ou reembolsado).
- RD09 — Avaliações e Satisfação de Clientes: O sistema deve guardar o feedback deixado pelos clientes, composto por uma pontuação quantitativa numa escala de 1 a 5 e um comentário textual opcional.

---

## 2.2. Regras de Negócio (RN)

- RN01 — Unicidade de Utilizador: O e-mail é o identificador único do utilizador no sistema. Não podem existir duas contas registadas com o mesmo endereço eletrónico.
- RN02 — Exclusividade do Calendário: Apenas utilizadores com o papel 'barbeiro' possuem um calendário de trabalho. Clientes e administradores não possuem calendário.
- RN03 — Habilitação de Serviço: Um barbeiro só pode ser associado a marcações de serviços que esteja devidamente habilitado a executar no seu perfil profissional.
- RN04 — Origem Única de Pagamento: Cada registo de pagamento associa-se de forma estritamente exclusiva a UMA marcação avulsa OU a UMA subscrição mensal.
- RN05 — Cobertura e Quota de Subscrição: Marcações realizadas por clientes com subscrição ativa não originam pagamentos avulsos, desde que cobertas pela subscrição. Os cortes disponíveis no mês resultam do seguinte cálculo:
  $\text{Cortes\_Disponíveis} = \text{PLANO\_SUBSCRICAO.cortes\_por\_mes} - \text{Marcacoes\_Cobertas\_No\_Mes}$
- RN06 — Exclusividade de Alvo em Avaliações: Uma avaliação submetida por um cliente deve incidir exatamente sobre UM Barbeiro OU sobre UM Serviço, garantindo a diferenciação entre a qualidade do atendimento pessoal e a qualidade técnica do procedimento.

---

## 2.3. Atores e Necessidades de Acesso

### Cliente
- Consultar o catálogo de serviços, preços, duração e biografia dos barbeiros.
- Consultar a disponibilidade de horários dos barbeiros e efetuar/cancelar marcações.
- Consultar, contratar e gerir os seus planos de subscrição e histórico de pagamentos.
- Registar avaliações e comentários aos serviços usufruídos ou barbeiros atendentes.

### Barbeiro
- Consultar a sua agenda diária e semanal de marcações e serviços previstos.
- Configurar e atualizar o seu calendário de disponibilidade (dias de trabalho e horários).
- Atualizar o estado das suas marcações (confirmar agendamentos e assinalar conclusões).
- Consultar o seu histórico de serviços realizados e as avaliações recebidas dos clientes.

### Gestor / Admin
- Registar, atualizar e desativar contas de utilizadores (clientes e barbeiros).
- Gerir o catálogo de serviços (preços, categorias, duração) e planos de subscrição.
- Consultar relatórios operacionais globais, receitas financeiras e taxas de ocupação.
- Auditar todas as transações, cancelamentos e métricas de satisfação da barbearia.

---

## 2.4. Necessidades de Informação (Perguntas Relevantes)

- NI01 — Barbeiros Mais Bem Avaliados: Quais são os barbeiros que apresentam a maior classificação média de avaliação no último trimestre e quantos comentários receberam?
- NI02 — Ocupação e Volume por Barbeiro: Qual é o número total de cortes/serviços concluídos e o volume de horas trabalhadas por cada barbeiro no mês transato?
- NI03 — Adesão a Subscrições: Quantos clientes possuem atualmente uma subscrição ativa e qual é o plano de subscrição com maior número de adesões?
- NI04 — Saldo de Cortes de Cliente: Qual é o número de cortes ainda disponíveis no mês corrente para um determinado cliente titular de uma subscrição ativa?
- NI05 — Receita Monetária por Origem: Qual foi o faturamento total gerado por serviços avulsos comparado com a receita de mensalidades de subscrições num dado mês?
- NI06 — Serviços Mais Requisitados: Quais os serviços mais procurados pelos clientes por categoria (cabelo, barba, coloração, etc.) ao longo do último ano?
- NI07 — Agenda Pendente do Dia: Quais são as marcações que se encontram no estado 'pendente' de confirmação para a data de hoje, organizadas por barbeiro?
- NI08 — Taxa de Cancelamentos: Qual é a percentagem e número absoluto de marcações canceladas em relação ao total de agendamentos efetuados por mês?

---

## 2.5. Matriz de Rastreabilidade (Requisitos vs. Atores vs. Regras)

| Requisito de Dados | Atores Impactados | Regras de Negócio Associadas |
| :--- | :--- | :--- |
| RD01, RD02 (Utilizadores e Perfis) | Cliente, Barbeiro, Gestor | RN01 (Unicidade E-mail) |
| RD03, RD05 (Calendário e Marcações) | Cliente, Barbeiro | RN02, RN03 (Calendário / Habilitação) |
| RD06, RD07 (Planos e Subscrições) | Cliente, Gestor | RN05 (Quota de Subscrição) |
| RD08 (Pagamentos e Transações) | Cliente, Gestor | RN04 (Exclusividade Origem) |
| RD09 (Avaliações de Clientes) | Cliente, Barbeiro, Gestor | RN06 (Alvo da Avaliação) |