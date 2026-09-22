<?php
$adminTitle = 'Ошибка';
view('admin/partials/header', ['adminTitle' => $adminTitle]);
?>
<div class="adm-card adm-card--danger">
    <h1 class="adm-h1"><?= e($message ?? 'Ошибка') ?></h1>
    <p class="adm-hint"><a href="<?= url('/admin/products') ?>">← Вернуться к списку товаров</a></p>
</div>
<?php view('admin/partials/footer'); ?>