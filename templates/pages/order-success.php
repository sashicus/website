<?php
$orderId = (int) ($_GET['id'] ?? 0);
$pageMeta = ['title' => 'Заказ оформлен'];
$crumbs = [['label' => 'Главная', 'url' => url('/')], ['label' => 'Заказ оформлен']];
?>
<?php view('layout/header', ['page' => $pageMeta, 'categories' => $categories]); ?>
<?php view('partials/breadcrumbs', ['crumbs' => $crumbs]); ?>

<section class="section">
    <div class="container">
        <div class="thanks">
            <div class="thanks__icon">✓</div>
            <h1 class="thanks__title">Заказ оформлен!</h1>
            <?php if ($orderId > 0): ?>
                <p class="thanks__id">Номер вашего заказа: <strong>№<?= $orderId ?></strong></p>
            <?php endif; ?>
            <p>Менеджер свяжется с вами в ближайшее время для подтверждения заказа, уточнения сроков доставки и способа оплаты.</p>
            <div class="thanks__actions">
                <a class="btn btn--accent" href="<?= url('/') ?>">Продолжить покупки</a>
            </div>
        </div>
    </div>
</section>

<?php view('layout/footer', ['categories' => $categories]); ?>