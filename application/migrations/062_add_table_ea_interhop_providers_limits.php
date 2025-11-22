<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Create table: ea_interhop_providers_limits
 * - PK 1:1 sur provider_id
 * - FK provider_id -> ea_users(id) (les "providers" sont des users avec role=provider)
 * - FK updated_by  -> ea_users(id)
 */
class Migration_Add_table_ea_interhop_providers_limits extends CI_Migration
{
    public function up()
    {
        $this->load->dbforge();

        if (!$this->db->table_exists('ea_interhop_providers_limits')) {
            $fields = [
                'provider_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => TRUE,
                    'null'       => FALSE,
                ],
                'max_patients' => [
                    'type'       => 'INT',
                    'constraint' => 10,
                    'unsigned'   => TRUE,   // 0..N, NULL = illimité
                    'null'       => TRUE,
                    'default'    => NULL,
                ],
                'updated_at' => [
                    'type'    => 'TIMESTAMP',
                    'null'    => TRUE,
                    'default' => NULL,
                ],
                'updated_by' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => TRUE,
                    'null'       => TRUE,
                    'default'    => NULL,
                ],
            ];

            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('provider_id', TRUE); // PK 1:1
            $this->dbforge->create_table('ea_interhop_providers_limits', TRUE);

            // Index utile
            $this->db->query('CREATE INDEX idx_eaipl_updated_by ON `ea_interhop_providers_limits`(`updated_by`)');

            // FKs si tables cibles présentes
            if ($this->db->table_exists('ea_users')) {
                // provider_id -> ea_users(id)
                @$this->db->query("
                    ALTER TABLE `ea_interhop_providers_limits`
                    ADD CONSTRAINT `fk_interhop_limits_provider`
                    FOREIGN KEY (`provider_id`) REFERENCES `ea_users`(`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE
                ");
                // updated_by -> ea_users(id)
                $this->db->query("
                    ALTER TABLE `ea_interhop_providers_limits`
                    ADD CONSTRAINT `fk_interhop_limits_updated_by`
                    FOREIGN KEY (`updated_by`) REFERENCES `ea_users`(`id`)
                    ON DELETE SET NULL ON UPDATE CASCADE
                ");
            }
        } else {
            // Normalisations idempotentes
            if ($this->db->field_exists('provider_id', 'ea_interhop_providers_limits')) {
                $this->db->query("
                    ALTER TABLE `ea_interhop_providers_limits`
                    MODIFY `provider_id` INT(11) UNSIGNED NOT NULL
                ");
            } else {
                $this->dbforge->add_column('ea_interhop_providers_limits', [
                    'provider_id' => ['type'=>'INT','constraint'=>11,'unsigned'=>TRUE,'null'=>FALSE],
                ]);
            }

            if ($this->db->field_exists('max_patients', 'ea_interhop_providers_limits')) {
                $this->db->query("
                    ALTER TABLE `ea_interhop_providers_limits`
                    MODIFY `max_patients` INT(10) UNSIGNED NULL DEFAULT NULL
                ");
            } else {
                $this->dbforge->add_column('ea_interhop_providers_limits', [
                    'max_patients' => ['type'=>'INT','constraint'=>10,'unsigned'=>TRUE,'null'=>TRUE,'default'=>NULL],
                ]);
            }

            if (!$this->db->field_exists('updated_at', 'ea_interhop_providers_limits')) {
                $this->dbforge->add_column('ea_interhop_providers_limits', [
                    'updated_at' => ['type'=>'TIMESTAMP','null'=>TRUE,'default'=>NULL],
                ]);
            } else {
                $this->db->query("
                    ALTER TABLE `ea_interhop_providers_limits`
                    MODIFY `updated_at` TIMESTAMP NULL DEFAULT NULL
                ");
            }

            if (!$this->db->field_exists('updated_by', 'ea_interhop_providers_limits')) {
                $this->dbforge->add_column('ea_interhop_providers_limits', [
                    'updated_by' => ['type'=>'INT','constraint'=>11,'unsigned'=>TRUE,'null'=>TRUE,'default'=>NULL],
                ]);
            } else {
                $this->db->query("
                    ALTER TABLE `ea_interhop_providers_limits`
                    MODIFY `updated_by` INT(11) UNSIGNED NULL DEFAULT NULL
                ");
            }

            // PK 1:1 (au cas où)
            $this->db->query('ALTER TABLE `ea_interhop_providers_limits` DROP PRIMARY KEY');
            $this->db->query('ALTER TABLE `ea_interhop_providers_limits` ADD PRIMARY KEY (`provider_id`)');

            // Index updated_by (idempotent)
            $this->db->query('CREATE INDEX idx_eaipl_updated_by ON `ea_interhop_providers_limits`(`updated_by`)');

            // Reposer les FKs proprement si ea_users existe
            if ($this->db->table_exists('ea_users')) {
                // Drop si ancien nom ou mauvaise cible
                $this->db->query("ALTER TABLE `ea_interhop_providers_limits` DROP FOREIGN KEY `fk_eaipl_provider`");
                $this->db->query("ALTER TABLE `ea_interhop_providers_limits` DROP FOREIGN KEY `fk_interhop_limits_provider`");
                $this->db->query("
                    ALTER TABLE `ea_interhop_providers_limits`
                    ADD CONSTRAINT `fk_interhop_limits_provider`
                    FOREIGN KEY (`provider_id`) REFERENCES `ea_users`(`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE
                ");

                $this->db->query("ALTER TABLE `ea_interhop_providers_limits` DROP FOREIGN KEY `fk_eaipl_updated_by`");
                $this->db->query("ALTER TABLE `ea_interhop_providers_limits` DROP FOREIGN KEY `fk_interhop_limits_updated_by`");
                $this->db->query("
                    ALTER TABLE `ea_interhop_providers_limits`
                    ADD CONSTRAINT `fk_interhop_limits_updated_by`
                    FOREIGN KEY (`updated_by`) REFERENCES `ea_users`(`id`)
                    ON DELETE SET NULL ON UPDATE CASCADE
                ");
            }
        }
    }

    public function down()
    {
        $this->load->dbforge();
        if ($this->db->table_exists('ea_interhop_providers_limits')) {
            $this->dbforge->drop_table('ea_interhop_providers_limits', TRUE);
        }
    }
}
