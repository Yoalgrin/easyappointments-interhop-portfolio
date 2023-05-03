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
 * Class Create_Appointments_Externals_Tools_Table
 *
 * @property CI_DB_query_builder $db
 * @property CI_DB_forge $dbforge
 */
class Migration_Create_Appointments_Externals_Tools extends CI_Migration
{
    /**
     * Upgrade method.
     */
    public function up()
    {
        $this->dbforge->add_field([
            'id_externals_tools' => [
                'type' => 'INT',
                'auto_increment' => false,
                'null' => false,
            ],
            'id_appointments' => [
                'type' => 'INT',
                'auto_increment' => false,
                'null' => false,
            ],
        ]);

        $this->dbforge->add_key('id_externals_tools', true);
        $this->dbforge->add_key('id_appointments', true);

        $this->dbforge->create_table('appointments_externals_tools',TRUE, ['engine' => 'InnoDB']);

        $this->db->query('ALTER TABLE `' . $this->db->dbprefix('appointments_externals_tools') . '`
                ADD CONSTRAINT `appointments_externals_tools_appointments` FOREIGN KEY (`id_appointments`) REFERENCES `' . $this->db->dbprefix('appointments') . '` (`id`)
                ON DELETE CASCADE 
                ON UPDATE CASCADE;');
        $this->db->query('ALTER TABLE `' . $this->db->dbprefix('appointments_externals_tools') . '` 
                ADD CONSTRAINT `appointments_externals_tools_externals_tools` FOREIGN KEY (`id_externals_tools`) REFERENCES `' . $this->db->dbprefix('externals_tools') . '` (`id`)
                ON DELETE CASCADE 
                ON UPDATE CASCADE;');
    }

    /**
     * Downgrade method.
     */
    public function down()
    {
        $this->db->query('ALTER TABLE `' . $this->db->dbprefix('appointments_externals_tools') . '` DROP FOREIGN KEY `appointments_externals_tools_appointments`');
        $this->db->query('ALTER TABLE `' . $this->db->dbprefix('appointments_externals_tools') . '` DROP FOREIGN KEY `appointments_externals_tools_externals_tools`');
        $this->dbforge->drop_table('appointments_externals_tools');
    }
}

