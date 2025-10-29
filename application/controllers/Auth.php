<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller
{
    public function callback()
    {
        $code  = $this->input->get('code');
        $state = $this->input->get('state');

        header('Content-Type: text/plain; charset=utf-8');
        if (!$code) {
            echo "Callback OK (pas de code)\n";
            return;
        }
        echo "Callback OK\ncode={$code}\nstate={$state}\n";
        // TODO: ici (plus tard) échange code->token avec Keycloak, création session, redirection…
    }
}

