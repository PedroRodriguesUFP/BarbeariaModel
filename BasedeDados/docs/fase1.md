# Fase 1 — Análise e Modelação Conceptual

## 1. Análise e âmbito

O projeto pretende modelar a informação necessária à gestão de uma barbearia. O domínio inclui os utilizadores da aplicação, os serviços disponibilizados, as marcações, a disponibilidade dos barbeiros, as avaliações, os planos de mensalidade, as subscrições e os pagamentos.

Não são criadas entidades distintas para **cliente** e **barbeiro**. Todos são utilizadores do sistema e o atributo **papel** determina as permissões principais de cada utilizador. Um utilizador com papel de barbeiro pode também utilizar o sistema como cliente e marcar um serviço com outro barbeiro.

A disponibilidade de um barbeiro deve considerar não só as marcações em que presta o serviço, mas também marcações em que esse utilizador é cliente, bem como períodos de indisponibilidade registados no calendário.

A vertente de pagamentos considera dois cenários: subscrições/mensalidades e serviços pontuais. Um utilizador pode aderir a um plano de mensalidade; quando não possui uma subscrição aplicável, um serviço pontual pode ser pago no estabelecimento.

### Fora do âmbito da Fase 1

Nesta fase não são tomadas decisões de implementação em PostgreSQL, não são definidas tabelas, tipos SQL, chaves estrangeiras, triggers, índices, papéis de PostgreSQL nem consultas SQL. Também não é implementada a interface de calendário; apenas são identificados os dados e regras que a suportarão nas fases seguintes.

---

## 2. Glossário

| Conceito | Definição |
|---|---|
| Utilizador | Pessoa registada no sistema. Pode possuir papel de cliente, barbeiro ou administrador. |
| Cliente | Papel funcional de um utilizador quando marca ou recebe um serviço. Não constitui uma entidade separada. |
| Barbeiro | Utilizador autorizado a realizar serviços e a gerir a sua agenda. Pode também agir como cliente. |
| Administrador | Utilizador com necessidades de gestão global da informação do sistema. |
| Serviço | Tipo de atendimento disponibilizado pela barbearia, como corte, barba ou outro serviço. |
| Marcação | Reserva de um serviço para uma determinada data e hora, associando um cliente, um barbeiro e um serviço. |
| Disponibilidade | Períodos em que um barbeiro pode aceitar marcações, considerando marcações existentes e bloqueios de agenda. |
| Indisponibilidade | Período registado por um barbeiro em que não pode receber marcações, por exemplo por folga, pausa ou compromisso. |
| Avaliação | Classificação e comentário efetuados por um utilizador após um atendimento, incidindo sobre um barbeiro ou um serviço. |
| Plano de mensalidade | Oferta com preço e condições definidas que pode ser subscrita por um utilizador. |
| Subscrição | Adesão de um utilizador a um plano de mensalidade durante um determinado período. |
| Pagamento | Registo de um valor pago ou a pagar, relacionado com uma subscrição ou com um atendimento pontual. |

---

## 3. Requisitos de dados

**RD01 —** O sistema deverá representar os utilizadores, incluindo informação de identificação, contacto, estado e papel funcional.

**RD02 —** O sistema deverá representar os serviços disponibilizados, incluindo designação, descrição, preço, duração, categoria e estado de disponibilidade.

**RD03 —** Deverá ser possível representar quais os serviços que cada utilizador com papel de barbeiro está habilitado a realizar.

**RD04 —** O sistema deverá representar marcações, incluindo cliente, barbeiro, serviço, data, hora e estado.

**RD05 —** Deverá ser possível representar períodos de indisponibilidade de utilizadores com papel de barbeiro.

**RD06 —** O sistema deverá permitir representar avaliações efetuadas por utilizadores, incluindo classificação, comentário e o respetivo objeto de avaliação.

**RD07 —** Deverão ser representados planos de mensalidade, incluindo designação, preço, descrição, condições e estado.

**RD08 —** Deverão ser representadas as subscrições dos utilizadores aos planos de mensalidade, incluindo período e estado.

**RD09 —** O sistema deverá representar pagamentos, incluindo valor, método, estado e data de pagamento.

**RD10 —** Deverá ser possível distinguir pagamentos associados a mensalidades de pagamentos relativos a atendimentos pontuais.

**RD11 —** A informação deverá permitir determinar a agenda e a disponibilidade de cada barbeiro numa data e hora específicas.

**RD12 —** A informação deverá permitir conhecer o histórico de marcações, pagamentos, subscrições e avaliações de um utilizador.

---

## 4. Regras de negócio

**RN01 —** Cada utilizador deverá possuir um email único no domínio considerado.

**RN02 —** O papel de um utilizador determina as suas permissões principais, mas um utilizador com papel de barbeiro pode também realizar ações de cliente.

**RN03 —** Apenas utilizadores com papel de barbeiro podem ser selecionados para realizar uma marcação.

**RN04 —** Cada marcação associa exatamente um cliente, um barbeiro e um serviço.

**RN05 —** O barbeiro associado a uma marcação deverá estar habilitado a realizar o serviço escolhido.

**RN06 —** Um barbeiro não pode realizar duas marcações com sobreposição temporal.

**RN07 —** Quando um utilizador com papel de barbeiro possui uma marcação como cliente, esse período deve ser considerado ocupado na sua própria agenda.

**RN08 —** Períodos de indisponibilidade registados pelo barbeiro impedem a aceitação de marcações sobrepostas.

**RN09 —** O cliente e o barbeiro de uma mesma marcação devem ser utilizadores distintos.

**RN10 —** Uma avaliação só pode ser registada no contexto de um atendimento concluído.

**RN11 —** Cada avaliação incide sobre exatamente um alvo: um barbeiro ou um serviço.

**RN12 —** Uma classificação deverá situar-se entre 1 e 5.

**RN13 —** Cada subscrição pertence a um único utilizador e corresponde a um único plano de mensalidade.

**RN14 —** Uma subscrição possui um período de validade e um estado que permite determinar se está ativa.

**RN15 —** Um pagamento representa a liquidação de uma subscrição ou de um atendimento pontual, não de ambos em simultâneo.

**RN16 —** Um atendimento pontual sem mensalidade aplicável pode ser pago no estabelecimento.

**RN17 —** O preço e as condições de um plano de mensalidade devem estar definidos antes de existirem novas adesões ao plano.

---

## 5. Atores e necessidades de acesso

| Ator / papel | Necessidades de acesso |
|---|---|
| Utilizador / Cliente | Consultar serviços e barbeiros; consultar disponibilidade; criar e consultar marcações; cancelar uma marcação quando permitido; consultar planos; aderir a mensalidades; consultar os seus pagamentos e subscrições; registar e consultar as suas avaliações. |
| Barbeiro | Possui também as necessidades de um cliente. Adicionalmente necessita de consultar a sua agenda, consultar as marcações em que presta serviços, atualizar o estado dos atendimentos, consultar os serviços que realiza e registar períodos de indisponibilidade. |
| Administrador | Consultar e gerir a informação global do domínio, incluindo utilizadores, serviços, habilitações dos barbeiros, marcações, planos de mensalidade, subscrições, pagamentos e avaliações. |

Nesta fase estas necessidades são descritas ao nível do domínio e não correspondem ainda a papéis ou privilégios específicos de PostgreSQL.

---

## 6. Necessidades de informação

**Q01 —** Que barbeiros estão disponíveis para realizar um determinado serviço numa data e hora?

**Q02 —** Qual é a agenda de um barbeiro num determinado dia ou período?

**Q03 —** Quais são as próximas marcações de um utilizador como cliente?

**Q04 —** Quais são as próximas marcações de um utilizador como barbeiro?

**Q05 —** Que serviços está cada barbeiro habilitado a realizar?

**Q06 —** Que períodos de indisponibilidade possui um barbeiro?

**Q07 —** Quais são os barbeiros com melhor classificação média?

**Q08 —** Quais são os serviços com melhor classificação média?

**Q09 —** Que utilizadores possuem uma subscrição ativa e qual é o respetivo plano?

**Q10 —** Quantas subscrições ativas existem por plano de mensalidade?

**Q11 —** Que pagamentos se encontram pendentes, pagos, falhados ou reembolsados?

**Q12 —** Qual é o histórico de pagamentos de um determinado utilizador?

**Q13 —** Que marcações concluídas ainda não possuem avaliação?

**Q14 —** Qual é o histórico de atendimentos realizados por um barbeiro?

**Q15 —** Em que períodos um utilizador com papel de barbeiro está ocupado por uma marcação própria como cliente?

Estas perguntas serão concretizadas através de consultas SQL nas fases seguintes.

---

## 7. Modelo conceptual ER

O modelo conceptual encontra-se em `modelo/modelo_conceptual_barbearia.drawio`, acompanhado por uma versão visual em `modelo/modelo_conceptual_barbearia.svg`.

### Entidades principais

- **UTILIZADOR** — representa todas as pessoas registadas, independentemente do papel.
- **SERVIÇO** — representa os serviços disponibilizados pela barbearia.
- **MARCAÇÃO** — representa a reserva de um serviço entre um cliente e um barbeiro.
- **INDISPONIBILIDADE** — representa períodos em que um barbeiro não pode aceitar marcações.
- **AVALIAÇÃO** — representa classificações e comentários associados a um atendimento.
- **PLANO_MENSALIDADE** — representa ofertas de subscrição disponibilizadas pela barbearia.
- **SUBSCRIÇÃO** — representa a adesão de um utilizador a um plano.
- **PAGAMENTO** — representa valores pagos ou a pagar.

### Relações conceptuais relevantes

- Um utilizador **agenda** marcações enquanto cliente.
- Um utilizador com papel de barbeiro **realiza** marcações.
- Cada marcação **corresponde a** um serviço.
- Utilizadores com papel de barbeiro **executam** serviços numa relação N:M.
- Um utilizador com papel de barbeiro **regista** períodos de indisponibilidade.
- Um utilizador **escreve** avaliações.
- Uma avaliação **incide sobre** um barbeiro ou um serviço, de forma exclusiva.
- Um utilizador **adere a** subscrições.
- Cada subscrição **corresponde a** um plano de mensalidade.
- Um utilizador **efetua** pagamentos.
- Um pagamento pode **liquidar** uma subscrição ou um atendimento pontual, de forma exclusiva.

### Decisão de modelação: cliente e barbeiro

Não são utilizadas entidades separadas para cliente e barbeiro. Ambos correspondem à entidade **UTILIZADOR**, diferenciada pelo papel e pelas relações em que participa. Esta decisão permite representar naturalmente o caso de um barbeiro que marca um serviço com outro barbeiro e evita duplicação de informação pessoal.

---

## 8. Checklist da Fase 1

- [x] Análise e delimitação do âmbito
- [x] Glossário
- [x] Requisitos de dados numerados
- [x] Regras de negócio numeradas
- [x] Atores e necessidades de acesso
- [x] Necessidades de informação numeradas
- [x] Modelo conceptual ER em notação de Chen
- [x] Representação visual do modelo
- [x] Ficheiro editável do modelo
- [x] README como ponto de entrada
- [ ] Confirmar com o docente que o contexto **Barbearia** está autorizado
- [ ] Rever nomes e números dos elementos do grupo
- [ ] Fazer commits finais e criar a tag/release **v1.0**
