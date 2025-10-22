<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.5.0
 * ---------------------------------------------------------------------------- */

if (!function_exists('env')) {
    /**
     * Gets the value of an environment variable.
     *
     * Example:
     *
     * $debug = env('debug', FALSE);
     *
     * @param string $key Environment key.
     * @param mixed|null $default Default value in case the requested variable has no value.
     *
     * @return mixed
     *
     * @throws InvalidArgumentException
     */
    function env(string $key, mixed $default = null): mixed
    {
        if (empty($key)) {
            throw new InvalidArgumentException('The $key argument cannot be empty.');
        }

        return $_ENV[$key] ?? $default;
    }
    if (!function_exists('env_pick')) {
        /**
         * Retourne la première variable d'env non vide parmi la liste $candidates.
         * @param array $candidates Liste de clés env par ordre de priorité
         * @param mixed $default Valeur par défaut si rien n'est trouvé
         */
        function env_pick(array $candidates, $default = null) {
            foreach ($candidates as $key) {
                $v = getenv($key);
                if ($v !== false && $v !== '') return $v;
            }
            return $default;
        }
    }
}
