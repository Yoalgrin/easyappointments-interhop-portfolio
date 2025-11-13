/*!
 * InterHop – Account override (modulaire)
 *
 * Page /account (ou /account/edit) :
 *  - Ajoute le champ "Limite de patients"
 *  - Préremplit via GET /interhop/providerslimit/get (self)
 *  - Injecte interhop[max_patients] dans le POST /account/save
 *  - Filet de sécurité : après succès de /account/save, upsert via
 *    POST /interhop/providerslimit/upsert (avec CSRF)
 */

(function () {
    'use strict';
    if (!/\/account(\/edit)?$/.test(location.pathname)) return;

    var __IH_SELF_PROVIDER_ID = null;

    /* ---------------------------- Utils ---------------------------- */

    function baseUrl(p) {
        var base = (window.BASE_URL || (window.App && App.Vars && App.Vars.base_url) || '/');
        return base.replace(/\/?$/, '/') + String(p).replace(/^\/+/, '');
    }

    function findForm() {
        return (
            document.querySelector('#account') ||
            document.querySelector('form[action*="/account/save"]') ||
            document.querySelector('form')
        );
    }

    function readLimit() {
        var el = document.getElementById('interhop-max-patients');
        if (!el) return '';
        var s = (el.value || '').trim();
        if (s === '') return ''; // vide = illimité (NULL côté DB)
        var n = parseInt(s, 10);
        return Number.isFinite(n) && n >= 1 ? String(n) : '1';
    }

    // ---- CSRF helpers (CI3) ----
    function getCsrfPair() {
        // Essaye d'abord App.Vars (souvent exposé par EA)
        var name = (window.App && App.Vars && App.Vars.csrf_token_name) || null;
        var hash = (window.App && App.Vars && App.Vars.csrf_hash) || null;

        // Fallback cookie (si exposé) : nom par défaut souvent "csrf_cookie" ou config custom
        if (!hash) {
            var cookieName =
                (window.App && App.Vars && App.Vars.csrf_cookie_name) ||
                'csrf_cookie';
            var m = document.cookie.match(new RegExp('(?:^|; )' + cookieName.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '=([^;]*)'));
            if (m) {
                try { hash = decodeURIComponent(m[1]); } catch (_) { hash = m[1]; }
            }
        }

        // Nom du champ POST attendu
        if (!name) {
            name =
                (window.App && App.Vars && App.Vars.csrf_token_name) ||
                (window.csrf_token_name) ||
                'csrf_token';
        }

        return { name: name, hash: hash };
    }

    /* ---------------------------- UI ---------------------------- */

    function ensureUI() {
        if (document.getElementById('interhop-max-patients')) return;

        var form = findForm();
        if (!form) return;

        var host = form.querySelector('.card-body, .container, .row, .col, fieldset') || form;

        var wrap = document.createElement('div');
        wrap.className = 'form-group';
        wrap.innerHTML =
            '<label for="interhop-max-patients">Limite de patients</label>' +
            '<input id="interhop-max-patients" type="number" min="1" class="form-control" placeholder="ex: 100">' +
            '<small class="form-text text-muted">Laisser vide pour illimité</small>';
        host.appendChild(wrap);

        // Préremplissage self
        try {
            fetch(baseUrl('interhop/providerslimit/get'), { credentials: 'same-origin' })
                .then(function (r) { return r && r.ok ? r.json() : null; })
                .then(function (j) {
                    if (!j || j.success !== true) return;
                    __IH_SELF_PROVIDER_ID = (j.data && j.data.provider_id) || null;

                    var v = j.data && (j.data.max_patients ?? j.data.max_patient);
                    var el = document.getElementById('interhop-max-patients');
                    if (!el) return;

                    el.value =
                        v == null || v === '' || isNaN(parseInt(v, 10))
                            ? ''
                            : String(parseInt(v, 10));
                })
                .catch(function () {});
        } catch (_) {}
    }

    /* ------------------------ AJAX patch ------------------------- */

    function patchAjaxOnce() {
        if (!window.jQuery) return false;
        if (window.__ih_patient_limit_ajax_patched) return true;
        window.__ih_patient_limit_ajax_patched = true;

        var $ = window.jQuery;
        var origAjax = $.ajax;

        $.ajax = function (opts) {
            try {
                var url = String((opts && opts.url) || '');
                if (/\/account\/save(?:\?|$)/i.test(url)) {
                    var limit = readLimit(); // '' = illimité

                    // 1) Injecte interhop[max_patients] dans la charge utile
                    if (opts.data instanceof FormData) {
                        if (!opts.data.has('interhop[max_patients]')) {
                            opts.data.append('interhop[max_patients]', limit);
                        }
                    } else if (typeof opts.data === 'string') {
                        var encKey = encodeURIComponent('interhop[max_patients]');
                        var re = new RegExp('(?:^|&)' + encKey.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '=');
                        if (!re.test(opts.data)) {
                            opts.data += (opts.data ? '&' : '') + encKey + '=' + encodeURIComponent(limit);
                        }
                    } else if (opts.data && typeof opts.data === 'object') {
                        if (!('interhop[max_patients]' in opts.data)) {
                            opts.data['interhop[max_patients]'] = limit;
                        }
                    } else {
                        opts.data = encodeURIComponent('interhop[max_patients]') + '=' + encodeURIComponent(limit);
                    }

                    // 2) Filet de sécurité : upsert après succès du /account/save (avec CSRF)
                    var origSuccess = opts.success;
                    opts.success = function (res, status, xhr) {
                        try {
                            var pid = __IH_SELF_PROVIDER_ID;
                            if (!pid && res && typeof res === 'object') {
                                pid = res.id || pid;
                            }

                            if (pid) {
                                var fd = new FormData();
                                fd.append('provider_id', String(pid));
                                fd.append('max_patients', limit); // '' => NULL côté contrôleur

                                // CSRF (indispensable sinon 403)
                                var csrf = getCsrfPair();
                                if (csrf && csrf.name && csrf.hash) {
                                    fd.append(csrf.name, csrf.hash);
                                }

                                fetch(baseUrl('interhop/providerslimit/upsert'), {
                                    method: 'POST',
                                    credentials: 'same-origin',
                                    body: fd
                                }).catch(function () { /* best-effort */ });
                            } else {
                                try { console.warn('[InterHop] upsert ignoré : provider_id self introuvable.'); } catch (_) {}
                            }
                        } catch (e) {}

                        if (typeof origSuccess === 'function') return origSuccess.apply(this, arguments);
                    };

                    try { console.debug('[InterHop] account: inject interhop[max_patients] →', limit); } catch (_) {}
                }
            } catch (_) {}

            return origAjax.apply(this, arguments);
        };

        try { console.debug('[InterHop] patient-limit: $.ajax patched'); } catch (_) {}
        return true;
    }

    function patchSaveOnce() {
        if (!window.App || !App.Pages || !App.Pages.Account || !App.Pages.Account.save) return false;
        if (App.Pages.Account.__ih_patient_limit_save_patched) return true;
        App.Pages.Account.__ih_patient_limit_save_patched = true;

        var orig = App.Pages.Account.save;
        App.Pages.Account.save = function () {
            // Rien à faire ici : l’injection + upsert sont gérés dans $.ajax
            return orig.apply(this, arguments);
        };

        try { console.debug('[InterHop] patient-limit: Account.save patched'); } catch (_) {}
        return true;
    }

    /* --------------------------- Boot --------------------------- */

    document.addEventListener('DOMContentLoaded', ensureUI);

    (function wait() {
        var n = 0, t = setInterval(function () {
            var ok1 = patchAjaxOnce();
            var ok2 = patchSaveOnce();
            if ((ok1 && ok2) || ++n > 60) clearInterval(t);
        }, 100);
    })();
})();
