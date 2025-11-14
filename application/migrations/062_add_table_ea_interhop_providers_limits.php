<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Create table: ea_interhop_providers_limits
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
                    'null'       => FALSE,
                ],
                'max_patients' => [
                    'type'       => 'INT',
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
            $this->dbforge->create_table('ea_interhop_providers_limits', TRUE);

            @$this->db->query('CREATE INDEX idx_eaipl_updated_by ON `ea_interhop_providers_limits`(`updated_by`)');

                @$this->db->query("
                    ALTER TABLE `ea_interhop_providers_limits`
                    ADD CONSTRAINT `fk_interhop_limits_provider`
                    ON DELETE CASCADE ON UPDATE CASCADE
                ");
                @$this->db->query("
                    ALTER TABLE `ea_interhop_providers_limits`
                    ADD CONSTRAINT `fk_interhop_limits_updated_by`
                    FOREIGN KEY (`updated_by`) REFERENCES `ea_users`(`id`)
                    ON DELETE SET NULL ON UPDATE CASCADE
                ");
            }
        } else {
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

                $this->dbforge->add_column('ea_interhop_providers_limits', [
                ]);
            }

            if (!$this->db->field_exists('updated_at', 'ea_interhop_providers_limits')) {
                $this->dbforge->add_column('ea_interhop_providers_limits', [
                    'updated_at' => ['type'=>'TIMESTAMP','null'=>TRUE,'default'=>NULL],
                ]);
            } else {
                @$this->db->query("
                    ALTER TABLE `ea_interhop_providers_limits`
                    MODIFY `updated_at` TIMESTAMP NULL DEFAULT NULL
                ");
            }

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

            @$this->db->query('ALTER TABLE `ea_interhop_providers_limits` DROP PRIMARY KEY');
            @$this->db->query('ALTER TABLE `ea_interhop_providers_limits` ADD PRIMARY KEY (`provider_id`)');

            @$this->db->query('CREATE INDEX idx_eaipl_updated_by ON `ea_interhop_providers_limits`(`updated_by`)');

                @$this->db->query("ALTER TABLE `ea_interhop_providers_limits` DROP FOREIGN KEY `fk_eaipl_provider`");
                @$this->db->query("
                    ALTER TABLE `ea_interhop_providers_limits`
                    ADD CONSTRAINT `fk_interhop_limits_provider`
                    ON DELETE CASCADE ON UPDATE CASCADE
                ");
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
