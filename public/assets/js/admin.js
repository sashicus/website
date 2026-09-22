(function () {
    'use strict';

    var dragRow = null;
    var dragTbody = null;
    var busy = false;

    function idOf(row) {
        return row.getAttribute('data-id') || '';
    }

    function nearestRow(element) {
        return element && element.closest ? element.closest('.adm-sort-tr') : null;
    }

    function computePlace(row, event) {
        var rect = row.getBoundingClientRect();
        var tbody = row.closest('tbody');
        if (tbody && tbody.getAttribute('data-sort-type') === 'categories' && event.clientX - rect.left <= 30) {
            return 'inside';
        }
        var mid = rect.top + rect.height / 2;
        return event.clientY < mid ? 'before' : 'after';
    }

    function clearIndicators() {
        var rows = document.querySelectorAll('.adm-sort-tr.drop-before, .adm-sort-tr.drop-after, .adm-sort-tr.drop-inside');
        Array.prototype.forEach.call(rows, function (row) {
            row.classList.remove('drop-before', 'drop-after', 'drop-inside');
        });
    }

    function onDragStart(event) {
        if (busy) {
            event.preventDefault();
            return;
        }
        var box = event.target.closest ? event.target.closest('[data-sort-group]') : null;
        if (!box) {
            return;
        }
        var row = box.closest('tr');
        if (!row) {
            return;
        }
        dragRow = row;
        dragTbody = row.parentNode;
        event.dataTransfer.setData('text/plain', box.getAttribute('data-id'));
        event.dataTransfer.effectAllowed = 'move';
        row.classList.add('is-dragging');
    }

    function onDragOver(event) {
        var row = nearestRow(event.target);
        if (!dragRow || !row) {
            return;
        }
        event.preventDefault();

        var targetId = idOf(row);
        if (!targetId || targetId === idOf(dragRow)) {
            clearIndicators();
            return;
        }

        var place = computePlace(row, event);
        event.dataTransfer.dropEffect = 'move';
        clearIndicators();
        row.classList.add('drop-' + place);
    }

    function onDrop(event) {
        if (!dragRow) {
            return;
        }
        event.preventDefault();

        var targetRow = nearestRow(event.target);
        var targetId = targetRow ? idOf(targetRow) : '';
        if (!targetRow || !targetId || targetId === idOf(dragRow)) {
            finish();
            return;
        }

        var tbody = dragTbody;
        var box = dragRow.querySelector('[data-sort-group]');
        var type = tbody.getAttribute('data-sort-type') || '';
        if (!type) {
            finish();
            return;
        }

        var base = (tbody.getAttribute('data-base') || '/').replace(/\/+$/, '');
        var place = computePlace(targetRow, event);

        var params = new URLSearchParams();
        params.set('id', box.getAttribute('data-id'));
        params.set('target', targetId);
        params.set('place', place);
        params.set('_csrf', box.getAttribute('data-csrf'));

        finish();
        busy = true;

        fetch(base + '/admin/' + type + '/sort', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: params.toString(),
        }).then(function (response) {
            if (!response.ok) {
                location.reload();
                return;
            }
            refreshTable(tbody);
        }).catch(function () {
            location.reload();
        });
    }

    function refreshTable(tbody) {
        var url = new URL(window.location.href);
        ['created', 'saved', 'deleted', 'error'].forEach(function (key) {
            url.searchParams.delete(key);
        });

        fetch(url.toString(), { credentials: 'same-origin' })
            .then(function (response) {
                return response.text();
            })
            .then(function (html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var fresh = doc.querySelector('.adm-table tbody[data-sort-type]');
                if (!fresh) {
                    location.reload();
                    return;
                }
                var holder = tbody.parentNode;
                holder.replaceChild(fresh, tbody);
                busy = false;
                clearIndicators();
            })
            .catch(function () {
                location.reload();
            });
    }

    function finish() {
        clearIndicators();
        if (dragRow) {
            dragRow.classList.remove('is-dragging');
        }
        dragRow = null;
        dragTbody = null;
    }

    document.addEventListener('dragstart', onDragStart);
    document.addEventListener('dragover', onDragOver);
    document.addEventListener('drop', onDrop);
    document.addEventListener('dragend', finish);
})();