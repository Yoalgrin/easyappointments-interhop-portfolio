<?php defined('BASEPATH') or exit('No direct script access allowed');

class Interhop_providers_limit_model extends CI_Model
{
    private $table = 'ea_interhop_providers_limits';

    public function get_by_provider(int $provider_id): ?array
    {
        $row = $this->db->get_where($this->table, ['provider_id' => $provider_id])->row_array();
        return $row ?: null;
    }

    public function upsert(int $provider_id, $max_patients, ?int $updated_by = null): bool
    {
        $max = (is_null($max_patients) || $max_patients === '') ? null : max(1, (int)$max_patients);
        $now = date('Y-m-d H:i:s');

        $sql = "INSERT INTO {$this->table} (provider_id, max_patients, updated_at, updated_by)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    max_patients = VALUES(max_patients),
                    updated_at   = VALUES(updated_at),
                    updated_by   = VALUES(updated_by)";
        return (bool)$this->db->query($sql, [$provider_id, $max, $now, $updated_by]);
    }
}

