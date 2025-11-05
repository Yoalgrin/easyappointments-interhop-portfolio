<?php defined('BASEPATH') or exit('No direct script access allowed');

class InterhopTranslationHook
{
    /**
     * Injecte et normalise les libellés relatifs à la limite de patients.
     * À exécuter après le chargement des fichiers de langue (ex. post_controller_constructor).
     */
    public function inject(): void
    {
        $CI = &get_instance();

        // Garde-fou : vérifier la disponibilité du conteneur de langue
        if (!isset($CI->lang) || !is_array($CI->lang->language)) {
            return;
        }

        // Clés « canoniques »
        $lang_new = [
            'max_patients'             => 'Nombre maximum de patients',
            'max_patients_placeholder' => 'Laisser vide = illimité',
            'max_patients_help'        => 'Nombre maximum de patients suivis par ce soignant. Laissez vide pour désactiver la limite.',
            'max_patients_invalid'     => 'La limite doit être un entier ≥ 1 ou vide.',
        ];

        // Mappages de rétrocompatibilité (anciennes clés → clés canoniques)
        $legacy_alias = [
            'interhop_max_patients'             => 'max_patients',
            'interhop_max_patients_placeholder' => 'max_patients_placeholder',
            'interhop_max_patients_info'        => 'max_patients_help',
            'interhop_max_patients_invalid'     => 'max_patients_invalid',
            'max_patients_info'                 => 'max_patients_help',
        ];

        // Injecter les clés canoniques sans écraser les définitions existantes
        foreach ($lang_new as $key => $value) {
            if (!array_key_exists($key, $CI->lang->language)) {
                $CI->lang->language[$key] = $value;
            }
        }

        // Créer les alias si absents et si la clé cible existe
        foreach ($legacy_alias as $oldKey => $newKey) {
            if (!array_key_exists($oldKey, $CI->lang->language) && array_key_exists($newKey, $CI->lang->language)) {
                $CI->lang->language[$oldKey] = $CI->lang->language[$newKey];
            }
        }
    }
}
