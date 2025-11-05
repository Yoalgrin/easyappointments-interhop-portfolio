<?php defined('BASEPATH') or exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Hooks
| -------------------------------------------------------------------------
| This file lets you define "hooks" to extend CI without hacking the core
| files.  Please see the user guide for info:
|
|	http://codeigniter.com/user_guide/general/hooks.html
|
*/
$hook['post_controller_constructor'][] = [
    'class'    => 'InterhopTranslationHook',
    'function' => 'inject',
    'filename' => 'InterhopTranslationHook.php',
    'filepath' => 'hooks',
];
$hook['post_controller'][] = [
    'class'    => 'InterhopAccountHook',
    'function' => 'afterAccountSave',
    'filename' => 'InterhopAccountHook.php',
    'filepath' => 'hooks',
    'params'   => [],
];
// application/config/hooks.php (exemples)
$hook['post_controller_constructor'][] = [
    'class'    => 'InterhopTranslationHook',
    'function' => 'inject',
    'filename' => 'InterhopTranslationHook.php',
    'filepath' => 'hooks',
];

$hook['post_controller'][] = [
    'class'    => 'InterhopProvidersLimitSaveHook',
    'function' => 'afterAccountSave',
    'filename' => 'InterhopProvidersLimitSaveHook.php',
    'filepath' => 'hooks',
];

$hook['post_controller'][] = [
    'class'    => 'InterhopProvidersLimitSaveHook',
    'function' => 'afterProvidersSave',
    'filename' => 'InterhopProvidersLimitSaveHook.php',
    'filepath' => 'hooks',
];

// Pour le hook d’enrichissement de payload
$hook['post_controller_constructor'][] = [
    'class'    => 'InterhopAccount',
    'function' => 'augment_account_view',
    'filename' => 'InterhopAccount.php',
    'filepath' => 'hooks',
];
$hook['post_controller_constructor'][] = [
    'class'    => 'InterhopAccount',
    'function' => 'augment_provider_view',
    'filename' => 'InterhopAccount.php',
    'filepath' => 'hooks',
];
$hook['display_override'][] = [
    'class'    => 'InterhopAssetsHook',
    'function' => 'injectOverrides',
    'filename' => 'InterhopAssetsHook.php',
    'filepath' => 'hooks',
];


/* End of file hooks.php */
/* Location: ./application/config/hooks.php */
