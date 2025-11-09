// InterHop — wire "account[...]" à l'instant T du POST, sans miroirs DOM
/*(function(){
    'use strict';
    if (!/\/account(\/edit)?$/.test(location.pathname)) return;
    if (!window.jQuery) return;

    function val(sel){ var el=document.querySelector(sel); return el ? (el.value||'').trim() : ''; }

    // Extrait les champs plats existants (ton thème)
    function snapshotFlat(){
        return {
            id:            val('#id') || val('[name="id"]'),
            first_name:    val('#first-name') || val('[name="first_name"]'),
            last_name:     val('#last-name')  || val('[name="last_name"]'),
            email:         val('#email')      || val('[name="email"]'),
            username:      val('#username')   || val('[name="settings[username]"]') || val('[name="username"]'),
            max_patients:  (function(){
                var s = val('#interhop-max-patients'); if (s==='') return '';
                var n = parseInt(s,10); return (Number.isFinite(n)&&n>=1) ? String(n) : '';
            })()
        };
    }

    // Patch temporaire de $.ajax le temps d’UN appel
    var iv = setInterval(function(){
        if (!(window.App && App.Pages && App.Pages.Account && typeof App.Pages.Account.save === 'function')) return;
        clearInterval(iv);

        var origSave = App.Pages.Account.save;
        App.Pages.Account.save = function(){
            var $ = window.jQuery;
            var _ajax = $.ajax;

            $.ajax = function(opts){
                try {
                    if (opts && /\/account\/save(?:\?|$)/.test(opts.url||'') && typeof opts.data === 'string') {
                        var snap = snapshotFlat();

                        // On AJOUTE uniquement ; on ne remplace jamais la data existante
                        var extra = '';

                        // Bloc attendu par le core
                        if (snap.first_name) extra += '&' + encodeURIComponent('account[first_name]') + '=' + encodeURIComponent(snap.first_name);
                        if (snap.last_name)  extra += '&' + encodeURIComponent('account[last_name]')  + '=' + encodeURIComponent(snap.last_name);
                        if (snap.email)      extra += '&' + encodeURIComponent('account[email]')      + '=' + encodeURIComponent(snap.email);
                        if (snap.username)   extra += '&' + encodeURIComponent('account[settings][username]') + '=' + encodeURIComponent(snap.username);
                        if (snap.id)         extra += '&' + encodeURIComponent('account[id]')         + '=' + encodeURIComponent(snap.id);

                        // Notre feature 22
                        extra += '&' + encodeURIComponent('raw[interhop_max_patients]') + '=' + encodeURIComponent(snap.max_patients || '');

                        // On append (sans écraser)
                        opts.data = (opts.data || '') + extra;
                    }
                } catch(_) {}
                var res = _ajax.apply(this, arguments);
                $.ajax = _ajax; // on remet aussitôt l’original
                return res;
            };

            return origSave.apply(this, arguments);
        };
    }, 50);
})();*/
