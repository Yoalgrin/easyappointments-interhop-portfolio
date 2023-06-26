<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#35A768">

    <title><?= lang('CGU') ?></title>

    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/ext/bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/ext/jquery-ui/jquery-ui.min.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/ext/cookieconsent/cookieconsent.min.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/css/frontend.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/css/general.css') ?>">

    <link rel="icon" type="image/x-icon" href="<?= asset_url('assets/img/favicon.ico') ?>">
    <link rel="icon" sizes="192x192" href="<?= asset_url('assets/img/logo.png') ?>">

    <script src="<?= asset_url('assets/ext/fontawesome/js/fontawesome.min.js') ?>"></script>
    <script src="<?= asset_url('assets/ext/fontawesome/js/solid.min.js') ?>"></script>
    <script>
        function myFunction(elem){
            elsuiv = elem.nextSibling;
            if(elsuiv.nodeName == '#text'){
                elsuiv = elsuiv.nextSibling;
            }
            if(elsuiv.style.display == 'none'){
                elsuiv.style.display = 'block';
            }else{
                elsuiv.style.display = 'none';
            }
        }
    </script>
</head>



<body>
    <div id="main" class="container">
        <div class="row wrapper">
            <div id="book-appointment-wizard" class="col-12 col-lg-10 col-xl-8">
                <div id="header">
                    <span id="company-name"><?= $company_name ?></span>
                </div>
                <div id="wizard-frame-1" class="wizard-frame">
                        <div class="frame-container">
                            <h2 class="frame-title"><?= lang('terms_and_conditions') ?></h2>

                <div class="body">
                    <p><?= $privacy_policy_content ?></p>
                    <p><?= $terms_and_conditions_content ?></p>
                </div>
                </div>
        </div>

                <div class="command-buttons">
                    <button type="button" id="button-back-2" class="btn button-back-custom btn-outline-secondary"
                            data-step_index="2">
                        <i class="fas fa-chevron-left mr-2"></i>
                        <?= lang('back') ?>
                    </button>
                </div>


<!-- FRAME FOOTER -->
<footer>
    <div id="frame-footer">
        <small>
            <span class="footer-powered-by">
                Powered By
                <a href="https://easyappointments.org" target="_blank">Easy!Appointments</a>
            </span>
            <span class="footer-options">
                <span id="select-language" class="badge badge-secondary">
                    <i class="fas fa-language mr-2"></i>
                    <?= ucfirst(config('language')) ?>
                </span>
                <a class="backend-link badge badge-primary" href="<?= site_url('backend'); ?>">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    <?= $this->session->user_id ? lang('backend_section') : lang('login') ?>
                </a>
            </span>
        </small>
    </div>
    <script>
        document.getElementById('button-back-2').addEventListener('click', function() {
            history.back();
        });
    </script>
</footer>


</body>


