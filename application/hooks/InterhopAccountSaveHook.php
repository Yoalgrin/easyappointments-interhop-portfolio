<?php
class InterhopAccountSaveHook {
    public function save_max_patients() {
        $CI =& get_instance();

        if ($CI->router->class === 'account' && $CI->router->method === 'save_account') {
            $providerId = $CI->input->post('id');
            $maxPatients = $CI->input->post('interhop_max_patients');

            if ($providerId && $CI->session->userdata('type') === DB_SLUG_PROVIDER) {
                $CI->db->replace('ea_interhop_providers_limits', [
                    'provider_id' => $providerId,
                    'max_patients' => $maxPatients ?: null,
                ]);
            }
        }
    }
}
