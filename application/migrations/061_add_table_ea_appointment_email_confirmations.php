<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Create table: ea_appointment_email_confirmations
 *
 * Relation (optionnelle) : appointment_id -> appointments(id)
 * Contrainte FK posée si la table "appointments" existe.
 */
class Migration_Add_table_ea_appointment_email_confirmations extends CI_Migration
{
    public function up()
    {
        $this->load->dbforge();

        if (!$this->db->table_exists('ea_appointment_email_confirmations')) {
            $fields = [
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => TRUE,
                    'auto_increment' => TRUE,
                ],
                'appointment_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => TRUE,
                    'null'       => TRUE,
                ],
                'email' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => TRUE,
                ],
                'token' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                    'null'       => FALSE,
                ],
                'status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20, // pending|sent|confirmed|expired|canceled
                    'null'       => FALSE,
                    'default'    => 'pending',
                ],
                'sent_at' => [ 'type' => 'DATETIME', 'null' => TRUE, 'default' => NULL ],
                'confirmed_at' => [ 'type' => 'DATETIME', 'null' => TRUE, 'default' => NULL ],
                'expires_at' => [ 'type' => 'DATETIME', 'null' => TRUE, 'default' => NULL ],
                'created_at' => [ 'type' => 'DATETIME', 'null' => TRUE, 'default' => NULL ],
                'updated_at' => [ 'type' => 'DATETIME', 'null' => TRUE, 'default' => NULL ],
            ];

            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);     // PK
            $this->dbforge->add_key('appointment_id'); // index
            $this->dbforge->add_key('token', TRUE);  // unique
            $this->dbforge->create_table('ea_appointment_email_confirmations', TRUE);

            // FK si ea_appointments existe
            if ($this->db->table_exists('ea_appointments')) {
                @$this->db->query("
                    ALTER TABLE `ea_appointment_email_confirmations`
                    ADD CONSTRAINT `fk_ea_aec_appointment`
                    FOREIGN KEY (`appointment_id`) REFERENCES `ea_appointments`(`id`)
                    ON DELETE SET NULL ON UPDATE CASCADE
                ");
            }
        }
    }

    public function down()
    {
        $this->load->dbforge();
        if ($this->db->table_exists('ea_appointment_email_confirmations')) {
            $this->dbforge->drop_table('ea_appointment_email_confirmations', TRUE);
        }
    }
}
