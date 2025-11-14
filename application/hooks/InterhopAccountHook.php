<?php defined('BASEPATH') or exit('No direct script access allowed');

class InterhopAccountHook
{
    /**
     * Après /account/save : upsert dans {dbprefix}interhop_providers_limits
     * - Ne lit QUE account[id] (id utilisateur)
     * - Considère qu’un user est "soignant" s’il apparaît dans ea_services_providers.id_users
     * - Lit interhop[max_patients] (''/0 => NULL ; n>=1 => int)
     * - N’échoue JAMAIS la sauvegarde du compte si la table est absente
     * Hook: post_controller
     */
    public function afterAccountSave(): void
    {
        // Contexte minimal
        if (php_sapi_name() === 'cli') return;
        if (!function_exists('get_instance')) return;

        $CI = get_instance();
        if (!is_object($CI)) return;

        // 1) Ne s'exécute que sur POST /account/save
        $http = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($http !== 'POST') {
            return;
        }

        $RTR = @load_class('Router', 'core');
        $class  = (string)($RTR->class  ?? '');
        $method = (string)($RTR->method ?? '');
        if (!(strcasecmp($class, 'Account') === 0 && strcasecmp($method, 'save') === 0)) {
            return;
        }

        // ⚠️ On NE LIMITE PLUS au rôle "provider" uniquement.
        // Un admin peut aussi être soignant : on regarde les données, pas role_slug.

        // 2) ID utilisateur en cours d'édition (ea_users.id)
        $account = $CI->input->post('account') ?? [];
        $userId  = (int)($account['id'] ?? 0);
        if ($userId <= 0) {
            $userId = (int)$CI->input->post('id');
        }
        if ($userId <= 0) {
            log_message('debug', '[InterHop] afterAccountSave: userId invalide, upsert limite ignoré.');
            return;
        }

        $CI->load->database();

        // 3) Vérifier si cet utilisateur est réellement un soignant
        //    ==> présence dans ea_services_providers.id_users
        $isProvider = $this->userIsProvider($CI, $userId);
        if (!$isProvider) {
            // Ce user n'est associé à aucun service en tant que soignant
            log_message(
                'debug',
                '[InterHop] upsert limite ignoré : user_id=' . $userId . ' non lié comme provider (ea_services_providers.id_users).'
            );
            return; // on n'échoue pas la sauvegarde du compte
        }

        // Ici, on peut considérer que "provider_id" == "user_id"
        $providerId = $userId;

        // 4) Valeur transmise par le front :
        //    priorité à interhop[max_patients], fallback sur raw[interhop_max_patients]
        $max = null;

        $interhop = $CI->input->post('interhop');
        if (is_array($interhop) && array_key_exists('max_patients', $interhop)) {
            $s = trim((string)$interhop['max_patients']);
            if ($s !== '' && ctype_digit($s)) {
                $ival = (int)$s;
                if ($ival >= 1) {
                    $max = $ival; // '' ou <1 => NULL (illimité)
                }
            }
        } else {
            // rétro-compat: ancien champ raw[interhop_max_patients]
            $raw = $CI->input->post('raw');
            if (is_array($raw) && array_key_exists('interhop_max_patients', $raw)) {
                $s = trim((string)$raw['interhop_max_patients']);
                if ($s !== '' && ctype_digit($s)) {
                    $ival = (int)$s;
                    if ($ival >= 1) {
                        $max = $ival;
                    }
                }
            }
        }

        // 5) Upsert dans {dbprefix}interhop_providers_limits
        $table_prefixed = $CI->db->dbprefix('interhop_providers_limits'); // ea_interhop_providers_limits

        // table_exists attend le nom NON-préfixé
        if (!$CI->db->table_exists('interhop_providers_limits')) {
            log_message(
                'error',
                "[InterHop] afterAccountSave: table {$table_prefixed} manquante => upsert limite ignoré."
            );
            return; // ne casse pas la sauvegarde
        }

        // Stratégie : NULL => supprime la ligne (illimité), sinon UPSERT
        $userEditorId = (int)($CI->session->userdata('user_id') ?? 0);

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
                    log_message(
                        'debug',
                        "[InterHop] delete limite (illimité) table={$table_prefixed} provider_id={$providerId}"
                    );
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
            $CI->db->query($sql, [$providerId, $max, $userEditorId]);
            if ($CI->db->error()['code']) {
                log_message('error', '[InterHop] upsert limite erreur: ' . json_encode($CI->db->error()));
            } else {
                log_message(
                    'debug',
                    "[InterHop] upsert limite OK table={$table_prefixed} provider_id={$providerId} max="
                    . var_export($max, true)
                );
            }
        } catch (Throwable $e) {
            log_message('error', '[InterHop] upsert limite exception: ' . $e->getMessage());
        } finally {
            $CI->db->db_debug = $prev_debug; // rétablit le comportement CI
        }
    }

    /**
     * Vérifie si un user est soignant :
     * - vrai si on trouve au moins une entrée dans ea_services_providers.id_users = userId
     */
    private function userIsProvider($CI, int $userId): bool
    {
        $row = $CI->db->select('id_users')
            ->from('services_providers') // ea_services_providers avec préfixe automatique
            ->where('id_users', $userId)
            ->limit(1)
            ->get()
            ->row();

        return (bool)($row && isset($row->id_users));
    }
}
