# DevContainer Setup Skill

## Name
VS Code Dev Container — Configuração e Manutenção

## Description
Gerencia a configuração do ambiente de desenvolvimento baseado em VS Code Dev Containers para este projeto. Cobre o `devcontainer.json`, scripts de inicialização, symlink `/var/www/html`, mapeamento de portas (OrbStack vs Docker Desktop), e coexistência com o fluxo externo via `./dev.sh`.

## When to use
Carregue esta skill quando:
- Modificar `.devcontainer/devcontainer.json`
- Trabalhar com `scripts/devcontainer-setup.sh` ou `scripts/dev-internal.sh`
- Diagnosticar problemas de porta, Apache, symlink ou `postCreateCommand`
- Adicionar novas extensões ou features ao Dev Container
- Explicar diferenças entre Dev Container e `./dev.sh`
- Atualizar documentação de onboarding em `docs/DEV_CONTAINER.md`

## Key Files
- `.devcontainer/devcontainer.json` — configuração principal
- `scripts/devcontainer-setup.sh` — inicialização automática (postCreateCommand)
- `scripts/dev-internal.sh` — gerenciamento após inicialização (substitui dev.sh)
- `docs/DEV_CONTAINER.md` — documentação para desenvolvedores
- `Dockerfile` — imagem base compartilhada (Dev Container + dev.sh)
- `apache-config.conf` — vhost de desenvolvimento (Alias /4sqmet/)
- `ssl/localhost.pem`, `ssl/localhost-key.pem` — certificado self-signed

## Architecture Decision: Por que Dev Container aqui?

O container já é a imagem `php:8.1-apache` usada em produção. Dev Container permite desenvolver **na mesma imagem**, eliminando discrepâncias. O Dockerfile usa `BUILD_ENV=development` para:
1. Copiar `apache-config.conf` (com Alias `/4sqmet/`) em vez de `apache-config-production.conf`
2. Instalar certificados SSL self-signed de `ssl/localhost.pem`

## Symlink Pattern (crítico)

O VS Code monta o workspace em `/workspaces/Foursquare-Mass-Editor-Tools`.
O Apache serve de `/var/www/html` (definido no Dockerfile).

**Solução**: `postCreateCommand` cria um symlink:
```bash
rm -rf /var/www/html && ln -s ${containerWorkspaceFolder} /var/www/html
```

**NÃO usar `workspaceMount`**: Mudar o mount point quebra o VS Code — ele perde o workspace salvo e exige navegação manual para o novo path a cada rebuild. Use sempre o symlink.

## Port Mapping: OrbStack vs Docker Desktop

### OrbStack (macOS) — `appPort`
```jsonc
"appPort": ["80:80", "443:443"]
```
- OrbStack gerencia portas privilegiadas via extensão de rede
- Resultado: `https://localhost/4sqmet/` na porta padrão 443
- `forwardPorts` com OrbStack cria túnel SSH com porta aleatória (ex: 61637) — evitar

### Docker Desktop (macOS/Windows/Linux) — `forwardPorts`
```jsonc
"forwardPorts": [80, 443],
"portsAttributes": {
    "443": {
        "protocol": "https",
        "label": "App HTTPS (4sqmet)",
        "onAutoForward": "openBrowserOnce"
    }
}
```
- Docker Desktop não mapeia portas privilegiadas diretamente com `appPort`
- `forwardPorts` cria túnel SSH com porta aleatória
- `onAutoForward: "openBrowserOnce"` abre o browser automaticamente na URL correta
- URL visível na aba PORTS do VS Code

## overrideCommand: false (crítico)

Sem esta opção, o VS Code substitui o PID 1 do container pelo seu próprio processo, e o `docker-entrypoint.sh` (que inicia o Apache via `apache2-foreground`) nunca é executado. Apache fica parado.

```jsonc
"overrideCommand": false  // Preserva docker-entrypoint.sh como PID 1
```

O `postStartCommand` serve como segurança extra caso o Apache não inicie automaticamente:
```bash
service apache2 status > /dev/null 2>&1 || apache2ctl start
```

## Coexistência com ./dev.sh

Os dois fluxos **não devem rodar simultaneamente** — ambos mapeiam portas 80 e 443 no host e o segundo a tentar subir falhará com erro de port conflict.

| Contexto | Usar | Não usar |
|----------|------|----------|
| Dentro do Dev Container (terminal VS Code) | `./scripts/dev-internal.sh` | `./dev.sh` |
| Fora do Dev Container (terminal macOS) | `./dev.sh` | `./scripts/dev-internal.sh` |

A variável `DEVCONTAINER=true` (definida em `containerEnv`) permite que scripts detectem o ambiente:
```bash
if [ -z "$DEVCONTAINER" ] && [ ! -f /.dockerenv ]; then
    echo "Este script é para uso dentro do DevContainer"
    exit 1
fi
```

## Verificação de portas sem ss/netstat

A imagem `php:8.1-apache` não inclui `ss` nem `netstat`. Use `curl` ou `/proc/net/tcp`:

```bash
# Verificação funcional (preferida)
curl -s http://localhost/ --max-time 2 -o /dev/null -w "%{http_code}"   # 301 = OK
curl -sk https://localhost/ --max-time 2 -o /dev/null -w "%{http_code}" # 200 = OK

# Via /proc (portas LISTEN: estado 0A)
cat /proc/net/tcp | grep " 0A " | awk '{print $2}' | cut -d: -f2 | \
    while read hex; do printf "%d\n" "0x$hex"; done | sort -u
# 80 e 443 devem aparecer
```

## build.sh: bc não está disponível

A imagem base não inclui `bc`. Substituir cálculos float por `awk`:

```bash
# ❌ Não funciona — bc ausente
REDUCTION=$(echo "scale=1; 100 - ($B * 100 / $A)" | bc)

# ✅ Correto — awk sempre disponível
REDUCTION=$(awk "BEGIN {printf \"%.1f\", 100 - ($B * 100 / $A)}")
```

Esta correção já está aplicada em `build.sh` (7 ocorrências, linhas 107–153).

## Extensões: o que é obrigatório vs opcional

| Extensão | Status | Motivo |
|----------|--------|--------|
| `bmewburn.vscode-intelephense-client` | Obrigatória | IntelliSense PHP com PHP 8.1 do container |
| `eamodio.gitlens` | Opcional | Histórico Git inline — útil mas não essencial |

## postCreateCommand: ordem de execução

```bash
# 1. Remove /var/www/html estático (copiado pelo Dockerfile)
rm -rf /var/www/html

# 2. Cria symlink para o workspace
ln -s ${containerWorkspaceFolder} /var/www/html

# 3. Executa setup do ambiente
bash scripts/devcontainer-setup.sh
```

`${containerWorkspaceFolder}` é a variável do Dev Containers que resolve para o path do workspace dentro do container (equivalente a `/workspaces/Foursquare-Mass-Editor-Tools`).

## Troubleshooting rápido

| Sintoma | Causa provável | Solução |
|---------|---------------|---------|
| Apache não responde após rebuild | `overrideCommand: true` implícito | Verificar `"overrideCommand": false` no devcontainer.json |
| Workspace abre em `/var/www/html` | `workspaceMount` em versão antiga | Fazer Rebuild — versão atual usa symlink |
| Portas mapeadas aleatoriamente (OrbStack) | Usando `forwardPorts` | Trocar para `appPort` |
| `./build.sh` falha com exit code 127 | `bc` ausente | Substituir por `awk` (já corrigido) |
| `./dev.sh` não funciona dentro do container | Docker não disponível no container | Usar `./scripts/dev-internal.sh` |
