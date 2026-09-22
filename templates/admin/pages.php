<?php
$adminTitle = 'Страницы';
$adminActive = 'pages';
view('admin/partials/header', ['adminTitle' => $adminTitle, 'adminActive' => $adminActive]);
?>
<div class="adm-pagehead">
    <div>
        <h1 class="adm-h1">Страницы</h1>
        <div class="adm-sub"><?= count($items) ?> шт. · страницы «О компании» и «Контакты» создаются здесь же</div>
    </div>
    <a class="adm-btn adm-btn--primary" href="<?= url('/admin/pages/new') ?>">+ Добавить страницу</a>
</div>

<?php if (isset($_GET['created'])): ?>
    <div class="adm-alert adm-alert--ok">Страница создана.</div>
<?php elseif (isset($_GET['saved'])): ?>
    <div class="adm-alert adm-alert--ok">Изменения сохранены.</div>
<?php elseif (isset($_GET['deleted'])): ?>
    <div class="adm-alert adm-alert--ok">Страница удалена.</div>
<?php endif; ?>

<div class="adm-table-wrap">
    <table class="adm-table">
        <thead>
        <tr>
            <th>Название</th>
            <th>URL</th>
            <th>Порядок</th>
            <th>Статус</th>
            <th class="adm-col--actions">Действия</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $pageRow): ?>
            <tr>
                <td>
                    <a class="adm-item-title" href="<?= url('/admin/pages/' . $pageRow['id'] . '/edit') ?>"><?= e($pageRow['title']) ?></a>
                    <?php if (!empty($pageRow['meta_title'])): ?><span class="adm-badge adm-badge--new">SEO</span><?php endif; ?>
                    <?php if (!$pageRow['is_active']): ?><span class="adm-badge adm-badge--off">Выкл</span><?php endif; ?>
                </td>
                <td class="adm-muted">
                    <?= e('/' . $pageRow['slug']) ?> · <a href="<?= url('/' . $pageRow['slug']) ?>" target="_blank" rel="noopener">открыть ↗</a>
                </td>
                <td><?= (int) $pageRow['sort_order'] ?></td>
                <td>
                    <form method="post" action="<?= url('/admin/pages/' . $pageRow['id'] . '/toggle') ?>" class="adm-inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="adm-btn adm-btn--sm <?= $pageRow['is_active'] ? 'adm-btn--ghost' : 'adm-btn--primary' ?>">
                            <?= $pageRow['is_active'] ? 'Выключить' : 'Включить' ?>
                        </button>
                    </form>
                </td>
                <td class="adm-col--actions">
                    <a class="adm-btn adm-btn--ghost adm-btn--sm" href="<?= url('/admin/pages/' . $pageRow['id'] . '/edit') ?>">Изменить</a>
                    <a class="adm-btn adm-btn--sm adm-btn--danger-link" href="<?= url('/admin/pages/' . $pageRow['id'] . '/edit') ?>">Удалить</a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$items): ?>
            <tr><td colspan="5" class="adm-empty">Страниц пока нет</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php view('admin/partials/footer'); ?>