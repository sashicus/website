<?php
$pageHead = $meta ?? ['title' => 'Каталог оборудования'];
$breadcrumbs = [['label' => 'Главная', 'url' => url('/')]];
foreach ($catChain ?? [] as $c) {
    $breadcrumbs[] = ['label' => $c['name'], 'url' => category_url($c)];
}

$h1 = $currentCat !== null
    ? ((string) ($currentCat['title'] ?? '') !== '' ? $currentCat['title'] : $currentCat['name'])
    : ($pageHead['title'] ?? 'Каталог оборудования');

$subcats = [];
if ($currentCat !== null) {
    $currentId = (int) $currentCat['id'];
    $parentId = (int) ($currentCat['parent_id'] ?? 0);
    foreach ($categories as $c) {
        $cid = (int) ($c['id'] ?? 0);
        if ($cid === $currentId || $cid === $parentId) {
            $subcats = $c['children'] ?? [];
            break;
        }
    }
}
?>
<?php view('layout/header', ['page' => $pageHead, 'categories' => $categories]); ?>
<?php view('partials/breadcrumbs', ['crumbs' => $breadcrumbs]); ?>

<section class="page-head">
    <div class="container">
        <h1 class="page-head__title"><?= e($h1) ?></h1>
        <?php if (!empty($currentCat['short_desc'])): ?>
            <p class="page-head__desc"><?= e($currentCat['short_desc']) ?></p>
        <?php endif; ?>
    </div>
</section>

<?php if ($subcats): ?>
<section class="section section--cats">
    <div class="container">
        <nav class="cat-chips" aria-label="Подкатегории">
            <?php foreach ($subcats as $c): ?>
                <?php if ((int) ($c['id'] ?? 0) === (int) $currentCat['id']) continue; ?>
                <a class="chip" href="<?= category_url($c) ?>"><?= e($c['name']) ?></a>
            <?php endforeach; ?>
        </nav>
    </div>
</section>
<?php endif; ?>

<section class="section">
    <div class="container">
        <?php if ($items): ?>
            <div class="cards-grid">
                <?php foreach ($items as $item): ?>
                    <?php view('partials/product-card', ['item' => $item]); ?>
                <?php endforeach; ?>
            </div>
            <?php view('partials/pagination', [
                'f' => $f, 'total' => $total, 'page' => $page, 'perPage' => $perPage,
            ]); ?>
        <?php else: ?>
            <div class="empty">
                <div class="empty__icon">?</div>
                <h3>Ничего не найдено</h3>
                <p>Попробуйте изменить запрос или сбросить фильтры.</p>
                <a class="btn btn--accent" href="<?= url($f['category'] !== '' ? '/' . $f['category'] : '/search') ?>">Сбросить фильтры</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if ($currentCat !== null && !empty($currentCat['description'])): ?>
<section class="section section--seo">
    <div class="container">
        <div class="catalog-seo"><?= e($currentCat['description']) ?></div>
    </div>
</section>
<?php endif; ?>

<?php view('layout/footer', ['categories' => $categories]); ?>