<?php
/** @var array $crumbs [['label', 'url'?]] */
$crumbs = $crumbs ?? [['label' => 'Главная', 'url' => url('/')]];
?>
<nav class="breadcrumbs" aria-label="Хлебные крошки">
    <div class="container">
        <ol class="breadcrumbs__list">
            <?php foreach ($crumbs as $i => $crumb): ?>
                <li>
                    <?php if (!empty($crumb['url']) && $i < count($crumbs) - 1): ?>
                        <a href="<?= e($crumb['url']) ?>"><?= e($crumb['label']) ?></a>
                    <?php else: ?>
                        <span aria-current="page"><?= e($crumb['label']) ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</nav>