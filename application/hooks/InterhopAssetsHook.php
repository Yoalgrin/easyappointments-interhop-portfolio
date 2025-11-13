<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * InterhopAssetsHook
 *
 * Injecte, en mode display_override, les surcharges JS InterHop spécifiques
 * aux pages Account/Providers sans modifier le core.
 *
 * Principes:
 * - N'injecter que sur des réponses HTML complètes (présence de </body>).
 * - Ne jamais injecter sur les requêtes POST/AJAX/JSON (ex: /login/validate).
 * - Cibler strictement les contrôleurs Account/Providers.
 * - Toujours réémettre la sortie originale ($output).
 */
class InterhopAssetsHook
{
    /**
     * Point d'entrée du hook (display_override).
     * Règle impérative : echo $output (ou la version modifiée) avant de retourner.
     */
    public function injectOverrides(): void
    {
        $CI = &get_instance();

        // Récupération du buffer rendu par CI
        $output = $CI->output->get_output();
        if (!is_string($output)) { $output = ''; }

        // Classe contrôleur (account/providers attendu côté back-office)
        $class = strtolower((string)($CI->router->class ?? ''));
        if ($class !== 'account' && $class !== 'providers') { echo $output; return; }

        // -------------------------------
        // 1) Règles de non-injection strictes
        // -------------------------------

        // 1.1 Ne jamais injecter sur des requêtes non-GET (ex: /account/save, /login/validate)
        $httpMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($httpMethod !== 'GET') { echo $output; return; }

        // 1.2 Ne jamais injecter sur les routes de login/validate, quelle que soit la classe contrôleur
        $uri      = $_SERVER['REQUEST_URI'] ?? '';
        $uriLower = strtolower($uri);
        if (
            strpos($uriLower, '/login')    !== false ||
            strpos($uriLower, '/validate') !== false ||
            strpos($uriLower, '/auth')     !== false ||   // garde large (auth)
            strpos($uriLower, '/api/')     !== false ||   // endpoints API
            strpos($uriLower, '/ajax/')    !== false      // endpoints ajax
        ) {
            echo $output; return;
        }

        // 1.3 Ne pas injecter si la réponse n'est pas une page HTML complète
        if (stripos($output, '</body>') === false) { echo $output; return; }

        // 1.4 Ne pas injecter si l'appel est AJAX ou si les en-têtes suggèrent du JSON
        $isAjax    = $CI->input->is_ajax_request();
        $accept    = strtolower($_SERVER['HTTP_ACCEPT']  ?? '');
        $ctypeReq  = strtolower($_SERVER['CONTENT_TYPE'] ?? '');
        $ctypeOut  = method_exists($CI->output, 'get_content_type')
            ? strtolower((string)$CI->output->get_content_type())
            : '';
        $looksJson = ($output !== '' && ($output[0] === '{' || $output[0] === '['));
        if (
            $isAjax ||
            $looksJson ||
            strpos($accept, 'json') !== false ||
            strpos($ctypeReq, 'json') !== false ||
            strpos($ctypeOut, 'json') !== false
        ) { echo $output; return; }

        // -------------------------------
        // 2) Ciblage contrôleurs/méthodes
        // -------------------------------
        $method = strtolower((string)($CI->router->method ?? ''));

        // Ne viser que Account / Providers (back-office)
        if (!in_array($class, ['account', 'providers'], true)) { echo $output; return; }

        // Facultatif : limiter aux pages vues (évite d’injecter sur des endpoints techniques)
        if ($method && !in_array($method, ['index', 'edit', 'view', 'details'], true)) {
            echo $output; return;
        }

        // -------------------------------
        // 3) Feature flag optionnel
        // -------------------------------
        $featureEnabled = true;
        if ($CI->config->load('interhop', true, true)) {
            $tmp = $CI->config->item('interhop_provider_limits_enabled', 'interhop');
            if ($tmp === false) { $featureEnabled = false; }
        }
        if ($featureEnabled === false) { echo $output; return; }

        // -------------------------------
        // 4) Injections ciblées
        // -------------------------------
        if (!function_exists('base_url')) { $CI->load->helper('url'); }

        $tags = [];

        // Shim minimal garantissant l'existence de window.raw
        $tags[] =
            '<script>(function(){try{if(!("raw" in window)){' .
            'Object.defineProperty(window,"raw",{configurable:true,writable:true,value:{}});' .
            '}else if(window.raw==null||typeof window.raw!=="object"){window.raw={};}}catch(_){window.raw={};}})();</script>';

        if ($class === 'account') {
            // --- ACCOUNT ---
            $accPath = FCPATH . 'assets/js/pages/interhop-account-override.js';
            if (is_file($accPath)) {
                $tags[] = '<script src="' . base_url('assets/js/pages/interhop-account-override.js') .
                    '?v=' . rawurlencode($this->assetStamp($accPath)) . '"></script>';
                $tags[] = '<script>try{console.debug("[InterHop] account override injected");}catch(_){}</script>';
            }
        } else {
            // --- PROVIDERS ---
            // Injecte d'abord un bloc i18n minimal (fallback front)
            $i18n = [
                'max_patients'             => $CI->lang->line('max_patients') ?: 'Limite de patients',
                'max_patients_placeholder' => $CI->lang->line('max_patients_placeholder') ?: 'ex : 25 (laisser vide pour illimité)',
                'max_patients_help'        => $CI->lang->line('max_patients_help') ?: 'Nombre maximum de patients autorisés pour ce soignant (vide = illimité).',
                'max_patients_invalid'     => $CI->lang->line('max_patients_invalid') ?: 'La limite doit être un entier ≥ 1 ou vide.',
            ];
            $json = json_encode($i18n, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $tags[] = '<script>(function(){window.EALang=window.EALang||{};var add='.$json.';
                        for(var k in add){if(Object.prototype.hasOwnProperty.call(add,k)&&!EALang[k]){EALang[k]=add[k];}}
                        if(typeof window.lang==="function"){try{var _orig=window.lang;window.lang=function(key){try{var v=_orig(key);if(v&&v!==key)return v;}catch(e){}return (window.EALang&&window.EALang[key])?window.EALang[key]:key;};}catch(e){}}
            console.debug("[IH i18n] providers fallback loaded:",Object.keys(add));})();</script>';

            // 1) Override HTTP (normalise /providers/search -> Array) AVANT l’override UI
            $provHttpPath = FCPATH . 'assets/js/pages/interhop-providers-http-override.js';
            if (is_file($provHttpPath)) {
                $tags[] = '<script src="' . base_url('assets/js/pages/interhop-providers-http-override.js') .
                    '?v=' . rawurlencode($this->assetStamp($provHttpPath)) . '"></script>';
                $tags[] = '<script>try{console.debug("[InterHop] providers HTTP override injected");}catch(_){}</script>';
            }

            // 2) Override UI (injection champ max_patients, etc.)
            $provUiPath = FCPATH . 'assets/js/pages/interhop-providers-override.js';
            if (is_file($provUiPath)) {
                $tags[] = '<script src="' . base_url('assets/js/pages/interhop-providers-override.js') .
                    '?v=' . rawurlencode($this->assetStamp($provUiPath)) . '"></script>';
                $tags[] = '<script>try{console.debug("[InterHop] providers UI override injected");}catch(_){}</script>';
            }
        }

        // Injection avant </body>
        $final = $this->injectBeforeClosingBody($output, implode("\n", $tags));
        echo $final; return;
    }

    /**
     * Génère un identifiant de version à partir du mtime du fichier
     * (permet l'invalidation de cache sur déploiement).
     */
    private function assetStamp(string $path): string
    {
        $t = @filemtime($path);
        return $t ? (string)$t : (string)time();
    }

    /**
     * Insère $insertion avant la balise fermante </body> si trouvée,
     * sinon concatène à la fin du document.
     */
    private function injectBeforeClosingBody(string $html, string $insertion): string
    {
        $pos = strripos($html, '</body>');
        if ($pos === false) {
            return $html . "\n" . $insertion . "\n";
        }
        return substr($html, 0, $pos) . "\n" . $insertion . "\n" . substr($html, $pos);
    }
}
