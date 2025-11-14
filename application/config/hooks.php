<?php defined('BASEPATH') or exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Hooks — EasyAppointments-InterHop (modulaire, sans core changes)
| -------------------------------------------------------------------------
| Ordre important :
| - pre_system                : bootstrap debug très tôt
| - pre_controller            : guards + compat (ex: Providers user_settings)
| - post_controller_constructor : i18n inject (backend)
| - post_controller           : callbacks après actions (ex: afterAccountSave)
| - display_override          : 1) export i18n vers <head>, 2) injection assets JS
| -------------------------------------------------------------------------
*/

/** 0) Bootstrap logging (tout début) */
$hook['pre_system'][] = [
    'class'    => 'InterhopDebugBootstrapHook',
    'function' => 'preSystem',
    'filename' => 'InterhopDebugBootstrapHook.php',
    'filepath' => 'hooks'
];

/** 0bis) Auth JSON guard : garantit un JSON propre pour /login/validate */
$hook['pre_controller'][] = [
    'class'    => 'InterhopAuthJsonGuardHook',
    'function' => 'guardLoginValidate',
    'filename' => 'InterhopAuthJsonGuardHook.php',
    'filepath' => 'hooks'
];

/** 0ter) Compat Providers : garantit une ligne ea_user_settings pour chaque soignant (évite null dans get_settings) */
$hook['pre_controller'][] = [
    'class'    => 'InterhopProvidersCompatHook',
    'function' => 'ensureUserSettings',
    'filename' => 'InterhopProvidersCompatHook.php',
    'filepath' => 'hooks'
];

/** 1) i18n backend : complète/normalise les clés côté PHP */
$hook['post_controller_constructor'][] = [
    'class'    => 'InterhopTranslationHook',
    'function' => 'inject',
    'filename' => 'InterhopTranslationHook.php',
    'filepath' => 'hooks'
];

/** 1bis) Activer le logging CI quand l’instance est prête */
$hook['post_controller_constructor'][] = [
    'class'    => 'InterhopDebugBootstrapHook',
    'function' => 'enableCiLogging',
    'filename' => 'InterhopDebugBootstrapHook.php',
    'filepath' => 'hooks'
];

/** 2) Compat qui fabrique account[...] si absent (MUST) */
$hook['post_controller_constructor'][] = [
    'class'    => 'InterhopAccountCompatHook',
    'function' => 'preNormalizeAccountPost',
    'filename' => 'InterhopAccountCompatHook.php',
    'filepath' => 'hooks'
];

/** 3) Upsert après succès core (sauvegarde Account) */

$hook['post_controller'][] = [
    'class'    => 'InterhopAccountHook',
    'function' => 'afterAccountSave',
    'filename' => 'InterhopAccountHook.php',
    'filepath' => 'hooks'
];

/** 4a) EXPORT I18N FRONT : pousse les clés (EALang + patch lang()) dans le <head>
 *     IMPORTANT : doit passer AVANT l’injection des assets JS.
 */
$hook['display_override'][] = [
    'class'    => 'InterhopTranslationHook',
    'function' => 'exportToHead',
    'filename' => 'InterhopTranslationHook.php',
    'filepath' => 'hooks'
];

/** 4b) Injection des overrides JS (HTTP override + UI override) */
$hook['display_override'][] = [
    'class'    => 'InterhopAssetsHook',
    'function' => 'injectOverrides',
    'filename' => 'InterhopAssetsHook.php',
    'filepath' => 'hooks'
];
$hook['pre_controller'][] = [
    'class'    => 'InterhopProvidersCompatHook',
    'function' => 'preNormalizeProvidersPost',
    'filename' => 'InterhopProvidersCompatHook.php',
    'filepath' => 'hooks',
    'params'   => []
];
/* End of file hooks.php */
/* Location: ./application/config/hooks.php */
