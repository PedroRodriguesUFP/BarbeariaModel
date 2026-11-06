# Dados de teste e consultas (início)

## Dados (`sql/02_dados.sql`)

| Tabela | Linhas | Notas |
|---|---|---|
| utilizador | 12 | 1 admin, 3 barbeiros, 8 clientes (1 inativo, 1 sem telefone, 1 sem NIF) |
| calendario / calendario_dia | 3 / 14 | turnos diferentes; Miguel só de manhã |
| servico | 8 | 1 inativo sem barbeiro; 2 serviços "combo" |
| barbeiro_servico | 16 | cada barbeiro com habilitações diferentes |
| plano_subscricao / subscricao | 3 / 5 | 1 plano inativo; subscrições ativas, expirada e cancelada |
| marcacao | 22 | set–out 2026; 3 canceladas; 5 ativas hoje (2026-10-31) |
| marcacao_servico | 24 | 2 marcações com 2 serviços |
| pagamento | 20 | avulsos e mensalidades; 1 falhado, 1 reembolsado |
| avaliacao | 14 | a barbeiro e a serviço |

### Casos limite cobertos

| Caso | Onde | Regra testada |
|---|---|---|
| Marcação com vários serviços | m4 (corte + barba), m11 (degradé + barba) | C1 |
| Marcação avulsa vs coberta por subscrição | m4 vs m1 | RN05 |
| Cliente com subscrição expirada e nova ativa | cliente 7 (sub 3 e sub 5) | RN09 |
| Quota esgotada no mês | cliente 7 e 6 em outubro | RN05 / Q04 |
| Slot libertado por cancelamento | m22 (cancelada) e m19 (ativa), mesmo barbeiro/hora | RN07 |
| Pagamento falhado | m16 | RD08 |
| Pagamento reembolsado | m13 (cancelada) | RD08 |
| Preço histórico diferente do atual | m1 (14.00 vs 15.00) | `preco_aplicado` |
| Serviço inativo sem habilitados | serviço 8 | RN03 |
| Avaliação ao barbeiro e ao serviço | várias | RN06 |

## Consultas (`sql/03_consultas.sql`)

Mapeamento NI → Q (numeração igual: NI01 = Q01, ...). Resultados esperados com os dados fornecidos:

| Q | Pergunta | Resultado esperado (resumo) |
|---|---|---|
| Q01 | Barbeiros mais bem avaliados (trimestre jul–set) | Tiago 4.50 e Miguel 4.50 (2 av. cada), Rui 4.00 |
| Q02 | Serviços e horas por barbeiro em setembro | Rui 3 marcações / 2.00 h; Miguel 2 / 2.00 h; Tiago 2 marcações (3 serviços) / 1.50 h |
| Q03 | Adesão a subscrições | Básico 2, Premium 1; total 3 |
| Q04 | Saldo de cortes em outubro | Marta 0/2, Pedro 0/2, Joana 2 disponíveis (de 4) |
| Q05 | Receita avulsa vs mensalidade em setembro | avulso 108.00 (4 pagamentos), mensalidade 95.00 (3) |
| Q06 | Serviços mais requisitados por categoria (12 meses) | Corte Clássico 6, Corte Degradé 5, Barba 4, ... |
| Q07 | Pendentes hoje por barbeiro | 4 marcações: Miguel 1, Tiago 3 |
| Q08 | Taxa de cancelamento por mês | 2026-09: 12.5 %, 2026-10: 14.3 % |

Verificado: todos estes resultados foram confirmados a correr os scripts num PostgreSQL 16 limpo.

## Pendentes para a v3.0
Vistas, papéis/privilégios, trigger(s) (habilitação RN03, quota RN05, sobreposição RN07, avaliação só de marcação concluída), transações, concorrência, índices/EXPLAIN com dados sintéticos.
