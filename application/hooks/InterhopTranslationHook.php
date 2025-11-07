<?php defined('BASEPATH') or exit('No direct script access allowed');

class InterhopTranslationHook
{
    /**
     * Normalise/complète les clés de langue (backend uniquement).
     * À exécuter tôt (ex. post_controller_constructor) pour que $CI->lang soit prêt.
     */
    public function inject(): void
    {
        $CI = &get_instance();

        // Feature flag (optionnel)
        if ($CI->config->load('interhop', true, true)) {
            $enabled = $CI->config->item('interhop_translation_enabled', 'interhop');
            if ($enabled === false) {
                return;
            }
        }

        if (!isset($CI->lang) || !is_array($CI->lang->language)) {
            return;
        }

        // Clés canoniques
        $lang_new = [
            'max_patients'             => 'Nombre maximum de patients',
            'max_patients_placeholder' => 'Laisser vide = illimité',
            'max_patients_help'        => 'Nombre maximum de patients suivis par ce soignant. Laissez vide pour désactiver la limite.',
            'max_patients_invalid'     => 'La limite doit être un entier ≥ 1 ou vide.',
        ];

        // Alias rétrocompat
        $legacy_alias = [
            'interhop_max_patients'             => 'max_patients',
            'interhop_max_patients_placeholder' => 'max_patients_placeholder',
            'interhop_max_patients_info'        => 'max_patients_help',
            'interhop_max_patients_invalid'     => 'max_patients_invalid',
            'max_patients_info'                 => 'max_patients_help',
        ];

        foreach ($lang_new as $key => $value) {
            if (!array_key_exists($key, $CI->lang->language)) {
                $CI->lang->language[$key] = $value;
            }
        }
        foreach ($legacy_alias as $oldKey => $newKey) {
            if (!array_key_exists($oldKey, $CI->lang->language) && array_key_exists($newKey, $CI->lang->language)) {
                $CI->lang->language[$oldKey] = $CI->lang->language[$newKey];
            }
        }
    }

    /**
     * Exporte un sous-ensemble de libellés au front (EALang + patch lang()).
     * IMPORTANT : protéger contre JSON/AJAX/POST pour ne pas polluer les réponses.
     * À raccorder sur display_override ou post_controller.
     */
    public function exportToHead(): void
    {
        $CI = &get_instance();

        // Feature flag
        if ($CI->config->load('interhop', true, true)) {
            $enabled = $CI->config->item('interhop_translation_enabled', 'interhop');
            if ($enabled === false) {
                return;
            }
        }

        // --- Garde-fous globaux ---
        // 1) GET uniquement
        $httpMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($httpMethod !== 'GET') { return; }

        // 2) Pas AJAX
        if (!empty($CI->input) && $CI->input->is_ajax_request()) { return; }

        // 3) Exclure login/validate
        $class  = strtolower($CI->router->class ?? '');
        $method = strtolower($CI->router->method ?? '');
        $isLogin = in_array($class, ['user','users','auth','login'], true)
            || in_array($method, ['login','validate'], true);
        if ($isLogin) { return; }

        // Récupérer la sortie
        $output = $CI->output->get_output();
        if ($output === null || $output === '') { return; }

        // 4) Ne pas toucher aux réponses JSON
        $trim = ltrim($output);
        if ($trim !== '' && json_decode($trim, true) !== null) { return; }

        // 5) Injecter seulement si HTML (head/body trouvés)
        $hasHead = stripos($output, '</head>') !== false;
        $hasBody = stripos($output, '</body>') !== false;
        if (!$hasHead && !$hasBody) { return; }

        // Sélection des clés à exposer
        $wantKeys = [
            'max_patients',
            'max_patients_placeholder',
            'max_patients_help',
            'max_patients_invalid',
        ];

        $src = [];
        if (isset($CI->lang) && is_array($CI->lang->language)) {
            foreach ($wantKeys as $k) {
                if (array_key_exists($k, $CI->lang->language)) {
                    $src[$k] = $CI->lang->language[$k];
                }
            }
        }

        $json = json_encode($src, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $script = <<<HTML
<script>
(function(){
  window.EALang = window.EALang || {};
  var add = $json || {};
  for (var k in add){ if(Object.prototype.hasOwnProperty.call(add,k)){ EALang[k] = add[k]; } }

  // Sécuriser lang() pour renvoyer les fallbacks si une clé manque
  if (typeof window.lang === 'function') {
    try {
      var _origLang = window.lang;
      window.lang = function(key) {
        try {
          var v = _origLang(key);
          if (v && v !== key) return v;
        } catch(e){}
        return (window.EALang && window.EALang[key]) ? window.EALang[key] : key;
      };
    } catch(e){}
  }
})();
</script>
HTML;

        // Injection : priorité à </head>, sinon </body>, sinon append.
        if ($hasHead) {
            $output = str_ireplace('</head>', $script . "\n</head>", $output);
        } elseif ($hasBody) {
            $output = str_ireplace('</body>', $script . "\n</body>", $output);
        } else {
            $output .= $script;
        }

        $CI->output->set_output($output);
        // NB: Ne PAS appeler _display() ici — laisse le hook caller (display_override) le faire,
        // sinon double affichage potentiel.
    }
}
