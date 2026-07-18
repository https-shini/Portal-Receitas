<?php
/**
 * Painel de filtros do catálogo (dentro do <form> GET da home). Categorias
 * vêm do banco (data-driven); ordenação por whitelist. Funciona sem JS
 * (botão "Aplicar"); catalog.js só melhora a experiência (abrir/fechar,
 * auto-submit da ordenação).
 *
 * @var list<array{id:int,name:string,icon:?string,total:int}> $categories
 * @var array{search:?string,categoryIds:list<int>,sort:string}            $filters
 */
$sortLabels = [
    'relevancia' => 'Mais relevantes',
    'nome' => 'Nome (A–Z)',
    'tempo' => 'Tempo de preparo',
];
$selecionadas = $filters['categoryIds'] ?? [];
?>
<div class="filters-panel" id="filtersPanel" hidden>
    <div class="filters-panel__group">
        <h2 class="filters-panel__title" id="filtroCategorias">Categorias</h2>
        <ul class="filters-cats" role="list" aria-labelledby="filtroCategorias">
            <?php foreach ($categories as $categoria): ?>
                <li>
                    <label class="filter-chip">
                        <input type="checkbox" name="categoriaReceita[]" value="<?= (int) $categoria['id'] ?>"
                               <?= in_array($categoria['id'], $selecionadas, true) ? 'checked' : '' ?>
                               <?= $categoria['total'] === 0 ? 'disabled' : '' ?>>
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
        <label class="filters-panel__title" for="ordenar">Ordenar por</label>
        <div class="field__control">
            <i class="las la-sort" aria-hidden="true"></i>
            <select class="field__input js-ordenar" name="ordenar" id="ordenar">
                <?php foreach ($sortLabels as $valor => $rotulo): ?>
                    <option value="<?= $valor ?>" <?= ($filters['sort'] ?? 'relevancia') === $valor ? 'selected' : '' ?>>
                        <?= htmlspecialchars($rotulo) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="filters-panel__actions">
        <a class="btn btn--ghost" href="index.php">Limpar</a>
        <button class="btn btn--primary" type="submit">Aplicar filtros</button>
    </div>
</div>
