<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Bootstrap du logging (100% modulaire, pas de core touché)
 * - pre_system : prépare PHP + dossier logs
 * - post_controller_constructor : force le log_threshold et trace un message
 */
class InterhopDebugBootstrapHook
{
    /** Très tôt : PHP ini + dossier logs prêt */
    public function preSystem(): void
    {
        // PHP (ne pas afficher, mais logguer)
        @ini_set('display_errors', '0');
        @ini_set('log_errors', '1');

        // Facultatif: log PHP dédié (en plus de CI)
        $phpLog = APPPATH . 'logs/php-errors.log';
        @ini_set('error_log', $phpLog);

        // S’assurer que application/logs/ existe et est écrivable
        $logDir = APPPATH . 'logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
        @chmod($logDir, 0775);
        // Petit test d’écriture
        @file_put_contents($logDir . '/._ih_write_test', date('c') . " init\n", FILE_APPEND);
    }

    /** Une fois CI initialisé : régler le logging CI + tracer */
    public function enableCiLogging(): void
    {
        $CI = &get_instance();
        if (!is_object($CI)) return;

        // Seuil verbeux (4 = ALL : error, debug, info)
        // (si CI >=3.1.0, un array est possible, mais 4 est simple et efficace)
        $CI->config->set_item('log_threshold', 4);

        // Optionnels (mais utiles)
        $CI->config->set_item('log_path', ''); // '' => APPPATH.'logs/'
        $CI->config->set_item('log_file_permissions', 0644);
        $CI->config->set_item('log_date_format', 'Y-m-d H:i:s');

        // Trace de contrôle
        log_message('debug', '[IH] CI logging enabled (threshold=4)');
    }
}

