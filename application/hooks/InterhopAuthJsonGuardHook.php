<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * InterhopAuthJsonGuardHook
 * - Objectif : garantir une réponse JSON propre pour /login/validate
 * - Sans toucher au core : s'exécute AVANT le contrôleur.
 * - Ne fait AUCUNE sortie (pas d'echo), juste prépare l'environnement.
 */
class InterhopAuthJsonGuardHook
{
    /** Hook: pre_controller */
    public function guardLoginValidate(): void
    {
        // Pas en CLI
        if (php_sapi_name() === 'cli') return;

        // Récupère route
        if (!function_exists('load_class')) return;
        $RTR  = @load_class('Router', 'core');
        $class  = strtolower((string)($RTR->class  ?? ''));
        $method = strtolower((string)($RTR->method ?? ''));

        // Cible: Login::validate uniquement (POST)
        $http = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if (!($class === 'login' && $method === 'validate' && $http === 'POST')) return;

        // 1) Vide tout buffer éventuel ouvert avant (anti-bruit)
        while (ob_get_level() > 0) { @ob_end_clean(); }

        // 2) Force le Content-Type JSON tôt (le contrôleur peut le refaire, ce n'est pas grave)
        $CI = &get_instance();
        if (is_object($CI) && isset($CI->output)) {
            $CI->output->set_content_type('application/json', 'utf-8');
        } else {
            // fallback ultra-simple si $CI->output pas prêt (rare en pre_controller)
            @header('Content-Type: application/json; charset=UTF-8', true);
        }

        // Surtout ne RIEN afficher/echo ici.
    }
}
