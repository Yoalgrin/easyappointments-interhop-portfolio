/*!
 * InterHop – Providers HTTP override (Admin)
 *
 * Philosophie : comme pour /account, on ne bidouille pas le réseau.
 * On laisse App.Http.Providers.find(...) tel quel et on prépare les données
 * juste avant l’affichage : wrap UNIQUE sur App.Pages.Providers.display.
 *
 * Ce que fait ce fichier :
 *  - (optionnel) Normaliser search(...) → Array (sans impact fonctionnel)
 *  - Wrap de App.Pages.Providers.display :
 *      • clone sûr de `provider`
 *      • p.settings forcé en objet + champs JSON en CHAÎNES valides (parseables)
 *      • p.services forcé en Array
 *      • miroir identité : racine <-> user.* (couvre toutes les variantes du core)
 *      • appel du display core avec le clone prêt
 *
 * Ce que ce fichier NE fait PAS :
 *  - Pas de patch global $.ajax / fetch / XHR
 *  - Pas d’override de App.Http.Providers.find
 *  - Pas de fill DOM
 */

// --- InterHop compat: certains cores lisent window.$maxPatients dans providers.js ---
// DOIT exister avant que providers.js s'exécute, sinon display() plante.
(function ensureLegacyMaxPatientsVar(){
    try {
        if (window.jQuery && !window.$maxPatients) {
            // Input factice, jamais visible, juste pour satisfaire le core
            window.$maxPatients = window.jQuery('<input type="number" id="max-patients-legacy" style="display:none">');
        }
    } catch (_) {}
})();

(function () {
    if (window.__IH_PROVIDERS_HTTP_OVERRIDE__) return;
    window.__IH_PROVIDERS_HTTP_OVERRIDE__ = true;

    /* -------------------- Helpers -------------------- */
    function deepCloneSafe(obj) {
        try { return JSON.parse(JSON.stringify(obj)); } catch (_) { return obj || {}; }
    }

    function toArray(maybe) {
        if (Array.isArray(maybe)) return maybe;
        if (maybe && typeof maybe === 'object') { try { return Object.values(maybe); } catch(_){} }
        return [];
    }

    function asValidJsonString(val, fallbackJson) {
        // On veut toujours retourner une CHAÎNE JSON parseable
        if (typeof val === 'string') {
            var s = val.trim();
            try { JSON.parse(s); return s; } catch (_) { return fallbackJson; }
        }
        try {
            // si val est déjà un objet/array, on le stringify
            return JSON.stringify(val ?? JSON.parse(fallbackJson));
        } catch (_) {
            return fallbackJson;
        }
    }

    function mirrorIdentity(p) {
        if (!p.user || typeof p.user !== 'object') p.user = {};
        var keys = [
            'id','first_name','last_name','email','phone_number','alt_number',
            'address','city','state','zip_code','timezone','language','notes',
            'id_roles','is_private','ldap_dn','username'
        ];
        // user.* -> racine si racine vide
        keys.forEach(function(k){
            if ((p[k] == null || p[k] === '') && p.user[k] != null) {
                p[k] = p.user[k];
            }
        });
        // racine -> user.* si user.* vide
        keys.forEach(function(k){
            if ((p.user[k] == null || p.user[k] === '') && p[k] != null) {
                p.user[k] = p[k];
            }
        });
    }

    /* -------------------- (Optionnel) search → Array -------------------- */
    if (window.App && App.Http && App.Http.Providers && typeof App.Http.Providers.search === 'function') {
        var __origSearch = App.Http.Providers.search;
        App.Http.Providers.search = function (keyword, limit, offset, orderBy) {
            var p = __origSearch.call(App.Http.Providers, keyword, limit, offset, orderBy);
            return p.then(function (resp) {
                try {
                    var r = (typeof resp === 'string') ? JSON.parse(resp) : resp;
                    if (Array.isArray(r)) return r;
                    if (r && Array.isArray(r.data)) return r.data;
                    if (r && Array.isArray(r.results)) return r.results;
                    if (r && Array.isArray(r.providers)) return r.providers;
                    if (r && typeof r === 'object' && r.success && r.data && Array.isArray(r.data.list)) return r.data.list;
                    var vals = r && typeof r === 'object' ? Object.values(r) : [];
                    if (vals.length && vals.every(function (x){ return typeof x === 'object'; })) return vals;
                } catch (_) {}
                return [];
            }, function () { return []; });
        };
    }

    /* -------------------- Préparation avant affichage -------------------- */
    function prepareForDisplay(providerIn) {
        // Clone pour ne PAS muter l'objet d'origine
        var raw = (providerIn && typeof providerIn === 'object') ? providerIn : {};
        var p = deepCloneSafe(raw);

        if (!p.settings || typeof p.settings !== 'object') p.settings = {};
        var s = p.settings;

        // Champs potentiellement JSON — on force des chaînes parseables SANS supposer l'existence
        s.working_plan = asValidJsonString(
            (s.working_plan != null ? s.working_plan : '{}'),
            '{}'
        );
        s.working_plan_exceptions = asValidJsonString(
            (s.working_plan_exceptions != null ? s.working_plan_exceptions : '[]'),
            '[]'
        );

        if ('schedule' in s) {
            s.schedule = asValidJsonString(
                (s.schedule != null ? s.schedule : '{}'),
                '{}'
            );
        }
        if ('schedule_exceptions' in s) {
            s.schedule_exceptions = asValidJsonString(
                (s.schedule_exceptions != null ? s.schedule_exceptions : '[]'),
                '[]'
            );
        }

        // Services → tableau sûr
        p.services = toArray(p.services);

        // Miroir identité (racine <-> user.*)
        mirrorIdentity(p);

        return p;
    }

    /* -------------------- Wrap display (unique) -------------------- */
    function wrapDisplayOnce() {
        if (!(window.App && App.Pages && App.Pages.Providers && typeof App.Pages.Providers.display === 'function')) return false;
        if (App.Pages.Providers.__IH_display_wrapped__) return true;
        App.Pages.Providers.__IH_display_wrapped__ = true;

        var __origDisplay = App.Pages.Providers.display;
        App.Pages.Providers.display = function (provider) {
            try {
                // sécurité : garantir aussi ici l'existence de $maxPatients
                try {
                    if (window.jQuery && !window.$maxPatients) {
                        window.$maxPatients = window.jQuery('<input type="number" id="max-patients-legacy" style="display:none">');
                    }
                } catch (_) {}

                var p = prepareForDisplay(provider);

                // +++ PATCH: mémoriser l'ID soignant courant +++
                try {
                    window.__IH_CURRENT_PROVIDER_ID__ =
                        (p && (p.id || p.provider_id || (p.user && (p.user.id || p.user.user_id)))) || null;
                } catch (_) { /* noop */ }

                // affichage core
                return __origDisplay.call(this, p);

            } catch (e) {
                console.error('[IH] display guard (fallback to core args)', e);
                return __origDisplay.apply(this, arguments);
            }
        };
        return true;
    }

    // wrap
    if (!wrapDisplayOnce()) {
        window.addEventListener('load', wrapDisplayOnce, { once: true });
    }
    // -----------------------------------------------------------------------------
// InterHop – Patch JSON pour App.Http.Providers.find
// - Certains chemins de réponse renvoient une STRING JSON.
// - Le core EasyAppointments attend un OBJET provider.
// - Ce wrapper garantit que find(...) renvoie toujours un objet.
// -----------------------------------------------------------------------------
    (function () {
        if (!window.App || !App.Http || !App.Http.Providers) {
            return;
        }

        // Évite de patcher plusieurs fois si le fichier est rechargé
        if (window.__IH_PROVIDERS_FIND_JSON_FIX__) {
            return;
        }
        window.__IH_PROVIDERS_FIND_JSON_FIX__ = true;

        var originalFind = App.Http.Providers.find;

        App.Http.Providers.find = function (id) {
            var result = originalFind.call(this, id);

            // Comportement natif : une Promise
            if (result && typeof result.then === 'function') {
                return result.then(function (provider) {
                    if (typeof provider === 'string') {
                        try {
                            var parsed = JSON.parse(provider);
                            // Debug facultatif :
                            // console.log('[InterHop] Providers.find: string JSON convertie en objet.', parsed);
                            return parsed;
                        } catch (e) {
                            console.error('[InterHop] Providers.find: JSON.parse échoué sur provider string.', e, provider);
                        }
                    }

                    return provider;
                });
            }

            // Si un jour find() ne renvoie plus une Promise, on laisse tel quel
            return result;
        };
    })();

})();
