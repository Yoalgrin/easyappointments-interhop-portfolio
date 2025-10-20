<?php //defined('BASEPATH') or exit('No direct script access allowed');
//
//// Add custom values by settings them to the $config array.
//// Example: $config['smtp_host'] = 'smtp.gmail.com';
//// @link https://codeigniter.com/user_guide/libraries/email.html
//
//$config['useragent'] = 'Easy!Appointments';
//$config['protocol'] = 'smtp'; // or 'smtp'
//$config['mailtype'] = 'html'; // or 'text'
//$config['smtp_debug'] = '0'; // or '1'
//$config['smtp_auth'] = TRUE; //or FALSE for anonymous relay.
//$config['smtp_host'] = 'mail.riseup.net';
//$config['smtp_user'] = 'interhop@riseup.net';
//$config['smtp_pass'] = '&I.ip';
//$config['smtp_crypto'] = 'tls'; // or 'tls'
//$config['smtp_port'] = 587;
//// $config['from_name'] = '';
//// $config['from_address'] = '';
//// $config['reply_to'] = '';
//$config['crlf'] = "\r\n";
//$config['newline'] = "\r\n";

defined('BASEPATH') or exit('No direct script access allowed');

/*
 * email.php — CodeIgniter 3
 * Bascule via EA_SMTP_DRIVER: 'mailhog' | 'personal' | 'riseup'
 * - 'personal' = Outlook/Hotmail/Gmail/etc. (host/port/crypto depuis l'env)
 * - 'riseup'   = mail.riseup.net (587 tls ou 465 ssl)
 * - 'mailhog'  = localhost:1025 sans auth (par défaut)
 */

$driver = getenv('EA_SMTP_DRIVER') ?: 'mailhog';

$config = [
    'useragent' => 'EA-InterHop',
    'protocol' => 'smtp',
    'mailtype' => 'html',
    'charset' => 'UTF-8',
    'newline' => "\r\n",
    'crlf' => "\r\n",
    'smtp_timeout' => 10,
];

switch ($driver) {
    case 'riseup':
        $config['smtp_host'] = 'mail.riseup.net';
        $config['smtp_port'] = (int)(getenv('EA_SMTP_PORT') ?: 587);
        $config['smtp_crypto'] = getenv('EA_SMTP_CRYPTO') ?: 'tls'; // 'ssl' si 465
        $config['smtp_user'] = getenv('EA_SMTP_USER');            // ex: interhop@riseup.net
        $config['smtp_pass'] = getenv('EA_SMTP_PASS');
        break;

    case 'personal':
        // Ex: Outlook/Hotmail (smtp.office365.com:587 tls) ou autre fournisseur
        $config['smtp_host'] = getenv('EA_SMTP_HOST') ?: 'smtp.office365.com';
        $config['smtp_port'] = (int)(getenv('EA_SMTP_PORT') ?: 587);
        $config['smtp_crypto'] = getenv('EA_SMTP_CRYPTO') ?: 'tls';
        $config['smtp_user'] = getenv('EA_SMTP_USER');            // ex: yoalgrin@hotmail.com
        $config['smtp_pass'] = getenv('EA_SMTP_PASS');            // mot de passe d'application
        break;

    default: // 'mailhog'
        $config['smtp_host'] = getenv('EA_MAILHOG_HOST') ?: 'localhost';
        $config['smtp_port'] = (int)(getenv('EA_MAILHOG_PORT') ?: 1025);
        $config['smtp_crypto'] = '';
        $config['smtp_user'] = '';
        $config['smtp_pass'] = '';
        break;
}

