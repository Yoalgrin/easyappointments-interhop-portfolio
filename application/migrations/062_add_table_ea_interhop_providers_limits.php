<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Create table: ea_interhop_providers_limits
 *
 * - Clé primaire 1:1 sur provider_id
 * - FK -> providers(id), cascade on delete/update
 */
class Migration_Add_table_ea_interhop_providers_limits extends CI_Migration
{
    public function up()
    {
        $this->load->dbforge();

        if (!$this->db->table_exists('ea_interhop_providers_limits')) {
            // Création fraîche
            $fields = [
                'provider_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => TRUE,  // match ea_providers(id)
                    'null'       => FALSE,
                ],
                'max_patients' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => FALSE, // peut être signé, pas bloquant ici
                    'null'       => TRUE,  // NULL = illimité
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
            $this->dbforge->add_key('provider_id', TRUE); // PK
            $this->dbforge->create_table('ea_interhop_providers_limits', TRUE);

            // Index utile pour la FK updated_by (selon SGBD)
            @$this->db->query('CREATE INDEX idx_eaipl_updated_by ON `ea_interhop_providers_limits`(`updated_by`)');

            // FKs si les tables cibles existent
            if ($this->db->table_exists('ea_providers')) {
                @$this->db->query("
                    ALTER TABLE `ea_interhop_providers_limits`
                    ADD CONSTRAINT `fk_interhop_limits_provider`
                    FOREIGN KEY (`provider_id`) REFERENCES `ea_providers`(`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE
                ");
            }
            if ($this->db->table_exists('ea_users')) {
                @$this->db->query("
                    ALTER TABLE `ea_interhop_providers_limits`
                    ADD CONSTRAINT `fk_interhop_limits_updated_by`
                    FOREIGN KEY (`updated_by`) REFERENCES `ea_users`(`id`)
                    ON DELETE SET NULL ON UPDATE CASCADE
                ");
            }
        } else {
            // Table déjà là : on normalise colonnes + FKs (idempotent)
            // provider_id
            if ($this->db->field_exists('provider_id', 'ea_interhop_providers_limits')) {
                @$this->db->query("
                    ALTER TABLE `ea_interhop_providers_limits`
                    MODIFY `provider_id` INT(11) UNSIGNED NOT NULL
                ");
            } else {
                $this->dbforge->add_column('ea_interhop_providers_limits', [
                    'provider_id' => ['type'=>'INT','constraint'=>11,'unsigned'=>TRUE,'null'=>FALSE],
                ]);
            }

            // max_patients
            if (!$this->db->field_exists('max_patients', 'ea_interhop_providers_limits')) {
                $this->dbforge->add_column('ea_interhop_providers_limits', [
                    'max_patients' => ['type'=>'INT','constraint'=>11,'null'=>TRUE,'default'=>NULL],
                ]);
            }

            // updated_at
            if (!$this->db->field_exists('updated_at', 'ea_interhop_providers_limits')) {
                $this->dbforge->add_column('ea_interhop_providers_limits', [
                    'updated_at' => ['type'=>'TIMESTAMP','null'=>TRUE,'default'=>NULL],
                ]);
            } else {
                // harmonise la nullabilité
                @$this->db->query("
                    ALTER TABLE `ea_interhop_providers_limits`
                    MODIFY `updated_at` TIMESTAMP NULL DEFAULT NULL
                ");
            }

            // updated_by
            if (!$this->db->field_exists('updated_by', 'ea_interhop_providers_limits')) {
                $this->dbforge->add_column('ea_interhop_providers_limits', [
                    'updated_by' => ['type'=>'INT','constraint'=>11,'unsigned'=>TRUE,'null'=>TRUE,'default'=>NULL],
                ]);
            } else {
                @$this->db->query("
                    ALTER TABLE `ea_interhop_providers_limits`
                    MODIFY `updated_by` INT(11) UNSIGNED NULL DEFAULT NULL
                ");
            }

            // PK sur provider_id (au cas où)
            @$this->db->query('ALTER TABLE `ea_interhop_providers_limits` DROP PRIMARY KEY');
            @$this->db->query('ALTER TABLE `ea_interhop_providers_limits` ADD PRIMARY KEY (`provider_id`)');

            // Index updated_by
            @$this->db->query('CREATE INDEX idx_eaipl_updated_by ON `ea_interhop_providers_limits`(`updated_by`)');

            // (Re)pose les FKs si absentes et tables cibles présentes
            if ($this->db->table_exists('ea_providers')) {
                // supprime l’ancienne si nom différent
                // (on ignore les erreurs si elle n'existe pas)
                @$this->db->query("ALTER TABLE `ea_interhop_providers_limits` DROP FOREIGN KEY `fk_eaipl_provider`");
                @$this->db->query("
                    ALTER TABLE `ea_interhop_providers_limits`
                    ADD CONSTRAINT `fk_interhop_limits_provider`
                    FOREIGN KEY (`provider_id`) REFERENCES `ea_providers`(`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE
                ");
            }
            if ($this->db->table_exists('ea_users')) {
                @$this->db->query("ALTER TABLE `ea_interhop_providers_limits` DROP FOREIGN KEY `fk_eaipl_updated_by`");
                @$this->db->query("
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
