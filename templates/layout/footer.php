</main>

<footer class="footer">
    <div class="container">
        <div class="footer__grid">
            <div class="footer__col">
                <a class="logo logo--footer" href="<?= url('/') ?>">
                    <span class="logo__icon">H</span>
                    <span class="logo__text">
                        <span class="logo__name">HUAWEI <em>Enterprise</em></span>
                        <span class="logo__tagline">Оригинальное оборудование</span>
                    </span>
                </a>
                <p class="footer__about">Поставка серверного, сетевого оборудования и систем хранения данных Huawei с гарантией и настройкой под задачи вашей инфраструктуры.</p>
            </div>
            <div class="footer__col">
                <h4 class="footer__title">Каталог товаров</h4>
                <ul class="footer__list">
                    <?php foreach (site_menu('footer-catalog') as $menuItem): ?>
                        <li><a href="<?= e($menuItem['url']) ?>"><?= e($menuItem['label']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
	    <div class="footer__col">
                <h4 class="footer__title">Покупателям</h4>
                <ul class="footer__list">
                    <?php foreach (site_menu('footer-info') as $menuItem): ?>
                        <li><a href="<?= e($menuItem['url']) ?>"><?= e($menuItem['label']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="footer__col">
                <h4 class="footer__title">Контакты</h4>
                <ul class="footer__list footer__list--contacts">
                    <li><a href="tel:<?= e(config('site.phone_intern')) ?>"><?= e(config('site.phone')) ?></a></li>
                    <li><a href="mailto:<?= e(config('site.email')) ?>"><?= e(config('site.email')) ?></a></li>
                    <li><?= e(config('site.address')) ?></li>
                    <li><?= e(config('site.hours')) ?></li>
                </ul>
            </div>
        </div>
        <div class="footer__bottom">
            <span>© <?= date('Y') ?> <?= e(config('site.name')) ?>. Все права защищены.</span>
            <span>Не является публичной офертой. Цены уточняйте у менеджера.</span>
        </div>
    </div>
</footer>

<script>
    window.SHOP_CONFIG = {
        baseUrl: <?= json_encode(url('/'), JSON_UNESCAPED_SLASHES) ?>,
        cartUrl: <?= json_encode(url('/cart'), JSON_UNESCAPED_SLASHES) ?>,
        apiCart: <?= json_encode(url('/api/cart'), JSON_UNESCAPED_SLASHES) ?>,
        apiOrder: <?= json_encode(url('/api/order'), JSON_UNESCAPED_SLASHES) ?>,
        apiSearch: <?= json_encode(url('/api/search'), JSON_UNESCAPED_SLASHES) ?>
    };
</script>
<script src="<?= url('/assets/js/main.js') ?>"></script>
</body>
</html>