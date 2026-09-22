<?php
$isEdit = $item !== null;
$adminTitle = $isEdit ? 'Редактирование: ' . ($item['category_name'] !== '' && $item['category_id'] !== null ? $item['category_name'] : $item['label']) : 'Новый пункт меню';
$adminActive = 'menu';
view('admin/partials/header', ['adminTitle' => $adminTitle, 'adminActive' => $adminActive]);

$val = static function (string $key, string $default = '') use ($old, $item): string {
    if (!empty($old) && isset($old[$key])) {
        return (string) $old[$key];
    }
    if ($item !== null && ($item['category_id'] ?? null) === null) {
        $v = $item[$key] ?? $default;
        return is_array($v) ? '' : (string) $v;
    }
    return $default;
};

$err = static function (string $field) use ($errors): string {
    return isset($errors[$field]) ? '<div class="adm-error">' . e($errors[$field]) . '</div>' : '';
};

$selectedCategory = $isEdit ? (int) ($item['category_id'] ?? 0) : (int) ($old['category_id'] ?? 0);
$selectedPosition = $isEdit ? (string) ($item['position'] ?? 'top') : (string) ($old['position'] ?? $position ?? 'top');
?>
<div class="adm-pagehead">
    <div>
        <h1 class="adm-h1"><?= $isEdit ? 'Редактирование пункта меню' : 'Новый пункт меню' ?></h1>
        <div class="adm-sub">Ссылка на категорию или произвольная страница</div>
    </div>
    <div class="adm-pagehead__actions">
        <?php if ($isEdit && ($item['category_id'] ?? null) === null): ?>
            <form method="post" action="<?= url('/admin/menu/' . $item['id'] . '/delete') ?>"
                  onsubmit="return confirm('Удалить пункт «<?= e($item['label']) ?>»?')">
                <?= csrf_field() ?>
                <button type="submit" class="adm-btn adm-btn--danger">Удалить</button>
            </form>
        <?php endif; ?>
        <a class="adm-btn adm-btn--ghost" href="<?= url('/admin/menu', ['position' => $selectedPosition]) ?>">← К списку</a>
    </div>
</div>

<?php if (!empty($errors['db'])): ?>
    <div class="adm-alert adm-alert--error"><?= e($errors['db']) ?></div>
<?php endif; ?>
<?php if (isset($_GET['saved'])): ?>
    <div class="adm-alert adm-alert--ok">Сохранено.</div>
<?php endif; ?>

<form method="post" action="<?= url($isEdit ? '/admin/menu/' . $item['id'] . '/edit' : '/admin/menu/new') ?>" class="adm-form">
    <?= csrf_field() ?>

    <div class="adm-card">
        <h2 class="adm-h2">Пункт меню</h2>

        <label class="adm-field">
            <span>Меню (позиция)</span>
            <select name="position" class="adm-input adm-input--select">
                <?php $positions = $positions ?? ['top' => 'Верхнее меню']; ?>
                <?php foreach ($positions as $posKey => $posLabel): ?>
                    <option value="<?= e($posKey) ?>" <?= $selectedPosition === $posKey ? 'selected' : '' ?>><?= e($posLabel) ?></option>
                <?php endforeach; ?>
            </select>
            <?= $err('position') ?>
        </label>

        <label class="adm-field">
            <span>Категория каталога</span>
            <select name="category_id" id="menuCategory" class="adm-input adm-input--select">
                <option value="">— нет, своя ссылка —</option>
                <?php $renderOptions = static function (array $node, int $depth) use (&$renderOptions, $selectedCategory): void { ?>
                    <option value="<?= (int) $node['id'] ?>" <?= $selectedCategory === (int) $node['id'] ? 'selected' : '' ?>>
                        <?= str_repeat('&nbsp;&nbsp;', $depth) . '└ ' . e($node['name']) . (!$node['is_active'] ? ' (выкл)' : '') ?>
                    </option>
                    <?php foreach ($node['children'] ?? [] as $child): ?>
                        <?php $renderOptions($child, $depth + 1); ?>
                    <?php endforeach; ?>
                <?php }; ?>
                <?php foreach ($tree as $node): ?>
                    <?php $renderOptions($node, 0); ?>
                <?php endforeach; ?>
            </select>
            <?= $err('category_id') ?>
            <p class="adm-hint">Если выбрать категорию, название и ссылка подставятся автоматически.</p>
        </label>

        <div class="adm-row">
            <label class="adm-field">
                <span>Название пункта</span>
                <input type="text" name="label" id="menuLabel" class="adm-input" maxlength="120" value="<?= e($val('label')) ?>" placeholder="для своей ссылки">
                <?= $err('label') ?>
            </label>
            <label class="adm-field">
                <span>Ссылка</span>
                <input type="text" name="url" id="menuUrl" class="adm-input" maxlength="255" value="<?= e($val('url')) ?>" placeholder="/contacts, /about, https://…">
                <?= $err('url') ?>
            </label>
        </div>

        <label class="adm-field">
            <span>Порядок сортировки</span>
            <input type="number" name="sort_order" class="adm-input" min="0" step="1" value="<?= e($val('sort_order', '0')) ?>">
        </label>

        <div class="adm-checks">
            <label class="adm-check"><input type="checkbox" name="is_active" value="1" <?= ($isEdit ? (int) $item['is_active'] : (int) ($old['is_active'] ?? 1)) === 1 ? 'checked' : '' ?>> Активен (показывается в меню)</label>
        </div>
    </div>

    <div class="adm-actions">
        <button type="submit" class="adm-btn adm-btn--primary adm-btn--lg"><?= $isEdit ? 'Сохранить' : 'Добавить пункт' ?></button>
        <a class="adm-btn adm-btn--ghost" href="<?= url('/admin/menu') ?>">Отмена</a>
    </div>
</form>

<script>
    (function () {
        var select = document.getElementById('menuCategory');
        var label = document.getElementById('menuLabel');
        var url = document.getElementById('menuUrl');
        if (!select || !label || !url) return;
        var cats = <?= json_encode(array_reduce($tree, static function (array $carry, array $node): array {
            $carry[] = $node;
            foreach ($node['children'] ?? [] as $child) {
                $carry[] = $child;
            }
            return $carry;
        }, [])) ?>;
        select.addEventListener('change', function () {
            var id = parseInt(this.value, 10) || 0;
            var match = null;
            for (var i = 0; i < cats.length; i++) {
                if (cats[i].id === id) { match = cats[i]; break; }
            }
            if (match) {
                url.value = '/' + match.slug;
                if (label.value === '') label.value = match.name;
            } else {
                if (url.value === '' || url.value.indexOf('/') === 0) url.value = '';
            }
        });
    })();
</script>

<?php view('admin/partials/footer'); ?>