# fail2ban (opcional, só em servidor Linux)

O fail2ban lê um ficheiro de log e bloqueia na firewall os IPs que falham muitas vezes.
Só serve num servidor Linux onde tens acesso root. Não serve em `localhost`, Render, Railway ou alojamento partilhado.

O Laravel já escreve a linha certa em `storage/logs/auth.log`:

```
[2026-11-09 21:00:00] production.WARNING: Login falhado {"email":"x@y.pt","ip":"203.0.113.9"}
```

## Filtro

`/etc/fail2ban/filter.d/laravel-auth.conf`

```
[Definition]
failregex = Login falhado .*"ip":"<HOST>"
```

## Jail

`/etc/fail2ban/jail.d/laravel-auth.conf`

```
[laravel-auth]
enabled  = true
filter   = laravel-auth
logpath  = /var/www/barbearia/storage/logs/auth.log
maxretry = 5
findtime = 10m
bantime  = 1h
port     = http,https
```

## Testar e ativar

```bash
sudo fail2ban-regex /var/www/barbearia/storage/logs/auth.log /etc/fail2ban/filter.d/laravel-auth.conf
sudo systemctl restart fail2ban
sudo fail2ban-client status laravel-auth
```

## Cuidados

- Atrás de um proxy (nginx, Cloudflare) configura o `trustProxies` no `bootstrap/app.php`. Senão o IP que vai para o log é o do proxy e o fail2ban bloqueia todos os utilizadores.
- Numa rede partilhada (por exemplo, a faculdade) vários utilizadores têm o mesmo IP. Um `maxretry` muito baixo bloqueia inocentes.
- O `auth.log` cresce sem parar. Põe-no no `logrotate`.
