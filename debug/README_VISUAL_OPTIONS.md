# 🎨 Opções Visuais para Linhas Editadas

## Como Testar

Abra o arquivo `test_visual_edited_rows.html` no navegador para ver as 5 opções de destaque visual side-by-side.

## Opções Disponíveis

### Opção 1: Borda Azul Persistente (Sugestão Original)
**CSS:**
```css
#listContainer .row.edited {
    border-color: #2d5be3;
    border-width: 2px;
}
```
- ✅ Simples e direto
- ❌ Pode não ser suficientemente distinto

---

### Opção 2: Faixa Lateral + Background ⭐ **IMPLEMENTADA**
**CSS:**
```css
/* Todas as linhas têm borda cinza para harmonia visual */
#listContainer .row {
    border-left: 4px solid #dee2e6;
}

/* Linha editada apenas troca a cor da borda */
#listContainer .row.edited {
    border-left-color: #2d5be3;
    background: #f8f9ff;
}
```
- ✅ **Padrão da indústria** (VS Code, GitHub, Slack)
- ✅ Alta visibilidade sem ser agressivo
- ✅ Diferenciação clara do estado normal
- ✅ Elegante e moderno
- ✅ **Sem desalinhamento** - todas as linhas têm mesmo espaçamento
- ✅ **Harmonia visual** - borda cinza mantém consistência

---

### Opção 3: Background Gradiente Sutil
**CSS:**
```css
#listContainer .row.edited {
    background: linear-gradient(90deg, #f0f2ff 0%, #ffffff 100%);
    border-color: #2d5be3;
}
```
- ✅ Visualmente elegante
- ❌ Pode ser muito sutil para alguns usuários

---

### Opção 4: Ícone + Badge
**CSS:**
```css
#listContainer .row.edited {
    border-color: #2d5be3;
    position: relative;
}

#listContainer .row.edited::before {
    content: "✎";
    position: absolute;
    right: 10px;
    background: #2d5be3;
    color: white;
    width: 24px;
    height: 24px;
    border-radius: 50%;
}
```
- ✅ Máxima visibilidade
- ❌ Pode poluir visualmente com muitas linhas

---

### Opção 5: Glow/Shadow (Elegante) ⭐ **RECOMENDADA ALTERNATIVA**
**CSS:**
```css
#listContainer .row.edited {
    border-color: #2d5be3;
    box-shadow: 0 0 0 3px rgba(45, 91, 227, 0.1),
                0 2px 8px rgba(45, 91, 227, 0.15);
}
```
- ✅ Extremamente elegante
- ✅ Não-intrusivo mas visível
- ✅ Efeito "premium" e moderno

---

## Como Trocar de Opção

Edite o arquivo `estilo.css` e substitua o bloco:

```css
/* Linha com alterações pendentes (editada) */
#listContainer .row.edited {
    /* CÓDIGO DA OPÇÃO ESCOLHIDA */
}
```

## Implementação Atual

**Opção 2** está implementada por ser o padrão da indústria e oferecer o melhor equilíbrio entre:
- Visibilidade clara
- Design moderno
- Familiaridade (usuários reconhecem de outras ferramentas)
- Acessibilidade

## Sistema de Marcação

### Quando uma linha é marcada como editada:
1. Campo alterado via `verificarAlteracao()`
2. Categoria alterada via `processarEdicaoCategorias()`

### Quando uma linha perde a marcação:
1. Salvamento bem-sucedido via `limparLinhasEditadas()`
2. Recarregamento de dados via `limparLinhasEditadas()`
3. Linha desabilitada via `desabilitarLinha()`

### Código JavaScript:
```javascript
// Adicionar classe
var linhaElement = dojo.byId("linha" + i);
if (linhaElement && !dojo.hasClass(linhaElement, "edited")) {
    dojo.addClass(linhaElement, "edited");
}

// Remover classe
var linhaElement = dojo.byId("linha" + i);
if (linhaElement) {
    dojo.removeClass(linhaElement, "edited");
}
```

---

**Última atualização:** 06/01/2026  
**Versão:** 5.0.1
