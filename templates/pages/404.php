<?php
$pageMeta = ['title' => 'Страница не найдена'];
?>
<?php view('layout/header', ['page' => $pageMeta, 'categories' => $categories]); ?>

<section class="section">
    <div class="container">
        <div class="empty">
            <div class="empty__icon">404</div>
            <h1>Страница не найдена</h1>
            <p>Возможно, ссылка устарела или страница была перемещена.</p>
            <div class="empty__actions">
                <a class="btn btn--accent" href="<?= url('/') ?>">На главную</a>
                <a class="btn btn--ghost" href="<?= url('/') ?>">На главную</a>
            </div>
        </div>
    </div>
</section>

<?php view('layout/footer', ['categories' => $categories]); ?>