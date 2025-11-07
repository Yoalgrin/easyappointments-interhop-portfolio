<?php defined('BASEPATH') or exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Hooks
| -------------------------------------------------------------------------
| EasyAppointments-InterHop — extensions sans toucher au core.
| -------------------------------------------------------------------------
*/

// a) i18n (ok)
$hook['post_controller_constructor'][] = [
    'class'=>'InterhopTranslationHook','function'=>'inject',
    'filename'=>'InterhopTranslationHook.php','filepath'=>'hooks'
];

// b) Compat qui fabrique account[...] si absent (MUST)
$hook['post_controller_constructor'][] = [
    'class'=>'InterhopAccountCompatHook','function'=>'preNormalizeAccountPost',
    'filename'=>'InterhopAccountCompatHook.php','filepath'=>'hooks'
];

// c) Upsert après succès core (ok)
$hook['post_controller'][] = [
    'class'=>'InterhopAccountHook','function'=>'afterAccountSave',
    'filename'=>'InterhopAccountHook.php','filepath'=>'hooks'
];

// d) Injection JS (ok)
$hook['display_override'][] = [
    'class'=>'InterhopAssetsHook','function'=>'injectOverrides',
    'filename'=>'InterhopAssetsHook.php','filepath'=>'hooks'
];
// 0) Bootstrap logging (avant tout le reste)
$hook['pre_system'][] = [
    'class'    => 'InterhopDebugBootstrapHook',
    'function' => 'preSystem',
    'filename' => 'InterhopDebugBootstrapHook.php',
    'filepath' => 'hooks'
];

// 0bis) Activer le logging CI (quand l'instance CI est prête)
$hook['post_controller_constructor'][] = [
    'class'    => 'InterhopDebugBootstrapHook',
    'function' => 'enableCiLogging',
    'filename' => 'InterhopDebugBootstrapHook.php',
    'filepath' => 'hooks'
];

/* End of file hooks.php */
/* Location: ./application/config/hooks.php */
