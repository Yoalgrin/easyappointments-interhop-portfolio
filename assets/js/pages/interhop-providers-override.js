/*!
 * InterHop - Providers override
 * Admin: ajoute le champ "Limite patients" (lecture seule hors mode édition),
 * et sérialise la valeur lors de la sauvegarde.
 */

// --- I18N bootstrap (évite les erreurs "Cannot find translation...") ---
(function ensureInterHopI18N(){
    // EA expose un objet global EALang + une fonction lang(key)
    window.EALang = window.EALang || {};
    const missing = (k) => typeof window.EALang[k] === 'undefined' || window.EALang[k] === null;

    if (missing('max_patients')) {
        window.EALang['max_patients'] = 'Limite de patients';
    }
    if (missing('max_patients_help')) {
        window.EALang['max_patients_help'] = 'Laisser vide pour illimité';
    }
    if (missing('max_patients_placeholder')) {
        window.EALang['max_patients_placeholder'] = 'ex: 100';
    }

    // safeLang évite d’appeler lang() si la clé manque (et ne spam pas la console)
    window.IH_safeLang = function(key, fallback){
        try {
            if (typeof window.lang === 'function') {
                const v = window.lang(key);
                if (v && v !== key) return v; // valeur traduite
            }
        } catch(e) {}
        return (typeof fallback === 'string') ? fallback : (window.EALang[key] || key);
    };
})();

(function () {
    'use strict';

    // Démarre quand l'environnement App.Pages.Providers est prêt.
    window.addEventListener('load', function () {
        var tries = 0;
        var iv = setInterval(function () {
            tries++;
            if (window.App && App.Pages && App.Pages.Providers && typeof App.Pages.Providers.fillForm === 'function') {
                clearInterval(iv);
                init();
            } else if (tries > 200) {
                clearInterval(iv); // évite une boucle infinie si la page change
            }
        }, 50);
    });

    /**
     * Point d’entrée : branche les hooks UI (injection du champ) et data (sérialisation).
     */
    function init() {
        // Conserve la référence d’origine
        var _fill = App.Pages.Providers.fillForm;

        /**
         * Active/désactive le champ selon le mode (édition ou non).
         * @param {boolean} enabled
         */
        function setEnabled(enabled) {
            var el = document.getElementById('interhop-max-patients');
            if (el) el.disabled = !enabled;
        }

        /**
         * Injecte le groupe de champs si absent.
         * Point d’ancrage : après un form-group existant (ex. #providers form).
         */
        function ensureFieldInjected() {
            if (document.getElementById('interhop-max-patients')) return;

            const $after = $('#last-name').closest('.form-group'); // point d’ancrage stable
            const html = `
    <div class="form-group" id="interhop-max-patients-row">
      <label for="interhop-max-patients">${IH_safeLang('max_patients','Limite de patients')}</label>
      <input id="interhop-max-patients" type="number" min="1" class="form-control"
             placeholder="${IH_safeLang('max_patients_placeholder','ex: 100')}">
      <small class="form-text text-muted">
        ${IH_safeLang('max_patients_help','Laisser vide pour illimité')}
      </small>
    </div>
  `;
            if ($after.length) {
                $(html).insertAfter($after);
            } else {
                // fallback : en fin de formulaire
                ($('#account').length ? $('#account') : $('form:first')).append(html);
            }
        }


        /**
         * Wrapping du remplissage du formulaire provider pour injecter le champ
         * et le valoriser depuis les données du provider.
         */
        App.Pages.Providers.fillForm = function (provider) {
            // Exécution d’origine
            _fill(provider);

            // Injection idempotente du champ
            ensureFieldInjected();

            // Valorisation (lecture seule par défaut)
            var input = document.getElementById('interhop-max-patients');
            if (input) {
                var input = document.getElementById('interhop-max-patients');
                if (input) {
                    var v = '';
                    if (provider) {
                        if (provider.interhop_max_patients != null) {
                            v = provider.interhop_max_patients;
                        } else if (provider.max_patients != null) {
                            v = provider.max_patients;
                        } else if (provider.settings && provider.settings.max_patients != null) {
                            v = provider.settings.max_patients;
                        } else {
                            v = '';
                        }
                    }
                    input.value = (v == null ? '' : String(v));
                }

            }
            setEnabled(false);
        };

        // Gestion des boutons d’édition/annulation (activer/désactiver le champ).
        wireEditModeToggles(setEnabled);

        // Sérialisation étendue à la sauvegarde si la fonction d’origine existe.
        if (typeof App.Pages.Providers.serializeForm === 'function') {
            var _serialize = App.Pages.Providers.serializeForm;
            App.Pages.Providers.serializeForm = function () {
                var payload = _serialize() || {};
                var v = readMaxPatients(); // utilise la même helper que ci-dessus

                // Chemin officiel attendu par le backend: provider[max_patients]
                payload.provider = payload.provider || {};
                payload.provider.max_patients = v; // '' = illimité

                // Fallbacks tolérants (si ton PHP lit ailleurs)
                payload.max_patients = v;
                payload.settings = payload.settings || {};
                payload.settings.max_patients = v;

                return payload;
            };
        } else if (typeof App.Pages.Providers.gatherFormData === 'function') {
            // Certaines versions exposent gatherFormData plutôt que serializeForm
            var _gather = App.Pages.Providers.gatherFormData;
            App.Pages.Providers.gatherFormData = function () {
                var data = _gather() || {};
                var v = readMaxPatients();

                data.provider = data.provider || {};
                data.provider.max_patients = v;

                data.max_patients = v;
                data.settings = data.settings || {};
                data.settings.max_patients = v;

                return data;
            };
        } else {
            // Dernier recours : on s'en remet au hidden name="provider[max_patients]"
            console.info('[IH] Pas de serializeForm/gatherFormData: fallback hidden carrier actif');
        }
    }

    /**
     * Rattache les listeners aux contrôles d’édition/annulation natifs,
     * en couvrant plusieurs sélecteurs selon les templates.
     * @param {(enabled:boolean)=>void} setEnabled
     */
    function wireEditModeToggles(setEnabled) {
        // Boutons "Éditer"
        [
            document.querySelector('#edit-provider'),
            document.querySelector('[data-action="edit"]'),
            document.querySelector('#edit')
        ]
            .filter(Boolean)
            .forEach(function (btn) {
                btn.addEventListener('click', function () {
                    // Micro-délai pour laisser EA basculer en mode édition
                    setTimeout(function () { setEnabled(true); }, 0);
                });
            });

        // Boutons "Annuler"
        [
            document.querySelector('#cancel-provider'),
            document.querySelector('[data-action="cancel"]'),
            document.querySelector('#cancel')
        ]
            .filter(Boolean)
            .forEach(function (btn) {
                btn.addEventListener('click', function () {
                    // Micro-délai pour laisser EA revenir en mode lecture
                    setTimeout(function () { setEnabled(false); }, 0);
                });
            });
    }

    /**
     * Helper sécurisé pour récupérer un libellé depuis lang(), avec repli.
     * @param {string} key
     * @returns {string}
     */
    function safeLang(key) {
        try {
            if (typeof lang === 'function') {
                var s = lang(key);
                if (s && typeof s === 'string') return s;
            }
        } catch (e) { /* noop */ }
        // Valeurs par défaut si la clé de traduction n’existe pas
        var fallback = {
            max_patients: 'Limite patients',
            max_patients_help: 'Laisser vide pour aucune limite. Valeur entière ≥ 1.',
            max_patients_invalid: 'Valeur invalide pour la limite patients.'
        };
        return fallback[key] || key;
    }
    // --- IH Save Fixer (Providers) ---
    (function () {
        function waitNS(cb, tries=200) {
            const ok = window.App && App.Pages && App.Pages.Providers;
            if (ok) return cb(App.Pages.Providers);
            if (tries <= 0) return console.warn('[IH] Providers NS introuvable');
            setTimeout(() => waitNS(cb, tries-1), 50);
        }

        function findSaveButton() {
            const cands = Array.from(document.querySelectorAll('button, a.btn, input[type=button], input[type=submit]'));
            return cands.find(b =>
                /enregistr|save|sauveg|valider/i.test((b.textContent || '').trim()) ||
                /save|enregistrer/i.test(b.id || '') ||
                /save/i.test(b.className || '') ||
                /save/i.test(b.getAttribute('data-action') || '')
            ) || null;
        }

        // input hidden "transporteur" pour garantir provider[max_patients]
        function ensureHiddenCarrier() {
            let hid = document.getElementById('ih-max-patients-hidden');
            if (!hid) {
                hid = document.createElement('input');
                hid.type = 'hidden';
                hid.id = 'ih-max-patients-hidden';
                hid.name = 'provider[max_patients]';
                (document.querySelector('#providers') || document.querySelector('form') || document.body).appendChild(hid);
            }
            return hid;
        }

        function readMaxPatients() {
            const el = document.getElementById('interhop-max-patients');
            if (!el) return '';
            const raw = (el.value || '').trim();
            if (raw === '') return ''; // vide = illimité
            const n = parseInt(raw, 10);
            return (Number.isFinite(n) && n >= 1) ? String(n) : ''; // on laisse vide si invalide
        }

        function ensureSaveWiring(NS) {
            const form = document.querySelector('#providers') || document.querySelector('form');
            if (!form) {
                console.warn('[IH] Formulaire Providers introuvable.');
                return;
            }

            // Bouton
            let btn = findSaveButton();
            if (!btn) {
                btn = document.createElement('button');
                btn.id = 'ih-force-save';
                btn.className = 'btn btn-primary';
                btn.textContent = 'Enregistrer';
                (document.querySelector('.page-actions') || form).appendChild(btn);
            }
            if (btn.type !== 'submit') btn.setAttribute('type', 'submit');
            btn.removeAttribute('disabled');

            // Hidden carrier
            const hidden = ensureHiddenCarrier();

            // Router le submit vers Providers.save() et MAJ du hidden avant
            if (!form.dataset.ihSaveBound) {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    // met à jour le hidden à chaque submit
                    hidden.value = readMaxPatients();

                    try {
                        if (typeof NS.save === 'function') {
                            NS.save();
                        } else {
                            console.warn('[IH] App.Pages.Providers.save manquant, tentative fallback clic bouton');
                            try { btn.click(); } catch(_) {}
                        }
                    } catch (err) {
                        console.error('[IH] Providers.save a levé une exception', err);
                    }
                });
                form.dataset.ihSaveBound = '1';
                console.info('[IH] Submit → Providers.save() connecté.');
            }
        }

        waitNS(ensureSaveWiring);
    })();
})();
