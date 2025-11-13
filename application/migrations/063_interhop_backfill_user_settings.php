<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Interhop_backfill_user_settings extends CI_Migration
{
    public function up()
    {
        $db = $this->db;

        // Liste des providers sans settings
        $missing = $db->query("
            SELECT u.id AS user_id
            FROM ea_users u
            INNER JOIN ea_roles r ON r.id = u.id_roles
            LEFT JOIN ea_user_settings s ON s.id_users = u.id
            WHERE r.slug = 'provider'
              AND s.id_users IS NULL
        ")->result_array();

        if (empty($missing)) { return; }

        foreach ($missing as $row) {
            // Insertion minimale : SEULEMENT id_users (tout le reste peut rester NULL / DEFAULT)
            $db->insert('ea_user_settings', [
                'id_users' => (int)$row['user_id'],
            ]);
        }
    }

    public function down()
    {
        // Pas de rollback destructif ici.
    }
}

