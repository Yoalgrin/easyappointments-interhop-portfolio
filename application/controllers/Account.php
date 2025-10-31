<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.5.0
 * ---------------------------------------------------------------------------- */

/**
 * Account controller.
 *
 * Handles current account related operations.
 *
 * @package Controllers
 */
class Account extends EA_Controller
{
    public array $allowed_user_fields = [
        'id',
        'first_name',
        'last_name',
        'email',
        'mobile_number',
        'phone_number',
        'address',
        'city',
        'state',
        'zip_code',
        'notes',
        'timezone',
        'language',
        'settings',

    ];

    public array $optional_user_fields = [
        //
    ];

    public array $allowed_user_setting_fields = ['username', 'password', 'notifications', 'calendar_view'];

    public array $optional_user_setting_fields = [
        //
    ];

    /**
     * Account constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('appointments_model');
        $this->load->model('customers_model');
        $this->load->model('services_model');
        $this->load->model('providers_model');
        $this->load->model('roles_model');
        $this->load->model('settings_model');

        $this->load->library('accounts');
        $this->load->library('google_sync');
        $this->load->library('notifications');
        $this->load->library('synchronization');
        $this->load->library('timezones');
    }

    /**
     * Render the settings page.
     */
    public function index(): void
    {
        session(['dest_url' => site_url('account')]);

        $user_id = session('user_id');

        if (cannot('view', PRIV_USER_SETTINGS)) {
            if ($user_id) {
                abort(403, 'Forbidden');
            }

            redirect('login');

            return;
        }

        $account = $this->users_model->find($user_id);
        // Charge la limite (table: ea_interhop_providers_limits, FK: provider_id)
        $limitRow = $this->db
            ->select('max_patients')
            ->from('ea_interhop_providers_limits')
            ->where('provider_id', $user_id)
            ->get()
            ->row_array();

        $account['interhop_max_patients'] = $limitRow['max_patients'] ?? null;

        script_vars([
            'account' => $account,
        ]);

        html_vars([
            'page_title' => lang('settings'),
            'active_menu' => PRIV_SYSTEM_SETTINGS,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'grouped_timezones' => $this->timezones->to_grouped_array(),
        ]);

        $this->load->view('pages/account');
    }

    /**
     * Save general settings.
     */
    public function save(): void
    {
        try {
            if (cannot('edit', PRIV_USER_SETTINGS)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            // 1) Récupérer payload et attacher l'id
            $payload = request('account') ?: [];
            $payload['id'] = session('user_id');

            // 2) Lire l'état actuel en base (assure qu'on a les champs requis)
            $current = $this->users_model->find($payload['id']);
            if (!$current) {
                throw new RuntimeException('User not found.');
            }

            // 3) Normaliser et extraire la limite (mapping frontend -> DB)
            $interhopMax = $payload['interhop_max_patients'] ?? null;
            if ($interhopMax === '' || $interhopMax === 'null') {
                $interhopMax = null; // vide = illimité
            }

            // 4) Fusionner l'état actuel avec le payload (le payload écrase l'existant)
            //    -> On évite "Not all required fields" si le client oublie un champ
            $account = array_replace_recursive($current, $payload);

            // 5) Filtrages "only/optional"
            // IMPORTANT: $allowed_user_fields doit contenir 'settings' et NE PAS contenir 'interhop_max_patients'
            $this->users_model->only($account, $this->allowed_user_fields);
            $this->users_model->optional($account, $this->optional_user_fields);

            if (!isset($account['settings']) || !is_array($account['settings'])) {
                $account['settings'] = [];
            }

            $this->users_model->only($account['settings'], $this->allowed_user_setting_fields);
            $this->users_model->optional($account['settings'], $this->optional_user_setting_fields);

            // 6) Mot de passe vide => ne pas modifier
            if (empty($account['settings']['password'])) {
                unset($account['settings']['password']);
            }

            // 7) Sauvegarde user + settings
            $this->users_model->save($account);

            // 8) Upsert de la limite (FK: provider_id, colonne: max_patients)
            $userId = $payload['id'];

            if ($interhopMax === null) {
                // illimité => supprimer la ligne si elle existe
                $this->db->delete('ea_interhop_providers_limits', ['provider_id' => $userId]);
            } else {
                $exists = $this->db->select('provider_id')
                        ->from('ea_interhop_providers_limits')
                        ->where('provider_id', $userId)
                        ->get()->num_rows() > 0;

                if ($exists) {
                    $this->db->update(
                        'ea_interhop_providers_limits',
                        ['max_patients' => $interhopMax],
                        ['provider_id' => $userId]
                    );
                } else {
                    $this->db->insert('ea_interhop_providers_limits', [
                        'provider_id'  => $userId,
                        'max_patients' => $interhopMax,
                    ]);
                }
            }

            // 9) Rafraîchir la session
            session([
                'user_email' => $account['email'] ?? session('user_email'),
                'username'   => $account['settings']['username'] ?? session('username'),
                'timezone'   => $account['timezone'] ?? session('timezone'),
                'language'   => $account['language'] ?? session('language'),
            ]);

            response();
        } catch (Throwable $e) {
            json_exception($e);
        }
    }


    /**
     * Make sure the username is valid and unique in the database.
     */
    public function validate_username(): void
    {
        try {
            $username = request('username');

            $user_id = request('user_id');

            $is_valid = $this->users_model->validate_username($username, $user_id);

            json_response([
                'is_valid' => $is_valid,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
