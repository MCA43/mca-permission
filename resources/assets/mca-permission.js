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

    var ICON_TRASH =
        '<svg class="mca-ui-icon mca-ui-icon--xs" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">' +
        '<path stroke-linecap="round" d="M4 7h16"/><path stroke-linecap="round" d="M10 11v6M14 11v6"/>' +
        '<path stroke-linecap="round" d="M6 7l1 12h10l1-12"/><path stroke-linecap="round" d="M9 7V5h6v2"/></svg>';

    function confirmDelete(message) {
        var i18n = window.McaUiI18n || {};
        if (window.McaUi && window.McaUi.confirm) {
            return window.McaUi.confirm({
                title: i18n.delete_title || i18n.confirm_title || '',
                message: message,
                confirmText: i18n.confirm || '',
                danger: true,
            });
        }
        return Promise.resolve(window.confirm(message));
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
            segmentsUrl: root.dataset.segmentsUrl,
            segmentsStoreUrl: root.dataset.segmentsStoreUrl,
            segmentsSyncUrl: root.dataset.segmentsSyncUrl,
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

        var segmentsBody = document.getElementById('mcaPermSegmentsBody');
        var segmentsCountEl = document.getElementById('mcaPermSegmentsCount');
        var segmentForm = document.getElementById('mcaPermSegmentForm');
        var btnSyncSegments = document.getElementById('mcaPermBtnSyncSegments');

        function segmentUrl(id) {
            return cfg.segmentsUrl.replace(/\/$/, '') + '/' + id;
        }

        function renderSegments(items) {
            if (segmentsCountEl) {
                segmentsCountEl.textContent = items.length
                    ? t('segments_count', { count: items.length })
                    : '';
            }
            if (!segmentsBody) return;
            if (!items.length) {
                segmentsBody.innerHTML = '<tr><td colspan="5" class="mca-perm-empty">' + escapeHtml(t('segments_empty')) + '</td></tr>';
                return;
            }
            segmentsBody.innerHTML = items.map(function (row) {
                var badge = row.from_config ? ' <span class="mca-perm-badge mca-perm-badge--mode">' + escapeHtml(t('segments_from_config')) + '</span>' : '';
                return '<tr>' +
                    '<td>' + escapeHtml(row.folder) + badge + '</td>' +
                    '<td><code class="mca-perm-mono">' + escapeHtml(row.path) + '</code></td>' +
                    '<td><code class="mca-perm-mono">' + escapeHtml(row.namespace) + '</code></td>' +
                    '<td><input type="checkbox" class="mca-perm-segment-active" data-id="' + row.id + '"' + (row.is_active ? ' checked' : '') + '></td>' +
                    '<td><button type="button" class="mca-perm-btn mca-perm-btn--danger mca-perm-btn--icon mca-perm-segment-delete" data-id="' + row.id + '"' +
                    ' title="' + escapeHtml(t('common_delete')) + '" aria-label="' + escapeHtml(t('common_delete')) + '">' + ICON_TRASH + '</button></td>' +
                    '</tr>';
            }).join('');

            segmentsBody.querySelectorAll('.mca-perm-segment-active').forEach(function (el) {
                el.addEventListener('change', function () {
                    fetch(segmentUrl(el.getAttribute('data-id')), {
                        method: 'PUT',
                        headers: headers(),
                        credentials: 'same-origin',
                        body: JSON.stringify({ is_active: el.checked }),
                    }).then(function () { loadSegments(); });
                });
            });

            segmentsBody.querySelectorAll('.mca-perm-segment-delete').forEach(function (el) {
                el.addEventListener('click', function () {
                    confirmDelete(t('segments_delete_confirm')).then(function (ok) {
                        if (!ok) return;
                        fetch(segmentUrl(el.getAttribute('data-id')), {
                            method: 'DELETE',
                            headers: headers(),
                            credentials: 'same-origin',
                        }).then(function () {
                            setStatus(t('segments_deleted'));
                            loadSegments();
                        });
                    });
                });
            });
        }

        function loadSegments() {
            if (!cfg.segmentsUrl) return;
            fetch(cfg.segmentsUrl, { headers: headers(), credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) { renderSegments(data.segments || []); });
        }

        if (segmentForm) {
            segmentForm.addEventListener('submit', function (e) {
                e.preventDefault();
                var fd = new FormData(segmentForm);
                fetch(cfg.segmentsStoreUrl, {
                    method: 'POST',
                    headers: headers(),
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        folder: fd.get('folder'),
                        path: fd.get('path'),
                        namespace: fd.get('namespace'),
                        is_active: true,
                    }),
                }).then(function (r) { return r.json(); }).then(function () {
                    segmentForm.reset();
                    setStatus(t('segments_saved'));
                    loadSegments();
                });
            });
        }

        if (btnSyncSegments) {
            btnSyncSegments.addEventListener('click', function () {
                fetch(cfg.segmentsSyncUrl, { method: 'POST', headers: headers(), credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
                    .then(function (data) { renderSegments(data.segments || []); });
            });
        }

        loadSegments();
    }

    function initPermissionList() {
        var root = document.getElementById('mcaPermPermissionListRoot');
        if (!root) return;

        var form = document.getElementById('mcaPermPermissionForm');
        var formCard = document.getElementById('mcaPermPermissionFormCard');
        var formTitle = document.getElementById('mcaPermFormTitle');
        var formHint = document.getElementById('mcaPermFormHint');
        var formEditingName = document.getElementById('mcaPermFormEditingName');
        var formMethod = document.getElementById('mcaPermFormMethod');
        var submitLabel = document.getElementById('mcaPermFormSubmitLabel');
        var cancelBtn = document.getElementById('mcaPermFormCancel');
        var folderEl = document.getElementById('mcaPermFolder');
        var controllerEl = document.getElementById('mcaPermController');
        var methodEl = document.getElementById('mcaPermMethod');
        var moduleDescEl = document.getElementById('mcaPermModuleDesc');
        var methodDescEl = document.getElementById('mcaPermMethodDesc');
        var rootOnlyEl = document.getElementById('mcaPermRootOnly');
        var table = document.getElementById('mcaPermPermissionTable');
        var activeRow = null;

        var i18n = {
            newTitle: root.dataset.i18nNew || '',
            editTitle: root.dataset.i18nEdit || '',
            hint: root.dataset.i18nHint || '',
            add: root.dataset.i18nAdd || '',
            save: root.dataset.i18nSave || '',
            cancel: root.dataset.i18nCancel || '',
            editing: root.dataset.i18nEditing || '',
        };

        function setCreateMode() {
            if (!form) return;

            form.action = root.dataset.storeUrl || form.action;
            formMethod.disabled = true;
            formTitle.textContent = i18n.newTitle;
            formHint.textContent = i18n.hint;
            formHint.hidden = false;
            formEditingName.hidden = true;
            formEditingName.textContent = '';
            submitLabel.textContent = i18n.add;
            cancelBtn.hidden = true;
            formCard.classList.remove('mca-perm-permission-form-card--edit');

            if (activeRow) {
                activeRow.classList.remove('is-editing');
                activeRow = null;
            }
        }

        function setEditMode(btn) {
            if (!form || !btn) return;

            form.action = btn.getAttribute('data-update-url') || form.action;
            formMethod.disabled = false;
            folderEl.value = btn.getAttribute('data-folder') || '';
            controllerEl.value = btn.getAttribute('data-controller') || '';
            methodEl.value = btn.getAttribute('data-method') || '';
            moduleDescEl.value = btn.getAttribute('data-module-description') || '';
            methodDescEl.value = btn.getAttribute('data-method-description') || '';
            rootOnlyEl.checked = btn.getAttribute('data-root-only') === '1';

            var name = btn.getAttribute('data-name') || '';
            formTitle.textContent = i18n.editTitle;
            formHint.hidden = true;
            formEditingName.textContent = i18n.editing.replace('__NAME__', name);
            formEditingName.hidden = !name;
            submitLabel.textContent = i18n.save;
            cancelBtn.hidden = false;
            formCard.classList.add('mca-perm-permission-form-card--edit');

            if (table) {
                var row = btn.closest('tr');
                if (activeRow && activeRow !== row) {
                    activeRow.classList.remove('is-editing');
                }
                activeRow = row;
                if (activeRow) {
                    activeRow.classList.add('is-editing');
                }
            }

            if (formCard && typeof formCard.scrollIntoView === 'function') {
                formCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        root.querySelectorAll('.mca-perm-edit-permission').forEach(function (btn) {
            btn.addEventListener('click', function () {
                setEditMode(btn);
            });
        });

        if (cancelBtn) {
            cancelBtn.addEventListener('click', function () {
                form.reset();
                folderEl.value = 'Panel';
                methodEl.value = 'index';
                rootOnlyEl.checked = false;
                setCreateMode();
            });
        }
    }

    function boot() {
        initShellNav();
        initScanner();
        initPermissionList();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
