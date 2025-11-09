(function () {
    'use strict';
    if (!/\/account(\/edit)?$/.test(location.pathname)) return;

    // -------- utilitaires --------
    function findForm(){
        return document.querySelector('#account')
            || document.querySelector('form[action*="/account/save"]')
            || document.querySelector('form');
    }
    function ensureUI() {
        if (document.getElementById('interhop-max-patients')) return;
        var form = findForm(); if (!form) return;

        // essaie de cibler un conteneur propre si présent, sinon form
        var host = form.querySelector('.card-body, .container, .row, .col, fieldset') || form;

        var wrap = document.createElement('div');
        wrap.className = 'form-group';
        wrap.innerHTML =
            '<label for="interhop-max-patients">Limite de patients</label>' +
            '<input id="interhop-max-patients" type="number" min="1" class="form-control" placeholder="ex: 100">' +
            '<small class="form-text text-muted">Laisser vide pour illimité</small>';
        host.appendChild(wrap);

        // Préremplir sans deviner l'ID (self via session)
        try {
            fetch(baseUrl('interhop/providerslimit/get'), { credentials: 'same-origin' })
                .then(r => r.ok ? r.json() : null)
                .then(j => {
                    if (!j || j.success !== true) return;
                    var v = j.data && (j.data.max_patients ?? j.data.max_patient);
                    var el = document.getElementById('interhop-max-patients');
                    if (!el) return;

                    if (v == null || v === '' || isNaN(parseInt(v,10))) {
                        el.value = ''; // NULL => illimité
                    } else {
                        el.value = String(parseInt(v,10));
                    }
                })
                .catch(()=>{});
        } catch(_) {}
    }
    function baseUrl(p){
          const base = (window.BASE_URL || window.App?.Vars?.base_url || '/');
          return base.replace(/\/?$/, '/') + String(p).replace(/^\/+/, '');
        }
    function readLimit(){
        var el = document.getElementById('interhop-max-patients');
        if (!el) return '';
        var s = (el.value || '').trim();
        if (s === '') return '';
        var n = parseInt(s,10);
        return Number.isFinite(n) && n >= 1 ? String(n) : '1'; // bornage minimal
    }

    // -------- patch: injecter la paire dans l'AJAX réel --------
    function patchAjaxOnce(){
        if (!window.jQuery) return false;
        if (window.__ih_patient_limit_ajax_patched) return true;
        window.__ih_patient_limit_ajax_patched = true;

        var $ = window.jQuery;
        var origAjax = $.ajax;

        $.ajax = function(opts){
            try {
                // cibler uniquement /account/save
                if (opts && /\/account\/save(?:\?|$)/i.test(String(opts.url||''))) {
                    var limit = readLimit(); // '' = illimité
                    // Normaliser data -> y injecter interhop[max_patients]
                    if (opts.data instanceof FormData) {
                        // encodage multi-part
                        if (!opts.data.has('interhop[max_patients]')) {
                            opts.data.append('interhop[max_patients]', limit);
                        }
                    } else if (typeof opts.data === 'string') {
                        // x-www-form-urlencoded (cas courant EA)
                        var encKey = encodeURIComponent('interhop[max_patients]');
                        var re = new RegExp('(?:^|&)'+ encKey.replace(/[.*+?^${}()|[\]\\]/g,'\\$&') + '=');
                        if (!re.test(opts.data)) {
                            opts.data += (opts.data ? '&' : '') + encKey + '=' + encodeURIComponent(limit);
                        }
                    } else if (opts.data && typeof opts.data === 'object') {
                        // objet -> laisser jQuery sérialiser ensuite
                        if (!('interhop[max_patients]' in opts.data)) {
                            opts.data['interhop[max_patients]'] = limit;
                        }
                    } else {
                        // pas de data -> créer une QS minimale
                        opts.data = encodeURIComponent('interhop[max_patients]') + '=' + encodeURIComponent(limit);
                    }

                    try { console.debug('[IH] inject interhop[max_patients] →', limit); } catch(_){}
                }
            } catch(_) {}
            return origAjax.apply(this, arguments);
        };

        console.debug('[IH] patient-limit: $.ajax patched');
        return true;
    }

    // -------- patch: appeler le save d’origine (facultatif) --------
    function patchSaveOnce(){
        if (!window.App?.Pages?.Account?.save) return false;
        if (App.Pages.Account.__ih_patient_limit_save_patched) return true;
        App.Pages.Account.__ih_patient_limit_save_patched = true;

        var orig = App.Pages.Account.save;
        App.Pages.Account.save = function(){
            // rien à faire ici, l’injection se fait dans $.ajax
            return orig.apply(this, arguments);
        };
        console.debug('[IH] patient-limit: Account.save patched');
        return true;
    }

    document.addEventListener('DOMContentLoaded', ensureUI);
    (function wait(){
        var n=0, t=setInterval(function(){
            var ok1 = patchAjaxOnce();
            var ok2 = patchSaveOnce();
            if ((ok1 && ok2) || ++n > 60) clearInterval(t);
        }, 100);
    })();
})();
