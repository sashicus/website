<?php
$adminTitle = 'Товары';
$adminActive = 'products';
view('admin/partials/header', ['adminTitle' => $adminTitle, 'adminActive' => $adminActive]);
$perPage = 20;
$shown = count($items ?? []);
$start = $total > 0 ? ($currentPage - 1) * $perPage + 1 : 0;
$end = $currentPage > 0 ? $start + $shown - 1 : 0;
?>
<div class="adm-pagehead">
    <div>
        <h1 class="adm-h1">Товары</h1>
        <div class="adm-sub"><?= e((string) $total) ?> шт.<?php if ($f['q'] !== ''): ?> · поиск «<?= e($f['q']) ?>»<?php endif; ?></div>
    </div>
    <a class="adm-btn adm-btn--primary" href="<?= url('/admin/products/new') ?>">+ Добавить товар</a>
</div>

<?php if (isset($_GET['deleted'])): ?>
    <div class="adm-alert adm-alert--ok">Товар удалён.</div>
<?php endif; ?>

<form class="adm-filters" method="get" action="<?= url('/admin/products') ?>">
    <input type="search" name="q" class="adm-input" placeholder="Поиск: название, артикул…" value="<?= e($f['q']) ?>">
    <select name="category" class="adm-input adm-input--select">
        <option value="">Все категории</option>
        <?php foreach ($tree as $root): ?>
            <option value="<?= e($root['slug']) ?>" <?= ($currentCat['slug'] ?? '') === $root['slug'] ? 'selected' : '' ?>>
                <?= e($root['name']) ?>
            </option>
            <?php foreach ($root['children'] ?? [] as $child): ?>
                <option value="<?= e($child['slug']) ?>" <?= ($currentCat['slug'] ?? '') === $child['slug'] ? 'selected' : '' ?>>
                    &nbsp;&nbsp;└ <?= e($child['name']) ?>
                </option>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="adm-btn">Найти</button>
    <?php if ($f['q'] !== '' || $f['category'] !== ''): ?>
        <a class="adm-btn adm-btn--ghost" href="<?= url('/admin/products') ?>">Сбросить</a>
    <?php endif; ?>
</form>

<div class="adm-table-wrap">
    <table class="adm-table">
        <thead>
        <tr>
            <th class="adm-col--narrow">Фото</th>
            <th>Название</th>
            <th>Категория</th>
            <th>Артикул</th>
            <th>Цена</th>
            <th class="adm-col--actions">Действия</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td>
                    <img class="adm-thumb" src="<?= product_img_url($item) ?>" alt="" loading="lazy">
                </td>
                <td>
                    <a class="adm-item-title" href="<?= url('/admin/products/' . $item['id'] . '/edit') ?>"><?= e($item['title']) ?></a>
                    <?php if (!empty($item['is_hit'])): ?><span class="adm-badge">Хит</span><?php endif; ?>
                    <?php if (!empty($item['is_new'])): ?><span class="adm-badge adm-badge--new">Новинка</span><?php endif; ?>
                </td>
                <td class="adm-muted"><?= e((string) ($item['category_name'] ?? '')) ?></td>
                <td class="adm-muted"><?= e((string) $item['sku']) ?></td>
                <td><?= $item['price'] !== null ? money((int) $item['price']) : 'по запросу' ?></td>
                <td class="adm-col--actions">
                    <form method="get" action="<?= url('/admin/products/' . $item['id'] . '/gallery') ?>" class="adm-inline">
                        <button type="submit" class="adm-btn adm-btn--sm" title="Фотографии">Фото (<?= (int) ($item['images_count'] ?? 0) + ($item['image'] !== '' ? 1 : 0) ?>)</button>
                    </form>
                    <a class="adm-btn adm-btn--ghost adm-btn--sm" href="<?= url('/admin/products/' . $item['id'] . '/edit') ?>">Изменить</a>
                    <a class="adm-btn adm-btn--sm adm-btn--danger-link" href="<?= url('/admin/products/' . $item['id'] . '/edit') ?>">Удалить</a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$items): ?>
            <tr><td colspan="6" class="adm-empty">Ничего не найдено</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
    <div class="adm-pager">
        <?php $query = http_build_query(array_filter(['q' => $f['q'], 'category' => $f['category']])); ?>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a class="adm-pager__link <?= $i === $currentPage ? 'is-active' : '' ?>"
               href="<?= url('/admin/products?' . ($query !== '' ? $query . '&' : '') . 'page=' . $i) ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
<?php endif; ?>

<?php view('admin/partials/footer'); ?>