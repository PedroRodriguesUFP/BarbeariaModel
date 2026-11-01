#!/usr/bin/env bash
# Recria a BD do zero e corre tudo. Uso: ./run_all.sh [nome_da_bd]   (por omissão: barbearia)
set -euo pipefail
DB="${1:-barbearia}"
cd "$(dirname "$0")"
dropdb --if-exists "$DB" && createdb "$DB"
psql -d "$DB" -v ON_ERROR_STOP=1 -q -f sql/01_schema.sql
psql -d "$DB" -v ON_ERROR_STOP=1 -q -f sql/02_seed.sql > /dev/null
psql -d "$DB" -v ON_ERROR_STOP=1 -f sql/03_consultas.sql
psql -d "$DB" -v ON_ERROR_STOP=1 -f sql/04_testes_integridade.sql
