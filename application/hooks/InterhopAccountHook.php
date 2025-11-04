<?php defined('BASEPATH') or exit('No direct script access allowed');

class InterhopAccountHook
{
    public function afterAccountSave(): void
    {
        $CI = &get_instance();

        // Ciblage limité au POST /account/save.
        if (strtolower($CI->input->method(TRUE)) !== 'POST') return;
        if ($CI->router->class !== 'Account' || $CI->router->method !== 'save') return;

        // Rôle soignant(e) uniquement.
        if (session('role_slug') !== DB_SLUG_PROVIDER) return;

        // Récupération du payload imbriqué "account".
        $account = $CI->input->post('account');
        if (!is_array($account)) return;

        // ID provider (sécurité).
        $providerId = (int)($account['id'] ?? session('user_id') ?? 0);
        if ($providerId <= 0) return;

        // Valeur depuis settings.
        $raw = $account['settings']['interhop_max_patients'] ?? null;
        $max = (is_numeric($raw) && (int)$raw >= 1) ? (int)$raw : null; // 0/vide => NULL (illimité)

        // Persistance dans la table validée.
        $table = 'ea_interhop_providers_limits';

        $existing = $CI->db->where('provider_id', $providerId)->get($table)->row();

        if ($existing) {
            $CI->db->where('provider_id', $providerId)
                ->update($table, ['max_patients' => $max]);
        } else {
            $CI->db->insert($table, [
                'provider_id'  => $providerId,
                'max_patients' => $max,
            ]);
        }
    }
}

