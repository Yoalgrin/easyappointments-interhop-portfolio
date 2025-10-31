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
function asset_url(string $uri = '', ?string $protocol = null): string
{
    $debug = (bool) config('debug');
    $cache_busting_token = '?' . config('cache_busting_token');
    $base = basename($uri);
    // JS
    if (str_ends_with($base, '.js')) {
        if ($debug) {
            $uri = str_replace('.min.js', '.js', $uri);
        } else {
            if (!str_contains($base, '.min.js')) {
                $uri = str_replace('.js', '.min.js', $uri);
            }
        }
    }
    // CSS
    if (str_ends_with($base, '.css')) {
        if ($debug) {
            $uri = str_replace('.min.css', '.css', $uri);
        } else {
            if (!str_contains($base, '.min.css')) {
                $uri = str_replace('.css', '.min.css', $uri);
            }
        }
    }

    return base_url($uri . $cache_busting_token, $protocol);
}
