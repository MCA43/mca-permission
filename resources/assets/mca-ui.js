(function (global) {
    'use strict';

    var ICONS = {
        success: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="m8.5 12.2 2.2 2.2L16 9.2"/></svg>',
        error: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M9 9l6 6M15 9l-6 6"/></svg>',
        warning: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" d="M12 8v5"/><circle cx="12" cy="16.5" r=".6" fill="currentColor" stroke="none"/><path stroke-linecap="round" stroke-linejoin="round" d="M10.3 4.7 2.6 18a1 1 0 0 0 .9 1.5h17a1 1 0 0 0 .9-1.5L13.7 4.7a1 1 0 0 0-1.7 0z"/></svg>',
        info: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 10v6"/><circle cx="12" cy="7.5" r=".6" fill="currentColor" stroke="none"/></svg>',
        question: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M9.5 9.2a2.5 2.5 0 0 1 4.6 1.2c0 1.7-2.1 2.1-2.1 3.8"/><circle cx="12" cy="16.8" r=".6" fill="currentColor" stroke="none"/></svg>',
    };

    var state = {
        modalResolve: null,
        modalReject: null,
        lastFocus: null,
    };

    function t(key, fallback) {
        var i18n = global.McaUiI18n || {};
        return i18n[key] || fallback || key;
    }

    function ensureShell() {
        if (document.getElementById('mcaUiModal')) {
            return;
        }

        var wrap = document.createElement('div');
        wrap.innerHTML =
            '<div id="mcaUiModal" class="mca-ui-modal" hidden aria-hidden="true">' +
                '<div class="mca-ui-modal__backdrop" data-mca-modal-close></div>' +
                '<div class="mca-ui-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="mcaUiModalTitle">' +
                    '<div class="mca-ui-modal__icon" id="mcaUiModalIcon" aria-hidden="true"></div>' +
                    '<h2 class="mca-ui-modal__title" id="mcaUiModalTitle"></h2>' +
                    '<p class="mca-ui-modal__message" id="mcaUiModalMessage"></p>' +
                    '<div class="mca-ui-modal__actions" id="mcaUiModalActions"></div>' +
                '</div>' +
            '</div>' +
            '<div id="mcaUiToastHost" class="mca-ui-toast-host" aria-live="polite" aria-atomic="true"></div>';

        document.body.appendChild(wrap);
        bindModalEvents();
    }

    function bindModalEvents() {
        var modal = document.getElementById('mcaUiModal');
        if (!modal || modal.dataset.bound) return;
        modal.dataset.bound = '1';

        modal.addEventListener('click', function (e) {
            if (e.target.matches('[data-mca-modal-close]')) {
                closeModal(false);
            }
        });

        document.addEventListener('keydown', function (e) {
            if (modal.hidden) return;
            if (e.key === 'Escape') {
                e.preventDefault();
                closeModal(false);
            }
        });
    }

    function setModalIcon(type) {
        var el = document.getElementById('mcaUiModalIcon');
        if (!el) return;
        el.className = 'mca-ui-modal__icon mca-ui-modal__icon--' + (type || 'info');
        el.innerHTML = ICONS[type] || ICONS.info;
    }

    function openModal(opts) {
        ensureShell();
        var modal = document.getElementById('mcaUiModal');
        var titleEl = document.getElementById('mcaUiModalTitle');
        var messageEl = document.getElementById('mcaUiModalMessage');
        var actionsEl = document.getElementById('mcaUiModalActions');

        state.lastFocus = document.activeElement;
        setModalIcon(opts.type || 'info');
        titleEl.textContent = opts.title || '';
        messageEl.textContent = opts.message || '';
        actionsEl.innerHTML = '';

        (opts.buttons || []).forEach(function (btn) {
            var button = document.createElement('button');
            button.type = 'button';
            button.textContent = btn.label;
            button.className = btn.className || 'mca-ui-btn mca-perm-btn mca-perm-btn--secondary';
            button.addEventListener('click', function () {
                closeModal(btn.value);
            });
            actionsEl.appendChild(button);
        });

        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('mca-ui-modal-open');

        var firstBtn = actionsEl.querySelector('button');
        if (firstBtn) firstBtn.focus();
    }

    function closeModal(value) {
        var modal = document.getElementById('mcaUiModal');
        if (!modal || modal.hidden) return;

        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('mca-ui-modal-open');

        if (state.modalResolve) {
            var resolve = state.modalResolve;
            state.modalResolve = null;
            resolve(value);
        }

        if (state.lastFocus && typeof state.lastFocus.focus === 'function') {
            state.lastFocus.focus();
        }
    }

    function alert(opts) {
        return new Promise(function (resolve) {
            state.modalResolve = resolve;
            openModal({
                type: opts.type || 'info',
                title: opts.title || t('alert_title', 'Bilgi'),
                message: opts.message || '',
                buttons: [{
                    label: opts.confirmText || t('ok', 'Tamam'),
                    className: 'mca-ui-btn mca-perm-btn mca-perm-btn--primary',
                    value: true,
                }],
            });
        });
    }

    function confirm(opts) {
        return new Promise(function (resolve) {
            state.modalResolve = resolve;
            openModal({
                type: opts.danger ? 'warning' : 'question',
                title: opts.title || t('confirm_title', 'Emin misiniz?'),
                message: opts.message || '',
                buttons: [
                    {
                        label: opts.cancelText || t('cancel', 'İptal'),
                        className: 'mca-ui-btn mca-perm-btn mca-perm-btn--secondary',
                        value: false,
                    },
                    {
                        label: opts.confirmText || t('confirm', 'Onayla'),
                        className: 'mca-ui-btn mca-perm-btn ' + (opts.danger ? 'mca-perm-btn--danger' : 'mca-perm-btn--primary'),
                        value: true,
                    },
                ],
            });
        });
    }

    function toast(message, type, duration) {
        ensureShell();
        var host = document.getElementById('mcaUiToastHost');
        if (!host || !message) return;

        type = type || 'info';
        duration = duration == null ? 4200 : duration;

        var el = document.createElement('div');
        el.className = 'mca-ui-toast mca-ui-toast--' + type;
        el.innerHTML =
            '<span class="mca-ui-toast__icon" aria-hidden="true">' + (ICONS[type] || ICONS.info) + '</span>' +
            '<span class="mca-ui-toast__text"></span>' +
            '<button type="button" class="mca-ui-toast__close" aria-label="' + t('close', 'Kapat') + '">&times;</button>';
        el.querySelector('.mca-ui-toast__text').textContent = message;

        var remove = function () {
            el.classList.add('is-leaving');
            setTimeout(function () { el.remove(); }, 180);
        };

        el.querySelector('.mca-ui-toast__close').addEventListener('click', remove);
        host.appendChild(el);

        if (duration > 0) {
            setTimeout(remove, duration);
        }
    }

    function initConfirmForms() {
        document.querySelectorAll('form[data-mca-confirm]').forEach(function (form) {
            if (form.dataset.mcaConfirmBound) return;
            form.dataset.mcaConfirmBound = '1';

            form.addEventListener('submit', function (e) {
                if (form.dataset.mcaConfirmed === '1') {
                    form.dataset.mcaConfirmed = '0';
                    return;
                }

                e.preventDefault();
                var message = form.getAttribute('data-mca-confirm') || '';
                var title = form.getAttribute('data-mca-confirm-title') || t('confirm_title', 'Emin misiniz?');
                var danger = form.getAttribute('data-mca-confirm-danger') !== '0';
                var confirmText = form.getAttribute('data-mca-confirm-text') || t('confirm', 'Onayla');

                confirm({ title: title, message: message, danger: danger, confirmText: confirmText }).then(function (ok) {
                    if (!ok) return;
                    form.dataset.mcaConfirmed = '1';
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                });
            });
        });
    }

    function initFlashQueue() {
        var queue = document.getElementById('mcaUiFlashQueue');
        if (!queue) return;

        queue.querySelectorAll('[data-message]').forEach(function (item) {
            toast(item.getAttribute('data-message'), item.getAttribute('data-type') || 'info');
        });

        queue.remove();
    }

    function init() {
        ensureShell();
        initConfirmForms();
        initFlashQueue();
    }

    global.McaUi = {
        alert: alert,
        confirm: confirm,
        toast: toast,
        openModal: openModal,
        closeModal: closeModal,
        init: init,
        initConfirmForms: initConfirmForms,
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(window);
