<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.0.0
 * ---------------------------------------------------------------------------- */

/**
 * Services Model
 *
 * @package Models
 */
class Services_model extends EA_Model {
    /**
     * Services_Model constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('data_validation');
    }

    /**
     * Add (insert or update) a service record on the database
     *
     * @param array $service Contains the service data. If an 'id' value is provided then the record will be updated.
     *
     * @return int Returns the record id.
     * @throws Exception
     */
    public function add($service)
    {
        $this->validate($service);

        //Extract service external tools if there is any.
        $externals_tools = $service['externals_tools'] ?? [];
        unset($service['externals_tools']);

        if ( ! isset($service['id']))
        {
            $service['id'] = $this->insert($service);
            $service_id = $this->db->insert_id();
        }
        else
        {
            $this->update($service);
            $service_id = $service['id'];
        }

        if ( ! empty($externals_tools)) {
            //Insert service externals tools in dedicated table.
            $this->save_service_externals_tools($externals_tools, $service_id );
        }

        return (int)$service['id'];
    }

    /**
     * Validate a service record data.
     *
     * @param array $service Contains the service data.
     *
     * @return bool Returns the validation result.
     *
     * @throws Exception If service validation fails.
     */
    public function validate($service)
    {
        // If record id is provided we need to check whether the record exists in the database.
        if (isset($service['id']))
        {
            $num_rows = $this->db->get_where('services', ['id' => $service['id']])->num_rows();

            if ($num_rows == 0)
            {
                throw new Exception('Provided service id does not exist in the database.');
            }
        }

        // Check if service category id is valid (only when present).
        if ( ! empty($service['id_service_categories']))
        {
            $num_rows = $this->db->get_where('service_categories',
                ['id' => $service['id_service_categories']])->num_rows();
            if ($num_rows == 0)
            {
                throw new Exception('Provided service category id does not exist in database.');
            }
        }

        //Check if service external tools id are valid if there is any
        if ( ! empty($service['externals_tools']))
        {
            foreach ($service['externals_tools'] as $external_tool){
                $num_rows = $this->db->get_where('externals_tools',['id' => intval($external_tool)])->num_rows();
                if ($num_rows == 0) {
                    throw new Exception('Provided external tool id does not exist in database.');
                }
            }
        }

        // Check for required fields
        if ($service['name'] == '')
        {
            throw new Exception('Not all required service fields where provided: '
                . print_r($service, TRUE));
        }

        // Duration must be int
        if ($service['duration'] !== NULL)
        {
            if ( ! is_numeric($service['duration']))
            {
                throw new Exception('Service duration is not numeric.');
            }

            if ((int)$service['duration'] < EVENT_MINIMUM_DURATION)
            {
                throw new Exception('The service duration cannot be less than ' . EVENT_MINIMUM_DURATION . ' minutes.');
            }
        }

        if ($service['price'] !== NULL)
        {
            if ( ! is_numeric($service['price']))
            {
                throw new Exception('Service price is not numeric.');
            }
        }

        // Availabilities type must have the correct value.
        if ($service['availabilities_type'] !== NULL && $service['availabilities_type'] !== AVAILABILITIES_TYPE_FLEXIBLE
            && $service['availabilities_type'] !== AVAILABILITIES_TYPE_FIXED)
        {
            throw new Exception('Service availabilities type must be either ' . AVAILABILITIES_TYPE_FLEXIBLE
                . ' or ' . AVAILABILITIES_TYPE_FIXED . ' (given ' . $service['availabilities_type'] . ')');
        }

        if ($service['attendants_number'] !== NULL && ( ! is_numeric($service['attendants_number'])
                || $service['attendants_number'] < 1))
        {
            throw new Exception('Service attendants number must be numeric and greater or equal to one: '
                . $service['attendants_number']);
        }

        return TRUE;
    }

    /**
     * Insert service record into database.
     *
     * @param array $service Contains the service record data.
     *
     * @return int Returns the new service record id.
     *
     * @throws Exception If service record could not be inserted.
     */
    protected function insert($service)
    {
        if ( ! $this->db->insert('services', $service))
        {
            throw new Exception('Could not insert service record.');
        }

        return (int)$this->db->insert_id();
    }

    /**
     * Update service record.
     *
     * @param array $service Contains the service data. The record id needs to be included in the array.
     *
     * @throws Exception If service record could not be updated.
     */
    protected function update($service)
    {
        $this->db->where('id', $service['id']);
        if ( ! $this->db->update('services', $service))
        {
            throw new Exception('Could not update service record');
        }
    }

    /**
     * Checks whether a service record already exists in the database.
     *
     * @param array $service Contains the service data. Name, duration and price values are mandatory in order to
     * perform the checks.
     *
     * @return bool Returns whether the service record exists.
     *
     * @throws Exception If required fields are missing.
     */
    public function exists($service)
    {
        if ( ! isset(
            $service['name'],
            $service['duration'],
            $service['price']
        ))
        {
            throw new Exception('Not all service fields are provided in order to check whether '
                . 'a service record already exists: ' . print_r($service, TRUE));
        }

        $num_rows = $this->db->get_where('services', [
            'name' => $service['name'],
            'duration' => $service['duration'],
            'price' => $service['price']
        ])->num_rows();

        return $num_rows > 0;
    }

    /**
     * Get the record id of an existing record.
     *
     * Notice: The record must exist, otherwise an exception will be raised.
     *
     * @param array $service Contains the service record data. Name, duration and price values are mandatory for this
     * method to complete.
     *
     * @return int
     *
     * @throws Exception If required fields are missing.
     * @throws Exception If requested service was not found.
     */
    public function find_record_id($service)
    {
        if ( ! isset($service['name'])
            || ! isset($service['duration'])
            || ! isset($service['price']))
        {
            throw new Exception('Not all required fields where provided in order to find the '
                . 'service record id.');
        }

        $result = $this->db->get_where('services', [
            'name' => $service['name'],
            'duration' => $service['duration'],
            'price' => $service['price']
        ]);

        if ($result->num_rows() == 0)
        {
            throw new Exception('Could not find service record id');
        }

        return $result->row()->id;
    }

    /**
     * Delete a service record from database.
     *
     * @param int $service_id Record id to be deleted.
     *
     * @return bool Returns the delete operation result.
     *
     * @throws Exception If $service_id argument is invalid.
     */
    public function delete($service_id)
    {
        if ( ! is_numeric($service_id))
        {
            throw new Exception('Invalid argument type $service_id (value:"' . $service_id . '"');
        }

        $num_rows = $this->db->get_where('services', ['id' => $service_id])->num_rows();
        if ($num_rows == 0)
        {
            return FALSE; // Record does not exist
        }

        return $this->db->delete('services', ['id' => $service_id]);
    }

    /**
     * Get a specific row from the services db table.
     *
     * @param int $service_id The record's id to be returned.
     *
     * @return array Returns an associative array with the selected record's data. Each key has the same name as the
     * database field names.
     *
     * @throws Exception If $service_id argument is not valid.
     */
    public function get_row($service_id)
    {
        if ( ! is_numeric($service_id))
        {
            throw new Exception('$service_id argument is not an numeric (value: "' . $service_id . '")');
        }

        $service = $this->db->get_where('services', ['id' => $service_id])->row_array();

        $this->get_service_external_tools($service);

        return $service;
    }


    /**
     * Get a specific field value from the database.
     *
     * @param string $field_name The field name of the value to be
     * returned.
     * @param int $service_id The selected record's id.
     *
     * @return string Returns the records value from the database.
     *
     * @throws Exception If $service_id argument is invalid.
     * @throws Exception If $field_name argument is invalid.
     * @throws Exception if requested service does not exist in the database.
     * @throws Exception If requested field name does not exist in the database.
     */
    public function get_value($field_name, $service_id)
    {
        if ( ! is_numeric($service_id))
        {
            throw new Exception('Invalid argument provided as $service_id: ' . $service_id);
        }

        if ( ! is_string($field_name))
        {
            throw new Exception('$field_name argument is not a string: ' . $field_name);
        }

        if ($this->db->get_where('services', ['id' => $service_id])->num_rows() == 0)
        {
            throw new Exception('The record with the $service_id argument does not exist in the database: ' . $service_id);
        }

        $row_data = $this->db->get_where('services', ['id' => $service_id])->row_array();

        if ( ! array_key_exists($field_name, $row_data))
        {
            throw new Exception('The given $field_name argument does not exist in the database: '
                . $field_name);
        }

        return $row_data[$field_name];
    }

    /**
     * Get all, or specific records from service's table.
     *
     * Example:
     *
     * $this->services_model->get_batch(['id' => $record_id]);
     *
     * @param mixed $where
     * @param int|null $limit
     * @param int|null $offset
     * @param mixed $order_by
     *
     * @return array Returns the rows from the database.
     */
    public function get_batch($where = NULL, $limit = NULL, $offset = NULL, $order_by = 'name ASC')
    {
        if ($where !== NULL)
        {
            $this->db->where($where);
        }

        if ($order_by !== NULL)
        {
            $this->db->order_by($order_by);
        }

        $services = $this->db->get('services', $limit, $offset)->result_array();

        foreach ($services as &$service) {
            $this->get_service_external_tools($service);
        }

        return $services;
    }

    /**
     * This method returns all the services from the database.
     *
     * @return array Returns an object array with all the database services.
     */
    public function get_available_services($where = NULL)
    {
        $this->db->distinct();

        $this->db->select('services.*, service_categories.name AS category_name, service_categories.id AS category_id')
            ->from('services')
            ->join('services_providers',
                'services_providers.id_services = services.id', 'left')
            ->join('service_categories',
                'service_categories.id = services.id_service_categories', 'inner')
            ->order_by('name ASC');

        if ($where) {
            $this->db->where($where);
        }

        $services = $this->db->get()->result_array();

        $uncategorized_services = $this->db->get_where('services', ['id_service_categories' => NULL])->result_array();

        $services = array_merge($services, $uncategorized_services);

        //Include all providers and external tools for each service.
        foreach ($services as &$service) {

            $providers = $this->db->get_where('services_providers', ['id_services' => $service['id']])->result_array();

            $service['providers'] = [];
            foreach ($providers as $provider) {
                $service['providers'][] = $provider['id_users'];
            }

            $this->get_service_external_tools($service);

        }

        return $services;
    }

    /**
     * Add (insert or update) a service category record into database.
     *
     * @param array $category Contains the service category data.
     *
     * @return int Returns the record ID.
     *
     * @throws Exception If service category data are invalid.
     */
    public function add_category($category)
    {
        if ( ! $this->validate_category($category))
        {
            throw new Exception('Service category data are invalid.');
        }

        if ( ! isset($category['id']))
        {
            $this->db->insert('service_categories', $category);
            $category['id'] = $this->db->insert_id();
        }
        else
        {
            $this->db->where('id', $category['id']);
            $this->db->update('service_categories', $category);
        }

        return (int)$category['id'];
    }

    /**
     * Validate a service category record data. This method must be used before adding
     * a service category record into database in order to secure the record integrity.
     *
     * @param array $category Contains the service category data.
     *
     * @return bool Returns the validation result.
     *
     * @throws Exception If required fields are missing.
     */
    public function validate_category($category)
    {

        try
        {
            // Required Fields
            if ( ! isset($category['name']))
            {
                throw new Exception('Not all required fields where provided ');
            }

            if ($category['name'] == '' || $category['name'] == NULL)
            {
                throw new Exception('Required fields cannot be empty or null ($category: '
                    . print_r($category, TRUE) . ')');
            }

            return TRUE;
        }
        catch (Exception $exception)
        {
            return FALSE;
        }
    }

    /**
     * Delete a service category record from the database.
     *
     * @param int $category_id Record id to be deleted.
     *
     * @return bool Returns the delete operation result.
     *
     * @throws Exception if Service category record was not found.
     */
    public function delete_category($category_id)
    {
        if ( ! is_numeric($category_id))
        {
            throw new Exception('Invalid argument given for $category_id: ' . $category_id);
        }

        $num_rows = $this->db->get_where('service_categories', ['id' => $category_id])
            ->num_rows();
        if ($num_rows == 0)
        {
            throw new Exception('Service category record not found in database.');
        }

        $this->db->where('id', $category_id);
        return $this->db->delete('service_categories');
    }

    /**
     * Get a service category record data.
     *
     * @param int $category_id Record id to be retrieved.
     *
     * @return array Returns the record data from the database.
     *
     * @throws Exception If $category_id argument is invalid.
     * @throws Exception If service category record does not exist.
     */
    public function get_category($category_id)
    {
        if ( ! is_numeric($category_id))
        {
            throw new Exception('Invalid argument type given $category_id: ' . $category_id);
        }

        $result = $this->db->get_where('service_categories', ['id' => $category_id]);

        if ($result->num_rows() == 0)
        {
            throw new Exception('Service category record does not exist.');
        }

        return $result->row_array();
    }

    /**
     * Get all service category records from database.
     *
     * @param mixed $where
     * @param int|null $limit
     * @param int|null $offset
     * @param mixed $order_by
     *
     * @return array Returns an array that contains all the service category records.
     */
    public function get_all_categories($where = NULL, $limit = NULL, $offset = NULL, $order_by = 'name ASC')
    {
        if ($where !== NULL)
        {
            $this->db->where($where);
        }

        if ($order_by !== NULL)
        {
            $this->db->order_by($order_by);
        }

        return $this->db->get('service_categories', $limit, $offset)->result_array();
    }


    /**
     * Get a service external tools from the externals_tools_services table.
     *
     * Use this in services get methods.
     *
     * @param array $service The service to be returned.
     */
    protected function get_service_external_tools(&$service)
    {
        $externals_tools = $this->db->get_where('ea_externals_tools_services', ['id_service' => $service['id']])->result_array();

        foreach ($externals_tools as $external_tool) {
            $service['externals_tools'][] = $external_tool['id_external_tool'];
        }
        return $service;
    }

    /**
     * Add (insert or update) an external tool record into database.
     *
     * @param array $external_tool Contains the external tool data.
     *
     * @return int Returns the record ID.
     *
     * @throws Exception If external tool data are invalid.
     */
    public function add_external_tool($external_tool)
    {
        if ( ! $this->validate_external_tool($external_tool))
        {
            throw new Exception('External tool data are invalid.');
        }

        if ( ! isset($external_tool['id']))
        {
            $this->db->insert('externals_tools', $external_tool);
            $external_tool['id'] = $this->db->insert_id();
        }
        else
        {
            $this->db->where('id', $external_tool['id']);
            $this->db->update('externals_tools', $external_tool);
        }


        return (int)$external_tool['id'];
    }

    /**
     * Validate an external tool record data. This method must be used before adding
     * an external tool record into database in order to secure the record integrity.
     *
     * @param array $external_tool Contains the external tool data.
     *
     * @return bool Returns the validation result.
     *
     * @throws Exception If required fields are missing.
     */
    public function validate_external_tool($external_tool)
    {
        try
        {
            // Required Fields
            if ( ! isset($external_tool['name']))
            {
                throw new Exception('Not all required fields where provided ');
            }

            if ($external_tool['name'] == '' || $external_tool['name'] == NULL)
            {
                throw new Exception('Required fields cannot be empty or null ($external_tool: '
                    . print_r($external_tool, TRUE) . ')');
            }

            return TRUE;
        }
        catch (Exception $exception)
        {
            return FALSE;
        }
    }

    /**
     * Delete an external tool record from the database.
     *
     * @param int $external_tool_id Record id to be deleted.
     *
     * @return bool Returns the delete operation result.
     *
     * @throws Exception if external tool record was not found.
     */
    public function delete_external_tool($external_tool_id)
    {


        if ( ! is_numeric($external_tool_id))
        {
            throw new Exception('Invalid argument given for $external_tool_id: ' . $external_tool_id);
        }

        $num_rows = $this->db->get_where('externals_tools', ['id' => $external_tool_id])
            ->num_rows();
        if ($num_rows == 0)
        {
            throw new Exception('External tool record not found in database.');
        }

        $this->db->where('id', $external_tool_id);
        return $this->db->delete('externals_tools');
    }

    /**
     * Get an external tool record data.
     *
     * @param int $external_tool_id Record id to be retrieved.
     *
     * @return array Returns the record data from the database.
     *
     * @throws Exception If $category_id argument is invalid.
     * @throws Exception If external tool record does not exist.
     */
    public function get_external_tool($external_tool_id)
    {
        if ( ! is_numeric($external_tool_id))
        {
            throw new Exception('Invalid argument type given $external_tool_id: ' . $external_tool_id);
        }

        $result = $this->db->get_where('externals_tools', ['id' => $external_tool_id]);

        if ($result->num_rows() == 0)
        {
            throw new Exception('External tool record does not exist.');
        }

        return $result->row_array();
    }

    /**
     * Get all external tool records from database.
     *
     * @param mixed $where
     * @param int|null $limit
     * @param int|null $offset
     * @param mixed $order_by
     *
     * @return array Returns an array that contains all the external tool records.
     */
    public function get_all_external_tools($where = NULL, $limit = NULL, $offset = NULL, $order_by = 'name ASC')
    {
        if ($where !== NULL)
        {
            $this->db->where($where);
        }

        if ($order_by !== NULL)
        {
            $this->db->order_by($order_by);
        }

        return $this->db->get('externals_tools', $limit, $offset)->result_array();
    }

    /**
     * Save the service external tools in the database (use on both insert and update operation).
     *
     * @param array $externals_tools Contains the external tools ids that the selected service can use.
     * @param int $service_id The selected service record id.
     *
     * @throws Exception When the $externals_tools argument type is not array.
     * @throws Exception When the $service_id argument type is not int.
     */
    protected function save_service_externals_tools($externals_tools, $service_id)
    {
        // Validate method arguments.
        if ( ! is_array($externals_tools))
        {
            throw new Exception('Invalid argument type $externals_tools: ' . $externals_tools);
        }

        if ( ! is_numeric($service_id))
        {
            throw new Exception('Invalid argument type $service_id: ' . $service_id);
        }

        // Save service external tools in the database (delete old records and add new).
        $this->db->delete('externals_tools_services', ['id_service' => $service_id]);

        foreach ($externals_tools as $external_tool_id)
        {
            $external_tool_service = [
                'id_service' => $service_id,
                'id_external_tool' => $external_tool_id
            ];
            $this->db->insert('externals_tools_services', $external_tool_service);
        }
    }


    /**
     * Get all external tool types from database.
     *
     * @return array Return an array that contains all the types.
     */
    public function get_external_tool_types()
    {
        $this->db->order_by('name ASC');
        return $this->db->get('types')->result_array();
    }


    /**
     * Validate an external tool type record data. This method must be used before adding
     * a tool type record into database in order to secure the record integrity.
     *
     * @param array $tool_type Contains the tool type data.
     *
     * @return bool Returns the validation result.
     *
     * @throws Exception If required fields are missing.
     */
    public function validate_tool_type($tool_type)
    {
        try
        {


            // Required Fields
            if ( ! isset($tool_type['name']))
            {
                throw new Exception('Not all required fields where provided ');
            }

            if ($tool_type['name'] == '' || $tool_type['name'] == NULL)
            {
                throw new Exception('Required fields cannot be empty or null ($tool_type: '
                    . print_r($tool_type, TRUE) . ')');
            }

            return TRUE;
        }
        catch (Exception $exception)
        {
            return FALSE;
        }
    }

    /**
     * Insert an external tool type record into database.
     *
     * @param array $tool_type Contains the tool type data.
     *
     * @return int Returns the record ID.
     *
     * @throws Exception If tool type data are invalid.
     */
    public function add_tool_type($tool_type)
    {
        if ( ! $this->validate_tool_type($tool_type))
        {
            throw new Exception('External tool type data are invalid.');
        }


        $this->db->insert('types', $tool_type);
        $tool_type['id'] = $this->db->insert_id();

        return (int)$tool_type['id'];
    }

    /**
     * Delete an external tool type record from the database.
     *
     * @param int $tool_type_id Record id to be deleted.
     *
     * @return bool Returns the delete operation result.
     *
     * @throws Exception if tool type record was not found.
     */
    public function delete_tool_type($tool_type_id)
    {
        if ( ! is_numeric($tool_type_id))
        {
            throw new Exception('Invalid argument given for $external_tool_id: ' . $tool_type_id);
        }

        $num_rows = $this->db->get_where('types', ['id' => $tool_type_id])
            ->num_rows();
        if ($num_rows == 0)
        {
            throw new Exception('External tool record not found in database.');
        }

        $this->db->where('id', $tool_type_id);
        return $this->db->delete('types');
    }
}
