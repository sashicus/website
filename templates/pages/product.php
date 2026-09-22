<?php
$p = $product;
$crumbs = [['label' => 'Главная', 'url' => url('/')]];
foreach ($catChain ?? [] as $c) {
    $crumbs[] = ['label' => $c['name'], 'url' => category_url($c)];
}
$crumbs[] = ['label' => $p['title']];
$catSlug = '';
foreach ($catChain ?? [] as $c) {
    $catSlug = $c['slug'];
}
$specs = $p['specs'] ?? [];
$configs = $p['configs'] ?? [];
$tags = $p['tags'] ?? [];
$gallery = $gallery ?? [];
$mainFile = (string) ($p['image'] ?? '');
$mainSrc = $mainFile !== ''
    ? url('/uploads/products/' . $mainFile)
    : ($gallery ? url('/uploads/products/' . $gallery[0]['filename']) : url('/assets/img/no-photo.svg'));
$thumbs = [];
if ($mainFile !== '') {
    $thumbs[] = $mainSrc;
}
foreach ($gallery as $_g) {
    $_u = url('/uploads/products/' . $_g['filename']);
    if (!in_array($_u, $thumbs, true)) {
        $thumbs[] = $_u;
    }
}
$pageMeta = [
    'title'       => ($p['meta_title'] ?? '') !== '' ? $p['meta_title'] : $p['title'],
    'description' => ($p['meta_description'] ?? '') !== '' ? $p['meta_description'] : ($p['title'] . '. ' . ($p['subtitle'] ?? '') . ' Артикул ' . ($p['sku'] ?? '') . '.'),
];
?>
<?php view('layout/header', ['page' => $pageMeta, 'categories' => $categories]); ?>
<?php view('partials/breadcrumbs', ['crumbs' => $crumbs]); ?>

<section class="section">
    <div class="container">
        <div class="product">
            <div class="product__gallery">
                <div class="product__img">
                    <?php if (!empty($p['is_hit'])): ?><span class="card__label card__label--hit">Хит</span><?php endif; ?>
                    <?php if (!empty($p['is_new'])): ?><span class="card__label card__label--new">Новинка</span><?php endif; ?>
                    <img id="productMainImg" src="<?= e($mainSrc) ?>" alt="<?= e($p['title']) ?>">
                </div>
                <?php if (count($thumbs) > 1): ?>
                    <div class="product__thumbs" id="productThumbs">
                        <?php foreach ($thumbs as $i => $thumb): ?>
                            <button type="button" class="product__thumb <?= $i === 0 ? 'is-active' : '' ?>"
                                    data-src="<?= e($thumb) ?>" aria-label="Фото <?= $i + 1 ?>">
                                <img src="<?= e($thumb) ?>" alt="" loading="lazy">
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="product__info">
                <h1 class="product__title"><?= e($p['title']) ?></h1>
                <div class="product__subtitle"><?= e($p['subtitle'] ?? '') ?></div>

                <div class="product__row">
                    <span class="product__sku">Артикул: <strong><?= e($p['sku'] ?? '—') ?></strong></span>
                    <?php if (!empty($p['group_label'])): ?>
                        <span class="product__group"><?= e($p['group_label']) ?></span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($tags)): ?>
                    <div class="product__tags">
                        <?php foreach ($tags as $t): ?>
                            <span class="tag"><?= e((string) $t) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="product__price-block">
                    <?php if ($p['price'] !== null && $p['price'] !== ''): ?>
                        <div class="product__price"><?= money((int) $p['price']) ?></div>
                        <?php if (!empty($p['old_price'])): ?>
                            <div class="product__old-price"><?= money((int) $p['old_price']) ?></div>
                        <?php endif; ?>
                        <div class="product__vat">Цена указана с НДС</div>
                    <?php else: ?>
                        <div class="product__price price-request">Цена по запросу</div>
                        <div class="product__vat">Оставьте заявку — менеджер рассчитает стоимость</div>
                    <?php endif; ?>
                </div>

                <div class="product__stock stock--in">
                    <span class="stock-dot"></span>В наличии
                </div>

                <div class="product__buy">
                    <div class="qty" data-qty>
                        <button type="button" class="qty__btn" data-qty-minus aria-label="Уменьшить">−</button>
                        <input type="number" class="qty__input" data-qty-input value="1" min="1" max="999" aria-label="Количество">
                        <button type="button" class="qty__btn" data-qty-plus aria-label="Увеличить">+</button>
                    </div>
                    <button class="btn btn--accent btn--lg btn--buy"
                            data-add-to-cart="<?= e($p['slug']) ?>"
                            data-name="<?= e($p['title']) ?>"
                            data-price="<?= (int) $p['price'] ?>">
                        В корзину
                    </button>
                </div>

                <div class="product__benefits">
                    <div>✓ Оригинальная продукция</div>
                    <div>✓ Официальная гарантия</div>
                    <div>✓ Доставка по России</div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if (!empty($configs)): ?>
<section class="section">
    <div class="container">
        <h2 class="section__title">Комплектации</h2>
        <div class="configs-wrap">
            <table class="table table--configs">
                <thead>
                <tr>
                    <th class="cell--part">Компонент</th>
                    <th>Описание</th>
                    <th class="cell--qty">Кол-во</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($configs as $_cfg): ?>
                    <?php
                    $_cfgName = is_array($_cfg) && isset($_cfg['name']) ? (string) $_cfg['name'] : '';
                    $_cfgPrice = is_array($_cfg) ? ($_cfg['price'] ?? null) : null;
                    $_components = is_array($_cfg) && isset($_cfg['components']) && is_array($_cfg['components']) ? $_cfg['components'] : [];
                    ?>
                    <tr class="configs-group">
                        <td colspan="3">
                            <?= e($_cfgName) ?>
                            <?php if ($_cfgPrice !== null && $_cfgPrice !== ''): ?>
                                <span class="configs-group__price"><?= money((int) $_cfgPrice) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php foreach ($_components as $_row): ?>
                        <tr>
                            <td class="cell--part">
                                <span class="config-row__part"><?= e((string) ($_row['part'] ?? '')) ?></span>
                            </td>
                            <td><?= e((string) ($_row['desc'] ?? '')) ?></td>
                            <td class="cell--qty">
                                <?php $qty = (string) ($_row['qty'] ?? ''); ?>
                                <?= $qty !== '' ? e($qty) : '—' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($p['description'])): ?>
<section class="section section--alt">
    <div class="container">
        <h2 class="section__title">Описание</h2>
        <div class="prose"><?= nl2br(e($p['description'])) ?></div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($specs)): ?>
<section class="section">
    <div class="container">
        <h2 class="section__title">Характеристики</h2>
        <table class="table table--specs">
            <tbody>
            <?php foreach ($specs as $k => $v): ?>
                <tr>
                    <th><?= e((string) $k) ?></th>
                    <td><?= e(is_array($v) ? implode(', ', $v) : (string) $v) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($related)): ?>
<section class="section section--alt">
    <div class="container">
        <div class="section__head">
            <h2 class="section__title">Похожие товары</h2>
            <a class="section__more" href="<?= url($catSlug !== '' ? '/' . $catSlug : '/search') ?>">Смотреть все →</a>
        </div>
        <div class="cards-grid">
            <?php foreach ($related as $item): ?>
                <?php view('partials/product-card', ['item' => $item]); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php view('layout/footer', ['categories' => $categories]); ?>