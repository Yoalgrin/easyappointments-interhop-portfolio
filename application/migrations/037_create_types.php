<?php
defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com> * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3 * @link        http://easyappointments.org * @since       v1.4.0
 * ---------------------------------------------------------------------------- */


/**
 * Class Create_Types_Table
 *
 * @property CI_DB_query_builder $db
 * @property CI_DB_forge $dbforge
 */
class Migration_Create_types extends CI_Migration
{
    /**
     * Upgrade method.
     */
    public function up()
    {
        $this->dbforge->add_field('id');
        $this->dbforge->add_field([
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => '256'
            ]
        ]);
        $this->dbforge->create_table('types', ['engine' => 'InnoDB']);

        //Add one-to-many relationship with externals_tools
        $this->db->query('ALTER TABLE ea_externals_tools ADD id_type INT');
        $this->db->query('ALTER TABLE ea_externals_tools ADD CONSTRAINT FK_externals_tools_types FOREIGN KEY (id_type) REFERENCES ea_types(id) ON DELETE CASCADE ON UPDATE CASCADE');
    }

    /**
     * Downgrade method.
     */
    public function down()
    {
        $this->db->query('ALTER TABLE ea_externals_tools DROP FOREIGN KEY FK_externals_tools_types');
        $this->db->query('ALTER TABLE ea_externals_tools DROP COLUMN  id_type');
        $this->dbforge->drop_table('types');
    }
}
