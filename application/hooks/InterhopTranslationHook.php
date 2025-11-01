<?php

class InterhopTranslationHook
{
    public function inject()
    {
        $CI =& get_instance();

        // Nouvelles clés
        $lang_new = [
            'max_patients'               => 'Limite de patient(es)',
            'max_patients_placeholder'   => 'Laisser vide = illimité',
            'max_patients_info'          => 'Nombre maximum de patient(es) autorisé(es) à prendre RDV',
            'max_patients_invalid'       => 'Valeur invalide pour la limite de patients.',
        ];

        // Alias rétro-compat (ancienne nomenclature)
        $legacy_alias = [
            'interhop_max_patients'              => 'max_patients',
            'interhop_max_patients_placeholder'  => 'max_patients_placeholder',
            'interhop_max_patients_info'         => 'max_patients_info',
            'interhop_max_patients_invalid'      => 'max_patients_invalid',
        ];

        // Fusionne les nouvelles clés
        $CI->lang->language = array_merge($CI->lang->language, $lang_new);

        // Crée les alias si une clé legacy est demandée ailleurs
        foreach ($legacy_alias as $old => $new) {
            if (!array_key_exists($old, $CI->lang->language) && isset($CI->lang->language[$new])) {
                $CI->lang->language[$old] = $CI->lang->language[$new];
            }
        }
    }
}
