<?php defined('BASEPATH') or exit('No direct script access allowed');

class InterhopProvidersLimit extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->output->set_content_type('application/json; charset=utf-8');
        $this->load->model('Interhop_providers_limit_model', 'LimitModel');
    }

    private function is_admin(): bool {
        return isset($_SESSION['role_slug']) && $_SESSION['role_slug'] === 'admin';
    }

    private function user_id(): int {
        return (int)($_SESSION['user_id'] ?? 0);
    }

    /**
     * Un "provider" = un user qui :
     *  - a le rôle provider OU
     *  - est présent dans ea_services_providers.id_users
     */
    private function is_provider(): bool {
        $uid = $this->user_id();
        if ($uid <= 0) {
            return false;
        }

        // 1) Cas simple : role provider en session
        if (isset($_SESSION['role_slug']) && $_SESSION['role_slug'] === DB_SLUG_PROVIDER) {
            return true;
        }

        // 2) Cas admin+soignant : vérifier en DB via ea_services_providers.id_users
        $row = $this->db->select('id_users')
            ->from('services_providers')            // ea_services_providers avec préfixe
            ->where('id_users', $uid)
            ->limit(1)
            ->get()
            ->row();

        return (bool)($row && isset($row->id_users));
    }

    // GET /interhop/providerslimit/get/{provider_id}
    public function get($provider_id = null)
    {
        $pid = (int)$provider_id;
        if ($pid <= 0) {
            return $this->output->set_output(json_encode([
                'success'=>true,
                'data'=>['provider_id'=>null,'max_patients'=>null]
            ]));
        }

        // provider lit sa propre limite ; admin lit tout
        if (!($this->is_admin() || ($this->is_provider() && $this->user_id() === $pid))) {
            return $this->output->set_status_header(403)
                ->set_output(json_encode(['success'=>false,'message'=>'Forbidden']));
        }

        $row = $this->LimitModel->get_by_provider($pid);
        $max = null;
        if ($row && array_key_exists('max_patients', $row) && $row['max_patients'] !== null) {
            $max = (int)$row['max_patients'];
        }
        $res = ['provider_id' => $pid, 'max_patients' => $max];

        return $this->output->set_output(json_encode(['success'=>true,'data'=>$res]));
    }

    // POST /interhop/providerslimit/set  (écriture directe côté Admin)
    public function set()
    {
        $pid = (int)$this->input->post('provider_id');
        if ($pid <= 0) {
            return $this->output->set_status_header(400)
                ->set_output(json_encode(['success'=>false,'message'=>'provider_id required']));
        }
        if (!$this->is_admin()) {
            return $this->output->set_status_header(403)
                ->set_output(json_encode(['success'=>false,'message'=>'Forbidden']));
        }

        // Valeur reçue par le hidden : '' => NULL ; n>=1 => int
        $max = $this->input->post('max_patients', true);
        $max = (is_string($max) ? trim($max) : $max);
        if ($max === '' || $max === '0' || $max === 0 || $max === null) {
            $max = null;
        } else {
            $max = (int)$max;
            if ($max < 1) $max = null;
        }

        $ok = $this->LimitModel->upsert($pid, $max, (int)$this->user_id());
        if (!$ok) {
            return $this->output->set_status_header(500)
                ->set_output(json_encode(['success'=>false,'message'=>'db error']));
        }
        return $this->output->set_output(json_encode([
            'success'=>true,
            'data'=>['provider_id'=>$pid,'max_patients'=>$max]
        ]));
    }

    // POST /interhop/providerslimit/upsert  (alias de set, pour le JS)
    public function upsert()
    {
        return $this->set();
    }

    public function get_self()
    {
        // Exemple : pour un soignant (ou admin+soignant) qui lit sa propre limite
        $pid = $this->user_id();
        if ($pid <= 0 || !$this->is_provider()) {
            return $this->output->set_output(json_encode([
                'success' => true,
                'data' => ['provider_id' => null, 'max_patients' => null]
            ]));
        }
        return $this->get($pid);
    }
}
