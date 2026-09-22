<?php
$isEdit = $category !== null;
$adminTitle = $isEdit ? 'Редактирование: ' . $category['name'] : 'Новая категория';
$adminActive = 'categories';
view('admin/partials/header', ['adminTitle' => $adminTitle, 'adminActive' => $adminActive]);

$val = static function (string $key, string $default = '') use ($old, $category): string {
    if (!empty($old) && isset($old[$key])) {
        return (string) $old[$key];
    }
    if ($category !== null) {
        $v = $category[$key] ?? $default;
        if (is_array($v)) {
            return '';
        }
        return (string) $v;
    }
    return $default;
};

$err = static function (string $field) use ($errors): string {
    return isset($errors[$field]) ? '<div class="adm-error">' . e($errors[$field]) . '</div>' : '';
};
?>
<div class="adm-pagehead">
    <div>
        <h1 class="adm-h1"><?= $isEdit ? 'Редактирование категории' : 'Новая категория' ?></h1>
        <?php if ($isEdit): ?>
            <div class="adm-sub"><?= e('/' . $category['slug']) ?> · <a href="<?= category_url($category) ?>" target="_blank" rel="noopener">открыть на сайте ↗</a></div>
        <?php endif; ?>
    </div>
    <div class="adm-pagehead__actions">
        <?php if ($isEdit): ?>
            <form method="post" action="<?= url('/admin/categories/' . $category['id'] . '/delete') ?>"
                  onsubmit="return confirm('Удалить категорию «<?= e($category['name']) ?>»?')">
                <?= csrf_field() ?>
                <button type="submit" class="adm-btn adm-btn--danger">Удалить</button>
            </form>
        <?php endif; ?>
        <a class="adm-btn adm-btn--ghost" href="<?= url('/admin/categories') ?>">← К списку</a>
    </div>
</div>

<?php if (!empty($errors['db'])): ?>
    <div class="adm-alert adm-alert--error"><?= e($errors['db']) ?></div>
<?php endif; ?>
<?php if (isset($_GET['saved'])): ?>
    <div class="adm-alert adm-alert--ok">Сохранено.</div>
<?php endif; ?>

<form method="post" action="<?= url($isEdit ? '/admin/categories/' . $category['id'] . '/edit' : '/admin/categories/new') ?>" class="adm-form">
    <?= csrf_field() ?>

    <div class="adm-card">
        <h2 class="adm-h2">Основное</h2>

        <label class="adm-field">
            <span>Название *</span>
            <input type="text" name="name" class="adm-input" required maxlength="160" value="<?= e($val('name')) ?>">
            <?= $err('name') ?>
        </label>

        <label class="adm-field">
            <span>URL (slug)</span>
            <input type="text" name="slug" class="adm-input" maxlength="64" value="<?= e($val('slug')) ?>" placeholder="автоматически из названия">
            <?= $err('slug') ?>
        </label>

        <label class="adm-field">
            <span>Родительская категория</span>
            <select name="parent_id" class="adm-input adm-input--select">
                <option value="">— нет, корневая категория —</option>
                <?php $selectedParent = $isEdit ? (int) ($category['parent_id'] ?? 0) : (int) ($old['parent_id'] ?? 0); ?>
                <?php $renderOptions = static function (array $node, int $depth) use (&$renderOptions, $selectedParent, $category, $isEdit): void { ?>
                    <option value="<?= (int) $node['id'] ?>" <?= $selectedParent === (int) $node['id'] ? 'selected' : '' ?> <?= $isEdit && $category['id'] == $node['id'] ? 'disabled' : '' ?>>
                        <?= str_repeat('&nbsp;&nbsp;', $depth) . '└ ' . e($node['name']) ?>
                    </option>
                    <?php foreach ($node['children'] ?? [] as $child): ?>
                        <?php $renderOptions($child, $depth + 1); ?>
                    <?php endforeach; ?>
                <?php }; ?>
                <?php foreach ($tree as $node): ?>
                    <?php $renderOptions($node, 0); ?>
                <?php endforeach; ?>
            </select>
            <?= $err('parent_id') ?>
        </label>

        <label class="adm-field">
            <span>Порядок сортировки</span>
            <input type="number" name="sort_order" class="adm-input" min="0" step="1" value="<?= e($val('sort_order', '0')) ?>">
        </label>

        <div class="adm-checks">
            <label class="adm-check"><input type="checkbox" name="is_active" value="1" <?= ($isEdit ? (int) $category['is_active'] : (int) ($old['is_active'] ?? 1)) === 1 ? 'checked' : '' ?>> Активна (показывается на сайте)</label>
        </div>
    </div>

    <div class="adm-card">
        <h2 class="adm-h2">Описание</h2>

        <label class="adm-field">
            <span>Название категории</span>
            <input type="text" name="title" class="adm-input" maxlength="255" value="<?= e($val('title')) ?>" placeholder="заголовок h1 на странице; пусто — обычное название">
            <?= $err('title') ?>
        </label>

        <label class="adm-field">
            <span>Краткое описание</span>
            <input type="text" name="short_desc" class="adm-input" maxlength="255" value="<?= e($val('short_desc')) ?>">
            <?= $err('short_desc') ?>
        </label>

        <label class="adm-field">
            <span>Полное описание</span>
            <textarea name="description" rows="8" class="adm-textarea"><?= e($val('description')) ?></textarea>
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
        <button type="submit" class="adm-btn adm-btn--primary adm-btn--lg"><?= $isEdit ? 'Сохранить' : 'Создать категорию' ?></button>
        <a class="adm-btn adm-btn--ghost" href="<?= url('/admin/categories') ?>">Отмена</a>
    </div>
</form>

<?php view('admin/partials/footer'); ?>