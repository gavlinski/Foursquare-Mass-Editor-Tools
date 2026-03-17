# Monitoramento DigitalOcean — Metrics Agent & Alertas

> **Referência oficial**: https://docs.digitalocean.com/products/monitoring/how-to/install-metrics-agent/  
> **Documentado em**: março de 2026

O DigitalOcean Monitoring é um serviço **gratuito e opt-in** que permite rastrear o uso de recursos do Droplet em tempo real, visualizar métricas de performance e receber alertas via e-mail ou Slack.

O agente **`do-agent`** é um utilitário open-source em Go que coleta métricas a nível de sistema e as envia à DigitalOcean. Ele é suportado em:

- Ubuntu 14.04 ou superior ✅ *(nosso servidor usa Ubuntu + Apache 2.4)*
- CentOS 6 ou superior
- Debian 8 ou superior
- Fedora 27 ou superior

---

## Parte 1 — Instalar o Metrics Agent no Droplet

### Opção A: Instalação em um único comando (recomendada)

Acesse o console do Droplet via painel DigitalOcean (More → Access Console → Launch Droplet Console) e execute:

```bash
curl -sSL https://repos.insights.digitalocean.com/install.sh | sudo bash
```

O script detecta automaticamente o sistema operacional, configura o repositório correto e instala o pacote `do-agent`. A saída esperada é semelhante a:

```
Cleaning up old sources...OK
Verifying machine compatibility...OK
Verifying compatibility with script...OK
Installing apt repository...
Installing gpg key...
Setting up do-agent (3.17.1) ...
Detecting SELinux
SELinux not enforced
enable systemd service
Created symlink '/etc/systemd/system/multi-user.target.wants/do-agent.service' → '/etc/systemd/system/do-agent.service'.
```

### Opção B: Baixar e revisar o script antes de executar

Se preferir auditar o script antes de rodar (boa prática em produção):

```bash
# 1. Baixar o script
curl -sSL https://repos.insights.digitalocean.com/install.sh -o /tmp/install.sh

# 2. Confirmar que foi baixado
ls -lh /tmp/install.sh

# 3. Revisar o conteúdo (pressione 'q' para sair)
less /tmp/install.sh

# 4. Executar após revisão
sudo bash /tmp/install.sh
```

---

## Parte 2 — Verificar se o Agente está Rodando

Após a instalação, verifique o status do serviço:

```bash
systemctl status do-agent
```

Saída esperada (agente **ativo**):

```
● do-agent.service - The DigitalOcean Monitoring Agent
     Loaded: loaded (/etc/systemd/system/do-agent.service; enabled; preset: enabled)
     Active: active (running) since ...
   Main PID: 4675 (do-agent)
```

Para confirmar o processo em execução:

```bash
ps aux | grep do-agent
```

Saída esperada:

```
do-agent  4675  0.0  0.3 1237180 14712 ?  Ssl  ...  /opt/digitalocean/bin/do-agent --syslog
```

Após o agente iniciar, as métricas aparecem na aba **Graphs** do Droplet no painel da DigitalOcean em alguns minutos.

---

## Parte 3 — Configurar Alertas de Recursos

> **Painel**: https://cloud.digitalocean.com/monitors/resource-alerts

O agente precisa estar instalado antes de configurar qualquer alerta.

### Como criar um alerta (passo a passo)

1. Acesse [Monitoring → Resource Alerts](https://cloud.digitalocean.com/monitors/resource-alerts)
2. Clique em **Create Resource Alert**

#### Passo 1: Selecionar a métrica

| Métrica | O que mede |
|---|---|
| **CPU Utilization Percent** | Uso total de CPU |
| **Memory Utilization Percent** | Memória total em uso |
| **Disk Utilization Percent** | Armazenamento em uso no disco raiz |
| **1 / 5 / 15 Minute Load Average** | Média de processos em execução |
| **Disk Read/Write I/O (Mbps)** | Atividade de leitura/escrita no disco |
| **Public Inbound/Outbound Bandwidth (Mbps)** | Tráfego de rede público |

#### Passo 2: Definir a regra

- **is above** → alerta quando o valor *supera* o limite (ex: CPU acima de 80%)
- **is below** → alerta quando o valor *cai* abaixo do limite (ex: bandwidth abaixo do esperado)

#### Passo 3: Definir o threshold (limiar)

Pontos de partida recomendados pela DigitalOcean:

| Métrica | Threshold sugerido |
|---|---|
| CPU, Memória, Disco | **70%** |
| Load Average | Próximo ou levemente acima do nº de vCPUs do Droplet |
| Bandwidth / Disk I/O | Baseado na atividade típica (verificar aba Graphs) |

#### Passo 4: Definir a duração

A métrica precisa permanecer acima/abaixo do threshold por esse tempo antes de disparar o alerta:

- **5 minutos** → Serviços críticos, resposta rápida
- **10 minutos** → Uso geral
- **30 minutos / 1 hora** → Workloads variáveis, reduz falsos positivos

#### Passo 5: Atribuir ao Droplet

- Selecione o Droplet específico do servidor de produção, ou
- Use uma **tag** para aplicar a múltiplos Droplets de forma consistente

#### Passo 6: Configurar notificações

- **E-mail**: adicione o e-mail da conta DigitalOcean ou outros membros da equipe
- **Slack**: integre com um canal Slack para notificações em tempo real
- É possível configurar ambos simultaneamente

#### Passo 7: Finalizar

Dê um nome descritivo ao alerta (ex: `prod-cpu-alta`, `prod-disco-cheio`) e clique em **Create Resource Alert**.

O alerta entra em vigor imediatamente e se resolve automaticamente quando o uso volta ao normal — você recebe uma segunda notificação de resolução.

---

## Parte 4 — Alertas recomendados para este projeto

Para a aplicação **Foursquare Mass Editor Tools** em produção, sugerimos configurar os seguintes alertas:

| Alerta | Métrica | Regra | Threshold | Duração | Tipo |
|---|---|---|---|---|---|
| CPU Alta | CPU Utilization | is above | 80% | 10 min | Percentual |
| CPU Crítico | CPU Utilization | is above | 95% | 5 min | Percentual |
| Memória Alta | Memory Utilization | is above | 75% | 10 min | Percentual |
| Memória Crítica | Memory Utilization | is above | 90% | 5 min | Percentual |
| Disco Cheio | Disk Utilization | is above | 85% | 5 min | Percentual |
| Load Avg Warning | 5 Minute Load Average | is above | **1** | 10 min | Inteiro absoluto |
| Load Avg Critical | 5 Minute Load Average | is above | **2** | 5 min | Inteiro absoluto |

> **⚠️ Load Average aceita apenas inteiros** no painel DO. Para 1 vCPU: `> 1` = fila acumulando por 10min; `> 2` = fila dobrou (crítico). Para 2 vCPUs: dobrar os valores (3 e 5).
>
> **Nota**: Após configurar, acompanhe os primeiros dias de operação e ajuste os thresholds se houver muitos falsos positivos.

---

## Notas de Segurança do Agente

O `do-agent` é seguro e não invasivo:

- Roda como **usuário não privilegiado**
- Acessa apenas: `/proc` (estado do sistema), `/var/opt` (autenticação), `/opt/digitalocean` (binário)
- Usa apenas **conexões de saída** — não abre portas de entrada
  - Porta 80: metadata service (token de autenticação)
  - Porta 443: envio de métricas (criptografado)
- **Não coleta** variáveis de ambiente nem argumentos de processos
- Histórico de dados retido por **90 dias** após desinstalação

---

## Links Úteis

- [Instalar o Metrics Agent](https://docs.digitalocean.com/products/monitoring/how-to/install-metrics-agent/)
- [Gerenciar Alertas de Recursos](https://docs.digitalocean.com/products/monitoring/how-to/manage-alerts/)
- [Desinstalar o Metrics Agent](https://docs.digitalocean.com/products/monitoring/how-to/uninstall-metrics-agent/)
- [Painel de Monitoramento](https://cloud.digitalocean.com/monitoring)
- [Painel de Alertas](https://cloud.digitalocean.com/monitors/resource-alerts)
