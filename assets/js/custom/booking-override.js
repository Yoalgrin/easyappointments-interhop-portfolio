
    // Patch après chargement de TOUTES les JS
    window.addEventListener('load', function () {
    // Attendre que App.Pages.Booking soit prêt, même si les bundles chargent lentement
    var iv = setInterval(function () {
    if (window.App && App.Pages && App.Pages.Booking && App.Pages.Booking.updateConfirmFrame) {
    clearInterval(iv);
    // On garde la version d'origine
    var _orig = App.Pages.Booking.updateConfirmFrame;
    // On remplace par une version qui ajoute les libellés
    App.Pages.Booking.updateConfirmFrame = function () {
    _orig(); // exécute le rendu d'origine
    var $span = $('.display-booking-selection');
    var sid   = $('#select-service').val();
    var pid   = $('#select-provider').val();
    var sTxt  = sid ? $('#select-service').find('option:selected').text()  : lang('service');
    var pTxt  = pid ? $('#select-provider').find('option:selected').text() : lang('provider');
    // Nouveau rendu : libellés + valeurs
    if (sid || pid) {
    $span.text(lang('service') + ' : ' + sTxt + ' │ ' + lang('provider') + ' : ' + pTxt);
} else {
    $span.text(lang('service') + ' │ ' + lang('provider'));
}
};
}
}, 50);
});
