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
 * Class Create_Externals_Tools_Table
 *
 * @property CI_DB_query_builder $db
 * @property CI_DB_forge $dbforge
 */
class Migration_Create_externals_tools extends CI_Migration
{
    /**
     * Upgrade method.
     */
    public function up()
    {
        $this->dbforge->add_field([
            'id' => [
                'type' => 'INT',
                'auto_increment' => true,
                'null' => false,
                'constraint' => 1,
            ],
            'name' => [
                'type' => 'varchar',
                'constraint' => '256',
                'null' => true,
            ],
            'description' => [
                'type' => 'varchar',
                'constraint' => '512',
                'null' => true,
            ],
            'link' => [
                'type' => 'varchar',
                'constraint' => '256',
                'null' => true,
            ],
         ]);

        $this->dbforge->add_key('id', true);
        $this->dbforge->create_table('externals_tools',TRUE, ['engine' => 'InnoDB']);
    }

    /**
     * Downgrade method.
     */
    public function down()
    {
        $this->dbforge->drop_table('externals_tools');
    }
}
