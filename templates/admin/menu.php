<?php
$adminTitle = 'Меню';
$adminActive = 'menu';
view('admin/partials/header', ['adminTitle' => $adminTitle, 'adminActive' => $adminActive]);

$position = $position ?? 'top';
$positions = $positions ?? ['top' => 'Верхнее меню'];
$positionEmptyHints = [
    'top'            => 'Пунктов нет — на сайте показаны верхние категории',
    'footer-catalog' => 'Пунктов нет — в футере показаны корневые категории',
    'footer-info'    => 'Пунктов нет — в футере показаны ссылки по умолчанию',
];
?>
<div class="adm-pagehead">
    <div>
        <h1 class="adm-h1">Меню сайта</h1>
        <div class="adm-sub">Три меню: верхнее в шапке и два нижних в футере. Всё — ссылки на категории или произвольные страницы</div>
    </div>
    <a class="adm-btn adm-btn--primary" href="<?= url('/admin/menu/new', ['position' => $position]) ?>">+ Добавить пункт</a>
</div>

<div class="adm-tabs">
    <?php foreach ($positions as $posKey => $posLabel): ?>
        <a class="adm-tab <?= $posKey === $position ? 'is-active' : '' ?>" href="<?= url('/admin/menu', ['position' => $posKey]) ?>"><?= e($posLabel) ?></a>
    <?php endforeach; ?>
</div>

<?php if (isset($_GET['created'])): ?>
    <div class="adm-alert adm-alert--ok">Пункт меню добавлен.</div>
<?php elseif (isset($_GET['saved'])): ?>
    <div class="adm-alert adm-alert--ok">Изменения сохранены.</div>
<?php elseif (isset($_GET['deleted'])): ?>
    <div class="adm-alert adm-alert--ok">Пункт меню удалён.</div>
<?php endif; ?>

<div class="adm-table-wrap">
    <table class="adm-table">
        <thead>
        <tr>
            <th class="adm-col--sort">⠿</th>
            <th>Пункт</th>
            <th>Ссылка</th>
            <th>Тип</th>
            <th>Статус</th>
            <th class="adm-col--actions">Действия</th>
        </tr>
        </thead>
        <tbody data-sort-type="menu" data-base="<?= e(url('/')) ?>">
        <?php foreach ($items as $item): ?>
            <?php $isCategory = ($item['category_id'] ?? null) !== null; ?>
            <tr class="adm-sort-tr" data-id="<?= (int) $item['id'] ?>" data-depth="0" data-parent="">
                <td class="adm-col--sort">
                    <div class="adm-sort" draggable="true" data-sort-group
                         data-id="<?= (int) $item['id'] ?>"
                         data-parent=""
                         data-depth="0"
                         data-csrf="<?= e(csrf_token()) ?>"
                         title="Перетащите строку за этот маркер">
                        <span class="adm-sort__grip" aria-hidden="true">⠿</span>
                    </div>
                </td>
                <td>
                    <a class="adm-item-title" href="<?= url('/admin/menu/' . $item['id'] . '/edit') ?>"><?= e($isCategory ? $item['category_name'] : $item['label']) ?></a>
                    <?php if (!$item['is_active']): ?><span class="adm-badge adm-badge--off">Выкл</span><?php endif; ?>
                </td>
                <td class="adm-muted"><?= e($isCategory ? '/' . $item['category_slug'] : $item['url']) ?></td>
                <td class="adm-muted"><?= $isCategory ? 'Категория' : 'Ссылка' ?></td>
                <td>
                    <form method="post" action="<?= url('/admin/menu/' . $item['id'] . '/toggle') ?>" class="adm-inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="adm-btn adm-btn--sm <?= $item['is_active'] ? 'adm-btn--ghost' : 'adm-btn--primary' ?>">
                            <?= $item['is_active'] ? 'Выключить' : 'Включить' ?>
                        </button>
                    </form>
                </td>
                <td class="adm-col--actions">
                    <a class="adm-btn adm-btn--ghost adm-btn--sm" href="<?= url('/admin/menu/' . $item['id'] . '/edit') ?>">Изменить</a>
                    <form method="post" action="<?= url('/admin/menu/' . $item['id'] . '/delete') ?>" class="adm-inline"
                          onsubmit="return confirm('Удалить пункт «<?= e($isCategory ? $item['category_name'] : $item['label']) ?>»?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="adm-btn adm-btn--sm adm-btn--danger-link">Удалить</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$items): ?>
            <tr><td colspan="6" class="adm-empty"><?= e($positionEmptyHints[$position]) ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php view('admin/partials/footer'); ?>