/* One Way Taxi — admin CMS behaviour (vanilla JS, no build step). */
(function () {
    'use strict';

    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    // Sidebar toggle (mobile): the backdrop and Escape close it; the button reports its state.
    var toggles = document.querySelectorAll('[data-sidebar-toggle]');
    var setSidebar = function (open) {
        document.body.classList.toggle('sidebar-open', open);
        toggles.forEach(function (btn) {
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            btn.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        });
    };
    // Desktop: the same button collapses the sidebar instead (remembered per browser).
    var desktop = window.matchMedia('(min-width: 901px)');
    var setCollapsed = function (collapsed) {
        document.body.classList.toggle('sidebar-collapsed', collapsed);
        toggles.forEach(function (btn) {
            btn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            btn.setAttribute('aria-label', collapsed ? 'Show menu' : 'Hide menu');
        });
        try { localStorage.setItem('admin-sidebar-collapsed', collapsed ? '1' : '0'); } catch (e) {}
    };
    var syncToggle = function () {
        if (desktop.matches) {
            var saved = '0';
            try { saved = localStorage.getItem('admin-sidebar-collapsed') || '0'; } catch (e) {}
            setSidebar(false);
            setCollapsed(saved === '1');
        } else {
            document.body.classList.remove('sidebar-collapsed');
            setSidebar(document.body.classList.contains('sidebar-open'));
        }
    };
    syncToggle();
    if (desktop.addEventListener) desktop.addEventListener('change', syncToggle);
    toggles.forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (desktop.matches) setCollapsed(!document.body.classList.contains('sidebar-collapsed'));
            else setSidebar(!document.body.classList.contains('sidebar-open'));
        });
    });
    document.querySelectorAll('[data-sidebar-close]').forEach(function (el) {
        el.addEventListener('click', function () { setSidebar(false); });
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && document.body.classList.contains('sidebar-open')) setSidebar(false);
    });

    // In-page section tabs (e.g. Settings): highlight the section currently in view.
    document.querySelectorAll('.tabs').forEach(function (nav) {
        var links = Array.prototype.filter.call(nav.querySelectorAll('a[href^="#"]'), function (a) { return a.getAttribute('href').length > 1; });
        if (!links.length || !('IntersectionObserver' in window)) return;
        var byId = {};
        links.forEach(function (a) { var t = document.getElementById(a.getAttribute('href').slice(1)); if (t) byId[t.id] = a; });
        var mark = function (id) {
            links.forEach(function (a) { a.classList.toggle('is-active', byId[id] === a); if (byId[id] === a) a.setAttribute('aria-current', 'true'); else a.removeAttribute('aria-current'); });
        };
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) { if (en.isIntersecting) mark(en.target.id); });
        }, { rootMargin: '-140px 0px -60% 0px' });
        Object.keys(byId).forEach(function (id) { io.observe(document.getElementById(id)); });
        if (links[0]) mark(links[0].getAttribute('href').slice(1));
    });

    // List tables: copy each column heading onto its cells (data-label) so phones can show every row as a
    // labelled card (see "Tables on phones" in admin.css). Cells under a blank heading (e.g. actions) get no label.
    document.querySelectorAll('.acard.atable-wrap table.atable').forEach(function (table) {
        var heads = Array.prototype.map.call(table.querySelectorAll('thead th'), function (th) { return th.textContent.trim(); });
        if (!heads.length) return;
        table.classList.add('atable--cards');
        table.querySelectorAll('tbody tr').forEach(function (row) {
            var col = 0;
            Array.prototype.forEach.call(row.cells, function (cell) {
                if (heads[col] && cell.colSpan === 1) cell.setAttribute('data-label', heads[col]);
                col += cell.colSpan;
            });
        });
    });

    // Confirm destructive forms
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (form.matches('[data-confirm]') && !window.confirm(form.getAttribute('data-confirm'))) {
            e.preventDefault();
        }
    });

    // Icon select live preview
    document.addEventListener('change', function (e) {
        var wrap = e.target.closest('[data-icon-select]');
        if (!wrap || e.target.tagName !== 'SELECT') return;
        wrap.querySelectorAll('[data-icon]').forEach(function (el) {
            el.hidden = el.getAttribute('data-icon') !== e.target.value;
        });
    });

    /* ---------------- Media picker modal ---------------- */
    var modal = document.querySelector('[data-media-modal]');
    var pickerCallback = null;
    var grid, statusEl, moreBtn, searchInput, nextUrl, searchTimer;

    function escapeHtml(v) {
        return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function renderItems(items, append) {
        if (!append) grid.innerHTML = '';
        items.forEach(function (item) {
            var b = document.createElement('button');
            b.type = 'button';
            b.innerHTML = '<img src="' + escapeHtml(item.url) + '" alt="" loading="lazy" style="object-position:' + escapeHtml(item.position) + '"><span>' + escapeHtml(item.title || ('#' + item.id)) + '</span>';
            b.addEventListener('click', function () {
                if (pickerCallback) pickerCallback(item);
                closeModal();
            });
            grid.appendChild(b);
        });
        if (!grid.children.length) grid.innerHTML = '<p class="muted">No images found. Upload one above.</p>';
    }

    function load(url, append) {
        statusEl.textContent = 'Loading…';
        fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (json) {
                statusEl.textContent = '';
                renderItems(json.data || [], append);
                nextUrl = json.next_page_url;
                moreBtn.hidden = !nextUrl;
            })
            .catch(function () { statusEl.textContent = 'Could not load the media library.'; });
    }

    function openModal(callback) {
        if (!modal) return;
        pickerCallback = callback;
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        searchInput.value = '';
        load(grid.getAttribute('data-picker-url'), false);
        searchInput.focus();
    }

    function closeModal() {
        if (!modal) return;
        modal.hidden = true;
        document.body.style.overflow = '';
        pickerCallback = null;
    }

    if (modal) {
        grid = modal.querySelector('[data-media-grid]');
        statusEl = modal.querySelector('[data-media-status]');
        moreBtn = modal.querySelector('[data-media-more]');
        searchInput = modal.querySelector('[data-media-search]');

        modal.querySelectorAll('[data-media-close]').forEach(function (el) { el.addEventListener('click', closeModal); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) closeModal(); });
        moreBtn.addEventListener('click', function () { if (nextUrl) load(nextUrl, true); });
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                var base = grid.getAttribute('data-picker-url');
                load(base + (base.indexOf('?') > -1 ? '&' : '?') + 'q=' + encodeURIComponent(searchInput.value), false);
            }, 300);
        });

        var uploadInput = modal.querySelector('[data-media-upload]');
        uploadInput.addEventListener('change', function () {
            if (!uploadInput.files.length) return;
            var data = new FormData();
            data.append('file', uploadInput.files[0]);
            statusEl.textContent = 'Uploading…';
            fetch(uploadInput.getAttribute('data-upload-url'), {
                method: 'POST', body: data, credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
            }).then(function (r) {
                return r.json().then(function (json) { return { ok: r.ok, json: json }; });
            }).then(function (res) {
                uploadInput.value = '';
                if (!res.ok) {
                    var errors = res.json.errors ? Object.values(res.json.errors).flat().join(' ') : (res.json.message || 'Upload failed.');
                    statusEl.textContent = errors;
                    return;
                }
                statusEl.textContent = 'Uploaded. Click the image to use it.';
                load(grid.getAttribute('data-picker-url'), false);
            }).catch(function () { statusEl.textContent = 'Upload failed.'; });
        });
    }

    // Media fields — delegated so fields inside newly added repeater rows work too.
    document.addEventListener('click', function (e) {
        var chooseBtn = e.target.closest('[data-media-choose]');
        var clearBtn = e.target.closest('[data-media-clear]');
        var field = (chooseBtn || clearBtn) ? (chooseBtn || clearBtn).closest('[data-media-field]') : null;
        if (!field) return;

        var input = field.querySelector('[data-media-input]');
        var preview = field.querySelector('[data-media-preview]');
        var clear = field.querySelector('[data-media-clear]');
        var editLink = field.querySelector('[data-media-edit]');

        if (clearBtn) {
            input.value = '';
            preview.innerHTML = '<span class="media-field__empty">No image selected</span>';
            clear.hidden = true;
            if (editLink) editLink.hidden = true;
            return;
        }

        openModal(function (item) {
            input.value = item.id;
            preview.innerHTML = '<img src="' + escapeHtml(item.url) + '" alt="" style="object-fit:cover;object-position:' + escapeHtml(item.position) + '">';
            clear.hidden = false;
            var editBase = document.querySelector('meta[name="media-edit-url"]');
            if (editLink && editBase) {
                editLink.hidden = false;
                editLink.href = editBase.content.replace('__ID__', item.id);
            }
        });
    });

    /* ---------------- Rich text editor ---------------- */
    document.querySelectorAll('[data-rich]').forEach(function (wrap) {
        var editor = wrap.querySelector('[data-rich-editor]');
        var source = wrap.querySelector('[data-rich-source]');
        var toggle = wrap.querySelector('[data-rich-toggle]');
        var sourceMode = false;

        editor.innerHTML = source.value;
        var sync = function () { if (!sourceMode) source.value = editor.innerHTML; };
        editor.addEventListener('input', sync);
        var form = wrap.closest('form');
        if (form) form.addEventListener('submit', sync);

        wrap.querySelectorAll('[data-cmd]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (sourceMode) return;
                var cmd = btn.getAttribute('data-cmd');
                editor.focus();
                if (cmd === 'createLink') {
                    var url = window.prompt('Link URL (https://… or /page):');
                    if (url) document.execCommand('createLink', false, url);
                } else if (cmd === 'insertImage') {
                    var selection = window.getSelection();
                    var range = selection.rangeCount ? selection.getRangeAt(0) : null;
                    openModal(function (item) {
                        editor.focus();
                        if (range) { selection.removeAllRanges(); selection.addRange(range); }
                        document.execCommand('insertHTML', false, '<img src="' + escapeHtml(item.url) + '" alt="' + escapeHtml(item.alt || '') + '" loading="lazy">');
                        sync();
                    });
                    return;
                } else {
                    document.execCommand(cmd, false, btn.getAttribute('data-value') || null);
                }
                sync();
            });
        });

        toggle.addEventListener('click', function () {
            sourceMode = !sourceMode;
            if (sourceMode) {
                source.value = editor.innerHTML;
                source.hidden = false;
                editor.hidden = true;
            } else {
                editor.innerHTML = source.value;
                source.hidden = true;
                editor.hidden = false;
            }
            toggle.classList.toggle('is-on', sourceMode);
        });
    });

    /* ---------------- Repeaters ---------------- */
    // Field names look like "<prefix>[<index>][field]"; the prefix comes from data-repeater-name.
    function renumber(repeater) {
        var prefix = repeater.getAttribute('data-repeater-name');
        var escaped = prefix.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        var nameRe = new RegExp('^' + escaped + '\\[(\\d+|__INDEX__)\\]');
        var idPrefix = 'f_' + prefix.replace(/\]\[|\[|\]/g, '_').replace(/_+$/, '') + '_';
        var idRe = new RegExp('^' + idPrefix.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '(\\d+|__INDEX__)_');
        var items = repeater.querySelectorAll('[data-repeater-item]');
        items.forEach(function (item, index) {
            item.querySelectorAll('[name]').forEach(function (el) {
                el.name = el.name.replace(nameRe, prefix + '[' + index + ']');
            });
            item.querySelectorAll('[id]').forEach(function (el) { el.id = el.id.replace(idRe, idPrefix + index + '_'); });
            item.querySelectorAll('label[for]').forEach(function (el) { el.htmlFor = el.htmlFor.replace(idRe, idPrefix + index + '_'); });
            var num = item.querySelector('[data-repeater-number]');
            if (num) num.textContent = index + 1;
        });
        var addBtn = repeater.querySelector('[data-repeater-add]');
        var max = parseInt(repeater.getAttribute('data-max') || '99', 10);
        if (addBtn) addBtn.hidden = items.length >= max;
    }

    document.querySelectorAll('[data-repeater]').forEach(function (repeater) {
        var list = repeater.querySelector('[data-repeater-list]');
        var template = repeater.querySelector('template[data-repeater-template]');

        repeater.addEventListener('click', function (e) {
            var btn = e.target.closest('button');
            if (!btn || !repeater.contains(btn)) return;
            var item = btn.closest('[data-repeater-item]');
            if (btn.hasAttribute('data-repeater-add')) {
                list.appendChild(template.content.cloneNode(true));
            } else if (btn.hasAttribute('data-repeater-remove') && item) {
                item.remove();
            } else if (btn.hasAttribute('data-repeater-up') && item && item.previousElementSibling) {
                list.insertBefore(item, item.previousElementSibling);
            } else if (btn.hasAttribute('data-repeater-down') && item && item.nextElementSibling) {
                list.insertBefore(item.nextElementSibling, item);
            } else {
                return;
            }
            renumber(repeater);
        });
        renumber(repeater);
    });

    /* ---------------- Slug helper ---------------- */
    document.querySelectorAll('[data-slug-from]').forEach(function (slug) {
        var source = document.getElementById(slug.getAttribute('data-slug-from'));
        if (!source) return;
        var touched = slug.value !== '';
        slug.addEventListener('input', function () { touched = slug.value !== ''; });
        source.addEventListener('input', function () {
            if (touched) return;
            slug.placeholder = source.value.toLowerCase().normalize('NFKD').replace(/[^\w\s-]/g, '').trim().replace(/[\s_-]+/g, '-');
        });
    });

    /* ---------------- Focal point / crop editor ---------------- */
    var focal = document.querySelector('[data-focal]');
    if (focal) {
        var fx = document.querySelector('[name="focal_x"]');
        var fy = document.querySelector('[name="focal_y"]');
        var fit = document.querySelector('[name="object_fit"]');
        var ratio = document.querySelector('[name="aspect_ratio"]');
        var dot = focal.querySelector('[data-focal-dot]');

        var apply = function () {
            var pos = fx.value + '% ' + fy.value + '%';
            dot.style.left = fx.value + '%';
            dot.style.top = fy.value + '%';
            document.querySelectorAll('[data-focal-preview]').forEach(function (img) {
                img.style.objectPosition = pos;
                img.style.objectFit = fit.value;
            });
            var main = document.querySelector('[data-ratio-preview]');
            if (main) {
                var r = ratio.value === 'original' ? main.getAttribute('data-original') : ratio.value.replace(':', ' / ');
                main.style.aspectRatio = r;
            }
            document.querySelectorAll('[data-focal-output]').forEach(function (el) { el.textContent = pos; });
        };

        var setFromEvent = function (e) {
            var rect = focal.getBoundingClientRect();
            var point = e.touches ? e.touches[0] : e;
            fx.value = Math.max(0, Math.min(100, Math.round((point.clientX - rect.left) / rect.width * 100)));
            fy.value = Math.max(0, Math.min(100, Math.round((point.clientY - rect.top) / rect.height * 100)));
            apply();
        };
        var dragging = false;
        focal.addEventListener('mousedown', function (e) { dragging = true; setFromEvent(e); });
        window.addEventListener('mousemove', function (e) { if (dragging) setFromEvent(e); });
        window.addEventListener('mouseup', function () { dragging = false; });
        focal.addEventListener('touchstart', function (e) { setFromEvent(e); e.preventDefault(); }, { passive: false });
        focal.addEventListener('touchmove', function (e) { setFromEvent(e); e.preventDefault(); }, { passive: false });
        [fx, fy, fit, ratio].forEach(function (el) { el.addEventListener('input', apply); el.addEventListener('change', apply); });
        apply();
    }

    /* ---------------- Drag & drop upload ---------------- */
    document.querySelectorAll('[data-dropzone]').forEach(function (zone) {
        var input = zone.querySelector('input[type="file"]');
        ['dragenter', 'dragover'].forEach(function (evt) {
            zone.addEventListener(evt, function (e) { e.preventDefault(); zone.classList.add('is-over'); });
        });
        ['dragleave', 'drop'].forEach(function (evt) {
            zone.addEventListener(evt, function (e) { e.preventDefault(); zone.classList.remove('is-over'); });
        });
        zone.addEventListener('drop', function (e) {
            if (!e.dataTransfer.files.length) return;
            input.files = e.dataTransfer.files;
            zone.closest('form').submit();
        });
    });
})();
