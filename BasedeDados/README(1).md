# Projeto Prático de Bases de Dados — Barbearia

## Contexto

Este projeto modela conceptualmente a informação necessária ao funcionamento de uma barbearia, com foco em utilizadores, serviços, marcações, disponibilidade dos barbeiros, avaliações, mensalidades/subscrições e pagamentos.

> **Nota importante:** o enunciado fornecido da UC enumera os contextos A (Alojamento), B (Transportes) e C (Loja online). Este trabalho assume que o contexto **Barbearia** foi previamente aceite pelo docente. Caso ainda não tenha sido aceite, esta validação deve ser feita antes da entrega.

## Elementos do grupo

- Pedro Rodrigues — nº 2024114929
- Guilherme Taipa — nº 2024118263

## Objetivo e âmbito

O objetivo é representar, ao nível conceptual, os dados e regras necessários para gerir uma barbearia. O sistema deverá permitir distinguir papéis de utilizador, gerir serviços e marcações, determinar a disponibilidade dos barbeiros, registar avaliações, representar planos de mensalidade/subscrições e registar pagamentos.

Um utilizador com papel de barbeiro pode também marcar um serviço com outro barbeiro. Nessa situação, a marcação pessoal ocupa igualmente o seu horário e deve ser considerada na sua disponibilidade.

## Organização do repositório

```text
.
├── README.md
├── docs/
│   └── fase1.md
└── modelo/
    ├── modelo_conceptual_barbearia.drawio
    └── modelo_conceptual_barbearia.svg
```

## Fase 1 — v1.0

A Fase 1 contém:

- análise e delimitação do âmbito;
- glossário;
- requisitos de dados;
- regras de negócio;
- atores e necessidades de acesso;
- necessidades de informação;
- modelo conceptual Entidade–Relacionamento em notação de Chen.

Nesta fase não é implementada a base de dados em PostgreSQL e não são incluídas consultas SQL.

## Reprodução da base de dados e testes

Não aplicável à versão **v1.0**, uma vez que a Fase 1 corresponde apenas à análise e modelação conceptual. As instruções de implementação e teste serão acrescentadas na Fase 2.
