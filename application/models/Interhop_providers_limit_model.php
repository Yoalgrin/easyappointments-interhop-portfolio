<?php defined('BASEPATH') or exit('No direct script access allowed');

class Interhop_providers_limit_model extends CI_Model
{
    protected $table = 'ea_interhop_providers_limits';

    public function get_by_provider(int $provider_id)
    {
        return $this->db->get_where($this->table, ['provider_id' => $provider_id])->row_array();
    }

    public function upsert(int $provider_id, ?int $max_patients, int $updated_by = 0): bool
    {
        // On tente une insertion ; s’il y a conflit sur PK, on update.
        $sql = "
            INSERT INTO {$this->table} (provider_id, max_patients, updated_at, updated_by)
            VALUES (?, ?, CURRENT_TIMESTAMP, ?)
            ON DUPLICATE KEY UPDATE
              max_patients = VALUES(max_patients),
              updated_at   = VALUES(updated_at),
              updated_by   = VALUES(updated_by)
        ";
        $this->db->query($sql, [$provider_id, $max_patients, $updated_by]);
        return $this->db->error()['code'] === 0;
    }
}
