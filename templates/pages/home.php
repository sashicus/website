<?php
$page = $page ?? ['title' => 'Поставка оборудования Huawei для бизнеса'];
?>
<?php view('layout/header', ['page' => $page, 'categories' => $categories]); ?>

<section class="hero">
    <div class="container">
        <div class="hero__body">
            <div class="hero__inner">
                <p class="hero__kicker">Официальные поставки Huawei</p>
                <h1 class="hero__title">Оборудование Huawei для вашей инфраструктуры</h1>
                <p class="hero__subtitle">Системы хранения OceanStor, NVMe-диски, коммутаторы и сетевые модули. Оригинальная продукция с гарантией и доставкой по всей России.</p>
                <div class="hero__actions">
                    <a class="btn btn--accent btn--lg" href="<?= url('/contacts') ?>">Получить консультацию</a>
                </div>
            </div>
            <div class="hero__advs">
                <div class="adv">
                    <span class="adv__icon">✓</span>
                    <h3>Оригинальная продукция</h3>
                    <p>Поставляем только сертифицированное оборудование Huawei напрямую от авторизованных партнёров.</p>
                </div>
                <div class="adv">
                    <span class="adv__icon">✓</span>
                    <h3>Официальная гарантия</h3>
                    <p>Гарантийная поддержка на всё оборудование, помощь в RMA и подборе совместимости.</p>
                </div>
                <div class="adv">
                    <span class="adv__icon">✓</span>
                    <h3>Отправляем по всей РФ</h3>
                    <p>Отправляем в любой регион. Оперативный расчёт стоимости и сроков доставки.</p>
                </div>
                <div class="adv">
                    <span class="adv__icon">✓</span>
                    <h3>Товар в наличии на складе</h3>
                    <p>Поможем подобрать конфигурацию систем хранения: диски, полки расширения, интерфейсные карты.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php foreach ($sections as $i => $sec): ?>
<section class="section <?= $i % 2 === 0 ? 'section--alt' : '' ?>">
    <div class="container">
        <div class="section__head">
            <h2 class="section__title"><?= e($sec['title']) ?></h2>
            <a class="section__more" href="<?= e($sec['url']) ?>">Смотреть все (<?= (int) $sec['count'] ?>) →</a>
        </div>
        <div class="cards-grid">
            <?php foreach ($sec['items'] as $item): ?>
                <?php view('partials/product-card', ['item' => $item]); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endforeach; ?>

<?php view('layout/footer', ['categories' => $categories]); ?>