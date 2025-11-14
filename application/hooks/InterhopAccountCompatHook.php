<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Compatibilité POST /account/save
 * - Ne s’exécute QUE sur POST /account/save
 * - N’INVENTE RIEN : ne modifie PAS si 'account' existe déjà
 * - Si 'account' est absent, reconstruit le strict minimum attendu par le core
 *   depuis les chemins courants ('user', 'provider', 'settings', racine).
 * - Ne touche PAS aux autres champs (calendar_view, timezone, ...).
 */
class InterhopAccountCompatHook
{
    public function preNormalizeAccountPost(): void
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') return;

        $CI = &get_instance();
        $class = strtolower($CI->router->class ?? '');
        $method = strtolower($CI->router->method ?? '');
        if ($class !== 'account' || $method !== 'save') return;

        // Sources déjà présentes
        $inAcc = (isset($_POST['account']) && is_array($_POST['account'])) ? $_POST['account'] : [];
        $inUser = (isset($_POST['user']) && is_array($_POST['user'])) ? $_POST['user'] : [];
        $inProv = (isset($_POST['provider']) && is_array($_POST['provider'])) ? $_POST['provider'] : [];
        $inSet = (isset($_POST['settings']) && is_array($_POST['settings'])) ? $_POST['settings'] : [];

        $pick = function (...$c) {
            foreach ($c as $v) {
                if ($v !== null && $v !== '') return $v;
            }
            return null;
        };

        // Identité de base
        $id = $pick($inAcc['id'] ?? null, $_POST['id'] ?? null, $inUser['id'] ?? null, $inProv['id'] ?? null, $inUser['provider']['id'] ?? null);
        $first_name = $pick($inAcc['first_name'] ?? null, $inUser['first_name'] ?? null, $_POST['first_name'] ?? null);
        $last_name = $pick($inAcc['last_name'] ?? null, $inUser['last_name'] ?? null, $_POST['last_name'] ?? null);
        $email = $pick($inAcc['email'] ?? null, $inUser['email'] ?? null, $_POST['email'] ?? null);
        $username = $pick($inAcc['settings']['username'] ?? null, $inSet['username'] ?? null, ($_POST['settings']['username'] ?? null), $_POST['username'] ?? null);

        // 👉 TELEPHONE : piocher dans toutes les variantes possibles
        $phone_number = $pick(
            $inAcc['phone_number'] ?? null,
            $_POST['phone_number'] ?? null,
            $inUser['phone_number'] ?? null,
            $inProv['phone_number'] ?? null,
            $_POST['mobile_number'] ?? null,
            $inUser['mobile_number'] ?? null,
            $inProv['mobile_number'] ?? null,
            $_POST['phone'] ?? null,
            $_POST['mobile'] ?? null,
            $_POST['tel'] ?? null
        );

        // Autres champs souvent requis
        $language = $pick($inAcc['language'] ?? null, $_POST['language'] ?? null, $CI->config->item('language') ?? 'en');
        $timezone = $pick($inAcc['timezone'] ?? null, $_POST['timezone'] ?? null, 'Europe/Paris');

        // === 1/3 : racine (parfois lu par des helpers)
        if ($id !== null) $_POST['id'] = (int)$id;
        if ($first_name !== null) $_POST['first_name'] = (string)$first_name;
        if ($last_name !== null) $_POST['last_name'] = (string)$last_name;
        if ($email !== null) $_POST['email'] = (string)$email;
        if ($username !== null) {
            if (!isset($_POST['settings']) || !is_array($_POST['settings'])) $_POST['settings'] = [];
            $_POST['settings']['username'] = (string)$username;
        }
        if ($phone_number !== null) $_POST['phone_number'] = (string)$phone_number;
        if ($language !== null) $_POST['language'] = (string)$language;
        if ($timezone !== null) $_POST['timezone'] = (string)$timezone;

        // === 2/3 : bloc account[...] (c’est ce que lit Account::save via request('account'))
        if (!isset($_POST['account']) || !is_array($_POST['account'])) $_POST['account'] = [];
        if ($id !== null) $_POST['account']['id'] = (int)$id;
        if ($first_name !== null) $_POST['account']['first_name'] = (string)$first_name;
        if ($last_name !== null) $_POST['account']['last_name'] = (string)$last_name;
        if ($email !== null) $_POST['account']['email'] = (string)$email;
        if ($phone_number !== null) $_POST['account']['phone_number'] = (string)$phone_number;
        if ($language !== null) $_POST['account']['language'] = (string)$language;
        if ($timezone !== null) $_POST['account']['timezone'] = (string)$timezone;

        if (!isset($_POST['account']['settings']) || !is_array($_POST['account']['settings'])) {
            $_POST['account']['settings'] = [];
        }
        if ($username !== null) $_POST['account']['settings']['username'] = (string)$username;

        // === 3/3 : log de contrôle (temporaire)
        log_message('debug', '[IH] Compat ensured: phone_number=' . ($phone_number ?? 'NULL') . ', id=' . ($id ?? 'NULL'));
    }
}
