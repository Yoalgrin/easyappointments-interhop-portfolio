<?php defined('BASEPATH') or exit('No direct script access allowed');

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

    public array $optional_user_fields = [];

    public array $allowed_user_setting_fields = ['username', 'password', 'notifications', 'calendar_view'];
    public array $optional_user_setting_fields = [];

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

        // Charger la limite dédiée (table: ea_interhop_providers_limits, FK: provider_id)
        $limitRow = $this->db->select('max_patients')
            ->from('ea_interhop_providers_limits')
            ->where('provider_id', $user_id)
            ->get()
            ->row_array();

        $account['interhop_max_patients'] = $limitRow['max_patients'] ?? null;

        script_vars([
            'account' => $account,
            'user_id' => $user_id,
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

            $userId = (int) session('user_id');

            // --------- [A] Lecture robuste du payload minimal ---------
            // 1) payload EA (peut être vide selon la sérialisation)
            $payload = request('account');
            if (empty($payload)) {
                $payload = $_POST['account'] ?? [];
                if (empty($payload)) {
                    $raw = file_get_contents('php://input');
                    if ($raw) {
                        $json = json_decode($raw, true);
                        if (is_array($json) && isset($json['account'])) {
                            $payload = $json['account'];
                        } elseif (is_array($json)) {
                            $payload = $json;
                        }
                    }
                }
            }
            $payload['id'] = $userId;

            // 2) Lire la limite directement depuis le POST (toutes formes)
            $val = $this->input->post('account[interhop_max_patients]');
            if ($val === null) {
                if (isset($_POST['account']['interhop_max_patients'])) {
                    $val = $_POST['account']['interhop_max_patients'];
                } elseif (($tmp = $this->input->post('interhop_max_patients')) !== null) {
                    $val = $tmp;
                } else {
                    $rawBody = file_get_contents('php://input');
                    if ($rawBody) {
                        $jb = json_decode($rawBody, true);
                        if (is_array($jb)) {
                            if (isset($jb['account']['interhop_max_patients'])) {
                                $val = $jb['account']['interhop_max_patients'];
                            } elseif (isset($jb['interhop_max_patients'])) {
                                $val = $jb['interhop_max_patients'];
                            }
                        } else {
                            parse_str($rawBody, $fb);
                            if (isset($fb['account']['interhop_max_patients'])) {
                                $val = $fb['account']['interhop_max_patients'];
                            } elseif (isset($fb['interhop_max_patients'])) {
                                $val = $fb['interhop_max_patients'];
                            }
                        }
                    }
                }
            }
            $interhopMax = ($val === '' || $val === 'null' || $val === null) ? null : (int) $val;

            // --------- [B] Upsert DÉDIÉ (avant la sauvegarde EA) ---------
            // NOTE: Feature 22 – limite de patients par soignant (InterHop)
            // Upsert séparé du flux EA pour éviter validation bloquante
            // Reproduit le comportement "ça s'insère même si le reste échoue"
            if ($interhopMax === null) {
                // illimité => supprimer l’éventuelle ligne
                $this->db->delete('ea_interhop_providers_limits', ['provider_id' => $userId]);
            } else {
                // upsert propre
                $sql = 'INSERT INTO ea_interhop_providers_limits (provider_id, max_patients)
                        VALUES (?, ?)
                        ON DUPLICATE KEY UPDATE max_patients = VALUES(max_patients)';
                $this->db->query($sql, [$userId, $interhopMax]);
            }

            // --------- [C] Sauvegarde EA standard (ne touche qu'aux champs envoyés) ---------
            // Fusion avec l’état courant pour éviter "Not all required fields..."
            $current = $this->users_model->find($userId);
            if (!$current) {
                throw new RuntimeException('User not found.');
            }
            $account = array_replace_recursive($current, $payload);

            $this->users_model->only($account, $this->allowed_user_fields);
            $this->users_model->optional($account, $this->optional_user_fields);

            if (!isset($account['settings']) || !is_array($account['settings'])) {
                $account['settings'] = [];
            }
            $this->users_model->only($account['settings'], $this->allowed_user_setting_fields);
            $this->users_model->optional($account['settings'], $this->optional_user_setting_fields);

            if (empty($account['settings']['password'])) {
                unset($account['settings']['password']);
            }

            $this->users_model->save($account);

            // --------- [D] Rafraîchit la session & réponse ---------
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

    public function validate_username(): void
    {
        try {
            $username = request('username');
            $user_id = request('user_id');

            $is_valid = $this->users_model->validate_username($username, $user_id);

            json_response(['is_valid' => $is_valid]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
