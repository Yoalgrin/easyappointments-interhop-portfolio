<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * CGU Controller
 *
 * @package Controllers
 */
class CGU extends EA_Controller
{

    /**
     * Class Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('settings_model');
        $this->load->helper('google_analytics');
        $this->load->driver('cache', ['adapter' => 'file']);
    }
    public function index()
    {
        $company_name = $this->settings_model->get_setting('company_name');
        $this->load->view('general/CGU', ['company_name'=>$company_name,'privacy_policy_content'=>'à faire',
            'terms_and_conditions_content'=>'à faire']);
    }
}
