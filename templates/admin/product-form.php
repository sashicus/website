<?php
$adminTitle = $product === null ? 'Новый товар' : 'Редактирование: ' . $product['title'];
$adminActive = 'products';
view('admin/partials/header', ['adminTitle' => $adminTitle, 'adminActive' => $adminActive]);

$isEdit = $product !== null;

$val = static function (string $key, string $default = '') use ($old, $product): string {
    if (!empty($old)) {
        return (string) ($old[$key] ?? $default);
    }
    if ($product !== null) {
        $v = $product[$key] ?? $default;
        if (is_array($v)) {
            return '';
        }
        return (string) $v;
    }
    return $default;
};

$jsonText = static function (string $phpKey, string $rawKey) use ($old, $product): string {
    if (!empty($old)) {
        $raw = $old[$rawKey] ?? null;
        if ($raw !== null) {
            return $raw;
        }
    }
    if ($product !== null) {
        $arr = $product[$phpKey] ?? [];
        return $arr ? (string) json_encode($arr, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
    }
    return '';
};

$tagsText = static function () use ($old, $product): string {
    if (!empty($old)) {
        return (string) ($old['tags'] ?? '');
    }
    if ($product !== null) {
        return implode(', ', $product['tags'] ?? []);
    }
    return '';
};

$err = static function (string $field) use ($errors): string {
    return isset($errors[$field]) ? '<div class="adm-error">' . e($errors[$field]) . '</div>' : '';
};
?>
<div class="adm-pagehead">
    <div>
        <h1 class="adm-h1"><?= $isEdit ? 'Редактирование товара' : 'Новый товар' ?></h1>
        <?php if ($isEdit): ?>
            <div class="adm-sub"><?= e($product['sku']) ?> · <a href="<?= product_url($product) ?>" target="_blank" rel="noopener">открыть на сайте ↗</a></div>
        <?php endif; ?>
    </div>
    <div class="adm-pagehead__actions">
        <?php if ($isEdit): ?>
            <form method="post" action="<?= url('/admin/products/' . $product['id'] . '/delete') ?>"
                  onsubmit="return confirm('Удалить товар «<?= e($product['title']) ?>»?')">
                <?= csrf_field() ?>
                <button type="submit" class="adm-btn adm-btn--danger">Удалить</button>
            </form>
        <?php endif; ?>
        <a class="adm-btn adm-btn--ghost" href="<?= url('/admin/products') ?>">← К списку</a>
    </div>
</div>

<?php if (!empty($errors['db'])): ?>
    <div class="adm-alert adm-alert--error"><?= e($errors['db']) ?></div>
<?php endif; ?>
<?php if (isset($_GET['saved'])): ?>
    <div class="adm-alert adm-alert--ok">Сохранено.</div>
<?php elseif (isset($_GET['created'])): ?>
    <div class="adm-alert adm-alert--ok">Товар создан. Добавьте фотографии.</div>
<?php endif; ?>

<form method="post" action="<?= url($isEdit ? '/admin/products/' . $product['id'] . '/edit' : '/admin/products/new') ?>" enctype="multipart/form-data" class="adm-form">
    <?= csrf_field() ?>

    <div class="adm-grid">
        <div class="adm-card">
            <h2 class="adm-h2">Основное</h2>

            <label class="adm-field">
                <span>Название *</span>
                <input type="text" name="title" class="adm-input" required maxlength="255" value="<?= e($val('title')) ?>">
                <?= $err('title') ?>
            </label>

            <label class="adm-field">
                <span>Подзаголовок</span>
                <input type="text" name="subtitle" class="adm-input" maxlength="255" value="<?= e($val('subtitle')) ?>">
            </label>

            <div class="adm-row">
                <label class="adm-field">
                    <span>Артикул (SKU) *</span>
                    <input type="text" name="sku" class="adm-input" required maxlength="160" value="<?= e($val('sku')) ?>">
                    <?= $err('sku') ?>
                </label>
                <label class="adm-field">
                    <span>URL (slug)</span>
                    <input type="text" name="slug" class="adm-input" maxlength="200" value="<?= e($val('slug')) ?>" placeholder="автоматически из названия">
                </label>
            </div>

            <label class="adm-field">
                <span>Категория *</span>
                <select name="category_id" class="adm-input adm-input--select" required>
                    <option value="">— выберите категорию —</option>
                    <?php $selectedCat = $isEdit ? (int) $product['category_id'] : (int) ($old['category_id'] ?? 0); ?>
                    <?php foreach ($tree as $root): ?>
                        <option value="<?= (int) $root['id'] ?>" <?= $selectedCat === (int) $root['id'] ? 'selected' : '' ?>>
                            <?= e($root['name']) ?>
                        </option>
                        <?php foreach ($root['children'] ?? [] as $child): ?>
                            <option value="<?= (int) $child['id'] ?>" <?= $selectedCat === (int) $child['id'] ? 'selected' : '' ?>>
                                &nbsp;&nbsp;└ <?= e($child['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </select>
                <?= $err('category_id') ?>
            </label>

            <label class="adm-field">
                <span>Группа (label)</span>
                <input type="text" name="group_label" class="adm-input" maxlength="255" value="<?= e($val('group_label')) ?>">
            </label>

            <label class="adm-field">
                <span>Бейдж (badge)</span>
                <input type="text" name="badge" class="adm-input" maxlength="64" value="<?= e($val('badge')) ?>">
            </label>

            <div class="adm-checks">
                <label class="adm-check"><input type="checkbox" name="is_hit" value="1" <?= $isEdit && !empty($product['is_hit']) ? 'checked' : (($old['is_hit'] ?? 0) ? 'checked' : '') ?>> Хит</label>
                <label class="adm-check"><input type="checkbox" name="is_new" value="1" <?= $isEdit && !empty($product['is_new']) ? 'checked' : (($old['is_new'] ?? 0) ? 'checked' : '') ?>> Новинка</label>
            </div>
        </div>

        <div class="adm-card">
            <h2 class="adm-h2">Цена и наличие</h2>

            <div class="adm-row">
                <label class="adm-field">
                    <span>Цена, ₽</span>
                    <input type="number" name="price" class="adm-input" min="0" step="1" value="<?= $isEdit && $product['price'] !== null ? (int) $product['price'] : e($val('price')) ?>">
                </label>
                <label class="adm-field">
                    <span>Старая цена, ₽</span>
                    <input type="number" name="old_price" class="adm-input" min="0" step="1" value="<?= $isEdit && $product['old_price'] !== null ? (int) $product['old_price'] : e($val('old_price')) ?>">
                </label>
            </div>

            <label class="adm-field">
                <span>Наличие</span>
                <select name="stock_state" class="adm-input adm-input--select">
                    <?php $stockState = $isEdit ? $product['stock_state'] : ($old['stock_state'] ?? 'in'); ?>
                    <?php foreach (['in' => 'В наличии', 'low' => 'Мало на складе', 'order' => 'Под заказ', 'out' => 'Нет в наличии'] as $sVal => $sLabel): ?>
                        <option value="<?= e($sVal) ?>" <?= $stockState === $sVal ? 'selected' : '' ?>><?= e($sLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="adm-field">
                <span>Количество на складе (не показывается на сайте)</span>
                <input type="number" name="stock" class="adm-input" min="0" step="1" value="<?= e($val('stock', '0')) ?>">
            </label>
        </div>
    </div>

    <div class="adm-card">
        <h2 class="adm-h2">Фотография (главная)</h2>
        <div class="adm-imgbox">
            <?php if ($isEdit && !empty($product['image'])): ?>
                <img class="adm-imgbox__prev" src="<?= product_img_url($product) ?>" alt="">
            <?php else: ?>
                <div class="adm-imgbox__empty">Фото нет</div>
            <?php endif; ?>
            <div class="adm-imgbox__right">
                <label class="adm-field">
                    <span>Загрузить новое фото (jpg, png, webp, gif, до 8 МБ)</span>
                    <input type="file" name="main_image" class="adm-input" accept="image/jpeg,image/png,image/webp,image/gif">
                </label>
                <?php if ($isEdit): ?>
                    <p class="adm-hint">Дополнительные фото и галерея — на странице
                        <a href="<?= url('/admin/products/' . $product['id'] . '/gallery') ?>">галереи товара</a>.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="adm-card">
        <h2 class="adm-h2">Описание</h2>
        <label class="adm-field">
            <span>Текст описания</span>
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

    <div class="adm-grid">
        <div class="adm-card">
            <h2 class="adm-h2">Характеристики (JSON)</h2>
            <label class="adm-field">
                <span>Объект «ключ: значение»</span>
                <textarea name="specs" rows="12" class="adm-textarea adm-textarea--code" placeholder='{"Емкость": "30 ТБ", "Интерфейс": "12G SAS"}'
                          spellcheck="false"><?= e($jsonText('specs', 'specs_json')) ?></textarea>
            </label>
        </div>
        <div class="adm-card">
            <h2 class="adm-h2">Комплектации (JSON)</h2>
            <label class="adm-field">
                <span>Массив конфигураций</span>
                <textarea name="configs" rows="12" class="adm-textarea adm-textarea--code"
                          spellcheck="false"><?= e($jsonText('configs', 'configs_json')) ?></textarea>
            </label>
        </div>
    </div>

    <div class="adm-card">
        <h2 class="adm-h2">Теги</h2>
        <label class="adm-field">
            <span>Через запятую</span>
            <input type="text" name="tags" class="adm-input" value="<?= e($tagsText()) ?>" placeholder="OceanStor, Dorado, NVMe…">
        </label>
    </div>

    <div class="adm-actions">
        <button type="submit" class="adm-btn adm-btn--primary adm-btn--lg"><?= $isEdit ? 'Сохранить' : 'Создать товар' ?></button>
        <a class="adm-btn adm-btn--ghost" href="<?= url('/admin/products') ?>">Отмена</a>
    </div>
</form>

<?php view('admin/partials/footer'); ?>