<?php
$adminTitle = 'Категории';
$adminActive = 'categories';
view('admin/partials/header', ['adminTitle' => $adminTitle, 'adminActive' => $adminActive]);
?>
<div class="adm-pagehead">
    <div>
        <h1 class="adm-h1">Категории</h1>
        <div class="adm-sub"><?= e((string) $total) ?> шт. · вложенные категории показываются с отступом</div>
    </div>
    <a class="adm-btn adm-btn--primary" href="<?= url('/admin/categories/new') ?>">+ Добавить категорию</a>
</div>

<?php if (isset($_GET['created'])): ?>
    <div class="adm-alert adm-alert--ok">Категория создана.</div>
<?php elseif (isset($_GET['saved'])): ?>
    <div class="adm-alert adm-alert--ok">Изменения сохранены.</div>
<?php elseif (isset($_GET['deleted'])): ?>
    <div class="adm-alert adm-alert--ok">Категория удалена.</div>
<?php elseif (isset($_GET['error'])): ?>
    <?php $catErr = $_GET['error'] === 'children' ? 'Нельзя удалить: есть вложенные категории.' : 'Нельзя удалить: внутри есть товары.'; ?>
    <div class="adm-alert adm-alert--error"><?= e($catErr) ?></div>
<?php endif; ?>

<div class="adm-table-wrap">
    <table class="adm-table">
        <thead>
        <tr>
            <th class="adm-col--sort">⠿</th>
            <th>Название</th>
            <th>URL</th>
            <th>Товаров</th>
            <th>Статус</th>
            <th class="adm-col--actions">Действия</th>
        </tr>
        </thead>
        <tbody data-sort-type="categories" data-base="<?= e(url('/')) ?>">
        <?php $renderRow = static function (array $cat, int $depth) use (&$renderRow): void { ?>
            <tr class="adm-sort-tr" data-id="<?= (int) $cat['id'] ?>" data-depth="<?= $depth ?>" data-parent="<?= $cat['parent_id'] === null ? '' : (int) $cat['parent_id'] ?>">
                <td class="adm-col--sort">
                    <div class="adm-sort" draggable="true" data-sort-group
                         data-id="<?= (int) $cat['id'] ?>"
                         data-parent="<?= $cat['parent_id'] === null ? '' : (int) $cat['parent_id'] ?>"
                         data-depth="<?= $depth ?>"
                         data-csrf="<?= e(csrf_token()) ?>"
                         title="Перетащите строку за этот маркер">
                        <span class="adm-sort__grip" aria-hidden="true">⠿</span>
                    </div>
                </td>
                <td>
                    <div class="adm-tree-name" style="padding-left: <?= $depth * 22 ?>px">
                        <a class="adm-item-title" href="<?= url('/admin/categories/' . $cat['id'] . '/edit') ?>"><?= e($cat['name']) ?></a>
                        <?php if (!$cat['is_active']): ?><span class="adm-badge adm-badge--off">Выкл</span><?php endif; ?>
                    </div>
                </td>
                <td class="adm-muted"><?= e('/' . $cat['slug']) ?></td>
                <td><?= (int) $cat['product_count'] ?></td>
                <td>
                    <form method="post" action="<?= url('/admin/categories/' . $cat['id'] . '/toggle') ?>" class="adm-inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="adm-btn adm-btn--sm <?= $cat['is_active'] ? 'adm-btn--ghost' : 'adm-btn--primary' ?>">
                            <?= $cat['is_active'] ? 'Выключить' : 'Включить' ?>
                        </button>
                    </form>
                </td>
                <td class="adm-col--actions">
                    <a class="adm-btn adm-btn--ghost adm-btn--sm" href="<?= url('/admin/categories/' . $cat['id'] . '/edit') ?>">Изменить</a>
                    <a class="adm-btn adm-btn--sm adm-btn--danger-link" href="<?= url('/admin/categories/' . $cat['id'] . '/edit') ?>">Удалить</a>
                </td>
            </tr>
            <?php if (!empty($cat['children'])): ?>
                <?php foreach ($cat['children'] as $child) $renderRow($child, $depth + 1); ?>
            <?php endif; ?>
        <?php }; ?>
        <?php foreach ($tree as $root) $renderRow($root, 0); ?>
        <?php if (!$tree): ?>
            <tr><td colspan="6" class="adm-empty">Категорий пока нет</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php view('admin/partials/footer'); ?>