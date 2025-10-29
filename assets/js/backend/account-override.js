(function () {
    document.addEventListener('DOMContentLoaded', function () {
        // 1) Injecter le champ dans le formulaire (à la fin de la colonne droite)
        var container = document.querySelector('#account .col-lg-6 .border')?.parentElement || document.querySelector('#account .col-lg-6');
        if (!container) return;

        var wrap = document.createElement('div');
        wrap.className = 'mb-3';
        wrap.innerHTML = `
      <label class="form-label" for="interhop-max-patients">${lang('interhop_max_patients')}</label>
      <input type="number" id="interhop-max-patients" class="form-control" placeholder="${lang('interhop_max_patients_placeholder')}" min="0">
      <small class="form-text text-muted">${lang('interhop_max_patients_info')}</small>
    `;
        container.insertBefore(wrap, container.firstChild);

        // 2) Monkey-patch serialize() pour ajouter la valeur côté POST
        if (App && App.Pages && App.Pages.Account) {
            var originalSerialize = App.Pages.Account.serialize;
            if (typeof originalSerialize === 'function') {
                App.Pages.Account.serialize = function () {
                    var data = originalSerialize.call(this);
                    var raw = document.getElementById('interhop-max-patients')?.value ?? '';
                    var v = (typeof raw === 'string') ? raw.trim() : raw;
                    var interhopMax = (v === '' || v === '0') ? null : v; // 0/vide ⇒ illimité
                    data.settings = data.settings || {};
                    data.settings.interhop_max_patients = interhopMax;
                    return data;
                };
            }
        }
    });
})();
