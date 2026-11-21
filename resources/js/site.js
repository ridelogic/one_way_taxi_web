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

    /* ---------- Desktop submenus open on click (never on hover) ---------- */
    var submenuToggles = document.querySelectorAll('[data-submenu-toggle]');
    var closeSubmenus = function (except) {
        submenuToggles.forEach(function (btn) {
            if (btn === except) return;
            btn.setAttribute('aria-expanded', 'false');
            var menu = document.getElementById(btn.getAttribute('aria-controls'));
            if (menu) menu.classList.remove('is-open');
        });
    };
    submenuToggles.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var menu = document.getElementById(btn.getAttribute('aria-controls'));
            var open = btn.getAttribute('aria-expanded') !== 'true';
            closeSubmenus(btn);
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (menu) menu.classList.toggle('is-open', open);
        });
    });
    if (submenuToggles.length) {
        document.addEventListener('click', function (e) { if (!e.target.closest('[data-submenu]')) closeSubmenus(); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeSubmenus(); });
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
        // Prefer the enabled field when a name is used twice (the Airport tab has its own drop field).
        var input = function (name) { return form.querySelector('[name="' + name + '"]:not(:disabled)') || form.querySelector('[name="' + name + '"]'); };
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
            var meta = [data.route_name, data.distance_km && parseFloat(data.distance_km) > 0 ? parseFloat(data.distance_km) + ' km' : '', data.duration].filter(Boolean);
            if (meta.length) html += '<br><small>' + meta.map(escapeHtml).join(' · ') + '</small>';
            if (data.lines && data.lines.length && !data.starting_from) {
                html += '<br><small>' + data.lines.map(function (l) { return escapeHtml(l.label) + ': ' + escapeHtml(l.formatted); }).join('<br>') + '</small>';
            }
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
            // Map coordinates, measured route, dates and rental package (used for fleet fares).
            ['pickup_lat', 'pickup_lng', 'drop_lat', 'drop_lng', 'route_km', 'route_sec', 'travel_date', 'travel_time', 'return_date', 'fleet_package_id'].forEach(function (name) {
                if ((name.indexOf('drop_') === 0 || name.indexOf('route_') === 0) && type === 'rental') return;
                var v = field(name);
                if (v) body.append(name, v);
            });
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
        form.addEventListener('fleet-route', function () { queue(50); });
        update();
    });

    /* ---------- Fleet fares: Google Places + Directions + rental packages ----------
       Same inputs as the admin Add Booking screen: map coordinates for pickup/drop, the driving distance and
       time between them, and (Rental) a package from the fleet backend. The server re-checks everything. */
    document.querySelectorAll('[data-quote-form][data-packages-url]').forEach(function (form) {
        var key = form.getAttribute('data-maps-key');
        var fleetVehicles = {};
        try { fleetVehicles = JSON.parse(form.getAttribute('data-fleet-vehicles') || '{}'); } catch (e) { /* none linked */ }
        var hidden = function (name) { return form.querySelector('[data-fleet-hidden="' + name + '"]'); };
        var setHidden = function (name, value) { var el = hidden(name); if (el) el.value = value == null ? '' : value; };
        var tripType = function () { var c = form.querySelector('[data-trip-type]:checked'); return c ? c.value : 'one_way'; };
        var vehicleSelect = form.querySelector('[name="vehicle_category_id"]');
        var packageField = form.querySelector('[data-fleet-package-field]');
        var packageSelect = form.querySelector('[name="fleet_package_id"]');
        var sitePackageField = form.querySelector('[data-site-package-field]');
        var directions = null;

        var isLinked = function () { return !!(vehicleSelect && fleetVehicles[vehicleSelect.value]); };

        /* Rental: fleet packages for linked vehicles, the site's package list otherwise. */
        var syncPackages = function () {
            if (!packageField || !sitePackageField) return;
            var fleetRental = tripType() === 'rental' && isLinked();
            packageField.hidden = !fleetRental;
            packageSelect.disabled = !fleetRental;
            if (tripType() === 'rental') {
                sitePackageField.hidden = fleetRental;
                sitePackageField.querySelectorAll('select').forEach(function (s) { s.disabled = fleetRental; });
            }
            if (fleetRental) loadPackages();
        };

        var packagesRequest = 0;
        var loadPackages = function () {
            var id = ++packagesRequest;
            var params = new URLSearchParams({ vehicle_category_id: vehicleSelect.value });
            if (hidden('pickup_lat').value) { params.append('pickup_lat', hidden('pickup_lat').value); params.append('pickup_lng', hidden('pickup_lng').value); }
            var keep = packageSelect.value || packageSelect.getAttribute('data-selected');
            fetch(form.getAttribute('data-packages-url') + '?' + params.toString(), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (data) {
                    if (id !== packagesRequest || !data) return;
                    packageSelect.innerHTML = '';
                    var first = document.createElement('option');
                    first.value = '';
                    first.textContent = data.packages && data.packages.length ? 'Select package' : (data.message || 'No packages available');
                    packageSelect.appendChild(first);
                    (data.packages || []).forEach(function (p) {
                        var o = document.createElement('option');
                        o.value = p.id;
                        o.textContent = p.label;
                        if (String(p.id) === String(keep)) o.selected = true;
                        packageSelect.appendChild(o);
                    });
                    packageSelect.dispatchEvent(new Event('change'));
                })
                .catch(function () { /* optional */ });
        };

        /* Vehicles not offered for the selected trip type (fleet motor_model enable_local / rental / outstation). */
        var syncVehicles = function () {
            if (!vehicleSelect) return;
            var type = tripType();
            Array.prototype.forEach.call(vehicleSelect.options, function (o) {
                var types = o.getAttribute('data-fleet-types');
                if (types === null) return;
                var offered = (' ' + types + ' ').indexOf(' ' + type + ' ') > -1;
                o.disabled = !offered;
                if (!o.hasAttribute('data-label')) o.setAttribute('data-label', o.textContent);
                o.textContent = o.getAttribute('data-label') + (offered ? '' : ' (not available for this trip)');
            });
            if (vehicleSelect.selectedOptions[0] && vehicleSelect.selectedOptions[0].disabled) vehicleSelect.value = '';
        };

        /* Driving distance and time, like calculateRoute() in the admin. */
        var measure = function () {
            setHidden('route_km', ''); setHidden('route_sec', '');
            var pLat = hidden('pickup_lat').value, pLng = hidden('pickup_lng').value, dLat = hidden('drop_lat').value, dLng = hidden('drop_lng').value;
            if (!pLat || !dLat || tripType() === 'rental' || !directions) { form.dispatchEvent(new Event('fleet-route')); return; }
            directions.route({
                origin: new google.maps.LatLng(parseFloat(pLat), parseFloat(pLng)),
                destination: new google.maps.LatLng(parseFloat(dLat), parseFloat(dLng)),
                travelMode: google.maps.TravelMode.DRIVING
            }, function (result, status) {
                if (status === 'OK' && result.routes[0]) {
                    var leg = result.routes[0].legs[0];
                    setHidden('route_km', ((leg.distance.value / 100) / 10).toFixed(2));
                    setHidden('route_sec', leg.duration.value);
                }
                form.dispatchEvent(new Event('fleet-route'));
            });
        };

        var attachPlaces = function () {
            directions = new google.maps.DirectionsService();
            form.querySelectorAll('[data-place]').forEach(function (input) {
                var which = input.getAttribute('data-place'); // pickup | drop
                input.removeAttribute('list'); // Google suggestions replace the city list
                var ac = new google.maps.places.Autocomplete(input, { componentRestrictions: { country: 'in' }, fields: ['geometry', 'formatted_address', 'name', 'address_components'] });
                var chosen = input.value;
                ac.addListener('place_changed', function () {
                    var place = ac.getPlace();
                    if (!place || !place.geometry) return;
                    if (place.name && place.formatted_address && place.formatted_address.indexOf(place.name) !== 0) input.value = place.name + ', ' + place.formatted_address;
                    chosen = input.value;
                    setHidden(which + '_lat', place.geometry.location.lat().toFixed(7));
                    setHidden(which + '_lng', place.geometry.location.lng().toFixed(7));
                    if (which === 'pickup') {
                        var city = (place.address_components || []).filter(function (c) { return c.types.indexOf('locality') > -1; })[0];
                        setHidden('pickup_city', city ? city.long_name : '');
                        syncPackages();
                    }
                    measure();
                });
                // Typing after choosing a suggestion means the coordinates no longer match.
                input.addEventListener('input', function () {
                    if (input.value !== chosen) { setHidden(which + '_lat', ''); setHidden(which + '_lng', ''); setHidden('route_km', ''); setHidden('route_sec', ''); }
                });
            });
            // Coordinates kept from a failed submit: measure again.
            if (hidden('pickup_lat').value && hidden('drop_lat').value) measure();
        };

        if (key) {
            window.__owtPlacesReady = function () { attachPlaces(); };
            var s = document.createElement('script');
            s.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(key) + '&libraries=places&callback=__owtPlacesReady&loading=async';
            s.async = true;
            document.head.appendChild(s);
        }

        if (vehicleSelect) vehicleSelect.addEventListener('change', syncPackages);
        form.addEventListener('trip-change', function () { syncVehicles(); syncPackages(); measure(); });
        syncVehicles();
        syncPackages();
    });
})();

/* Video promotions (homepage + district pages): one YouTube player, created only when a visitor clicks a video and
   destroyed on close (which stops playback). The official YouTube IFrame Player API is loaded on the first click so
   YouTube's error codes can be caught: if a video may not be played here (owner disabled embedding, age check, private
   or removed video…) the player is replaced by the thumbnail and a "Watch on YouTube" button — never a blank box.
   Nothing is bypassed: the player only plays what YouTube allows. Without JavaScript the cards are plain YouTube links. */
(function () {
    var modal = document.querySelector('[data-video-modal]');
    if (!modal || typeof modal.showModal !== 'function') return;
    var frame = modal.querySelector('[data-video-modal-frame]');
    var heading = modal.querySelector('[data-video-modal-title]');
    var fallback = modal.querySelector('[data-video-fallback]');
    var fallbackImg = modal.querySelector('[data-video-fallback-img]');
    var fallbackLink = modal.querySelector('[data-video-fallback-link]');
    var opener = null, player = null, current = null, apiState = 'idle', apiWaiters = [];

    var loadApi = function (done) {
        if (window.YT && window.YT.Player) { done(true); return; }
        apiWaiters.push(done);
        if (apiState === 'loading') return;
        apiState = 'loading';
        var previous = window.onYouTubeIframeAPIReady;
        window.onYouTubeIframeAPIReady = function () {
            if (typeof previous === 'function') previous();
            apiState = 'ready';
            apiWaiters.splice(0).forEach(function (fn) { fn(true); });
        };
        var s = document.createElement('script');
        s.src = 'https://www.youtube.com/iframe_api';
        s.async = true;
        s.onerror = function () { apiState = 'failed'; apiWaiters.splice(0).forEach(function (fn) { fn(false); }); };
        document.head.appendChild(s);
        // If the API is blocked or slow, fall back to a plain embed rather than leaving the visitor waiting.
        setTimeout(function () { if (apiState === 'loading') { apiWaiters.splice(0).forEach(function (fn) { fn(false); }); } }, 6000);
    };

    var stop = function () {
        if (player && typeof player.destroy === 'function') { try { player.destroy(); } catch (e) {} }
        player = null;
        frame.innerHTML = '';
        frame.hidden = false;
        fallback.hidden = true;
    };

    var showFallback = function () {
        if (!current) return;
        if (player && typeof player.destroy === 'function') { try { player.destroy(); } catch (e) {} }
        player = null;
        frame.innerHTML = '';
        frame.hidden = true;
        fallbackImg.src = current.thumb;
        fallbackLink.href = current.watch;
        fallback.hidden = false;
    };

    var plainIframe = function (video) {
        var iframe = document.createElement('iframe');
        iframe.src = 'https://www.youtube-nocookie.com/embed/' + video.id + '?autoplay=1&rel=0&playsinline=1';
        iframe.title = video.title;
        iframe.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
        iframe.allowFullscreen = true;
        iframe.referrerPolicy = 'strict-origin-when-cross-origin'; // YouTube requires a referrer to identify the embedding site
        frame.appendChild(iframe);
    };

    var play = function (video) {
        stop();
        var mount = document.createElement('div');
        frame.appendChild(mount);
        loadApi(function (ok) {
            if (current !== video) return; // the modal was closed or another video was opened meanwhile
            if (!ok) { frame.innerHTML = ''; plainIframe(video); return; }
            player = new window.YT.Player(mount, {
                host: 'https://www.youtube-nocookie.com',
                videoId: video.id,
                width: '100%',
                height: '100%',
                playerVars: { autoplay: 1, rel: 0, playsinline: 1, origin: window.location.origin },
                events: {
                    // 2 invalid id · 5 playback error · 100 removed/private · 101/150 the owner does not allow embedding.
                    onError: showFallback
                }
            });
            var el = player.getIframe && player.getIframe();
            if (el) {
                el.setAttribute('title', video.title);
                el.setAttribute('allow', 'autoplay; encrypted-media; picture-in-picture; fullscreen');
                el.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
            }
        });
    };

    document.addEventListener('click', function (event) {
        var card = event.target.closest('[data-video-id]');
        if (!card) return;
        var id = card.getAttribute('data-video-id') || '';
        if (!/^[A-Za-z0-9_-]{11}$/.test(id)) return;
        event.preventDefault();
        opener = card;
        current = { id: id, title: card.getAttribute('data-video-title') || 'Video', thumb: card.getAttribute('data-video-thumb') || '', watch: card.getAttribute('href') };
        heading.textContent = current.title;
        modal.showModal();
        document.documentElement.classList.add('has-video-modal');
        play(current);
    });

    modal.querySelector('[data-video-modal-close]').addEventListener('click', function () { modal.close(); });
    // Clicking the dimmed backdrop (outside the player box) closes the modal too.
    modal.addEventListener('click', function (event) { if (event.target === modal) modal.close(); });
    modal.addEventListener('close', function () {
        current = null;
        stop();
        document.documentElement.classList.remove('has-video-modal');
        if (opener) opener.focus();
    });
})();

/* Photo galleries: clicking a photo opens it in a lightbox (plain link to the image without JavaScript). */
(function () {
    var box = document.querySelector('[data-lightbox]');
    if (!box || typeof box.showModal !== 'function') return;
    var img = box.querySelector('[data-lightbox-img]');
    var title = box.querySelector('[data-lightbox-title-out]');
    var opener = null;
    document.addEventListener('click', function (event) {
        var link = event.target.closest('[data-lightbox-src]');
        if (!link) return;
        event.preventDefault();
        opener = link;
        img.src = link.getAttribute('data-lightbox-src');
        img.alt = link.getAttribute('data-lightbox-title') || '';
        title.textContent = link.getAttribute('data-lightbox-title') || '';
        box.showModal();
        document.documentElement.classList.add('has-video-modal');
    });
    box.querySelector('[data-lightbox-close]').addEventListener('click', function () { box.close(); });
    box.addEventListener('click', function (event) { if (event.target === box) box.close(); });
    box.addEventListener('close', function () {
        img.removeAttribute('src');
        document.documentElement.classList.remove('has-video-modal');
        if (opener) opener.focus();
    });
})();
