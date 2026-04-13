# MCP Playwright - Validation Runbook

## Objetivo

Padronizar validações de UI/UX e debug funcional via MCP no Dev Container.

## Pré-requisitos

1. Projeto aberto no Dev Container
2. App disponível em `https://localhost/4sqmet/`
3. Configuração MCP no workspace: `.vscode/mcp.json`
4. Trust do servidor MCP aprovado no VS Code

## Smoke Check (obrigatório)

1. Abrir `https://localhost/4sqmet/`
2. Confirmar carregamento sem erro SSL
3. Navegar para `https://localhost/4sqmet/debug/`
4. Abrir Session Test Manager
5. Capturar screenshot em cada etapa

## Fluxos de regressão recomendados

### 1) Sessão/Auth

- Criar sessão de teste em `debug/session_test_manager.php?action=create`
- Validar sessão em `debug/debug_session.php?mode=validate`
- Verificar comportamento visual e mensagens da interface

### 2) Edição em massa

- Acessar fluxo principal até `edit.php`
- Confirmar renderização dos campos Dojo
- Alterar alguns campos e validar sinalização de alteração

### 3) Google Maps

- Validar presença do mapa e marcadores
- Validar redimensionamento da interface
- Validar estabilidade ao navegar entre telas

### 4) Erros e retry

- Exercitar cenário com erro de API (quando possível)
- Verificar feedback visual e tentativa de retry

## Evidências

Salvar artefatos em `debug/mcp-artifacts/`:

- screenshots
- snapshots
- logs de console/network (quando aplicável)

## Critério de aprovação

A execução é aprovada quando:

1. Todos os smoke checks passam
2. Fluxos críticos (sessão, edição, mapas) estão estáveis
3. Não há regressão visual evidente
4. Evidências foram geradas e anexadas ao PR/revisão

## Nota sobre Chrome DevTools MCP

Para diagnóstico avançado (trace, Lighthouse, memória), usar Chrome DevTools MCP em fase complementar, preferencialmente após upgrade do Node do Dev Container para 20.19+.
