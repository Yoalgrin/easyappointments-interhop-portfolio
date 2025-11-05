<?php defined('BASEPATH') or exit('No direct script access allowed');

class InterhopProvidersLimitSaveHook
{
    /**
     * Soignant : POST /account/save
     * Sérialisation attendue côté JS : provider[max_patients]
     * Fallback accepté : account[settings][interhop_max_patients]
     */
    public function afterAccountSave(): void
    {
        $CI = &get_instance();

        // Route + méthode
        if (strtolower($CI->input->method(TRUE)) !== 'post') return;
        if ($CI->router->class !== 'Account' || $CI->router->method !== 'save') return;

        // Switch global (optionnel)
        if ($CI->config->load('interhop', true)) {
            $enabled = $CI->config->item('interhop_provider_limits_enabled', 'interhop');
            if ($enabled === false) return;
        }

        // Rôle soignant uniquement
        if (session('role_slug') !== DB_SLUG_PROVIDER) return;

        // Payloads possibles
        $provider = $CI->input->post('provider');
        $account  = $CI->input->post('account');

        if (!is_array($provider) && !is_array($account)) return;

        // ID provider fiable
        $providerId = (int)($account['id'] ?? session('user_id') ?? 0);
        if ($providerId <= 0) return;

        // Valeur
        $raw = null;
        if (is_array($provider) && array_key_exists('max_patients', $provider)) {
            $raw = $provider['max_patients'];
        } elseif (is_array($account)) {
            $raw = $account['settings']['interhop_max_patients'] ?? null;
        }
        if ($raw === null) return;

        // Validation : entier ≥1 ou NULL
        $max = (is_numeric($raw) && (int)$raw >= 1) ? (int)$raw : null;

        // Persistance via modèle dédié (upsert + updated_at/by)
        $CI->load->model('interhop_providers_limit_model');
        $CI->interhop_providers_limit_model->upsert(
            $providerId,
            $max,
            (int)(session('user_id') ?? 0)
        );
    }

    /**
     * Admin : POST /providers/save
     * Sérialisation attendue : provider[max_patients]
     */
    public function afterProvidersSave(): void
    {
        $CI = &get_instance();

        if (strtolower($CI->input->method(TRUE)) !== 'post') return;
        if ($CI->router->class !== 'Providers' || $CI->router->method !== 'save') return;

        if ($CI->config->load('interhop', true)) {
            $enabled = $CI->config->item('interhop_provider_limits_enabled', 'interhop');
            if ($enabled === false) return;
        }

        $provider = $CI->input->post('provider');
        if (!is_array($provider)) return;

        $pid = (int)($provider['id'] ?? 0);
        if ($pid <= 0) return;

        $raw = $provider['max_patients'] ?? null;
        if ($raw === null) return;

        $max = (is_numeric($raw) && (int)$raw >= 1) ? (int)$raw : null;

        $CI->load->model('interhop_providers_limit_model');
        $CI->interhop_providers_limit_model->upsert(
            $pid,
            $max,
            (int)(session('user_id') ?? 0)
        );
    }
}

