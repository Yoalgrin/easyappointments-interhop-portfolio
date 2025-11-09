<?php defined('BASEPATH') or exit('No direct script access allowed');

class Interhop_providers_limit_model extends CI_Model
{
    /** Nom logique (non préfixé) */
    protected $table_unprefixed = 'interhop_providers_limits';

    /** Nom complet (préfixé) */
    protected function table(): string
    {
        return $this->db->protect_identifiers(
            $this->db->dbprefix($this->table_unprefixed),
            TRUE // protect identifiers (quotes)
        );
    }

    public function get_by_provider(int $provider_id): ?array
    {
        $row = $this->db
            ->get_where($this->db->dbprefix($this->table_unprefixed), ['provider_id' => $provider_id])
            ->row_array();

        return $row ?: null;
    }

    /**
     * Upsert la limite.
     * - $max_patients NULL/'' => supprime la ligne (illimité)
     * - sinon entier >=1
     * Ne lève pas de 500 : log et retourne false en cas d’erreur.
     */
    public function upsert(int $provider_id, $max_patients, ?int $updated_by = null): bool
    {
        $tbl = $this->table();

        // NULL/'' => illimité => delete
        if ($max_patients === null || $max_patients === '') {
            $prev = $this->db->db_debug; $this->db->db_debug = false;
            try {
                $this->db->delete($tbl, ['provider_id' => $provider_id]);
                $err = $this->db->error();
                if ($err['code']) {
                    log_message('error', '[InterHop] providers_limit delete error: ' . json_encode($err));
                    return false;
                }
                return true;
            } finally {
                $this->db->db_debug = $prev;
            }
        }

        $max = max(1, (int)$max_patients);

        // UPSERT
        $sql = "INSERT INTO {$tbl} (provider_id, max_patients, updated_at, updated_by)
                VALUES (?, ?, CURRENT_TIMESTAMP, ?)
                ON DUPLICATE KEY UPDATE
                    max_patients = VALUES(max_patients),
                    updated_at   = VALUES(updated_at),
                    updated_by   = VALUES(updated_by)";

        $prev = $this->db->db_debug; $this->db->db_debug = false;
        try {
            $ok = $this->db->query($sql, [$provider_id, $max, $updated_by]);
            $err = $this->db->error();
            if ($err['code']) {
                log_message('error', '[InterHop] providers_limit upsert error: ' . json_encode($err));
                return false;
            }
            return (bool)$ok;
        } finally {
            $this->db->db_debug = $prev;
        }
    }
}
