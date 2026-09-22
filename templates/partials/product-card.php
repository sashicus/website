<?php
/** @var array $item Товар из модели Product::search()/bySlug(), + computed fields */
$img = product_img_url($item);
$labels = [];
if (!empty($item['is_hit'])) $labels[] = 'hit';
if (!empty($item['is_new'])) $labels[] = 'new';
?>
<article class="card" data-product data-sku="<?= e($item['sku'] ?? '') ?>">
    <!-- <?php if ($labels): ?>
        <div class="card__labels">
            <?php if (in_array('hit', $labels, true)): ?><span class="card__label card__label--hit">Хит</span><?php endif; ?>
            <?php if (in_array('new', $labels, true)): ?><span class="card__label card__label--new">Новинка</span><?php endif; ?>
        </div>
    <?php endif; ?> -->
    <a class="card__img" href="<?= product_url($item) ?>" aria-label="<?= e($item['title']) ?>">
        <img src="<?= e($img) ?>" alt="<?= e($item['title']) ?>" loading="lazy">
    </a>
    <div class="card__body">
        <?php if (!empty($item['group_label'])): ?>
            <div class="card__group"><?= e(mb_strimwidth($item['group_label'], 0, 44, '…')) ?></div>
        <?php endif; ?>
        <a class="card__title" href="<?= product_url($item) ?>"><?= e($item['title']) ?></a>
        <div class="card__sku">Артикул: <?= e($item['sku'] ?? '—') ?></div>
        <div class="card__specs">
            <?php foreach (array_slice($item['specs'] ?? [], 0, 3) as $k => $v): ?>
                <div><span><?= e($k) ?>:</span> <?= e(is_array($v) ? implode(', ', $v) : $v) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card__bottom">
        <div class="card__price" data-price>
            <?php if ($item['price'] !== null && $item['price'] !== ''): ?>
                <?= money((int) $item['price']) ?>
            <?php else: ?>
                <span class="price-request">по запросу</span>
            <?php endif; ?>
        </div>
        <button class="btn btn--accent btn--add"
                data-add-to-cart="<?= e($item['slug']) ?>"
                data-name="<?= e($item['title']) ?>"
                data-price="<?= (int) $item['price'] ?>">
            В корзину
        </button>
    </div>
    <div class="card__stock card__stock--in">
        <span class="stock-dot"></span>В наличии
    </div>
</article>