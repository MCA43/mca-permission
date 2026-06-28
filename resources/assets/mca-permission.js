(function () {
    'use strict';

    function initShellNav() {
        var btn = document.getElementById('mcaUiMenuBtn');
        var nav = document.getElementById('mcaUiNav');
        if (!btn || !nav) return;

        btn.addEventListener('click', function () {
            var open = nav.classList.toggle('is-open');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            btn.innerHTML = open
                ? '<svg class="mca-ui-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>'
                : '<svg class="mca-ui-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>';
        });

        nav.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.matchMedia('(max-width: 899px)').matches) {
                    nav.classList.remove('is-open');
                    btn.setAttribute('aria-expanded', 'false');
                }
            });
        });
    }

    function initScanner() {
        var root = document.getElementById('mcaPermScannerRoot');
        if (!root) return;

        var i18n = {};
        try {
            i18n = JSON.parse(root.dataset.i18n || '{}');
        } catch (e) {
            i18n = {};
        }

        function t(key, vars) {
            var msg = i18n[key] || key;
            if (!vars) return msg;
            Object.keys(vars).forEach(function (k) {
                msg = msg.replace(':' + k, String(vars[k]));
            });
            return msg;
        }

        var cfg = {
            scanUrl: root.dataset.scanUrl,
            bulkUrl: root.dataset.bulkUrl,
            syncLabelsUrl: root.dataset.syncLabelsUrl,
            syncAllUrl: root.dataset.syncAllUrl,
            csrf: root.dataset.csrf,
        };

        var btnScan = document.getElementById('mcaPermBtnScan');
        var btnAdd = document.getElementById('mcaPermBtnAddSelected');
        var btnSyncLabels = document.getElementById('mcaPermBtnSyncLabels');
        var btnSyncAll = document.getElementById('mcaPermBtnSyncAll');
        var checkAll = document.getElementById('mcaPermCheckAll');
        var statusEl = document.getElementById('mcaPermScanStatus');
        var missingBody = document.getElementById('mcaPermMissingBody');
        var existingBody = document.getElementById('mcaPermExistingBody');

        var lastMissing = [];

        function setStatus(msg) {
            if (statusEl) statusEl.textContent = msg || '';
        }

        function headers() {
            return {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': cfg.csrf,
                'X-Requested-With': 'XMLHttpRequest',
            };
        }

        function renderMissing(items) {
            lastMissing = items || [];
            if (!missingBody) return;
            if (!items.length) {
                missingBody.innerHTML = '<tr><td colspan="4" class="mca-perm-empty">' + escapeHtml(t('no_missing')) + '</td></tr>';
                if (btnAdd) btnAdd.disabled = true;
                return;
            }
            missingBody.innerHTML = items.map(function (row, idx) {
                return '<tr>' +
                    '<td><input type="checkbox" class="mca-perm-missing-check" data-idx="' + idx + '"></td>' +
                    '<td><code class="mca-perm-mono">' + escapeHtml(row.name) + '</code></td>' +
                    '<td>' + escapeHtml(row.module_description || row.module) + '</td>' +
                    '<td>' + escapeHtml(row.method_description || row.method) + '</td>' +
                    '</tr>';
            }).join('');
            if (btnAdd) btnAdd.disabled = false;
        }

        function renderExisting(items) {
            if (!existingBody) return;
            if (!items.length) {
                existingBody.innerHTML = '<tr><td colspan="2" class="mca-perm-empty">' + escapeHtml(t('empty_dash')) + '</td></tr>';
                return;
            }
            existingBody.innerHTML = items.map(function (row) {
                var roles = (row.assigned_roles || []).join(', ') || t('empty_dash');
                return '<tr><td><code class="mca-perm-mono">' + escapeHtml(row.name) + '</code></td><td>' + escapeHtml(roles) + '</td></tr>';
            }).join('');
        }

        function escapeHtml(s) {
            return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        function scan() {
            setStatus(t('scanning'));
            fetch(cfg.scanUrl, { headers: headers(), credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    renderMissing(data.missing_permissions || []);
                    renderExisting(data.existing_permissions || []);
                    setStatus(t('status_counts', {
                        missing: data.total_missing || 0,
                        existing: data.total_existing || 0,
                    }));
                })
                .catch(function () { setStatus(t('scan_error')); });
        }

        function selectedMissing() {
            var checks = document.querySelectorAll('.mca-perm-missing-check:checked');
            var out = [];
            checks.forEach(function (el) {
                var idx = parseInt(el.getAttribute('data-idx'), 10);
                if (!isNaN(idx) && lastMissing[idx]) out.push(lastMissing[idx]);
            });
            return out;
        }

        if (btnScan) btnScan.addEventListener('click', scan);
        if (btnSyncAll) btnSyncAll.addEventListener('click', function () {
            setStatus(t('syncing'));
            fetch(cfg.syncAllUrl, { method: 'POST', headers: headers(), credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.scan) {
                        renderMissing(data.scan.missing_permissions || []);
                        renderExisting(data.scan.existing_permissions || []);
                    }
                    setStatus(t('sync_result', {
                        added: data.added || 0,
                        labels: data.labels_updated || 0,
                    }));
                })
                .catch(function () { setStatus(t('sync_error')); });
        });

        if (btnAdd) btnAdd.addEventListener('click', function () {
            var items = selectedMissing();
            if (!items.length) return;
            fetch(cfg.bulkUrl, {
                method: 'POST',
                headers: headers(),
                credentials: 'same-origin',
                body: JSON.stringify({ permissions: items }),
            }).then(function (r) { return r.json(); }).then(function () { scan(); });
        });

        if (btnSyncLabels) btnSyncLabels.addEventListener('click', function () {
            fetch(cfg.syncLabelsUrl, { method: 'POST', headers: headers(), credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    setStatus(t('labels_updated', { count: d.labels_updated || 0 }));
                });
        });

        if (checkAll) checkAll.addEventListener('change', function () {
            document.querySelectorAll('.mca-perm-missing-check').forEach(function (el) {
                el.checked = checkAll.checked;
            });
        });
    }

    function boot() {
        initShellNav();
        initScanner();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
