# BarbeariaModel — BDAD 2026/27 (UFP)

Projeto prático da UC de Bases de Dados (Engenharia Informática, UFP, 3º ano, 1º semestre): modelação e implementação de uma base de dados relacional para a gestão de uma barbearia, com uma aplicação Laravel a consumir esse modelo.

## Contexto escolhido

O enunciado da UC disponibiliza três contextos (Alojamento, Transportes, Loja online); o grupo desenvolveu, com aceitação prévia do docente, o contexto **Barbearia**: gestão de utilizadores (clientes, barbeiros, administradores), serviços, habilitações dos barbeiros, horários e exceções de agenda, marcações (com ou sem conta), subscrições mensais, pagamentos e avaliações.

## Elementos do grupo

- Pedro Rodrigues — nº 2024114929
- Guilherme Taipa — nº 2024118263

## Objetivo e âmbito

Representar, do nível conceptual (EA) ao relacional, os dados e regras necessários para gerir uma barbearia, implementar esse modelo em PostgreSQL com restrições declarativas e testes de integridade, e alinhar a aplicação Laravel (nomes, enums, colunas) com o SQL final.

Um utilizador com papel de barbeiro pode também marcar um serviço com outro barbeiro; nessa situação atua como cliente e a marcação ocupa a sua disponibilidade como qualquer outra.

## Organização do repositório

- **`BasedeDados/`** — entregável da UC de Bases de Dados:
  - `sql/` — `01_schema.sql` (esquema), `02_seed.sql` (dados de teste), `03_consultas.sql` (Q01–Q08), `04_testes_integridade.sql` (testes de integridade)
  - `docs/fase1.md` — glossário, requisitos, regras de negócio e modelo conceptual (Fase 1, corrigido na Fase 2)
  - `docs/fase2/` — correções à Fase 1, mapeamento relacional, normalização, dados/consultas, alterações necessárias no Laravel e no diagrama ER
  - `modelo/` — diagramas (Chen, EA, UML)
  - `run_all.sh` — recria a base de dados do zero e corre tudo
- **Restante repositório** — a aplicação Laravel (`app/`, `resources/`, `routes/`, `database/migrations`, `tests/`) que implementa a barbearia sobre (uma versão evolutiva d)o mesmo modelo de dados.

## Como reproduzir

### Base de dados (PostgreSQL 16)
```bash
cd BasedeDados
./run_all.sh barbearia     # dropdb/createdb + schema + seed + consultas + testes (ON_ERROR_STOP=1)
```
Ver `BasedeDados/README.md` para correr cada script isoladamente.

### Aplicação Laravel
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed   # por omissão usa SQLite; ver docs/fase2/04-alteracoes-laravel.md
npm install && npm run build
php artisan serve
```

### Testes automatizados da aplicação
```bash
composer test
```

[![Architecture diagram of pedrorodriguesufp/barbeariamodel](https://gitdiagram.com/pedrorodriguesufp/barbeariamodel/diagram.png)](https://gitdiagram.com/pedrorodriguesufp/barbeariamodel?utm_source=readme&utm_medium=picture)
