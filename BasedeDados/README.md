# BarbeariaModel — Base de Dados (BDAD 2026/27, UFP) — Fase 2 (v2.0, unificada)

SQL unificado para PostgreSQL 16, alinhado com o Laravel (nomes, enums e colunas do repo). Decisão D1 = **Opção A** (uma marcação, um serviço).

## Como reproduzir
```bash
./run_all.sh barbearia     # dropdb/createdb + 01 + 02 + 03 + 04 (ON_ERROR_STOP=1)
```
Ou ficheiro a ficheiro: `psql -d <bd> -v ON_ERROR_STOP=1 -f sql/01_schema.sql` (apaga e recria só as suas tabelas), `02_seed.sql`, `03_consultas.sql`, `04_testes_integridade.sql`.

## Conteúdo
- `sql/` — 01 schema, 02 seed, 03 consultas Q01–Q08, 04 testes (52: 43 negativos + 9 positivos)
- `docs/fase2/00-correcoes-fase1.md` — decisões, o que mudar no fase1.md, regras e matriz
- `01-mapeamento-relacional.md`, `02-normalizacao.md`, `03-dados-e-consultas.md`
- `04-alteracoes-laravel.md`, `05-alteracoes-diagrama-er.md`
- `execucao_postgres16.txt` — saída real da execução (PostgreSQL 16.15)

Fase 3 (fora de âmbito): vistas, roles/GRANT, triggers, transações, concorrência, índices/EXPLAIN.
