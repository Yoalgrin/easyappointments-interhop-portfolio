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

/**
 * Assets URL helper function.
 *
 * This function will create an asset file URL that includes a cache busting parameter in order
 * to invalidate the browser cache in case of an update.
 *
 * @param string $uri Relative URI (just like the one used in the base_url helper).
 * @param string|null $protocol Valid URI protocol.
 *
 * @return string Returns the final asset URL.
 */
/**
 * Assets URL helper with min/non-min fallback.
 *
 * Usage in views:
 *   <script src="<?= asset_url('assets/js/vendor.js') ?>"></script>
 *   <script src="<?= asset_url('assets/js/app.js') ?>"></script>
 *   <script src="<?= asset_url('assets/js/pages/account.js') ?>"></script>
 *   <link rel="stylesheet" href="<?= asset_url('assets/css/app.css') ?>">
 *
 * In debug=true -> try .js first, fallback to .min.js if missing.
 * In debug=false -> try .min.js first, fallback to .js if missing.
 */
function asset_url(string $uri = '', ?string $protocol = null): string
{
    $debug = (bool) config('debug');
    $cache_busting_token = '?' . config('cache_busting_token');

    // Paths
    $rel = ltrim($uri, '/');
    $ext = pathinfo($rel, PATHINFO_EXTENSION);
    $is_js  = ($ext === 'js');
    $is_css = ($ext === 'css');

    if (!$is_js && !$is_css) {
        // Not a JS/CSS file: just return as-is with cache-busting
        return base_url($rel . $cache_busting_token, $protocol);
    }

    // Build min / non-min variants
    $suffix = $is_js ? 'js' : 'css';

    // Normalize both variants regardless of what caller passed
    $rel_min  = preg_replace('/\.'.$suffix.'$/', '.min.'.$suffix, preg_replace('/\.min\.'.$suffix.'$/', '.'.$suffix, $rel));
    $rel_min  = preg_replace('/\.'.$suffix.'$/', '.min.'.$suffix, $rel_min); // ensure .min.suffix
    $rel_norm = preg_replace('/\.min\.'.$suffix.'$/', '.'.$suffix, $rel);

    // Decide order based on debug
    $candidates = $debug ? [$rel_norm, $rel_min] : [$rel_min, $rel_norm];

    foreach ($candidates as $cand) {
        $abs = rtrim(FCPATH, '/\\') . '/' . ltrim($cand, '/');
        if (is_file($abs)) {
            return base_url($cand . $cache_busting_token, $protocol);
        }
    }

    // Last resort: return first candidate (even if 404) to make the failure visible
    return base_url($candidates[0] . $cache_busting_token, $protocol);
}
