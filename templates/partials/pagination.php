<?php
/** @var int $total, $page, $perPage */
$f = $f ?? ['q' => '', 'category' => ''];
$pages = (int) ceil($total / max(1, $perPage));
if ($pages <= 1) return;
$qsFilter = fn (array $over) => http_build_query(array_replace($f, $over));
$base = fn () => url(($f['category'] !== '' ? '/' . $f['category'] : '/search'));
$start = max(1, $page - 2);
$end = min($pages, $page + 2);
?>
<nav class="pagination" aria-label="Пагинация">
    <?php if ($page > 1): ?>
        <a class="pagination__arrow" href="<?= $base() . '?' . $qsFilter(['page' => $page - 1]) ?>" aria-label="Предыдущая">←</a>
    <?php endif; ?>
    <?php if ($start > 1): ?><a class="pagination__link" href="<?= $base() . '?' . $qsFilter(['page' => 1]) ?>">1</a><?php if ($start > 2): ?><span class="pagination__dots">…</span><?php endif; endif; ?>
    <?php for ($i = $start; $i <= $end; $i++): ?>
        <a class="pagination__link <?= $i === $page ? 'is-active' : '' ?>" href="<?= $base() . '?' . $qsFilter(['page' => $i]) ?>"><?= $i ?></a>
    <?php endfor; ?>
    <?php if ($end < $pages): ?><?php if ($end < $pages - 1): ?><span class="pagination__dots">…</span><?php endif; ?><a class="pagination__link" href="<?= $base() . '?' . $qsFilter(['page' => $pages]) ?>"><?= $pages ?></a><?php endif; ?>
    <?php if ($page < $pages): ?>
        <a class="pagination__arrow" href="<?= $base() . '?' . $qsFilter(['page' => $page + 1]) ?>" aria-label="Следующая">→</a>
    <?php endif; ?>
</nav>