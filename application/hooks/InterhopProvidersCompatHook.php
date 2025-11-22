<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * InterhopProvidersCompatHook
 *
 * Objectifs :
 *  - ensureUserSettings : s’assurer qu’un soignant a bien une ligne de settings
 *    (évite les NULL dans get_settings) — ne touche JAMAIS au POST.
 *  - preNormalizeProvidersPost : aujourd’hui, ne fait PLUS AUCUNE
 *    modification de $_POST (juste du log éventuel).
 *
 * Très important :
 *  - On NE TOUCHE PAS à $_POST['provider'].
 *  - On laisse le core gérer la sérialisation et la sauvegarde du profil.
 *  - Les problèmes de JSON (working_plan & co) sont gérés côté front
 *    OU par le core, mais plus par ce hook.
 */
class InterhopProvidersCompatHook
{
    public function ensureUserSettings(): void
    {
        if (!function_exists('get_instance')) {
            return;
        }

        $CI = &get_instance();
        if (!is_object($CI)) {
            return;
        }

        $class = strtolower($CI->router->class ?? '');
        if ($class !== 'providers') {
            return;
        }

        $http = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($http !== 'GET') {
            return;
        }

        try {
            $CI->load->model('user_model');
            $CI->load->model('user_settings_model');

            $providerId = (int)($CI->input->get('id') ?? 0);
            if ($providerId <= 0) {
                return;
            }

            $user = $CI->user_model->get_row($providerId);
            if (empty($user)) {
                return;
            }

            $settings = $CI->user_settings_model->get_row(['id_users' => $providerId]);
            if (!$settings) {
                $CI->user_settings_model->insert([
                    'id_users'      => $providerId,
                    'notifications' => null,
                    'working_plan'  => null,
                    'settings'      => null,
                ]);
                log_message('debug', '[IH] ensureUserSettings: created settings row for provider #' . $providerId);
            }
        } catch (Throwable $e) {
            log_message('error', '[IH] ensureUserSettings error: ' . $e->getMessage());
        }
    }

    public function preNormalizeProvidersPost(): void
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            return;
        }

        if (!function_exists('get_instance')) {
            return;
        }

        $CI = &get_instance();
        if (!is_object($CI)) {
            return;
        }

        $class  = strtolower($CI->router->class  ?? '');
        $method = strtolower($CI->router->method ?? '');

        if ($class !== 'providers' || !in_array($method, ['update', 'store'], true)) {
            return;
        }
        // DEBUG : log du POST brut sans modification
        log_message('debug', '[IH DEBUG] RAW POST /providers/'.$method.': ' . print_r($_POST, true));
    }
}
