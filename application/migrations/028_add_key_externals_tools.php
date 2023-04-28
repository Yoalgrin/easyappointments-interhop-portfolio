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
 * Class Add_Key_Externals_Tools
 *
 * @property CI_DB_query_builder $db
 * @property CI_DB_forge $dbforge
 */
class Migration_add_key_externals_tools extends CI_Migration
{
    /**
     * Upgrade method.
     */
    public function up()
    {
        $fields = [];

        // Add key_externals_tools column to users table if not exists.
        if (!$this->db->field_exists('key_externals_tools', 'appointments'))
            $fields['key_externals_tools'] = ['type' => 'VARCHAR', 'constraint' => '256', 'null' => TRUE];

        // If some column need to be added.
        if (count($fields)) $this->dbforge->add_column('appointments', $fields);
    }

    /**
     * Downgrade method.
     */
    public function down()
    {
        $this->dbforge->drop_column('appointments', 'key_externals_tools');
    }
}
