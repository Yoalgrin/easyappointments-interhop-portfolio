// Soignant(e): champ éditable + sérialisation provider[max_patients]
window.addEventListener('load', function () {
    var iv = setInterval(function () {
        if (window.App && App.Pages && App.Pages.Account && typeof App.Pages.Account.fillForm === 'function') {
            clearInterval(iv);

            const _fill = App.Pages.Account.fillForm;
            App.Pages.Account.fillForm = function (account) {
                _fill(account);

                if (!document.getElementById('interhop-max-patients')) {
                    const container = document.querySelector('#account form') || document.getElementById('account');
                    if (container) {
                        const group = document.createElement('div');
                        group.className = 'form-group';
                        group.innerHTML = `
              <label for="interhop-max-patients">${lang('max_patients')}</label>
              <input type="number" min="1" id="interhop-max-patients" class="form-control" placeholder="${lang('max_patients')}"/>
              <small class="text-muted">${lang('max_patients_help')}</small>
            `;
                        container.appendChild(group);
                    }
                }
                var v = (account && account.interhop_max_patients != null) ? account.interhop_max_patients : '';
                document.getElementById('interhop-max-patients').value = v;
            };

            //  La sérialisation est étendu pour inclure provider[max_patients]
            if (typeof App.Pages.Account.serializeForm === 'function') {
                const _serialize = App.Pages.Account.serializeForm;
                App.Pages.Account.serializeForm = function () {
                    const payload = _serialize();
                    const input = document.getElementById('interhop-max-patients');
                    if (input) {
                        const v = input.value.trim();
                        payload.provider = payload.provider || {};
                        if (v === '') {
                            payload.provider.max_patients = ''; // => NULL côté hook
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
