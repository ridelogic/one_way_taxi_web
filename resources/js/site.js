/* One Way Taxi — progressive enhancements (the site works without JavaScript). */
(function () {
    'use strict';

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    /* ---------- Mobile navigation ---------- */
    var toggle = document.querySelector('[data-nav-toggle]');
    if (toggle) {
        var setOpen = function (open) {
            document.body.classList.toggle('nav-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        };
        toggle.addEventListener('click', function () { setOpen(!document.body.classList.contains('nav-open')); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setOpen(false); });
        window.addEventListener('resize', function () { if (window.innerWidth >= 1280) setOpen(false); });
    }

    /* ---------- Header background on scroll ---------- */
    var header = document.querySelector('[data-header]');
    if (header) {
        var onScroll = function () { header.classList.toggle('is-scrolled', window.scrollY > 24); };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    /* ---------- Hero slider ---------- */
    document.querySelectorAll('[data-slider]').forEach(function (slider) {
        var slides = slider.querySelectorAll('[data-slide]');
        var dots = slider.querySelectorAll('[data-slider-dot]');
        var pauseBtn = slider.querySelector('[data-slider-pause]');
        var delay = parseInt(slider.getAttribute('data-autoplay') || '0', 10) * 1000;
        var current = 0, timer = null, paused = reduceMotion || delay === 0, hover = false;
        if (slides.length < 2) return;

        var show = function (index) {
            current = (index + slides.length) % slides.length;
            slides.forEach(function (slide, i) {
                var active = i === current;
                slide.classList.toggle('is-active', active);
                slide.setAttribute('aria-hidden', active ? 'false' : 'true');
                slide.querySelectorAll('a').forEach(function (a) { a.tabIndex = active ? 0 : -1; });
                if (active) {
                    slide.querySelectorAll('img[loading="lazy"]').forEach(function (img) { img.loading = 'eager'; });
                }
            });
            dots.forEach(function (dot, i) {
                dot.classList.toggle('is-active', i === current);
                dot.setAttribute('aria-selected', i === current ? 'true' : 'false');
            });
        };
        var schedule = function () {
            clearTimeout(timer);
            if (!paused && !hover && delay > 0) timer = setTimeout(function () { show(current + 1); schedule(); }, delay);
        };
        var go = function (index) { show(index); schedule(); };

        // Preload the next slide's image shortly after load so transitions are seamless.
        setTimeout(function () { if (slides[1]) slides[1].querySelectorAll('img').forEach(function (img) { img.loading = 'eager'; }); }, 1500);

        slider.querySelector('[data-slider-prev]').addEventListener('click', function () { go(current - 1); });
        slider.querySelector('[data-slider-next]').addEventListener('click', function () { go(current + 1); });
        dots.forEach(function (dot) { dot.addEventListener('click', function () { go(parseInt(dot.getAttribute('data-slider-dot'), 10)); }); });

        if (pauseBtn) {
            var syncPause = function () {
                pauseBtn.setAttribute('aria-pressed', paused ? 'true' : 'false');
                pauseBtn.setAttribute('aria-label', paused ? 'Play slideshow' : 'Pause slideshow');
                pauseBtn.classList.toggle('is-paused', paused);
            };
            pauseBtn.addEventListener('click', function () { paused = !paused; syncPause(); schedule(); });
            syncPause();
        }

        slider.addEventListener('mouseenter', function () { hover = true; clearTimeout(timer); });
        slider.addEventListener('mouseleave', function () { hover = false; schedule(); });
        slider.addEventListener('focusin', function () { hover = true; clearTimeout(timer); });
        slider.addEventListener('focusout', function () { hover = false; schedule(); });
        slider.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowLeft') go(current - 1);
            if (e.key === 'ArrowRight') go(current + 1);
        });

        var startX = null;
        slider.addEventListener('touchstart', function (e) { startX = e.touches[0].clientX; }, { passive: true });
        slider.addEventListener('touchend', function (e) {
            if (startX === null) return;
            var dx = e.changedTouches[0].clientX - startX;
            if (Math.abs(dx) > 50) go(current + (dx < 0 ? 1 : -1));
            startX = null;
        }, { passive: true });

        document.addEventListener('visibilitychange', function () { if (document.hidden) clearTimeout(timer); else schedule(); });
        schedule();
    });

    /* ---------- Horizontal carousels ---------- */
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-carousel-prev],[data-carousel-next]');
        if (!btn) return;
        var track = document.getElementById(btn.getAttribute('data-carousel-prev') || btn.getAttribute('data-carousel-next'));
        if (!track) return;
        var card = track.firstElementChild;
        var step = card ? card.getBoundingClientRect().width + 20 : 300;
        track.scrollBy({ left: btn.hasAttribute('data-carousel-prev') ? -step : step, behavior: reduceMotion ? 'auto' : 'smooth' });
    });

    /* ---------- Trip type tabs: show the right fields ---------- */
    document.querySelectorAll('form').forEach(function (form) {
        var radios = form.querySelectorAll('[data-trip-type]');
        if (!radios.length) return;
        var apply = function () {
            var checked = form.querySelector('[data-trip-type]:checked');
            var type = checked ? checked.value : 'one_way';
            form.querySelectorAll('[data-trip-show]').forEach(function (el) {
                var visible = el.getAttribute('data-trip-show').split(' ').indexOf(type) > -1;
                el.hidden = !visible;
                el.querySelectorAll('input,select,textarea').forEach(function (input) { input.disabled = !visible; });
            });
            form.setAttribute('data-trip', type);
        };
        radios.forEach(function (r) { r.addEventListener('change', function () { apply(); form.dispatchEvent(new Event('trip-change')); }); });
        apply();
    });

    /* ---------- Homepage taxi search: checks before opening the booking page ---------- */
    document.querySelectorAll('[data-trip-search]').forEach(function (form) {
        var message = form.querySelector('[data-search-error]');
        var leadHours = parseInt(form.getAttribute('data-lead-hours') || '0', 10);
        var input = function (name) { return form.querySelector('[name="' + name + '"]'); };
        var pad = function (n) { return (n < 10 ? '0' : '') + n; };
        var localDate = function (d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); };

        var problem = function () {
            var pickup = input('pickup_location'), drop = input('destination_location');
            var date = input('travel_date'), time = input('travel_time'), back = input('return_date');
            if (pickup.value.trim() && pickup.value.trim().toLowerCase() === drop.value.trim().toLowerCase()) {
                return [drop, 'Pickup and drop locations must be different.'];
            }
            if (date.value && time.value) {
                var earliest = new Date(Date.now() + leadHours * 3600 * 1000);
                var parts = date.value.split('-').concat(time.value.split(':')).map(Number);
                var pickupAt = new Date(parts[0], parts[1] - 1, parts[2], parts[3], parts[4]);
                if (pickupAt < earliest) {
                    return [time, leadHours > 0
                        ? 'Please choose a pickup time at least ' + leadHours + ' hour' + (leadHours === 1 ? '' : 's') + ' from now.'
                        : 'The pickup time cannot be in the past.'];
                }
            }
            if (!back.disabled && back.value && date.value && back.value < date.value) {
                return [back, 'The return date must be on or after the journey date.'];
            }
            return null;
        };

        var reset = function () {
            form.querySelectorAll('input').forEach(function (el) { el.setCustomValidity(''); });
            if (message) message.hidden = true;
        };

        input('travel_date').addEventListener('change', function () {
            input('return_date').min = this.value || localDate(new Date());
        });
        form.addEventListener('input', reset);
        form.addEventListener('change', reset);
        form.addEventListener('submit', function (e) {
            reset();
            var found = problem();
            if (found) {
                found[0].setCustomValidity(found[1]);
                if (message) { message.textContent = found[1]; message.hidden = false; }
            }
            if (!form.checkValidity()) {
                e.preventDefault();
                form.reportValidity();
            }
        });
    });

    /* ---------- Live fare estimate ---------- */
    var csrf = document.querySelector('meta[name="csrf-token"]');
    document.querySelectorAll('[data-quote-form]').forEach(function (form) {
        var output = form.querySelector('[data-estimate-output]');
        var url = form.getAttribute('data-estimate-url');
        if (!output || !url) return;

        var field = function (name) { var el = form.querySelector('[name="' + name + '"]:not(:disabled)'); return el ? el.value : ''; };
        var tripType = function () { var c = form.querySelector('[data-trip-type]:checked'); return c ? c.value : 'one_way'; };
        var timer, controller;

        var render = function (data) {
            output.hidden = false;
            output.classList.toggle('quote-preview--muted', !data.available);
            if (!data.available) {
                output.textContent = data.message || 'We will call you with a quote for this trip.';
                return;
            }
            var label = data.starting_from ? 'Starting from' : 'Estimated fare';
            var html = label + ': <strong>' + escapeHtml(data.total_formatted) + '</strong>';
            html += '<br><small>' + escapeHtml(data.route_name) + ' · ' + escapeHtml(parseFloat(data.distance_km)) + ' km · ' + escapeHtml(data.duration) + '</small>';
            if (data.message) html += '<br><small>' + escapeHtml(data.message) + '</small>';
            if (data.excluded && data.excluded.length) html += '<br><small>Excludes: ' + data.excluded.map(escapeHtml).join(', ') + '</small>';
            if (data.is_sample) html += '<br><small><em>Sample fare — subject to confirmation.</em></small>';
            output.innerHTML = html;
        };

        var update = function () {
            var type = tripType();
            var pickup = field('pickup_location').trim(), dest = field('destination_location').trim(), vehicle = field('vehicle_category_id');
            if (pickup.length < 2 || (type !== 'rental' && dest.length < 2)) { output.hidden = true; return; }
            if (type !== 'rental' && pickup.toLowerCase() === dest.toLowerCase()) {
                output.hidden = false;
                output.classList.add('quote-preview--muted');
                output.textContent = 'Pickup and destination must be different.';
                return;
            }
            if (controller) controller.abort();
            controller = 'AbortController' in window ? new AbortController() : null;
            var body = new FormData();
            body.append('trip_type', type);
            body.append('pickup_location', pickup);
            if (type !== 'rental') body.append('destination_location', dest);
            if (vehicle) body.append('vehicle_category_id', vehicle);
            var token = form.querySelector('input[name="_token"]');
            fetch(url, {
                method: 'POST', body: body, credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token ? token.value : (csrf ? csrf.content : '') },
                signal: controller ? controller.signal : undefined
            }).then(function (r) { return r.ok ? r.json() : null; })
              .then(function (data) { if (data) render(data); })
              .catch(function () { /* estimate is optional */ });
        };

        var queue = function (wait) { clearTimeout(timer); timer = setTimeout(update, wait); };
        form.querySelectorAll('[data-estimate-input]').forEach(function (el) {
            el.addEventListener('change', function () { queue(150); });
            el.addEventListener('input', function () { queue(500); });
        });
        form.addEventListener('trip-change', function () { queue(50); });
        update();
    });
})();
