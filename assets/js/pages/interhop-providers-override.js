// Admin: input désactivé sauf en mode édition; sérialisation quand on sauvegarde
window.addEventListener('load', function () {
    var iv = setInterval(function () {
        if (window.App && App.Pages && App.Pages.Providers && typeof App.Pages.Providers.fillForm === 'function') {
            clearInterval(iv);

            function setEnabled(enabled) {
                var el = document.getElementById('interhop-max-patients');
                if (el) el.disabled = !enabled;
            }

            const _fill = App.Pages.Providers.fillForm;
            App.Pages.Providers.fillForm = function (provider) {
                _fill(provider);

                if (!document.getElementById('interhop-max-patients')) {
                    const container = document.querySelector('#interhop-max-patients-wrapper') || document.querySelector('#providers form');
                    if (container) {
                        const group = document.createElement('div');
                        group.className = 'form-group';
                        group.innerHTML = `
              <label for="interhop-max-patients">${lang('max_patients')}</label>
              <input type="number" min="1" id="interhop-max-patients" class="form-control" placeholder="${lang('max_patients')}" />
              <small class="text-muted">${lang('max_patients_help')}</small>
            `;
                        container.appendChild(group);
                    }
                }

                var v = (provider && provider.interhop_max_patients != null) ? provider.interhop_max_patients : '';
                var input = document.getElementById('interhop-max-patients');
                input.value = v;

                // lecture seule par défaut
                setEnabled(false);
            };

            // Patcher l'activation édition selon les boutons natifs
            // Gestion adaptable des boutons Éditer/Annuler (prise en charge de plusieurs sélecteurs selon le template)

            const editBtns = [
                document.querySelector('#edit-provider'),
                document.querySelector('[data-action="edit"]'),
                document.querySelector('#edit') // fallback
            ].filter(Boolean);

            editBtns.forEach(btn => btn.addEventListener('click', () => setTimeout(() => setEnabled(true), 0)));

            const cancelBtns = [
                document.querySelector('#cancel-provider'),
                document.querySelector('[data-action="cancel"]'),
                document.querySelector('#cancel')
            ].filter(Boolean);

            cancelBtns.forEach(btn => btn.addEventListener('click', () => setTimeout(() => setEnabled(false), 0)));

            // Sérialisation quand on sauvegarde
            if (typeof App.Pages.Providers.serializeForm === 'function') {
                const _serialize = App.Pages.Providers.serializeForm;
                App.Pages.Providers.serializeForm = function () {
                    const payload = _serialize();
                    const input = document.getElementById('interhop-max-patients');
                    if (input && !input.disabled) {
                        const v = input.value.trim();
                        payload.provider = payload.provider || {};
                        if (v === '') {
                            payload.provider.max_patients = ''; // => NULL
                        } else {
                            const n = parseInt(v, 10);
                            if (!Number.isNaN(n) && n >= 1) {
                                payload.provider.max_patients = n;
                            } else {
                                App.Layout.message.error(lang('max_patients_invalid'));
                                throw new Error('invalid max_patients');
                            }
                        }
                    }
                    return payload;
                };
            }
        }
    }, 50);
});
