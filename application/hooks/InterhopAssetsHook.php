<?php defined('BASEPATH') or exit('No direct script access allowed');

class InterhopAssetsHook
{
    /**
     * Injecte scripts InterHop juste avant </body> (display_override).
     * RÈGLE IMPORTANTE: en display_override il faut TOUJOURS echo la sortie finale.
     */

    public function injectOverrides(): void
    {
        $CI = &get_instance();

        // Récupère le HTML déjà rendu par CI
        $output = $CI->output->get_output();
        if (!is_string($output)) { $output = ''; }

        // S’assurer d’avoir base_url()
        if (!function_exists('base_url')) {
            $CI->load->helper('url');
        }

        // Feature flag optionnel: même si désactivé, on doit QUAND MÊME afficher $output !
        $featureEnabled = true;
        if ($CI->config->load('interhop', true, true)) {
            $tmp = $CI->config->item('interhop_provider_limits_enabled', 'interhop');
            if ($tmp === false) { $featureEnabled = false; }
        }

        // Si on ne doit rien injecter, on ECHO le rendu tel quel et on sort proprement
        if ($featureEnabled === false) {
            echo $output;
            return;
        }

        // Router pour cibler la page
        $class  = (string)($CI->router->class ?? '');
        // $method = (string)($CI->router->method ?? '');

        // 0) INJECTION INLINE – crée window.raw si absent (garanti, sans dépendances)
        $inlineRawShim = <<<HTML
<script>
// [InterHop] inline raw shim (garanti)
(function(){
  try {
    if (!('raw' in window)) {
      Object.defineProperty(window, 'raw', { configurable: true, writable: true, value: {} });
    } else if (window.raw == null || typeof window.raw !== 'object') {
      window.raw = {};
    }
  } catch(_) { window.raw = {}; }
})();
</script>
HTML;

        $tags = [];
        $tags[] = $inlineRawShim;



        // 1) (optionnel) shim fichier
        $shimPath = FCPATH . 'assets/js/pages/interhop-raw-shim.js';
        if (is_file($shimPath)) {
            $tags[] = '<script src="' . base_url('assets/js/pages/interhop-raw-shim.js') . '?v=' . rawurlencode($this->assetStamp($shimPath)) . '"></script>';
        } else {
            $tags[] = '<script>console.warn("[InterHop] interhop-raw-shim.js introuvable (inline shim suffisant)");</script>';
        }

        // 2) Override Account uniquement si on est sur le contrôleur Account
        if (strcasecmp($class, 'Account') === 0) {
            $accPath = FCPATH . 'assets/js/pages/interhop-account-override.js';
            if (is_file($accPath)) {
                $tags[] = '<script src="' . base_url('assets/js/pages/interhop-account-override.js') . '?v=' . rawurlencode($this->assetStamp($accPath)) . '"></script>';
                $tags[] = '<script>console.debug("[InterHop] account override injecté");</script>';
                // 👇 AJOUT ICI : trace spécifique pour Account
                $tags[] = '<script>console.debug("[IH] assets injectés pour Account");</script>';
            } else {
                $tags[] = '<script>console.warn("[InterHop] interhop-account-override.js introuvable");</script>';
            }
        }


        // 3) Injecter avant </body> (sinon à la fin)
        $final = $this->injectBeforeClosingBody($output, implode("\n", $tags));

        // TOUJOURS ECHO le HTML final en display_override
        echo $final;
        return;
    }

    private function assetStamp(string $path): string
    {
        $t = @filemtime($path);
        return $t ? (string)$t : (string)time();
    }

    private function injectBeforeClosingBody(string $html, string $insertion): string
    {
        $pos = strripos($html, '</body>');
        if ($pos === false) {
            return $html . "\n" . $insertion . "\n";
        }
        return substr($html, 0, $pos) . "\n" . $insertion . "\n" . substr($html, $pos);
    }
}
