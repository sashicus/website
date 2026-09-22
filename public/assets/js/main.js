(function () {
    'use strict';

    var CFG = window.SHOP_CONFIG || {};

    /* ---------------- Cart (localStorage) ---------------- */
    var CART_KEY = 'huawei_cart_v1';
    var cart = loadCart();

    function loadCart() {
        try {
            var raw = localStorage.getItem(CART_KEY);
            var data = raw ? JSON.parse(raw) : {};
            if (typeof data !== 'object' || data === null) return {};
            // sanitize
            Object.keys(data).forEach(function (slug) {
                data[slug].qty = Math.max(1, Math.min(999, parseInt(data[slug].qty, 10) || 1));
            });
            return data;
        } catch (e) { return {}; }
    }

    function saveCart() {
        try { localStorage.setItem(CART_KEY, JSON.stringify(cart)); } catch (e) {}
        updateBadge();
        window.dispatchEvent(new CustomEvent('cart-changed', { detail: cart }));
    }

    function cartCount() {
        return Object.keys(cart).reduce(function (sum, s) { return sum + cart[s].qty; }, 0);
    }

    function updateBadge() {
        var badge = document.getElementById('cartBadge');
        if (!badge) return;
        var n = cartCount();
        if (n > 0) {
            badge.hidden = false;
            badge.textContent = n > 99 ? '99+' : String(n);
        } else {
            badge.hidden = true;
        }
    }

    function addToCart(slug, qty) {
        var btn = document.querySelector('[data-add-to-cart="' + slug + '"]');
        var meta = btn ? btn.dataset : {};
        if (!cart[slug]) {
            cart[slug] = { slug: slug, qty: 0, name: meta.name || '', price: parseInt(meta.price, 10) || 0 };
        }
        cart[slug].qty += qty;
        if (cart[slug].qty > 999) cart[slug].qty = 999;
        saveCart();
        return cart[slug].qty;
    }

    window.ShopCart = {
        get: function () { return cart; },
        count: cartCount,
        add: addToCart,
        setQty: function (slug, qty) {
            if (!cart[slug]) return;
            qty = Math.max(0, Math.min(999, parseInt(qty, 10) || 0));
            if (qty === 0) { delete cart[slug]; } else { cart[slug].qty = qty; }
            saveCart();
        },
        remove: function (slug) { delete cart[slug]; saveCart(); },
        clear: function () { cart = {}; saveCart(); }
    };

    /* ---------------- Add-to-cart buttons ---------------- */
    document.addEventListener('click', function (ev) {
        var btn = ev.target.closest('[data-add-to-cart]');
        if (!btn || btn.disabled) return;
        ev.preventDefault();
        var qty = 1;
        var qtyBlock = btn.closest('.product__buy, .card, .cart-line');
        if (qtyBlock && qtyBlock.querySelector('[data-qty-input]')) {
            qty = parseInt(qtyBlock.querySelector('[data-qty-input]').value, 10) || 1;
        }
        var slug = btn.dataset.addToCart;
        var total = addToCart(slug, qty);
        flashFeedback(btn, 'Добавлено (' + total + ')');
    });

    function flashFeedback(btn, text) {
        var original = btn.textContent;
        btn.textContent = text;
        btn.classList.add('is-added');
        setTimeout(function () {
            btn.textContent = original;
            btn.classList.remove('is-added');
        }, 1300);
    }

    /* ---------------- Quantity steppers ---------------- */
    document.addEventListener('click', function (ev) {
        var minus = ev.target.closest('[data-qty-minus]');
        var plus = ev.target.closest('[data-qty-plus]');
        var stepper = ev.target.closest('[data-qty]');
        if (!stepper) return;
        if (stepper.closest('[data-cart-line]')) return;
        var input = stepper.querySelector('[data-qty-input]');
        if (!input) return;
        var val = parseInt(input.value, 10) || 1;
        if (minus) { input.value = Math.max(1, val - 1); }
        if (plus) { input.value = Math.min(999, val + 1); }
    });

    /* ---------------- Search suggest ---------------- */
    var searchInput = document.getElementById('searchInput');
    var suggestBox = document.getElementById('searchSuggest');
    var suggestTimer = null;

    if (searchInput && suggestBox && CFG.apiSearch) {
        searchInput.addEventListener('input', function () {
            var q = searchInput.value.trim();
            clearTimeout(suggestTimer);
            if (q.length < 2) { suggestBox.hidden = true; return; }
            suggestTimer = setTimeout(function () {
                fetch(CFG.apiSearch + '?q=' + encodeURIComponent(q))
                    .then(function (r) { return r.json(); })
                    .then(function (items) {
                        if (!items.length) { suggestBox.hidden = true; return; }
                        suggestBox.innerHTML = items.map(function (it) {
                            var price = it.price != null
                                ? '<span class="ss-price">' + Number(it.price).toLocaleString('ru-RU').replace(/,/g, ' ') + ' ₽</span>'
                                : '<span class="ss-price">по запросу</span>';
                            return '<a href="' + CFG.baseUrl + it.category_slug + '/' + it.slug + '">'
                                + '<span class="ss-title">' + esc(it.title) + '</span>'
                                + '<span class="ss-meta">' + esc(it.sku || '') + '</span>'
                                + price + '</a>';
                        }).join('');
                        suggestBox.hidden = false;
                    }).catch(function () { suggestBox.hidden = true; });
            }, 250);
        });
        document.addEventListener('click', function (ev) {
            if (!suggestBox.contains(ev.target) && ev.target !== searchInput) suggestBox.hidden = true;
        });
    }

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    /* ---------------- Nav / mobile menu ---------------- */
    var burger = document.getElementById('burger');
    var mobileMenu = document.getElementById('mobileMenu');
    var backdrop = document.getElementById('mobileBackdrop');
    var closeBtn = document.getElementById('mobileMenuClose');

    function openMobile() {
        mobileMenu.classList.add('is-open');
        mobileMenu.setAttribute('aria-hidden', 'false');
        backdrop.hidden = false;
        document.body.style.overflow = 'hidden';
        burger.setAttribute('aria-expanded', 'true');
    }
    function closeMobile() {
        mobileMenu.classList.remove('is-open');
        mobileMenu.setAttribute('aria-hidden', 'true');
        backdrop.hidden = true;
        document.body.style.overflow = '';
        burger.setAttribute('aria-expanded', 'false');
    }
    if (burger && mobileMenu) {
        burger.addEventListener('click', openMobile);
        if (closeBtn) closeBtn.addEventListener('click', closeMobile);
        if (backdrop) backdrop.addEventListener('click', closeMobile);
        mobileMenu.querySelectorAll('a').forEach(function (a) {
            a.addEventListener('click', closeMobile);
        });
        document.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape') closeMobile();
        });
    }

    /* ---------------- Cart page ---------------- */
    var cartRoot = document.getElementById('cartRoot');
    if (cartRoot) renderCartPage();

    function renderCartPage() {
        var slugs = Object.keys(cart);
        if (!slugs.length) return;

        if (!CFG.apiCart) return;
        fetch(CFG.apiCart + '?slugs=' + encodeURIComponent(slugs.join(',')))
            .then(function (r) { return r.json(); })
            .then(function (rows) {
                var bySlug = {};
                rows.forEach(function (r) { bySlug[r.slug] = r; });
                cartRoot.innerHTML = '';
                slugs.forEach(function (slug) {
                    var info = bySlug[slug];
                    if (!info) { delete cart[slug]; return; }
                    cart[slug].name = cart[slug].name || info.title;
                    cart[slug].price = cart[slug].price || parseInt(info.price, 10) || 0;
                    cart[slug].sku = info.sku;
                    cartRoot.appendChild(buildLine(slug, info));
                });
                saveCart();
                cartRoot.appendChild(buildSummary());
            });
    }

    function buildLine(slug, info) {
        var tpl = document.getElementById('cartLineTemplate');
        var el = tpl.content.firstElementChild.cloneNode(true);
        el.querySelector('[data-line-name]').textContent = info.title;
        el.querySelector('[data-line-name]').href = CFG.baseUrl + info.category_slug + '/' + info.slug;
        el.querySelector('[data-line-sku]').textContent = info.sku ? 'Артикул: ' + info.sku : '';
        var qtyInput = el.querySelector('[data-qty-input]');
        qtyInput.value = cart[slug].qty;
        var price = parseInt(cart[slug].price, 10) || 0;
        var priceEl = el.querySelector('[data-line-price]');
        priceEl.textContent = formatMoney(price * cart[slug].qty);
        if (!price) priceEl.textContent = 'по запросу';

        el.querySelector('[data-qty-minus]').addEventListener('click', function () {
            ShopCart.setQty(slug, parseInt(qtyInput.value, 10) - 1);
            refreshLine(slug, el);
        });
        el.querySelector('[data-qty-plus]').addEventListener('click', function () {
            ShopCart.setQty(slug, parseInt(qtyInput.value, 10) + 1);
            refreshLine(slug, el);
        });
        qtyInput.addEventListener('change', function () {
            ShopCart.setQty(slug, parseInt(qtyInput.value, 10) || 1);
            refreshLine(slug, el);
        });
        el.querySelector('[data-line-remove]').addEventListener('click', function () {
            ShopCart.remove(slug);
            refreshCartPage();
        });
        return el;
    }

    function refreshLine(slug, el) {
        var entry = cart[slug];
        if (!entry) {
            el.remove();
            refreshSummaryTotal();
            return;
        }
        var qtyInput = el.querySelector('[data-qty-input]');
        qtyInput.value = entry.qty;
        var price = parseInt(entry.price, 10) || 0;
        var priceEl = el.querySelector('[data-line-price]');
        priceEl.textContent = price ? formatMoney(price * entry.qty) : 'по запросу';
        refreshSummaryTotal();
    }

    function refreshSummaryTotal() {
        var totalEl = document.querySelector('.cart-summary__total');
        if (!totalEl) return;
        var sum = Object.keys(cart).reduce(function (acc, s) {
            return acc + (parseInt(cart[s].price, 10) || 0) * cart[s].qty;
        }, 0);
        totalEl.textContent = formatMoney(sum);
    }

    function buildSummary() {
        var wrap = document.createElement('div');
        wrap.className = 'cart-summary';
        var sum = Object.keys(cart).reduce(function (acc, s) {
            return acc + (parseInt(cart[s].price, 10) || 0) * cart[s].qty;
        }, 0);
        wrap.innerHTML = '<span class="cart-summary__label">Итого:</span>'
            + '<span class="cart-summary__total">' + formatMoney(sum) + '</span>';
        var orderBtn = document.createElement('button');
        orderBtn.className = 'btn btn--accent btn--lg';
        orderBtn.textContent = 'Оформить заказ';
        orderBtn.addEventListener('click', function () { showOrderForm(); });
        wrap.appendChild(orderBtn);
        return wrap;
    }

    function refreshCartPage() {
        var root = document.getElementById('cartRoot');
        root.innerHTML = '';
        renderCartPage();
    }

    function formatMoney(n) {
        n = parseInt(n, 10) || 0;
        if (!n) return 'по запросу';
        return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₽';
    }

    /* ---------------- Order form & submission ---------------- */
    var orderFormInjected = false;
    function showOrderForm() {
        if (orderFormInjected) {
            document.getElementById('orderForm').scrollIntoView({ behavior: 'smooth' });
            return;
        }
        orderFormInjected = true;
        var root = document.getElementById('cartRoot');
        var div = document.createElement('div');
        div.id = 'orderForm';
        div.className = 'order-form';
        div.innerHTML =
            '<h2 class="section__title">Оформление заказа</h2>'
            + '<form class="form" data-order-form>'
            + '<div class="form__grid">'
            + '<div class="form__field"><label class="form__label">Ваше имя *</label>'
            + '<input class="input" name="name" required autocomplete="name"></div>'
            + '<div class="form__field"><label class="form__label">Телефон *</label>'
            + '<input class="input" name="phone" type="tel" required autocomplete="tel" placeholder="+7 (___) ___-__-__"></div>'
            + '</div>'
            + '<div class="form__field"><label class="form__label">E-mail</label>'
            + '<input class="input" name="email" type="email" autocomplete="email"></div>'
            + '<div class="form__field"><label class="form__label">Компания</label>'
            + '<input class="input" name="company"></div>'
            + '<div class="form__field"><label class="form__label">Комментарий</label>'
            + '<textarea class="textarea" name="comment" rows="4"></textarea></div>'
            + '<div class="form__errors" data-form-errors style="color:var(--accent);font-size:13px"></div>'
            + '<button class="btn btn--accent" type="submit">Отправить заказ</button>'
            + '<p class="form__note">Нажимая «Отправить», вы соглашаетесь на обработку персональных данных.</p>'
            + '</form>';
        root.appendChild(div);

        div.querySelector('[data-order-form]').addEventListener('submit', function (ev) {
            ev.preventDefault();
            submitOrder(div);
        });
    }

    function submitOrder(div) {
        var form = div.querySelector('[data-order-form]');
        var errBox = div.querySelector('[data-form-errors]');
        errBox.textContent = '';
        var items = Object.keys(cart).map(function (slug) {
            return { slug: slug, qty: cart[slug].qty };
        });

        fetch(CFG.apiOrder, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                name: form.name.value,
                phone: form.phone.value,
                email: form.email.value,
                company: form.company.value,
                comment: form.comment.value,
                items: items
            })
        }).then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
        .then(function (res) {
            if (res.ok && res.d.ok) {
                ShopCart.clear();
                window.location.href = CFG.baseUrl + 'order-success?id=' + res.d.order_id;
            } else {
                var errors = (res.d && res.d.errors) || {};
                var msg = Object.keys(errors).map(function (k) { return errors[k]; }).join('; ');
                errBox.textContent = 'Ошибка: ' + (msg || 'попробуйте ещё раз');
            }
        }).catch(function () {
            errBox.textContent = 'Ошибка сети. Попробуйте ещё раз или позвоните нам.';
        });
    }

    /* ---------------- Product gallery thumbs ---------------- */
    var mainImg = document.getElementById('productMainImg');
    var thumbsWrap = document.getElementById('productThumbs');
    if (mainImg && thumbsWrap) {
        thumbsWrap.addEventListener('click', function (ev) {
            var thumb = ev.target.closest('.product__thumb');
            if (!thumb) return;
            var src = thumb.getAttribute('data-src');
            if (!src) return;
            mainImg.src = src;
            thumbsWrap.querySelectorAll('.product__thumb').forEach(function (t) {
                t.classList.toggle('is-active', t === thumb);
            });
        });
    }

    /* init */
    updateBadge();
    window.addEventListener('cart-changed', updateBadge);
})();