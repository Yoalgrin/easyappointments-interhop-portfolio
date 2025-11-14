/*!
 * InterHop – Providers UI override (Admin)
 * - Champ "Limite de patients" + bouton "Sauvegarder" dans le formulaire de profil soignant.
 * - GET /interhop/providerslimit/get/{ID} (fallback ?provider_id=ID) pour hydrater.
 * - POST /interhop/providerslimit/upsert pour enregistrer.
 * - PID robuste: #providers input[name="id"] -> .provider-row.selected[data-id] -> window.__IH_CURRENT_PROVIDER_ID__
 * - Aucun wrap réseau du core : on écoute juste les clics existants (liste, éditer, annuler, ajouter).
 */

(function () {
    if (window.__IH_PROVIDERS_UI_OVERRIDE_SIMPLE__) return;
    window.__IH_PROVIDERS_UI_OVERRIDE_SIMPLE__ = true;

    var $ = window.jQuery || window.$;
    if (!$) return;

    // -------------------------------------------------
    // Sélecteurs & constantes
    // -------------------------------------------------
    var SEL = {
        page: '#providers',
        form: '#providers .record-details form',
        recordDetails: '#providers .record-details',
        idHidden: '#providers input[name="id"]',
        row: '.provider-row',
        editBtn: '#edit-provider',
        cancelBtn: '#cancel-provider',
        addBtn: '#add-provider'
    };

    // état global : est-ce qu’on considère le form en édition ?
    var __ihEditing = false;

    function t(key) {
        var map = {
            title: 'Limite de patients',
            placeholder: 'Laissez vide ou 0 pour illimité',
            save: 'Sauvegarder',
            saving: 'Sauvegarde…',
            saved: 'Valeur enregistrée',
            error: 'Erreur lors de la sauvegarde',
            no_provider: 'Aucun soignant sélectionné'
        };
        return (window.EALang && EALang[key]) ? EALang[key] : (map[key] || key);
    }

    function siteUrl(path) {
        try {
            if (window.App && App.Utils && App.Utils.Url) {
                return App.Utils.Url.siteUrl(path);
            }
        } catch (_) {}
        if (path.charAt(0) !== '/') path = '/' + path;
        return path;
    }

    // -------------------------------------------------
    // URL fallbacks (gère /index.php/)
    // -------------------------------------------------
    function ciUrlCandidates(path) {
        var out = [];
        try {
            if (window.App && App.Utils && App.Utils.Url && typeof App.Utils.Url.siteUrl === 'function') {
                out.push(App.Utils.Url.siteUrl(path)); // ex: http://localhost/index.php/...
            }
        } catch (_) {}
        out.push('/index.php/' + path);
        out.push('/' + path);
        return out.filter(function (v, i, a) { return v && a.indexOf(v) === i; });
    }

    function ajaxPostWithCiFallback(path, data) {
        var urls = ciUrlCandidates(path);
        var d = $.Deferred();
        (function tryNext(i) {
            if (i >= urls.length) {
                d.reject({ status: 404, message: 'All POST candidates failed' });
                return;
            }
            $.ajax({
                url: urls[i],
                method: 'POST',
                data: data,
                headers: csrfHeaders()
            }).then(d.resolve, function (xhr) {
                if (xhr && (xhr.status === 404 || xhr.status === 405)) {
                    tryNext(i + 1);
                } else {
                    d.reject(xhr);
                }
            });
        })(0);
        return d.promise();
    }

    function ajaxGetJsonWithCiFallback(path, query) {
        var urls = ciUrlCandidates(path);
        var d = $.Deferred();
        (function tryNext(i) {
            if (i >= urls.length) {
                d.reject({ status: 404, message: 'All GET candidates failed' });
                return;
            }
            $.getJSON(urls[i], query).then(d.resolve, function (xhr) {
                if (xhr && (xhr.status === 404 || xhr.status === 405)) {
                    tryNext(i + 1);
                } else {
                    d.reject(xhr);
                }
            });
        })(0);
        return d.promise();
    }

    // -------------------------------------------------
    // CSRF helpers (header + champ POST)
    // -------------------------------------------------
    function getCookie(name) {
        var all = document.cookie ? document.cookie.split(/;\s*/) : [];
        for (var i = 0; i < all.length; i++) {
            var pair = all[i].split('=');
            var key = decodeURIComponent(pair[0]);
            if (key === name) {
                return decodeURIComponent(pair.slice(1).join('=') || '');
            }
        }
        return '';
    }

    function csrfValue() {
        try {
            if (typeof vars === 'function') {
                var v = vars('csrf_token'); if (v) return v;
                var cName = vars('csrf_cookie_name') || 'csrf_cookie';
                var fromCookie = getCookie(cName); if (fromCookie) return fromCookie;
            }
        } catch (_) {}
        return getCookie('csrf_cookie'); // fallback générique
    }

    function csrfFieldName() {
        try { return (typeof vars === 'function' && vars('csrf_token_name')) || 'csrf_token'; }
        catch (_) { return 'csrf_token'; }
    }

    function csrfHeaders() {
        var v = csrfValue();
        return v ? { 'X-CSRF-TOKEN': v } : {};
    }

    $.ajaxSetup({
        beforeSend: function (xhr, settings) {
            var method = (settings.type || settings.method || 'GET').toUpperCase();
            if (method !== 'GET') {
                var h = csrfHeaders();
                Object.keys(h).forEach(function (k) { xhr.setRequestHeader(k, h[k]); });
            }
        }
    });

    // -------------------------------------------------
    // PID robuste
    // -------------------------------------------------
    function getPid() {
        // 1) hidden name="id"
        var el = document.querySelector(SEL.idHidden);
        if (el && el.value) {
            var n1 = parseInt(String(el.value).trim(), 10);
            if (Number.isFinite(n1) && n1 > 0) return n1;
        }

        // 2) ligne sélectionnée côté liste
        var selRow = document.querySelector('.provider-row.selected');
        if (selRow && selRow.getAttribute('data-id')) {
            var n2 = parseInt(selRow.getAttribute('data-id'), 10);
            if (Number.isFinite(n2) && n2 > 0) return n2;
        }

        // 3) fallback global éventuel
        var g = window.__IH_CURRENT_PROVIDER_ID__;
        if (g) {
            var n3 = parseInt(g, 10);
            if (Number.isFinite(n3) && n3 > 0) return n3;
        }

        return null;
    }

    // -------------------------------------------------
    // État : activer/désactiver notre champ et bouton
    // -------------------------------------------------
    function setEnabled(editing) {
        __ihEditing = !!editing;
        var input = document.getElementById('interhop-max-patients');
        var btn = document.getElementById('ih-max-patients-save');
        if (!input || !btn) return;
        input.disabled = !__ihEditing;
        btn.disabled = !__ihEditing;
    }

    // -------------------------------------------------
    // Lecture/saisie valeur
    // -------------------------------------------------
    function readValue() {
        var el = document.getElementById('interhop-max-patients');
        if (!el) return '';
        var raw = (el.value || '').trim();
        if (raw === '' || raw === '0') return ''; // '' = illimité (NULL côté PHP)
        var n = parseInt(raw, 10);
        return Number.isFinite(n) && n >= 1 ? String(n) : '';
    }

    // -------------------------------------------------
    // Hydratation (GET)
    // -------------------------------------------------
    function hydrateForCurrent() {
        var pid = getPid();
        if (!pid) { return; }

        ajaxGetJsonWithCiFallback('interhop/providerslimit/get/' + pid, null)
            .then(applyHydration)
            .fail(function () {
                ajaxGetJsonWithCiFallback('interhop/providerslimit/get', { provider_id: pid })
                    .then(applyHydration)
                    .fail(function (xhr) { console.error('[IH] hydrate error', xhr); });
            });

        function applyHydration(r) {
            try {
                var val = '';
                if (r && typeof r === 'object' && r.data && typeof r.data === 'object') {
                    var mp = r.data.max_patients;
                    if (mp !== null && mp !== undefined) {
                        var n = parseInt(mp, 10);
                        if (Number.isFinite(n) && n >= 0) val = String(n);
                    }
                }
                var input = document.getElementById('interhop-max-patients');
                if (input) input.value = val;
            } catch (_) {}
        }
    }

    // -------------------------------------------------
    // UI : injection champ + bouton (dans le FORM profil)
    // -------------------------------------------------
    function injectFieldOnce() {
        if (document.getElementById('interhop-max-patients-block')) return true;

        var host = document.querySelector(SEL.form) ||
            document.querySelector(SEL.recordDetails) ||
            document.querySelector('#providers form'); // dernier secours

        if (!host) return false;

        var block = document.createElement('div');
        block.id = 'interhop-max-patients-block';
        block.className = 'form-group mt-3';

        block.innerHTML =
            '<label for="interhop-max-patients" class="form-label d-block">' + t('title') + '</label>' +
            '<div class="d-flex align-items-center" style="gap:8px;max-width:420px;">' +
            '<input type="number" min="0" step="1" id="interhop-max-patients" class="form-control" ' +
            'placeholder="' + t('placeholder') + '" />' +
            '<button type="button" id="ih-max-patients-save" class="btn btn-primary">' + t('save') + '</button>' +
            '</div>' +
            '<div id="ih-max-patients-flash" class="small mt-1"></div>';

        host.appendChild(block);
        bindSave();
        setEnabled(false); // par défaut : lecture seule
        return true;
    }

    function setFlash(msg, ok) {
        var el = document.getElementById('ih-max-patients-flash');
        if (!el) return;
        el.textContent = msg || '';
        el.style.color = ok ? '#0a7d28' : '#b00020';
        if (msg) setTimeout(function () { el.textContent = ''; }, 1800);
    }

    // -------------------------------------------------
    // Sauvegarde (POST)
    // -------------------------------------------------
    function bindSave() {
        var btn = document.getElementById('ih-max-patients-save');
        if (!btn || btn.__ihBound) return;

        btn.addEventListener('click', function () {
            var pid = getPid();
            if (!pid) { setFlash(t('no_provider'), false); return; }

            var value = readValue();

            btn.disabled = true;
            var old = btn.textContent;
            btn.textContent = t('saving');
            setFlash('', true);

            var data = { provider_id: pid, max_patients: value };
            data[csrfFieldName()] = csrfValue();

            ajaxPostWithCiFallback('interhop/providerslimit/upsert', data)
                .then(function () {
                    setFlash(t('saved'), true);
                    hydrateForCurrent();
                })
                .fail(function (xhr) {
                    console.error('[IH] save error', xhr);
                    setFlash(t('error') + (xhr && xhr.status ? ' (' + xhr.status + ')' : ''), false);
                })
                .always(function () {
                    btn.textContent = old;
                    // on remet l'état “logique” (édition ou non)
                    setEnabled(__ihEditing);
                });
        });

        btn.__ihBound = true;
    }

    // -------------------------------------------------
    // Triggers (sélection / éditer / annuler / nouveau + mutation DOM)
    // -------------------------------------------------
    function wireHydrationTriggers() {
        var $page = $(SEL.page);

        // Sélection : form en lecture seule
        $page.on('click', SEL.row, function () {
            setTimeout(function () {
                injectFieldOnce();
                __ihEditing = false;
                setEnabled(false);
                hydrateForCurrent();
            }, 0);
        });

        // Éditer : on FORCE l’édition à true pour notre champ
        $page.on('click', SEL.editBtn, function () {
            setTimeout(function () {
                injectFieldOnce();
                __ihEditing = true;
                setEnabled(true);     // <- ON FORCE enabled ici
                hydrateForCurrent();
            }, 0);
        });

        // Annuler : retour en lecture seule
        $page.on('click', SEL.cancelBtn, function () {
            setTimeout(function () {
                injectFieldOnce();
                __ihEditing = false;
                setEnabled(false);
                hydrateForCurrent();
            }, 0);
        });

        // Nouveau : champ vide en mode édition
        $page.on('click', SEL.addBtn, function () {
            setTimeout(function () {
                injectFieldOnce();
                __ihEditing = true;
                var input = document.getElementById('interhop-max-patients');
                if (input) input.value = '';
                setEnabled(true);
            }, 0);
        });

        // Le core réécrit parfois la zone détails → on ré-applique notre état
        var details = document.querySelector(SEL.recordDetails);
        if (details && !details.__ihObserved) {
            details.__ihObserved = true;
            var mo = new MutationObserver(function () {
                clearTimeout(details.__ihDebounce);
                details.__ihDebounce = setTimeout(function () {
                    injectFieldOnce();
                    setEnabled(__ihEditing);
                    hydrateForCurrent();
                }, 50);
            });
            mo.observe(details, { childList: true, subtree: true });
        }
    }

    // -------------------------------------------------
    // Boot
    // -------------------------------------------------
    $(function () {
        injectFieldOnce();          // crée le bloc dans le form profil
        setEnabled(false);          // initialement : lecture seule
        wireHydrationTriggers();    // écoute les clics existants

        setTimeout(function () {
            hydrateForCurrent();    // premier passage si un record est déjà affiché
        }, 0);
    });

})();
