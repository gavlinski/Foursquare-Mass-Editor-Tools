# Dev Containers — Guia de Configuração para Desenvolvedores

> Para o fluxo de deploy e CI/CD, veja [BUILD_AND_DEPLOY.md](BUILD_AND_DEPLOY.md).  
> Para setup do servidor de produção, veja [deployment/DEPLOY.md](deployment/DEPLOY.md).

## 📋 Índice

- [O que é Dev Container?](#o-que-é-dev-container)
- [Vantagens para este projeto](#vantagens-para-este-projeto)
- [Pré-requisitos](#pré-requisitos)
- [Setup no macOS com OrbStack](#setup-no-macos-com-orbstack)
- [Setup com Docker Desktop](#setup-com-docker-desktop)
- [Uso diário](#uso-diário)
- [Playwright MCP no Dev Container](#playwright-mcp-no-dev-container)
- [Scripts disponíveis](#scripts-disponíveis)
- [Arquivos do DevContainer](#arquivos-do-devcontainer)
- [Solução de problemas](#solução-de-problemas)

---

## O que é Dev Container?

Dev Containers é uma especificação do VS Code que permite abrir um projeto **diretamente dentro de um container Docker**, usando-o como ambiente de desenvolvimento completo. O VS Code conecta ao container via SSH e executa extensões, terminal e IntelliSense dentro dele — não na máquina host.

No contexto deste projeto, o container já é a imagem `php:8.1-apache` que serve a aplicação em produção. Ao usar Dev Containers, você desenvolve **na mesma imagem que roda em produção**, eliminando discrepâncias de ambiente.

---

## Vantagens para este projeto

### Por que usar Dev Containers aqui?

| Aspecto | Desenvolvimento externo (`./dev.sh`) | Dev Container (VS Code) |
|--------|--------------------------------------|-------------------------|
| **IntelliSense PHP** | Depende do PHP instalado localmente | PHP 8.1 do container — autocomplete preciso |
| **npm / build** | Requer Node.js instalado localmente | Node.js v18 incluído na feature do container |
| **Inicialização** | `./dev.sh run` manual | VS Code inicia container automaticamente |
| **Docker visível** | OrbStack/Docker Desktop necessário na tela | Apenas serviço de background — sem janela |
| **Apache acessível** | `https://localhost/4sqmet/` | `https://localhost/4sqmet/` (via `appPort`) |
| **Edições → browser** | Imediato (volume mount) | Imediato (symlink `/var/www/html`) |
| **`build.sh`** | Requer npm local ou Docker | Funciona diretamente (`npm` disponível) |
| **Extensões PHP** | Requer configuração manual | Intelephense configurado automaticamente |
| **Conflito de portas** | — | Não abrir ambos os containers simultaneamente |

### Quando cada abordagem faz sentido

**Use Dev Containers quando:**
- Quiser IntelliSense PHP preciso sem instalar PHP localmente
- Quiser rodar `./build.sh` sem dependências externas
- Preferir que tudo suba automaticamente ao abrir o VS Code
- Trabalhar em uma máquina nova ou sem ferramentas locais configuradas

**Use `./dev.sh` quando:**
- Precisar de múltiplos containers ao mesmo tempo (ex: debugar integração)
- O Dev Container não estiver disponível (servidor sem VS Code, CI/CD local)
- Preferir manter controle manual do ciclo de vida do container

> ⚠️ **Conflito de portas**: Não abra o container pelo `./dev.sh run` e o Dev Container ao mesmo tempo. Ambos mapeiam as portas 80 e 443 do host e o segundo a tentar subir falhará.

---

## Pré-requisitos

### Comum a todos os sistemas
- **VS Code** com a extensão **Dev Containers** (`ms-vscode-remote.remote-containers`)
- **Git** configurado localmente

### macOS — OrbStack (recomendado)
- [OrbStack](https://orbstack.dev) instalado (alternativa leve ao Docker Desktop)
- OrbStack **não precisa de janela aberta** — basta o serviço rodando em background (ícone na barra de menu)

### Linux / Windows / macOS — Docker Desktop
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) instalado e rodando
- Ver nota sobre portas em [Setup com Docker Desktop](#setup-com-docker-desktop)

---

## Setup no macOS com OrbStack

### 1. Instale a extensão Dev Containers no VS Code

```
Cmd+Shift+X → buscar "Dev Containers" → instalar (ms-vscode-remote.remote-containers)
```

### 2. Abra o projeto em Dev Container

Com o projeto já clonado e o OrbStack rodando:

```
Cmd+Shift+P → "Dev Containers: Reopen in Container"
```

O VS Code irá:
1. Construir a imagem Docker com `BUILD_ENV=development`
2. Subir o container com Apache + PHP 8.1
3. Executar o `postCreateCommand` (cria symlink, instala Composer, configura `.env`)
4. Reconectar o editor ao container

### 3. Aguarde o `postCreateCommand`

Ao final do terminal de setup, você verá:

```
🚀 DevContainer pronto!

   🌐 App:   https://localhost/4sqmet/
   🐛 Debug: https://localhost/4sqmet/debug/
   🔧 Build: ./build.sh
   🛠  Mgmt:  ./scripts/dev-internal.sh <status|reload|logs|build>

   ⚠️  Edite .env com suas credenciais API se ainda não o fez.
```

### 4. Configure o `.env`

Se é a primeira vez, o arquivo `.env` foi criado automaticamente a partir do `.env.example`. Edite com suas credenciais:

```bash
# No terminal do VS Code (já está dentro do container)
code .env
```

Preencha ao menos:
```bash
FOURSQUARE_CLIENT_KEY="seu_client_id"
FOURSQUARE_CLIENT_SECRET="seu_client_secret"
FOURSQUARE_REDIRECT_URI="https://localhost/4sqmet/index.php"
GOOGLE_MAPS_API_KEY="sua_chave_maps"
GOOGLE_MAPS_MAP_ID="seu_map_id"
```

### 5. Acesse a aplicação

```
https://localhost/4sqmet/
```

O certificado é self-signed (`ssl/localhost.pem`). Aceite o aviso de segurança do browser na primeira vez.

### Próximas sessões

Basta abrir o VS Code — o container sobe automaticamente. Não é necessário nenhuma ação adicional.

---

## Setup com Docker Desktop

O processo é idêntico ao OrbStack. A diferença está no comportamento das portas:

**Com Docker Desktop no macOS/Windows**, as portas privilegiadas (80/443) não são mapeadas diretamente para o host pelo `appPort`. O VS Code cria um túnel SSH com porta aleatória em vez de usar a porta direta.

Para contornar isso, o `devcontainer.json` inclui configuração comentada para Docker Desktop. Veja o arquivo `.devcontainer/devcontainer.json` — a seção comentada instrui o VS Code a usar `forwardPorts` com `onAutoForward` que abre automaticamente o browser na URL correta (mesmo com porta aleatória):

```jsonc
// Se usar Docker Desktop em vez de OrbStack, substitua a seção appPort por:
// "forwardPorts": [80, 443],
// "portsAttributes": {
//     "443": {
//         "protocol": "https",
//         "label": "App HTTPS (4sqmet)",
//         "onAutoForward": "openBrowserOnce"
//     },
//     "80": { "label": "App HTTP → HTTPS" }
// },
```

Com essa configuração, o VS Code vai:
1. Atribuir uma porta aleatória do host para a 443 do container (ex: `localhost:61637`)
2. Abrir o browser automaticamente nessa URL ao iniciar o container
3. Você verá a porta correta na aba **PORTS** do painel inferior

---

## Uso diário

### Abrir o projeto
Apenas abra o VS Code com o projeto — o container já estava rodando ou será reiniciado automaticamente.

### Terminal
O terminal do VS Code já está dentro do container. Todos os comandos `php`, `composer`, `npm`, `git` e `./build.sh` funcionam diretamente:

```bash
# Rodar build (minifica JS, gera build-info.json)
./build.sh

# Ver status (Apache, PHP, Node, portas, Dojo)
./scripts/dev-internal.sh status

# Recarregar Apache após mudanças de config
./scripts/dev-internal.sh reload

# Ver logs do Apache em tempo real
./scripts/dev-internal.sh logs
```

### Reiniciar o container
```
Cmd+Shift+P → "Dev Containers: Rebuild Container"
```

Use quando:
- Mudou o `devcontainer.json` ou `Dockerfile`
- Algo ficou em estado inconsistente
- Quer aplicar atualizações de dependências do Composer

### Parar o container sem fechar o VS Code
```
Cmd+Shift+P → "Dev Containers: Stop Container"
```

---

## Playwright MCP no Dev Container

O workspace já inclui configuração inicial em `.vscode/mcp.json` para usar o Playwright MCP dentro do próprio Dev Container.

### Objetivo

- Validar telas e fluxos de UX com agente (main/load/edit/debug)
- Ajudar no debug funcional de erros de sessão/API
- Gerar artefatos de evidência em `debug/mcp-artifacts/`

### Configuração aplicada

O servidor está configurado para:
- Rodar em modo `headless`
- Ignorar erro de certificado local (`--ignore-https-errors`)
- Salvar logs/snapshots/sessão em `debug/mcp-artifacts/`
- Usar sessão isolada para evitar poluir estado local

### Como ativar no VS Code

1. Abra a visão de Chat
2. Confirme trust do servidor MCP quando o VS Code solicitar
3. No Chat, use **Configure Tools** para habilitar as tools do servidor Playwright

### Smoke test recomendado

Use o prompt abaixo no chat do agente:

```text
Abra https://localhost/4sqmet/, valide que a página principal carregou, navegue para https://localhost/4sqmet/debug/, abra o Session Test Manager, e capture uma screenshot de cada etapa.
```

Critérios de sucesso:
- Navegação em `https://localhost/4sqmet/` sem erro de SSL
- Captura de screenshot/snapshot funcionando
- Artefatos gerados em `debug/mcp-artifacts/`

### Observações

- Esta configuração roda no **remoto** (Dev Container), não no host macOS.
- Para análises avançadas de performance (trace/memory/Lighthouse), planeje fase complementar com Chrome DevTools MCP após upgrade de Node no container para 20.19+.

---

## Scripts disponíveis

### `./scripts/dev-internal.sh` — gerenciamento do container

Substitui os comandos Docker do `./dev.sh` para uso **dentro** do Dev Container.

| Comando | Descrição |
|---------|-----------|
| `status` | Status do Apache, PHP, Node, portas e Dojo |
| `reload` | Recarrega configuração do Apache (graceful, sem derrubar conexões) |
| `restart` | Para e reinicia o Apache |
| `logs` | Segue os logs do Apache em tempo real (Ctrl+C para sair) |
| `build` | Atalho para `./build.sh` — minifica JS e gera `build-info.json` |
| `dojo` | Instala/verifica Dojo Toolkit local (`js/dojo/`, `js/dijit/`, `js/dojox/`) |

```bash
./scripts/dev-internal.sh status
./scripts/dev-internal.sh reload
./scripts/dev-internal.sh logs
```

### `./scripts/devcontainer-setup.sh` — inicialização (automático)

Executado automaticamente pelo `postCreateCommand`. Não é necessário rodar manualmente, mas pode ser chamado para reinicializar o ambiente:

```bash
bash scripts/devcontainer-setup.sh
```

Faz:
- Cria `.env` a partir do `.env.example` (se não existir)
- `composer install --optimize-autoloader`
- Ajusta permissões de `data/` para Apache
- Exibe URLs de acesso

### `./dev.sh` — desenvolvimento externo (não usar dentro do Dev Container)

O script `./dev.sh` continua disponível para quem **não usa Dev Containers**. Ele gerencia o container Docker externamente (via OrbStack/Docker Desktop a partir do terminal do macOS). Não use `./dev.sh` de dentro do Dev Container — use `./scripts/dev-internal.sh`.

---

## Arquivos do DevContainer

Estes são os arquivos que compõem a configuração do Dev Container:

```
.devcontainer/
└── devcontainer.json          # Configuração principal do Dev Container

scripts/
├── devcontainer-setup.sh      # Inicialização (postCreateCommand)
└── dev-internal.sh            # Gerenciamento do container por dentro

Dockerfile                     # Imagem base (php:8.1-apache), usada tanto pelo
                               # Dev Container quanto pelo ./dev.sh
apache-config.conf             # Config Apache para desenvolvimento (/4sqmet/)
ssl/
├── localhost.pem              # Certificado SSL self-signed para desenvolvimento
└── localhost-key.pem          # Chave privada do certificado
```

### O que o `devcontainer.json` configura

| Opção | Valor | Por quê |
|-------|-------|---------|
| `build.args.BUILD_ENV` | `"development"` | Apache usa `apache-config.conf` (Alias `/4sqmet/`) + copia SSL certs |
| `features` | Node.js v18 | Permite `./build.sh` sem instalar Node localmente |
| `appPort` | `["80:80", "443:443"]` | Mapeamento fixo de portas (funciona com OrbStack) |
| `containerEnv.DEVCONTAINER` | `"true"` | Scripts detectam que estão dentro do Dev Container |
| `overrideCommand` | `false` | Preserva o entrypoint original (`docker-entrypoint.sh → apache2-foreground`) |
| `postCreateCommand` | symlink + setup | Conecta `/var/www/html` ao workspace e inicializa ambiente |
| `postStartCommand` | verifica Apache | Garante Apache rodando em cada reinício do container |

### Por que o symlink?

O VS Code monta o workspace em `/workspaces/Foursquare-Mass-Editor-Tools` (caminho padrão do Dev Containers). O Apache, porém, serve de `/var/www/html` (definido no `Dockerfile`). O `postCreateCommand` resolve isso criando um symlink:

```
/var/www/html → /workspaces/Foursquare-Mass-Editor-Tools
```

Isso garante que:
- VS Code mantém seu workspace no path padrão (sem quebrar integrações)
- Apache serve os arquivos reais do workspace
- Edições em qualquer arquivo refletem no browser ao dar F5, sem rebuild

### Extensões instaladas automaticamente

| Extensão | Obrigatória | Descrição |
|----------|-------------|-----------|
| `bmewburn.vscode-intelephense-client` | ✅ Sim | IntelliSense PHP com PHP 8.1 do container |
| `eamodio.gitlens` | ⬜ Opcional | Histórico Git inline, blame, comparação de branches |

Para remover o GitLens se não quiser, basta removê-lo das extensões do VS Code normalmente — não afeta o funcionamento do ambiente.

---

## Solução de problemas

### Apache não responde ao abrir o VS Code

O `postStartCommand` garante que o Apache sobe a cada reinício. Se ainda assim não responder:

```bash
./scripts/dev-internal.sh status   # ver o que está errado
./scripts/dev-internal.sh restart  # reiniciar o Apache
```

### `https://localhost/4sqmet/` não abre (certificado recusado)

O certificado `ssl/localhost.pem` é self-signed. Na primeira vez, o browser exibe um aviso. Para aceitar:
- **Chrome/Edge**: clique em "Avançado" → "Continuar para localhost (não seguro)"
- **Firefox**: clique em "Avançado" → "Aceitar o risco e continuar"
- **Safari**: clique em "Visitar este site" → confirme

### `./build.sh` falha com erro de permissão ou módulo

```bash
# Verificar se npm está disponível
node --version && npm --version

# Instalar dependências node se necessário
npm install

# Rodar build novamente
./build.sh
```

### Portas mapeadas com números aleatórios (Docker Desktop)

Configure o `devcontainer.json` para usar `forwardPorts` com `onAutoForward: "openBrowserOnce"` conforme descrito em [Setup com Docker Desktop](#setup-com-docker-desktop). A URL exata aparecerá na aba **PORTS** do painel inferior do VS Code.

### Conflict de portas ao subir container

Se `./dev.sh run` e o Dev Container estiverem ativos ao mesmo tempo, a segunda tentativa de subir falhará com erro de porta já em uso. Pare um dos dois:

```bash
# Para o container do dev.sh
./dev.sh stop

# Para o Dev Container
# Cmd+Shift+P → "Dev Containers: Stop Container"
```

### `postCreateCommand` falhou — como re-executar o setup

```bash
bash scripts/devcontainer-setup.sh
```

### Workspace abre em path errado após rebuild

Se o VS Code abrir em `/var/www/html` em vez de `/workspaces/Foursquare-Mass-Editor-Tools`:
- Use **File → Open Folder** → `/workspaces/Foursquare-Mass-Editor-Tools`
- Isso acontece apenas se uma versão antiga do `devcontainer.json` (com `workspaceMount`) ainda estiver em cache — o rebuild resolve definitivamente

---

*Última atualização: março de 2026*
