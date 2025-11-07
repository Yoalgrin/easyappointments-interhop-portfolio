<?php defined('BASEPATH') or exit('No direct script access allowed');

class InterhopAccountHook
{
    /**
     * Après /account/save : upsert dans {dbprefix}interhop_providers_limits
     * - Ne lit QUE account[id] (id utilisateur du soignant)
     * - Lit raw[interhop_max_patients] (''/0 => NULL ; n>=1 => int)
     * - N’échoue JAMAIS la sauvegarde du compte si la table est absente
     * Hook: post_controller
     */


    public function afterAccountSave(): void

    {
        if (php_sapi_name() === 'cli') return;
        if (!function_exists('get_instance')) return;
        $CI = get_instance();
        if (!is_object($CI)) return;

        // Détecter POST /Account/save
        $http = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($http !== 'POST') return;
        $RTR = @load_class('Router', 'core');
        $class  = (string)($RTR->class  ?? '');
        $method = (string)($RTR->method ?? '');
        if (!(strcasecmp($class, 'Account') === 0 && strcasecmp($method, 'save') === 0)) return;

        // (optionnel) limiter au rôle provider
        $role = (string)$CI->session->userdata('role_slug');
        if ($role && strcasecmp($role, 'provider') !== 0 && $role !== DB_SLUG_PROVIDER) {
            return;
        }

        // 1) ID du soignant = account[id] posté par /account/save (== ea_users.id chez toi)
        $account    = $CI->input->post('account');
        $providerId = (int)($account['id'] ?? 0);
        if ($providerId <= 0) $providerId = (int)$CI->input->post('id');
        if ($providerId <= 0) { log_message('debug','[InterHop] no provider id'); return; }


        // 2) Valeur transmise par le front : priorité à interhop[max_patients], fallback sur raw[interhop_max_patients]
            $max = null;
            $interhop = $CI->input->post('interhop');
            if (is_array($interhop) && array_key_exists('max_patients', $interhop)) {
                $s = trim((string)$interhop['max_patients']);
                if ($s !== '' && ctype_digit($s)) {
                        $ival = (int)$s;
                        if ($ival >= 1) $max = $ival; // '' ou <1 => NULL (illimité)
        }
    } else {
                // rétro-compat: ancien champ raw[interhop_max_patients]
                $raw = $CI->input->post('raw');
                if (is_array($raw) && array_key_exists('interhop_max_patients', $raw)) {
                        $s = trim((string)$raw['interhop_max_patients']);
                        if ($s !== '' && ctype_digit($s)) {
                                $ival = (int)$s;
                                if ($ival >= 1) $max = $ival;
            }
        }
    }

        // 3) Upsert dans {dbprefix}interhop_providers_limits
        $CI->load->database();
        $table_prefixed = $CI->db->dbprefix('interhop_providers_limits'); // ea_interhop_providers_limits

// table_exists attend le nom NON-préfixé
        if (!$CI->db->table_exists('interhop_providers_limits')) {
            log_message('error', "[InterHop] afterAccountSave: table {$table_prefixed} manquante => upsert ignoré.");
            return; // ne casse pas la sauvegarde
        }

// Stratégie : NULL => supprime la ligne (illimité), sinon UPSERT
        $userId = (int) ($CI->session->userdata('user_id') ?? 0);

// Blindage anti-500 : ne jamais laisser une erreur DB remonter comme 500
        $prev_debug = $CI->db->db_debug;
        $CI->db->db_debug = false;
        try {
            if ($max === null) {
                // illimité => on supprime l'éventuelle ligne existante
                $CI->db->delete($table_prefixed, ['provider_id' => $providerId]);
                if ($CI->db->error()['code']) {
                    log_message('error', '[InterHop] delete limite erreur: ' . json_encode($CI->db->error()));
                } else {
                    log_message('debug', "[InterHop] delete limite (illimité) table={$table_prefixed} provider_id={$providerId}");
                }
                return;
            }

            // UPSERT (PRIMARY KEY(provider_id) requis)
            $sql = "
        INSERT INTO {$table_prefixed} (provider_id, max_patients, updated_at, updated_by)
        VALUES (?, ?, CURRENT_TIMESTAMP, ?)
        ON DUPLICATE KEY UPDATE
          max_patients = VALUES(max_patients),
          updated_at   = VALUES(updated_at),
          updated_by   = VALUES(updated_by)
    ";
            $CI->db->query($sql, [$providerId, $max, $userId]);
            if ($CI->db->error()['code']) {
                log_message('error', '[InterHop] upsert erreur: ' . json_encode($CI->db->error()));
            } else {
                log_message('debug', "[InterHop] upsert OK table={$table_prefixed} provider_id={$providerId} max=" . var_export($max, true));
            }
        } catch (Throwable $e) {
            log_message('error', '[InterHop] upsert exception: ' . $e->getMessage());
        } finally {
            $CI->db->db_debug = $prev_debug; // rétablit le comportement CI
        }
    }
}
