# Dependabot

Este projeto usa o Dependabot para abrir pull requests automáticos de atualização de dependências e arquivos de infraestrutura versionados no repositório.

## Arquivo de configuração

O arquivo principal fica em [.github/dependabot.yml](../.github/dependabot.yml).

Configuração atual coberta neste projeto:

- `composer`: dependências PHP definidas em [composer.json](../composer.json)
- `npm`: dependências JavaScript definidas em [package.json](../package.json)
- `github-actions`: actions usadas em [.github/workflows/deploy.yml](../.github/workflows/deploy.yml)
- `devcontainers`: definição do ambiente de desenvolvimento em [.devcontainer/devcontainer.json](../.devcontainer/devcontainer.json)

Todas as verificações estão configuradas com frequência semanal.

## Como ativar no GitHub

O Dependabot começa a funcionar quando estas condições são atendidas:

1. O arquivo `.github/dependabot.yml` está commitado na branch padrão do repositório.
2. O repositório está hospedado no GitHub.
3. Os recursos de segurança/dependências estão habilitados no repositório.

Verificações recomendadas no GitHub:

1. Abra `Settings` do repositório.
2. Entre em `Security & analysis`.
3. Garanta que estejam habilitados:
   - `Dependency graph`
   - `Dependabot alerts`
   - `Dependabot security updates` (opcional, mas recomendado)

Depois disso, o GitHub executa o cronograma automaticamente e cria PRs quando encontra atualizações compatíveis.

## O que esperar dos PRs

Os PRs do Dependabot normalmente:

- atualizam versões em manifests e lockfiles suportados
- mostram changelog e nível de risco quando disponível
- executam os workflows normais de CI do repositório

Neste projeto, isso impacta principalmente:

- bibliotecas PHP do Composer
- ferramenta de build JavaScript usada no minify
- actions do workflow de deploy
- componentes do Dev Container usados no ambiente VS Code

## Limites e observações

- O Dependabot não substitui validação manual de deploy.
- Atualizações de infraestrutura devem ser revisadas com atenção, especialmente `github-actions` e `devcontainers`.
- Se um ecossistema parar de ser usado, ele deve ser removido da configuração para evitar PRs desnecessários.
- Se um novo manifesto for adicionado em subdiretório, é necessário incluir outro bloco `updates` apontando para o diretório correto.

## Manutenção recomendada

Revisar a configuração do Dependabot quando houver mudanças nestes pontos:

- novo workflow em `.github/workflows/`
- nova stack de frontend/backend
- múltiplos `package.json` ou `composer.json`
- mudanças relevantes no ambiente de Dev Container

## Exemplo resumido

```yml
version: 2
updates:
  - package-ecosystem: "composer"
    directory: "/"
    schedule:
      interval: "weekly"
```

Esse padrão pode ser repetido para cada ecossistema monitorado.