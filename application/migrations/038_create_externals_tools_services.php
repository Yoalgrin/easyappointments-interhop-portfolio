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
 * Class Create_Externals_Tools_Services_Table
 *
 * @property CI_DB_query_builder $db
 * @property CI_DB_forge $dbforge
 */
class Migration_Create_externals_tools_services extends CI_Migration
{
    /**
     * Upgrade method.
     */
    public function up()
    {
        $this->dbforge->add_field([
            'id_external_tool' => [
                'type' => 'INT',
                'auto_increment' => false,
                'null' => false,
            ],
            'id_service' => [
                'type' => 'INT',
                'auto_increment' => false,
                'null' => false,
            ],
        ]);

        $this->dbforge->add_key('id_external_tool', true);
        $this->dbforge->add_key('id_service', true);

        $this->dbforge->create_table('externals_tools_services',TRUE, ['engine' => 'InnoDB']);

        $this->db->query('ALTER TABLE `' . $this->db->dbprefix('externals_tools_services') . '`
                ADD CONSTRAINT `FK_externals_tools_services_external_tool` FOREIGN KEY (`id_external_tool`) 
                REFERENCES `' . $this->db->dbprefix('externals_tools') . '` (`id`)
                ON DELETE CASCADE 
                ON UPDATE CASCADE;');
        $this->db->query('ALTER TABLE `' . $this->db->dbprefix('externals_tools_services') . '` 
                ADD CONSTRAINT `FK_externals_tools_services_service` FOREIGN KEY (`id_service`) 
                REFERENCES `' . $this->db->dbprefix('services') . '` (`id`)
                ON DELETE CASCADE 
                ON UPDATE CASCADE;');
    }

    /**
     * Downgrade method.
     */
    public function down()
    {
        $this->db->query('ALTER TABLE `' . $this->db->dbprefix('externals_tools_services') . '` DROP FOREIGN KEY `FK_externals_tools_services_external_tool`');
        $this->db->query('ALTER TABLE `' . $this->db->dbprefix('externals_tools_services') . '` DROP FOREIGN KEY `FK_externals_tools_services_service`');
        $this->dbforge->drop_table('externals_tools_services');
    }
}
