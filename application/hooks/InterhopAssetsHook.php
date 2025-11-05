<?php defined('BASEPATH') or exit('No direct script access allowed');

class InterhopAssetsHook
{
    /**
     * Injecte les overrides InterHop juste avant </body> sans modifier le core.
     * À brancher sur "display_override" (CI3) ou "post_controller" si pas d'override d'affichage.
     */
    public function injectOverrides(): void
    {
        $CI = &get_instance();

        // Feature flag (optionnel)
        if ($CI->config->load('interhop', true)) {
            $enabled = $CI->config->item('interhop_provider_limits_enabled', 'interhop');
            if ($enabled === false) return;
        }

        // On cible uniquement les pages backend utiles
        $class = $CI->router->class;   // 'Account', 'Providers', ...
        if (!in_array($class, ['Account', 'Providers'], true)) {
            return;
        }

        // Construire les balises <script> nécessaires
        $CI->load->helper('url'); // pour base_url()
        $tags = [];

        if ($class === 'Account') {
            $path = FCPATH . 'assets/js/pages/interhop-account-override.min.js';
            if (is_file($path)) {
                $tags[] = '<script src="' . base_url('assets/js/pages/interhop-account-override.min.js') . '"></script>';
            }
        }

        if ($class === 'Providers') {
            $path = FCPATH . 'assets/js/pages/interhop-providers-override.min.js';
            if (is_file($path)) {
                $tags[] = '<script src="' . base_url('assets/js/pages/interhop-providers-override.min.js') . '"></script>';
            }
        }

        if (!$tags) return;

        // Récupérer la sortie et injecter avant </body>
        $output = $CI->output->get_output();
        $injection = "\n" . implode("\n", $tags) . "\n";

        if (stripos($output, '</body>') !== false) {
            $output = preg_replace('~</body>~i', $injection . '</body>', $output, 1);
        } else {
            // fallback: append à la fin si pas de </body>
            $output .= $injection;
        }

        $CI->output->set_output($output);
        // Important: ne pas appeler $CI->output->_display() ici, CI s’en charge après le hook.
    }
}
