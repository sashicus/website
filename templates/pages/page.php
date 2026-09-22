<?php
/** @var array $syspage */
$pageMeta = [
    'title'       => ($syspage['meta_title'] ?? '') !== '' ? $syspage['meta_title'] : $syspage['title'],
    'description' => ($syspage['meta_description'] ?? '') !== '' ? $syspage['meta_description'] : 'Страница «' . $syspage['title'] . '» — HUAWEI Enterprise. ' . config('site.meta'),
    'canonical'   => url('/' . $syspage['slug']),
];
$crumbs = [['label' => 'Главная', 'url' => url('/')], ['label' => $syspage['title']]];
?>
<?php view('layout/header', ['page' => $pageMeta, 'categories' => $categories]); ?>
<?php view('partials/breadcrumbs', ['crumbs' => $crumbs]); ?>

<section class="section">
    <div class="container">
        <h1 class="page-head__title"><?= e($syspage['title']) ?></h1>
        <div class="prose"><?= $syspage['content'] ?></div>

        <?php if ($syspage['slug'] === 'contacts'): ?>
            <?php if (isset($_GET['sent'])): ?>
                <div class="form-success">Сообщение отправлено. Мы свяжемся с вами в рабочее время.</div>
            <?php endif; ?>
            <div class="contact-form-wrap">
                <form class="form" method="post" action="/contacts">
                    <h2>Напишите нам</h2>
                    <div class="form__grid">
                        <label class="form__field">
                            <span class="form__label">Ваше имя *</span>
                            <input type="text" name="name" class="input" required autocomplete="name">
                        </label>
                        <label class="form__field">
                            <span class="form__label">Телефон *</span>
                            <input type="tel" name="phone" class="input" required autocomplete="tel">
                        </label>
                    </div>
                    <label class="form__field">
                        <span class="form__label">Сообщение</span>
                        <textarea name="message" rows="5" class="textarea"></textarea>
                    </label>
                    <button type="submit" class="btn btn--accent">Отправить сообщение</button>
                    <p class="form__note">Нажимая «Отправить», вы соглашаетесь на обработку персональных данных.</p>
                </form>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php view('layout/footer', ['categories' => $categories]); ?>