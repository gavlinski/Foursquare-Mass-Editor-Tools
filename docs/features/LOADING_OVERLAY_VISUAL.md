# Loading Overlay - Guia Visual

## 🎨 Estados do Loading Overlay

### Estado 1: Inicial (Imediato)
```
┌─────────────────────────────────────────┐
│                                         │
│                                         │
│            [Spinner Rotativo]          │
│                                         │
│         Carregando Elio Tools          │
│        Preparando interface...         │
│                                         │
│          ██████░░░░░░░░░░░             │ (Barra progresso - edit.php)
│                                         │
│                                         │
└─────────────────────────────────────────┘
Background: Gradient Purple (#667eea → #764ba2)
Z-index: 99999 (sobre todo conteúdo)
Opacity: 1.0 (opaco)
```

### Estado 2: Fade Out (Após Dojo Ready)
```
┌─────────────────────────────────────────┐
│                                         │
│                                         │
│            [Spinner Rotativo]          │  <-- 50% transparente
│                                         │
│         Carregando Elio Tools          │
│        Preparando interface...         │
│                                         │
│                                         │
└─────────────────────────────────────────┘
Opacity: 0.5 → 0.0 (transição 500ms)
```

### Estado 3: Removido (Interface Visível)
```
Overlay: display: none (removido do DOM)
Body class: 'loading' removida
Conteúdo: visibility: visible
```

## 📱 Responsividade

### Desktop (1920x1080)
```
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│                                                             │
│                     [Spinner 60x60px]                      │
│                                                             │
│              Carregando Elio Tools (18px)                  │
│           Preparando interface... (14px)                   │
│                                                             │
│                 ████████░░░░░░░ (200px)                    │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Mobile (375x667)
```
┌───────────────────────┐
│                       │
│                       │
│    [Spinner 60px]    │
│                       │
│  Carregando Editor   │
│  Preparando venues   │
│                       │
│   ████████░░░ (80%)  │
│                       │
└───────────────────────┘
```

## 🎭 Animações

### 1. Spinner Rotation
```
Keyframe: @keyframes spin
Duration: 0.8s
Timing: linear
Iteration: infinite

0%   → transform: rotate(0deg)
100% → transform: rotate(360deg)

┌───┐     ┌───┐     ┌───┐     ┌───┐
│╱  │  →  │ ╲ │  →  │  ╲│  →  │╱  │
└───┘     └───┘     └───┘     └───┘
 0ms      200ms     400ms     600ms
```

### 2. Progress Bar (edit.php)
```
Keyframe: @keyframes progress
Duration: 2s
Timing: ease-in-out
Iteration: infinite

0%   → width: 0%    [░░░░░░░░░░]
50%  → width: 70%   [███████░░░]
100% → width: 100%  [██████████]
```

### 3. Fade Out
```
Keyframe: opacity transition
Duration: 500ms
Timing: ease-out

0ms   → opacity: 1.0  ████████████
100ms → opacity: 0.8  ██████████░░
200ms → opacity: 0.6  ████████░░░░
300ms → opacity: 0.4  ██████░░░░░░
400ms → opacity: 0.2  ████░░░░░░░░
500ms → opacity: 0.0  ░░░░░░░░░░░░ (removido)
```

## 🎨 Paleta de Cores

### Background Gradient
```
Start: #667eea (Purple-Blue)
 ███████
   ↓
 ███████
   ↓
End:   #764ba2 (Deep Purple)
```

### Texto
```
Primary:   #FFFFFF (white) - 100% opacity
Secondary: #FFFFFF (white) - 80% opacity
```

### Spinner
```
Border:     rgba(255,255,255,0.3) - Track
Border-top: #FFFFFF               - Active segment
```

### Progress Bar (edit.php)
```
Track: rgba(255,255,255,0.3) ░░░░░░░░░░
Fill:  #FFFFFF                ██████████
```

## 📐 Especificações Técnicas

### Container
```css
position: fixed;
top: 0; left: 0;
width: 100%; height: 100%;
display: flex;
flex-direction: column;
justify-content: center;
align-items: center;
```

### Spinner
```css
width: 60px; height: 60px;
border: 4px solid rgba(255,255,255,0.3);
border-top-color: #fff;
border-radius: 50%;
```

### Typography
```css
Font Family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto
Primary:   18px / 500 weight
Secondary: 14px / normal weight
Color: white (rgba 0.8 for secondary)
```

## 🔄 Fluxo de Estados (Diagrama)

```
┌─────────────┐
│ HTML Loaded │
│   t=0ms     │
└──────┬──────┘
       │
       ▼
┌──────────────────────┐
│  Overlay Visível     │
│  Body class=loading  │
│  Content hidden      │
│  t=0-2000ms         │
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│  Dojo Downloaded     │
│  Parsing widgets     │
│  t=1000-3000ms      │
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│  dojo.ready()        │
│  Callback triggered  │
│  t=2000-4000ms      │
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│  Fade Out Start      │
│  opacity: 1 → 0      │
│  t=0-500ms          │
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│  Overlay Removed     │
│  Content visible     │
│  UI Ready            │
└──────────────────────┘
```

## 🎬 Exemplo de Timeline (main.php)

```
Time    Event                           UI State
─────   ─────────────────────────────   ──────────────────────
0ms     HTML Parse Start                [BLANK]
10ms    <head> CSS Loaded               [BLANK]
20ms    <body> Rendered                 [OVERLAY VISIBLE]
50ms    Dojo Script Download Start      [OVERLAY + SPINNER]
1200ms  Dojo Script Loaded              [OVERLAY + SPINNER]
1500ms  Dojo Parsing Widgets            [OVERLAY + SPINNER]
2100ms  dojo.ready() Fired              [OVERLAY + SPINNER]
2200ms  removeLoadingOverlay() Called   [FADE OUT START]
2700ms  Overlay Removed from DOM        [CONTENT VISIBLE ✅]
```

## 🎬 Exemplo de Timeline (edit.php)

```
Time    Event                           UI State
─────   ─────────────────────────────   ──────────────────────
0ms     HTML Parse Start                [BLANK]
10ms    <head> CSS Loaded               [BLANK]
20ms    <body> Rendered                 [OVERLAY + "X venues"]
50ms    Dojo Script Download Start      [OVERLAY + PROGRESS]
1200ms  Dojo Script Loaded              [OVERLAY + PROGRESS]
1500ms  Dojo Parsing Widgets            [OVERLAY + PROGRESS]
2100ms  dojo.ready() Fired              [OVERLAY + PROGRESS]
2200ms  Check venue data (interval)     [OVERLAY + PROGRESS]
2300ms  form[name="form1"] Found ✅     [OVERLAY + PROGRESS]
2500ms  removeLoadingOverlay() Called   [FADE OUT START]
3000ms  Overlay Removed from DOM        [EDITOR VISIBLE ✅]
```

## 🚦 Indicadores Visuais

### Durante Carregamento
- ✅ Spinner animando (confirma não travado)
- ✅ Texto informativo (tranquiliza usuário)
- ✅ Barra progresso (edit.php - feedback adicional)

### Pós Carregamento
- ✅ Fade out suave (evita "flash" brusco)
- ✅ Interface totalmente estilizada
- ✅ Widgets Dojo operacionais

### Em Caso de Erro
- ⚠️ Timeout após 30 segundos
- ⚠️ Overlay removido (usuário pode tentar refresh)
- ⚠️ Console log com aviso

---

**Visual Reference**: Este documento descreve os estados visuais do loading overlay.  
**Para detalhes técnicos**: Ver [LOADING_OVERLAY.md](LOADING_OVERLAY.md)
