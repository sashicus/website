<?php
$isEdit = $pg !== null;
$adminTitle = $isEdit ? 'Редактирование: ' . $pg['title'] : 'Новая страница';
$adminActive = 'pages';
view('admin/partials/header', ['adminTitle' => $adminTitle, 'adminActive' => $adminActive]);

$val = static function (string $key, string $default = '') use ($old, $pg): string {
    if (!empty($old) && isset($old[$key])) {
        return (string) $old[$key];
    }
    if ($pg !== null) {
        $v = $pg[$key] ?? $default;
        return is_array($v) ? '' : (string) $v;
    }
    return $default;
};

$err = static function (string $field) use ($errors): string {
    return isset($errors[$field]) ? '<div class="adm-error">' . e($errors[$field]) . '</div>' : '';
};
?>
<div class="adm-pagehead">
    <div>
        <h1 class="adm-h1"><?= $isEdit ? 'Редактирование страницы' : 'Новая страница' ?></h1>
        <?php if ($isEdit): ?>
            <div class="adm-sub"><?= e('/' . $pg['slug']) ?> · <a href="<?= url('/' . $pg['slug']) ?>" target="_blank" rel="noopener">открыть на сайте ↗</a></div>
        <?php endif; ?>
    </div>
    <div class="adm-pagehead__actions">
        <?php if ($isEdit): ?>
            <form method="post" action="<?= url('/admin/pages/' . $pg['id'] . '/delete') ?>"
                  onsubmit="return confirm('Удалить страницу «<?= e($pg['title']) ?>»?')">
                <?= csrf_field() ?>
                <button type="submit" class="adm-btn adm-btn--danger">Удалить</button>
            </form>
        <?php endif; ?>
        <a class="adm-btn adm-btn--ghost" href="<?= url('/admin/pages') ?>">← К списку</a>
    </div>
</div>

<?php if (!empty($errors['db'])): ?>
    <div class="adm-alert adm-alert--error"><?= e($errors['db']) ?></div>
<?php endif; ?>
<?php if (isset($_GET['saved'])): ?>
    <div class="adm-alert adm-alert--ok">Сохранено.</div>
<?php endif; ?>

<form method="post" action="<?= url($isEdit ? '/admin/pages/' . $pg['id'] . '/edit' : '/admin/pages/new') ?>" class="adm-form">
    <?= csrf_field() ?>

    <div class="adm-card">
        <h2 class="adm-h2">Страница</h2>

        <label class="adm-field">
            <span>Название *</span>
            <input type="text" name="title" class="adm-input" required maxlength="160" value="<?= e($val('title')) ?>">
            <?= $err('title') ?>
        </label>

        <div class="adm-row">
            <label class="adm-field">
                <span>URL (slug)</span>
                <input type="text" name="slug" class="adm-input" maxlength="64" value="<?= e($val('slug')) ?>" placeholder="автоматически из названия">
                <?= $err('slug') ?>
            </label>
            <label class="adm-field">
                <span>Порядок сортировки</span>
                <input type="number" name="sort_order" class="adm-input" min="0" step="1" value="<?= e($val('sort_order', '0')) ?>">
            </label>
        </div>

        <div class="adm-checks">
            <label class="adm-check"><input type="checkbox" name="is_active" value="1" <?= ($isEdit ? (int) $pg['is_active'] : (int) ($old['is_active'] ?? 1)) === 1 ? 'checked' : '' ?>> Активна (показывается на сайте)</label>
        </div>
    </div>

    <div class="adm-card">
        <h2 class="adm-h2">Содержимое</h2>
        <label class="adm-field">
            <span>Текст страницы (HTML)</span>
            <textarea name="content" rows="14" class="adm-textarea adm-textarea--code" spellcheck="false"><?= e($val('content')) ?></textarea>
            <p class="adm-hint">Ограничения по ссылкам и заголовкам отсутствуют — просто HTML. Картинки можно вставить с адресом от
                <a href="<?= url('/assets/img') ?>" target="_blank" rel="noopener">/assets/img</a>.</p>
        </label>
    </div>

    <div class="adm-card">
        <h2 class="adm-h2">SEO</h2>
        <label class="adm-field">
            <span>Meta title</span>
            <input type="text" name="meta_title" class="adm-input" maxlength="200" value="<?= e($val('meta_title')) ?>" placeholder="заголовок для поисковиков (title)">
            <?= $err('meta_title') ?>
        </label>
        <label class="adm-field">
            <span>Meta description</span>
            <textarea name="meta_description" rows="3" class="adm-textarea" maxlength="300" placeholder="краткое описание для поисковиков"><?= e($val('meta_description')) ?></textarea>
            <?= $err('meta_description') ?>
        </label>
    </div>

    <div class="adm-actions">
        <button type="submit" class="adm-btn adm-btn--primary adm-btn--lg"><?= $isEdit ? 'Сохранить' : 'Создать страницу' ?></button>
        <a class="adm-btn adm-btn--ghost" href="<?= url('/admin/pages') ?>">Отмена</a>
    </div>
</form>

<?php view('admin/partials/footer'); ?>