<?php
$adminTitle = 'Фотографии: ' . $product['title'];
$adminActive = 'products';
view('admin/partials/header', ['adminTitle' => $adminTitle, 'adminActive' => $adminActive]);
?>
<div class="adm-pagehead">
    <div>
        <h1 class="adm-h1">Фотографии товара</h1>
        <div class="adm-sub">
            <?= e($product['title']) ?> · <?= e($product['sku']) ?> ·
            <a href="<?= product_url($product) ?>" target="_blank" rel="noopener">открыть на сайте ↗</a>
        </div>
    </div>
    <a class="adm-btn adm-btn--ghost" href="<?= url('/admin/products/' . $product['id'] . '/edit') ?>">← К редактированию</a>
</div>

<?php if (isset($_GET['error'])): ?>
    <div class="adm-alert adm-alert--error">Часть файлов не загружена: неверный формат или слишком большой размер (до 8 МБ, jpg/png/webp/gif).</div>
<?php endif; ?>

<form method="post" action="<?= url('/admin/products/' . $product['id'] . '/images') ?>" enctype="multipart/form-data" class="adm-card adm-upload">
    <?= csrf_field() ?>
    <h2 class="adm-h2">Загрузить фото</h2>
    <div class="adm-upload__row">
        <input type="file" name="images[]" multiple class="adm-input"
               accept="image/jpeg,image/png,image/webp,image/gif">
        <button type="submit" class="adm-btn adm-btn--primary">Загрузить</button>
    </div>
    <p class="adm-hint">Можно выбрать несколько файлов сразу. Первое фото, помеченное как главное, показывается в карточке товара.</p>
</form>

<h2 class="adm-h2">Галерея (<?= count($images) ?>)</h2>
<?php if (!$images): ?>
    <div class="adm-card">
        <p class="adm-empty">Фотографии ещё не добавлены. Загрузите первое фото выше.</p>
    </div>
<?php endif; ?>

<div class="adm-gallery">
    <?php foreach ($images as $img): ?>
        <?php $isMain = ($img['filename'] ?? '') === ($product['image'] ?? ''); ?>
        <div class="adm-gallery__item <?= $isMain ? 'is-main' : '' ?>">
            <img class="adm-gallery__img" src="<?= url('/uploads/products/' . $img['filename']) ?>" alt="">
            <?php if ($isMain): ?>
                <span class="adm-gallery__badge">Главное</span>
            <?php endif; ?>
            <div class="adm-gallery__actions">
                <?php if (!$isMain): ?>
                    <form method="post" action="<?= url('/admin/products/' . $product['id'] . '/images/' . $img['id'] . '/main') ?>" class="adm-inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="adm-btn adm-btn--sm">Сделать главной</button>
                    </form>
                <?php endif; ?>
                <form method="post" action="<?= url('/admin/products/' . $product['id'] . '/images/' . $img['id'] . '/delete') ?>" class="adm-inline"
                      onsubmit="return confirm('Удалить это фото?')">
                    <?= csrf_field() ?>
                    <button type="submit" class="adm-btn adm-btn--sm adm-btn--danger-link">Удалить</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php view('admin/partials/footer'); ?>