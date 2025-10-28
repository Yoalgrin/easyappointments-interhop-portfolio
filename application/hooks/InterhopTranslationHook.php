<?php

class InterhopTranslationHook
{
    public function inject()
    {
        $CI =& get_instance();

        $interhop_lang = [
            'interhop_max_patients' => 'Limite de patient(es)',
            'interhop_max_patients_placeholder' => 'Laisser vide = illimité',
            'interhop_max_patients_info' => 'Nombre maximum de patient(es) autorisé(es) à prendre RDV',
        ];

        $CI->lang->language = array_merge($CI->lang->language, $interhop_lang);
    }
}

