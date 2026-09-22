<?php
$pageMeta = ['title' => 'Корзина'];
$crumbs = [['label' => 'Главная', 'url' => url('/')], ['label' => 'Корзина']];
?>
<?php view('layout/header', ['page' => $pageMeta, 'categories' => $categories]); ?>
<?php view('partials/breadcrumbs', ['crumbs' => $crumbs]); ?>

<section class="section">
    <div class="container">
        <h1 class="page-head__title">Корзина</h1>

        <div class="cart" id="cartRoot" data-empty-text="Пока пусто…">
            <div class="cart__empty">
                <div class="empty">
                    <h3>Ваша корзина пуста</h3>
                    <p>Загляните в разделы каталога — там много интересного (и нужного).</p>
                    <a class="btn btn--accent" href="<?= url('/') ?>">На главную</a>
                </div>
            </div>
        </div>
    </div>
</section>

<template id="cartLineTemplate">
    <div class="cart-line" data-cart-line>
        <div class="cart-line__info">
            <a class="cart-line__title" data-line-name href="#"></a>
            <div class="cart-line__sku" data-line-sku></div>
        </div>
        <div class="qty" data-qty>
            <button class="qty__btn" data-qty-minus aria-label="Уменьшить">−</button>
            <input class="qty__input" data-qty-input type="number" min="1" max="999" value="1">
            <button class="qty__btn" data-qty-plus aria-label="Увеличить">+</button>
        </div>
        <div class="cart-line__price" data-line-price></div>
        <button class="cart-line__remove" data-line-remove aria-label="Удалить">×</button>
    </div>
</template>

<?php view('layout/footer', ['categories' => $categories]); ?>