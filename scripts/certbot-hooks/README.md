# Certbot Hooks — Renovação SSL com Docker

Scripts de ciclo de vida para renovação automática do certificado Let's Encrypt quando a aplicação roda em Docker com certbot standalone.

## Problema

O certbot está configurado com `authenticator = standalone`, que precisa **bindar a porta 80** para o ACME HTTP-01 challenge. O Docker-proxy já ocupa essa porta, então sem os hooks abaixo a renovação falharia.

## Hooks

| Tipo | Arquivo | Quando executa |
|---|---|---|
| `pre` | `pre/01-stop-docker.sh` | Antes do ACME challenge — para o container (libera porta 80) |
| `deploy` | `deploy/restart-docker.sh` | Após renovação **bem-sucedida** — reinicia o container com o novo cert |
| `post` | `post/01-start-docker.sh` | Após qualquer tentativa — garante que o container sobe mesmo se houve erro |

## Instalação

Os hooks são instalados automaticamente por `scripts/setup-ssl-production.sh`. Para instalar manualmente:

```bash
# No servidor de produção, como root:
HOOKS_SRC="/var/www/4sqmet/scripts/certbot-hooks"
HOOKS_DEST="/etc/letsencrypt/renewal-hooks"

for dir in pre deploy post; do
    for script in "$HOOKS_SRC/$dir/"*.sh 2>/dev/null; do
        [ -f "$script" ] || continue
        cp "$script" "$HOOKS_DEST/$dir/"
        chmod +x "$HOOKS_DEST/$dir/$(basename $script)"
    done
done

echo "Hooks instalados:"
ls -la /etc/letsencrypt/renewal-hooks/{pre,deploy,post}/
```

## Validar

```bash
# Testa o fluxo completo sem emitir certificado real (~90s de downtime)
certbot renew --dry-run
```

## Downtime esperado

~90–120 segundos por renovação (a cada ~90 dias):
- `docker stop`: ~10s
- ACME challenge: ~30–60s  
- `docker start` + healthcheck: ~40–50s

Próxima renovação prevista: **~14 de maio de 2026** (certificado expira em 13/06/2026).
