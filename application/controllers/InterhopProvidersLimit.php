<?php defined('BASEPATH') or exit('No direct script access allowed');

class InterhopProvidersLimit extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->output->set_content_type('application/json; charset=utf-8');
    }

    private function is_admin(): bool {
        return isset($_SESSION['role_slug']) && $_SESSION['role_slug'] === 'admin';
    }
    private function is_provider(): bool {
        return isset($_SESSION['role_slug']) && $_SESSION['role_slug'] === DB_SLUG_PROVIDER;
    }
    private function user_id(): int {
        return (int)($_SESSION['user_id'] ?? 0);
    }

    // GET /interhop/providerslimit/get/{provider_id}
    public function get($provider_id)
    {
        $pid = (int)$provider_id;

        // Autoriser admin OU provider qui lit sa propre limite
        if (!($this->is_admin() || ($this->is_provider() && $this->user_id() === $pid))) {
            return $this->output->set_status_header(403)
                ->set_output(json_encode(['success'=>false,'message'=>'Forbidden']));
        }

        $row = $this->db->get_where('ea_interhop_providers_limits', ['provider_id' => $pid])->row_array();
        $res = ['provider_id'=>$pid, 'max_patients'=>$row['max_patients'] ?? null];
        return $this->output->set_output(json_encode(['success'=>true,'data'=>$res]));
    }

    // POST /interhop/providerslimit/upsert
    public function upsert()
    {
        $provider_id  = (int)$this->input->post('provider_id');
        $max_patients = $this->input->post('max_patients', true);

        // Autoriser admin OU provider qui modifie sa propre limite
        if (!($this->is_admin() || ($this->is_provider() && $this->user_id() === $provider_id))) {
            return $this->output->set_status_header(403)
                ->set_output(json_encode(['success'=>false,'message'=>'Forbidden']));
        }

        if ($provider_id <= 0) {
            return $this->output->set_status_header(400)
                ->set_output(json_encode(['success'=>false,'message'=>'provider_id manquant']));
        }

        if ($max_patients === '' || $max_patients === null) {
            $max_patients = null;
        } else {
            $max_patients = max(1, (int)$max_patients);
        }

        $data = ['provider_id'=>$provider_id,'max_patients'=>$max_patients,'updated_at'=>date('Y-m-d H:i:s'),
            'updated_by'=>$this->user_id()];

        $exists = $this->db->get_where('ea_interhop_providers_limits', ['provider_id'=>$provider_id])->row_array();
        if ($exists) {
            $this->db->where('provider_id', $provider_id)->update('ea_interhop_providers_limits', $data);
        } else {
            $this->db->insert('ea_interhop_providers_limits', $data);
        }

        return $this->output->set_output(json_encode(['success'=>true]));
    }
    public function get_self()
    {
        $pid = $this->user_id();
        if ($pid <= 0) {
            return $this->output->set_output(json_encode(['success'=>true,'data'=>['provider_id'=>null,'max_patients'=>null]]));
        }

        // provider: peut lire sa propre limite ; admin: peut tout lire
        if (!($this->is_admin() || ($this->is_provider() && $this->user_id() === $pid))) {
            return $this->output->set_status_header(403)
                ->set_output(json_encode(['success'=>false,'message'=>'Forbidden']));
        }

        $row = $this->db->get_where('ea_interhop_providers_limits', ['provider_id' => $pid])->row_array();
        $res = ['provider_id'=>$pid, 'max_patients'=>$row['max_patients'] ?? null];
        return $this->output->set_output(json_encode(['success'=>true,'data'=>$res]));
    }
}
