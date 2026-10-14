<?php
/**
 * Painel de filtros do catálogo (dentro do <form> GET da home).
 *
 * Visibilidade: entregue SEMPRE com `hidden`. Quem abre é o usuário, pelo
 * botão "Filtros" (catalog.js) — nenhum estado do servidor abre o painel.
 * O `[hidden] { display:none !important }` de base.css garante que nenhuma
 * regra de layout volte a exibi-lo.
 *
 * Categorias vêm do banco (data-driven); as sem receitas chegam desabilitadas
 * e recebem um modificador para ficarem visualmente secundárias, sem sumir.
 * A ordenação vive na barra de resultados (sempre acessível), associada a
 * este form por `form="formSearch"`.
 *
 * Funciona sem JS: o <noscript> revela o painel e o botão "Aplicar filtros"
 * submete normalmente.
 *
 * @var list<array{id:int,name:string,icon:?string,total:int}> $categories
 * @var array{search:?string,categoryIds:list<int>,difficulties:list<string>,sort:string} $filters
 */
$dificuldades = ['Fácil' => 'la-smile', 'Médio' => 'la-meh', 'Difícil' => 'la-fire'];
$selecionadas = $filters['categoryIds'] ?? [];
$difSelecionadas = $filters['difficulties'] ?? [];
?>
<noscript>
    <style>/* Sem JS não há botão de alternância: mantém os filtros utilizáveis. */
        .filters-panel[hidden] { display: grid !important; }
        #btnFiltros { display: none; }
    </style>
</noscript>
<div class="filters-panel" id="filtersPanel" role="group" aria-label="Filtros do catálogo" hidden>
    <div class="filters-panel__group">
        <h2 class="filters-panel__title" id="filtroCategorias">Categorias</h2>
        <ul class="filters-cats" role="list" aria-labelledby="filtroCategorias">
            <?php foreach ($categories as $categoria): ?>
                <?php $vazia = (int) $categoria['total'] === 0; ?>
                <li>
                    <label class="filter-chip<?= $vazia ? ' filter-chip--empty' : '' ?>">
                        <input type="checkbox" name="categoriaReceita[]" value="<?= (int) $categoria['id'] ?>"
                               <?= in_array($categoria['id'], $selecionadas, true) ? 'checked' : '' ?>
                               <?= $vazia ? 'disabled' : '' ?>>
                        <span class="filter-chip__body">
                            <i class="las <?= htmlspecialchars($categoria['icon'] ?? 'la-utensils') ?>" aria-hidden="true"></i>
                            <?= htmlspecialchars($categoria['name']) ?>
                            <span class="filter-chip__count"><?= (int) $categoria['total'] ?></span>
                        </span>
                    </label>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="filters-panel__group">
        <h2 class="filters-panel__title" id="filtroDificuldade">Dificuldade</h2>
        <ul class="filters-cats" role="list" aria-labelledby="filtroDificuldade">
            <?php foreach ($dificuldades as $nivel => $icone): ?>
                <li>
                    <label class="filter-chip">
                        <input type="checkbox" name="dificuldade[]" value="<?= htmlspecialchars($nivel) ?>"
                               <?= in_array($nivel, $difSelecionadas, true) ? 'checked' : '' ?>>
                        <span class="filter-chip__body">
                            <i class="las <?= $icone ?>" aria-hidden="true"></i>
                            <?= htmlspecialchars($nivel) ?>
                        </span>
                    </label>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="filters-panel__actions">
        <a class="btn btn--ghost" href="index.php">Limpar tudo</a>
        <button class="btn btn--primary" type="submit">Aplicar filtros</button>
    </div>
</div>
