<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.3.0
 * ---------------------------------------------------------------------------- */

// ------------------------------------------------------------------------
// Asset URL - version d'origine (EasyAppointments) [conservée pour mémoire]
// ------------------------------------------------------------------------
/*
function asset_url(string $uri = '', ?string $protocol = null): string
{
    $debug = config('debug');

    $cache_busting_token = '?' . config('cache_busting_token');

    if (str_contains(basename($uri), '.js') && !str_contains(basename($uri), '.min.js') && !$debug) {
        $uri = str_replace('.js', '.min.js', $uri);
    }

    if (str_contains(basename($uri), '.css') && !str_contains(basename($uri), '.min.css') && !$debug) {
        $uri = str_replace('.css', '.min.css', $uri);
    }

    return base_url($uri . $cache_busting_token, $protocol);
}
*/

// ------------------------------------------------------------------------
// Asset URL - version InterHop sécurisée (minify gardé sous contrôle)
// ------------------------------------------------------------------------

function asset_url(string $uri = '', ?string $protocol = null): string
{
    $debug = (bool) config('debug');
    $cache_busting_token = '?' . config('cache_busting_token');

    // On travaille toujours sur un chemin relatif nettoyé
    $rel = ltrim($uri, '/');

    // 1. En mode debug : comportement "source only", on ne touche à rien
    if ($debug === true) {
        return base_url($rel . $cache_busting_token, $protocol);
    }

    // 2. En mode prod : on applique la logique minify uniquement sur les assets autorisés
    if (ea_should_minify($rel)) {
        $rel = ea_pick_minified_if_exists($rel);
    }

    // 3. URL finale (avec cache-busting)
    return base_url($rel . $cache_busting_token, $protocol);
}

// ------------------------------------------------------------------------
// Garde-fou : décide sur quels fichiers on a le droit d'essayer de minifier
// ------------------------------------------------------------------------

if (!function_exists('ea_should_minify')) {
    function ea_should_minify(string $uri): bool
    {
        // On ne minifie que les .js / .css
        $ext = pathinfo($uri, PATHINFO_EXTENSION);
        if (!in_array($ext, ['js', 'css'], true)) {
            return false;
        }

        $base = basename($uri);

        // Si c'est déjà un .min.js / .min.css, on ne tente rien
        if (str_contains($base, '.min.' . $ext)) {
            return false;
        }

        // Récupérer la config si elle existe, sinon utiliser des valeurs par défaut
        $CI =& get_instance();

        // Dossiers EXCLUS : vendors / libs externes (on ne les touche jamais)
        $denyPrefixes = $CI->config->item('ea_minify_deny_prefixes') ?? [
            'assets/ext/',
            'assets/vendor/',
            'assets/libs/',
            'assets/plugins/',
        ];

        foreach ($denyPrefixes as $prefix) {
            if (strpos($uri, $prefix) === 0) {
                return false;
            }
        }

        // Dossiers AUTORISÉS : JS/CSS applicatifs + overrides InterHop
        $allowPrefixes = $CI->config->item('ea_minify_allow_prefixes') ?? [
            'assets/js/',
            'assets/css/',
            'assets/interhop/',
            'assets/js/pages/interhop-',
        ];

        foreach ($allowPrefixes as $prefix) {
            if (strpos($uri, $prefix) === 0) {
                return true;
            }
        }

        // Par défaut : on ne minifie pas
        return false;
    }
}

// ------------------------------------------------------------------------
// Choix du bon fichier : source vs .min, avec vérification des dates
// ------------------------------------------------------------------------

if (!function_exists('ea_pick_minified_if_exists')) {
    function ea_pick_minified_if_exists(string $uri): string
    {
        $ext = pathinfo($uri, PATHINFO_EXTENSION);

        // On ne gère que .js / .css
        if (!in_array($ext, ['js', 'css'], true)) {
            return $uri;
        }

        // Chemins physiques vers source et min
        $srcPath = rtrim(FCPATH, '/\\') . '/' . ltrim($uri, '/');

        // Construire le chemin potentiel vers le .min : file.js -> file.min.js
        $minUri  = preg_replace('/\.' . $ext . '$/', '.min.' . $ext, $uri);
        $minPath = rtrim(FCPATH, '/\\') . '/' . ltrim($minUri, '/');

        $srcExists = is_file($srcPath);
        $minExists = is_file($minPath);

        // 1. Si le .min n'existe pas -> on garde le fichier original
        if (!$minExists) {
            return $uri;
        }

        // 2. Si le source n'existe pas mais le .min existe -> on utilise le .min
        if (!$srcExists && $minExists) {
            return $minUri;
        }

        // 3. Si les deux existent, on compare les dates de modification
        if ($srcExists && $minExists) {
            $srcMtime = filemtime($srcPath);
            $minMtime = filemtime($minPath);

            // Si le .min est plus vieux que la source : obsolète -> on garde la source
            if ($minMtime < $srcMtime) {
                return $uri;
            }

            // Sinon : .min existe et est au moins aussi récent -> on l'utilise
            return $minUri;
        }

        // 4. Cas improbable (aucun fichier valide) : on renvoie l'URI de base
        return $uri;
    }
}
