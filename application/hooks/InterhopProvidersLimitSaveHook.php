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
        //Debug à supprimer.
        log_message('debug', '[IH] afterAccountSave: class='.$CI->router->class.' method='.$CI->router->method.' role='.session('role_slug'));
        log_message('debug', '[IH] POST interhop[max_patients]='.print_r($CI->input->post('interhop'), true));
        log_message('debug', '[IH] POST provider[max_patients]='.print_r($CI->input->post('provider'), true));

        $CI = &get_instance();

        if (strtolower($CI->input->method(TRUE)) !== 'post') return;
        if ($CI->router->class !== 'Account' || $CI->router->method !== 'save') return;

        // Feature flag optionnel
        if ($CI->config->load('interhop', true)) {
            $enabled = $CI->config->item('interhop_provider_limits_enabled', 'interhop');
            if ($enabled === false) return;
        }

        // Autoriser soignant ET admin sur leur propre compte
        $role = session('role_slug');
        if (!in_array($role, [DB_SLUG_PROVIDER, 'admin'], true)) return;

        // Récup payloads possibles
        $provider = $CI->input->post('provider');   // provider[max_patients]
        $account  = $CI->input->post('account');    // account[settings][interhop_max_patients]
        $interhop = $CI->input->post('interhop');   // interhop[max_patients]
        $rawTop   = $CI->input->post('max_patients'); // au cas où

        if (!is_array($provider) && !is_array($account) && !is_array($interhop) && $rawTop === null) return;

        // ID cible = compte en cours / user courant
        $providerId = (int)($account['id'] ?? session('user_id') ?? 0);
        if ($providerId <= 0) return;

        // Valeur : chercher dans tous les chemins possibles
        $raw = null;
        if (is_array($provider) && array_key_exists('max_patients', $provider)) {
            $raw = $provider['max_patients'];
        } elseif (is_array($interhop) && array_key_exists('max_patients', $interhop)) {
            $raw = $interhop['max_patients'];
        } elseif (is_array($account)) {
            $raw = $account['settings']['interhop_max_patients'] ?? null;
        } elseif ($rawTop !== null) {
            $raw = $rawTop;
        }
        if ($raw === null) return;

        $max = (is_numeric($raw) && (int)$raw >= 1) ? (int)$raw : null;

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
        if ($CI->router->class !== 'Providers') return;

        $method = $CI->router->method;
        if (!in_array($method, ['update'], true)) return;

        if ($CI->config->load('interhop', true)) {
            $enabled = $CI->config->item('interhop_provider_limits_enabled', 'interhop');
            if ($enabled === false) return;
        }

        // Récup post
        $provider = $CI->input->post('provider');   // attendu par design
        $interhop = $CI->input->post('interhop');   // ce que tu vois dans la requête
        $rawTop   = $CI->input->post('max_patients'); // au cas où (à plat)

        if (is_array($provider)) {
            $pid = (int)($provider['id'] ?? 0);
        } else {
            $pid = (int)$CI->input->post('provider_id'); // repli si jamais
        }
        if ($pid <= 0) return;

        // Cherche la valeur dans plusieurs chemins possibles
        $raw = null;
        if (is_array($provider) && array_key_exists('max_patients', $provider)) {
            $raw = $provider['max_patients'];
        } elseif (is_array($interhop) && array_key_exists('max_patients', $interhop)) {
            $raw = $interhop['max_patients'];
        } elseif ($rawTop !== null) {
            $raw = $rawTop;
        }

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

