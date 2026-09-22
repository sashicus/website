<?php
$adminTitle = 'Импорт прайс-листа';
$adminActive = 'import';
view('admin/partials/header', ['adminTitle' => $adminTitle, 'adminActive' => $adminActive]);
$mode = $mode ?? 'upload';
?>
<div class="adm-pagehead">
    <div>
        <h1 class="adm-h1">Импорт прайс-листа</h1>
        <div class="adm-sub">Загрузка и обновление товаров из файла Excel (.xlsx) или CSV (.csv)</div>
    </div>
    <?php if ($mode === 'preview'): ?>
        <div class="adm-pagehead__actions">
            <a class="adm-btn adm-btn--ghost" href="<?= url('/admin/import') ?>">← Начать заново</a>
        </div>
    <?php endif; ?>
</div>

<?php if ($mode === 'upload'): ?>

    <?php
    $errMsg = [
        'upload'  => 'Ошибка при загрузке файла. Попробуйте ещё раз.',
        'ext'     => 'Неподдерживаемый формат. Загрузите файл .xlsx или .csv.',
        'size'    => 'Файл слишком большой или пустой. Максимум ' . e((string) ($maxSize ?? 20)) . ' МБ.',
        'parse'   => 'Не удалось прочитать файл: это не корректный XLSX/CSV или он повреждён.',
        'empty'   => 'В файле не нашлось ни одной строки с товарами.',
        'session' => 'Сессия импорта устарела. Загрузите файл заново.',
    ];
    $errCode = $_GET['error'] ?? '';
    if (isset($errMsg[$errCode])): ?>
        <div class="adm-alert adm-alert--error"><?= e($errMsg[$errCode]) ?></div>
    <?php endif; ?>

    <div class="adm-card">
        <h2 class="adm-h2">Шаг 1 — выберите файл</h2>
        <form method="post" action="<?= url('/admin/import') ?>" enctype="multipart/form-data" class="adm-form">
            <?= csrf_field() ?>
            <div class="adm-upload__row">
                <input type="file" name="pricelist" class="adm-input" accept=".xlsx,.csv"
                       required value="<?= e((string) ($original ?? '')) ?>">
                <button type="submit" class="adm-btn adm-btn--primary">Загрузить и продолжить →</button>
            </div>
            <p class="adm-hint">
                Поддерживаются форматы .xlsx и .csv, до <?= e((string) ($maxSize ?? 20)) ?> МБ.
                После загрузки откроется предпросмотр: можно сопоставить колонки, выбрать категории
                и курс валюты — импорт выполнится только после подтверждения.
            </p>
        </form>
    </div>

    <div class="adm-card">
        <h2 class="adm-h2">Как подготовить прайс</h2>
        <p class="adm-hint">Ожидаемые колонки (порядок не обязателен, колонки сопоставляются по названиям):</p>
        <table class="adm-table">
            <thead>
            <tr>
                <th>Колонка</th>
                <th>Что это</th>
                <th>Как используется</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td><strong>Артикул</strong> (SKU)</td>
                <td>Уникальный код товара</td>
                <td>Обязательная. По нему находится существующий товар для обновления.</td>
            </tr>
            <tr>
                <td>Бренд</td>
                <td>Производитель</td>
                <td>Подзаголовок, тег и запасная категория (если категория не определяется из названия).</td>
            </tr>
            <tr>
                <td>Наименование</td>
                <td>Полное название товара</td>
                <td>Название, описание (при создании) и категория — берётся первая фраза до запятой.</td>
            </tr>
            <tr>
                <td>Наличие</td>
                <td>Количество на складе</td>
                <td>Остаток и статус: 5+ «в наличии», 1–4 «мало», 0 «под заказ».</td>
            </tr>
            <tr>
                <td>Цена</td>
                <td>Стоимость (обычно USD)</td>
                <td>При выборе валюты USD пересчитывается в рубли по курсу.</td>
            </tr>
            </tbody>
        </table>
    </div>

<?php elseif ($mode === 'preview'): ?>

    <?php
    $state = $state ?? [];
    $headers = $headers ?? [];
    $opts = $opts ?? [];
    $preview = $preview ?? [];
    $total = (int) ($total ?? 0);
    $map = $opts['map'] ?? [];
    $noSku = ($map['sku'] ?? null) === null;
    $optSel = static function (int $value, ?int $selected): string {
        return $value === $selected ? 'selected' : '';
    };
    ?>

    <div class="adm-alert adm-alert--ok">
        Файл «<?= e((string) ($state['original'] ?? '')) ?>» загружен — <?= e((string) $total) ?> строк.
        Проверьте настройки ниже и нажмите «Выполнить импорт».
    </div>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'sku'): ?>
        <div class="adm-alert adm-alert--error">Укажите, какая колонка содержит артикул (SKU) — без неё импорт невозможен.</div>
    <?php endif; ?>

    <form method="post" action="<?= url('/admin/import/preview') ?>">
        <?= csrf_field() ?>

        <div class="adm-card">
            <h2 class="adm-h2">Сопоставление колонок</h2>
            <div class="adm-row">
                <label class="adm-field">
                    <span>Артикул (SKU) <em class="adm-muted">— обязательно</em></span>
                    <select name="map_sku" class="adm-input adm-input--select">
                        <option value="-1">— не выбрано —</option>
                        <?php foreach ($headers as $i => $h): ?>
                            <option value="<?= $i ?>" <?= $optSel($i, $map['sku'] ?? null) ?>><?= e($h) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="adm-field">
                    <span>Название товара</span>
                    <select name="map_name" class="adm-input adm-input--select">
                        <option value="-1">— не импортировать —</option>
                        <?php foreach ($headers as $i => $h): ?>
                            <option value="<?= $i ?>" <?= $optSel($i, $map['name'] ?? null) ?>><?= e($h) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="adm-field">
                    <span>Бренд</span>
                    <select name="map_brand" class="adm-input adm-input--select">
                        <option value="-1">— не импортировать —</option>
                        <?php foreach ($headers as $i => $h): ?>
                            <option value="<?= $i ?>" <?= $optSel($i, $map['brand'] ?? null) ?>><?= e($h) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="adm-field">
                    <span>Наличие (количество)</span>
                    <select name="map_qty" class="adm-input adm-input--select">
                        <option value="-1">— не импортировать —</option>
                        <?php foreach ($headers as $i => $h): ?>
                            <option value="<?= $i ?>" <?= $optSel($i, $map['qty'] ?? null) ?>><?= e($h) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="adm-field">
                    <span>Цена</span>
                    <select name="map_price" class="adm-input adm-input--select">
                        <option value="-1">— не импортировать —</option>
                        <?php foreach ($headers as $i => $h): ?>
                            <option value="<?= $i ?>" <?= $optSel($i, $map['price'] ?? null) ?>><?= e($h) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
        </div>

        <div class="adm-card">
            <h2 class="adm-h2">Цены и остатки</h2>
            <div class="adm-row">
                <label class="adm-field">
                    <span>Валюта цены в файле</span>
                    <select name="currency" class="adm-input adm-input--select">
                        <option value="rub" <?= ($opts['currency'] ?? 'rub') === 'rub' ? 'selected' : '' ?>>Рубли (₽)</option>
                        <option value="usd" <?= ($opts['currency'] ?? 'rub') === 'usd' ? 'selected' : '' ?>>Доллары (USD)</option>
                    </select>
                </label>
                <label class="adm-field">
                    <span>Курс USD → ₽</span>
                    <input type="number" name="rate" step="0.01" min="0.01" class="adm-input"
                           value="<?= e((string) round((float) ($opts['rate'] ?? 0), 2)) ?>">
                </label>
                <label class="adm-field">
                    <span>Название товара</span>
                    <select name="title_mode" class="adm-input adm-input--select">
                        <option value="full" <?= ($opts['title_mode'] ?? 'full') === 'full' ? 'selected' : '' ?>>Полное наименование</option>
                        <option value="after_comma" <?= ($opts['title_mode'] ?? 'full') === 'after_comma' ? 'selected' : '' ?>>После первой запятой (короче)</option>
                    </select>
                </label>
                <div class="adm-field adm-checks" style="grid-column:1">
                    <label class="adm-check">
                        <input type="checkbox" name="price_zero_null" <?= !empty($opts['price_zero_null']) ? 'checked' : '' ?>>
                        Цена 0 показывать как «по запросу»
                    </label>
                </div>
            </div>
        </div>

        <div class="adm-card">
            <h2 class="adm-h2">Категории</h2>
            <div class="adm-field">
                <label class="adm-check">
                    <input type="radio" name="category_mode" value="name" <?= ($opts['category_mode'] ?? 'name') === 'name' ? 'checked' : '' ?>>
                    Из названия товара (до запятой)
                </label>
                <p class="adm-hint" style="margin-left:25px">
                    «Память для игрового ПК, SILICON POWER…» → категория «Память для игрового ПК». Для каждого варианта создаётся (или находится) категория.
                </p>
                <label class="adm-check">
                    <input type="radio" name="category_mode" value="brand" <?= ($opts['category_mode'] ?? 'name') === 'brand' ? 'checked' : '' ?>>
                    Создавать категории по брендам
                </label>
                <p class="adm-hint" style="margin-left:25px">
                    Для каждого бренда из прайса создаётся (или находится) категория с именем бренда.
                </p>
                <label class="adm-field" style="margin-left:25px; max-width:420px">
                    <span>Родительская категория для создаваемых</span>
                    <select name="parent_id" class="adm-input adm-input--select">
                        <option value="0" <?= (int) ($opts['parent_id'] ?? 0) === 0 ? 'selected' : '' ?>>— Корень —</option>
                        <?php foreach ($categories as $root): ?>
                            <option value="<?= (int) $root['id'] ?>" <?= (int) ($opts['parent_id'] ?? 0) === (int) $root['id'] ? 'selected' : '' ?>>
                                <?= e($root['name']) ?>
                            </option>
                            <?php foreach ($root['children'] ?? [] as $child): ?>
                                <option value="<?= (int) $child['id'] ?>" <?= (int) ($opts['parent_id'] ?? 0) === (int) $child['id'] ? 'selected' : '' ?>>
                                    &nbsp;&nbsp;└ <?= e($child['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <div class="adm-field">
                <label class="adm-check">
                    <input type="radio" name="category_mode" value="single" <?= ($opts['category_mode'] ?? 'name') === 'single' ? 'checked' : '' ?>>
                    Все товары — в одну выбранную категорию
                </label>
                <label class="adm-field" style="margin-left:25px; max-width:420px">
                    <span>Категория</span>
                    <select name="category_id" class="adm-input adm-input--select">
                        <option value="0">— не выбрана —</option>
                        <?php foreach ($categories as $root): ?>
                            <option value="<?= (int) $root['id'] ?>" <?= (int) ($opts['category_id'] ?? 0) === (int) $root['id'] ? 'selected' : '' ?>>
                                <?= e($root['name']) ?>
                            </option>
                            <?php foreach ($root['children'] ?? [] as $child): ?>
                                <option value="<?= (int) $child['id'] ?>" <?= (int) ($opts['category_id'] ?? 0) === (int) $child['id'] ? 'selected' : '' ?>>
                                    &nbsp;&nbsp;└ <?= e($child['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
        </div>

        <div class="adm-card">
            <h2 class="adm-h2">Обновление существующих товаров</h2>
            <div class="adm-checks">
                <label class="adm-check">
                    <input type="checkbox" name="update_existing" <?= !empty($opts['update_existing']) ? 'checked' : '' ?>>
                    Обновлять товары с уже существующим артикулом
                </label>
                <label class="adm-check">
                    <input type="checkbox" name="set_old_price" <?= !empty($opts['set_old_price']) ? 'checked' : '' ?>>
                    Ставить старую цену при снижении (old_price)
                </label>
            </div>
            <p class="adm-hint">
                При обновлении сохраняются фото, SEO-поля и ручные описания. Название, цена, остаток, категория
                и бренд обновляются из прайса.
            </p>
        </div>

        <div class="adm-card">
            <h2 class="adm-h2">Предпросмотр (первые <?= count($preview) ?> из <?= e((string) $total) ?>)</h2>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                    <tr>
                        <th>Действие</th>
                        <th>Артикул</th>
                        <th>Бренд</th>
                        <th>Название</th>
                        <th>Категория</th>
                        <th>Остаток</th>
                        <th>Цена</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($preview as $rec): ?>
                        <tr>
                            <td>
                                <?php if ($rec['existed']): ?>
                                    <span class="adm-muted">обновление</span>
                                <?php else: ?>
                                    <span class="adm-badge">новый</span>
                                <?php endif; ?>
                            </td>
                            <td class="adm-muted"><?= e($rec['sku']) ?></td>
                            <td class="adm-muted"><?= e($rec['brand']) ?></td>
                            <td><?= e($rec['title']) ?></td>
                            <td class="adm-muted">
                                <?= e((string) ($rec['category_label']['label'] ?? '')) ?>
                                <?php if (!empty($rec['category_label']['created'])): ?>
                                    <span class="adm-muted">(создастся)</span>
                                <?php endif; ?>
                            </td>
                            <td class="adm-muted"><?= e((string) $rec['qty']) ?></td>
                            <td><?= $rec['price'] !== null ? money($rec['price']) : 'по запросу' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($noSku): ?>
            <div class="adm-alert adm-alert--error">
                Артикул не сопоставлен. Выберите колонку «Артикул (SKU)» выше и нажмите «Обновить предпросмотр».
            </div>
        <?php endif; ?>

        <div class="adm-actions">
            <button type="submit" name="submit" value="preview" class="adm-btn">Обновить предпросмотр</button>
            <button type="submit" name="submit" value="run" class="adm-btn adm-btn--primary adm-btn--lg"
                    <?= $noSku ? 'disabled' : '' ?>>
                Выполнить импорт (<?= e((string) $total) ?> строк)
            </button>
            <button type="submit" name="submit" value="cancel" formaction="<?= url('/admin/import/cancel') ?>"
                    formnovalidate class="adm-btn adm-btn--danger-link">Отменить</button>
        </div>
    </form>

<?php elseif ($mode === 'report'): ?>

    <?php
    $report = $report ?? null;
    $useReport = is_array($report) && (isset($report['inserted']) || isset($report['updated']) || isset($report['skipped']));
    if (!$report) { $report = ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []]; }
    ?>

    <div class="adm-alert <?= !empty($report['errors']) ? 'adm-alert--error' : 'adm-alert--ok' ?>">
        <strong>Импорт завершён:</strong>
        добавлено — <strong><?= (int) $report['inserted'] ?></strong>,
        обновлено — <strong><?= (int) $report['updated'] ?></strong>,
        пропущено/с ошибками — <strong><?= (int) $report['skipped'] ?></strong>.
    </div>

    <?php if ($useReport): ?>
        <div class="adm-card">
            <h2 class="adm-h2">Итого</h2>
            <p class="adm-hint">
                Новые товары попали в каталог и доступны на сайте. Найдите их в разделе «Товары».
            </p>
        </div>
    <?php endif; ?>

    <?php if (!empty($report['errors'])): ?>
        <div class="adm-card adm-card--danger">
            <h2 class="adm-h2">Замечания (<?= count($report['errors']) ?>)</h2>
            <ul style="margin:0; padding-left:18px; color:var(--accent);">
                <?php foreach ($report['errors'] as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="adm-actions">
        <a class="adm-btn adm-btn--primary" href="<?= url('/admin/import') ?>">Импортировать ещё один прайс</a>
        <a class="adm-btn adm-btn--ghost" href="<?= url('/admin/products') ?>">К списку товаров</a>
    </div>

<?php else: ?>

    <div class="adm-alert adm-alert--error">Неизвестный режим страницы.</div>

<?php endif; ?>

<?php view('admin/partials/footer'); ?>