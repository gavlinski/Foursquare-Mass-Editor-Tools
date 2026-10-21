<?php

declare(strict_types=1);

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="../fonts/inter.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Debug - Comparação da Barra de Ações</title>
    <style>
        :root {
            --system-font: "Inter", -apple-system, BlinkMacSystemFont, Helvetica, Arial, sans-serif;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;
            color: #2f3a45;
            background: #f3f5f9;
            font-family: "Inter", -apple-system, BlinkMacSystemFont, Helvetica, Arial, sans-serif;
        }

        .page {
            width: min(1200px, 100%);
            margin: 0 auto;
        }

        .intro,
        .demo-card {
            padding: 20px;
            border: 1px solid #d6dde8;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
        }

        .intro {
            margin-bottom: 20px;
        }

        h1,
        h2,
        p {
            margin-top: 0;
        }

        h1 {
            margin-bottom: 8px;
            font-size: 1.5rem;
        }

        .intro p,
        .demo-card p {
            margin-bottom: 14px;
            color: #5a6571;
        }

        .controls {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .controls label {
            font-weight: 600;
        }

        .controls select {
            padding: 7px 10px;
            border: 1px solid #aab6c5;
            border-radius: 6px;
            background: white;
            color: inherit;
            font: inherit;
        }

        .demo-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .demo-card h2 {
            margin-bottom: 6px;
            font-size: 1.1rem;
        }

        .demo-card .description {
            min-height: 3em;
            font-size: 0.9rem;
        }

        .preview {
            position: relative;
            height: min(56vh, 480px);
            min-height: 320px;
            overflow: hidden;
            border: 1px solid #aeb9c8;
            border-radius: 7px;
            background: #f7f9fc;
        }

        .preview-content {
            padding: 14px;
        }

        .preview-title {
            margin-bottom: 10px;
            color: #596675;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .mock-list {
            max-height: min(34vh, 280px);
            overflow: auto;
            padding: 8px;
            border: 1px solid #cbd4df;
            border-radius: 5px;
            background: white;
        }

        .mock-row {
            display: flex;
            align-items: center;
            min-height: 38px;
            margin-bottom: 6px;
            padding: 6px 9px;
            border: 1px solid #dde3ee;
            border-left: 4px solid #2d5be3;
            border-radius: 4px;
            background: #fff;
            color: #3e4a58;
            font-size: 0.86rem;
        }

        .mock-row:last-child {
            margin-bottom: 0;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            width: 100%;
            padding: 8px;
            border-top: 1px solid #d6dde8;
            background: rgba(255, 255, 255, 0.96);
        }

        .actions button {
            padding: 5px 8px;
            border: 1px solid #aab6c5;
            border-radius: 3px;
            background: #fff;
            color: #34404d;
            font: inherit;
            font-size: 0.78rem;
        }

        .actions .save {
            border-color: #2d5be3;
            background: #2d5be3;
            color: #fff;
        }

        .current-actions {
            position: absolute;
            bottom: 0;
            left: 0;
        }

        .proposed-actions.is-fixed {
            position: absolute;
            bottom: 0;
            left: 0;
        }

        .proposed-actions.is-in-flow {
            position: static;
            margin-top: 8px;
        }

        .status {
            margin: 8px 0 0;
            color: #596675;
            font-size: 0.8rem;
        }

        footer {
            margin-top: 24px;
            padding: 24px;
            border-top: 2px solid #e0e0e0;
            text-align: center;
        }

        .back-link {
            display: inline-block;
            padding: 10px 18px;
            border-radius: 7px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            text-decoration: none;
        }

        @media (max-width: 800px) {
            body {
                padding: 14px;
            }

            .demo-grid {
                grid-template-columns: 1fr;
            }

            .preview {
                height: min(56vh, 480px);
            }
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="intro">
            <h1>Comparação da barra de ações</h1>
            <p>Compare a barra atual, sempre presa ao rodapé da tela, com a proposta: presa enquanto o último local está oculto e logo após a lista quando ele fica visível.</p>
            <div class="controls">
                <label for="venue-count">Locais simulados:</label>
                <select id="venue-count">
                    <option value="3">3 — poucos locais</option>
                    <option value="8">8 — lista média</option>
                    <option value="20">20 — lista longa</option>
                </select>
                <span>Role dentro da lista para revelar o último local.</span>
            </div>
        </section>

        <div class="demo-grid">
            <section class="demo-card">
                <h2>Comportamento atual</h2>
                <p class="description">Ações sempre ancoradas no rodapé; com poucos locais, sobra um espaço grande depois da lista.</p>
                <div class="preview" aria-label="Simulação do comportamento atual">
                    <div class="preview-content">
                        <div class="preview-title">Lista de locais</div>
                        <div class="mock-list"></div>
                    </div>
                    <div class="actions current-actions" aria-label="Ações sempre fixas">
                        <button class="save" type="button">Salvar</button>
                        <button type="button">Recarregar</button>
                        <button type="button">Voltar</button>
                        <button type="button">Mais</button>
                    </div>
                </div>
                <p class="status">A barra permanece no rodapé em qualquer situação.</p>
            </section>

            <section class="demo-card">
                <h2>Comportamento proposto</h2>
                <p class="description">Ações no rodapé enquanto o último local não está visível; depois, posicionadas logo abaixo dele.</p>
                <div class="preview" aria-label="Simulação do comportamento proposto">
                    <div class="preview-content">
                        <div class="preview-title">Lista de locais</div>
                        <div class="mock-list"></div>
                    </div>
                    <div class="actions proposed-actions is-fixed" aria-label="Ações dinâmicas">
                        <button class="save" type="button">Salvar</button>
                        <button type="button">Recarregar</button>
                        <button type="button">Voltar</button>
                        <button type="button">Mais</button>
                    </div>
                </div>
                <p class="status" aria-live="polite"></p>
            </section>
        </div>

        <footer>
            <a class="back-link" href="index.php">← Voltar para Debug</a>
        </footer>
    </main>

    <script>
        (function() {
            const countSelector = document.getElementById('venue-count');
            const previews = document.querySelectorAll('.preview');

            function renderRows(count) {
                document.querySelectorAll('.mock-list').forEach(function(list) {
                    list.replaceChildren();
                    for (let index = 1; index <= count; index += 1) {
                        const row = document.createElement('div');
                        row.className = 'mock-row';
                        row.textContent = 'Local de exemplo ' + index;
                        list.appendChild(row);
                    }
                    list.scrollTop = 0;
                });
                previews.forEach(updateProposedTray);
            }

            function updateProposedTray(preview) {
                const actions = preview.querySelector('.proposed-actions');
                const status = preview.closest('.demo-card').querySelector('.status');
                if (!actions) {
                    return;
                }

                const list = preview.querySelector('.mock-list');
                const lastRow = list.lastElementChild;
                if (!lastRow) {
                    actions.classList.add('is-in-flow');
                    actions.classList.remove('is-fixed');
                    return;
                }

                const listBounds = list.getBoundingClientRect();
                const lastRowBounds = lastRow.getBoundingClientRect();
                const previewBounds = preview.getBoundingClientRect();
                const actionsHeight = actions.getBoundingClientRect().height;
                const availableBottom = previewBounds.bottom - actionsHeight - 8;
                const lastRowIsVisible = lastRowBounds.top >= listBounds.top - 2
                    && lastRowBounds.bottom <= listBounds.bottom + 2
                    && lastRowBounds.bottom <= availableBottom;

                actions.classList.toggle('is-in-flow', lastRowIsVisible);
                actions.classList.toggle('is-fixed', !lastRowIsVisible);
                status.textContent = lastRowIsVisible
                    ? 'Último local visível: ações logo abaixo da lista.'
                    : 'Último local oculto: ações presas ao rodapé.';
            }

            countSelector.addEventListener('change', function() {
                renderRows(Number(countSelector.value));
            });

            previews.forEach(function(preview) {
                preview.querySelector('.mock-list').addEventListener('scroll', function() {
                    updateProposedTray(preview);
                }, { passive: true });
            });
            window.addEventListener('resize', function() {
                previews.forEach(updateProposedTray);
            });

            renderRows(Number(countSelector.value));
        })();
    </script>
</body>
</html>
