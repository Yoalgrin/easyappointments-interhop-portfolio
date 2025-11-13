<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * InterhopProvidersCompatHook
 *
 * Compatibilité POST /providers/(update|store)
 * - Ne s’exécute QUE sur POST /providers/update ou /providers/store.
 * - N’invente rien : n’écrase PAS si provider/settings existent déjà.
 * - Si provider[...] ou provider[settings][...] sont absents, reconstruit le strict minimum
 *   attendu par le core pour éviter les resets et les JSON.parse côté front.
 * - Ne touche PAS à d’autres champs métier (services, notes, etc.) en dehors du minimum vital.
 * - N’interfère pas avec interhop[max_patients] (laissée telle quelle pour l’upsert dédié).
 */
class InterhopProvidersCompatHook
{
    public function preNormalizeProvidersPost(): void
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') return;

        $CI = &get_instance();
        $class  = strtolower($CI->router->class ?? '');
        $method = strtolower($CI->router->method ?? '');

        if ($class !== 'providers' || !in_array($method, ['update','store'], true)) {
            return;
        }

        // Récup sources si présentes
        $inProv = (isset($_POST['provider']) && is_array($_POST['provider'])) ? $_POST['provider'] : [];
        $inSet  = (isset($inProv['settings']) && is_array($inProv['settings'])) ? $inProv['settings'] : [];

        // Helper simple
        $pick = static function (...$c) {
            foreach ($c as $v) { if ($v !== null && $v !== '') return $v; }
            return null;
        };

        // ---- Assurer l’existence des blocs attendus ----
        if (!isset($_POST['provider']) || !is_array($_POST['provider'])) {
            $_POST['provider'] = [];
        }
        if (!isset($_POST['provider']['settings']) || !is_array($_POST['provider']['settings'])) {
            $_POST['provider']['settings'] = [];
        }

        // ---- Identité basique (ne remplit que si manquant) ----
        $keys = ['id','first_name','last_name','email','phone_number','alt_number','address','city','state','zip_code','timezone','language','notes','id_roles','is_private','ldap_dn'];
        foreach ($keys as $k) {
            if (!array_key_exists($k, $_POST['provider'])) {
                $v = $pick($inProv[$k] ?? null, $_POST[$k] ?? null);
                if ($v !== null) $_POST['provider'][$k] = $v;
            }
        }

        // ---- SETTINGS : garantir la présence + types attendus par le core ----
        // username (souvent requis côté UI)
        if (!array_key_exists('username', $_POST['provider']['settings'])) {
            $u = $pick($inSet['username'] ?? null, $_POST['username'] ?? null);
            if ($u !== null) $_POST['provider']['settings']['username'] = (string)$u;
        }

        // working_plan (le core et le front le JSON.parse → on lui passe TOUJOURS une STRING JSON)
        $company_wp = setting('company_working_plan'); // JSON objet ou string selon config
        $wp_in = $pick(
            $inSet['working_plan'] ?? null,
            $_POST['provider']['settings']['working_plan'] ?? null,
            $_POST['working_plan'] ?? null,
            $company_wp ?? '{}'
        );
        $_POST['provider']['settings']['working_plan'] = self::asJsonString($wp_in, '{}');

        // working_plan_exceptions (array JSON en string)
        $wpe_in = $pick(
            $inSet['working_plan_exceptions'] ?? null,
            $_POST['provider']['settings']['working_plan_exceptions'] ?? null,
            $_POST['working_plan_exceptions'] ?? null,
            '[]'
        );
        $_POST['provider']['settings']['working_plan_exceptions'] = self::asJsonString($wpe_in, '[]');

        // notifications / calendar_view : laisser passer si fournis, sinon ne rien inventer
        // (le controller gère déjà allowed/optional)

        // ---- Services : NE PAS forcer (le controller sait gérer optional) ----
        // Juste s'assurer que si présent, c’est un array
        if (isset($_POST['provider']['services']) && !is_array($_POST['provider']['services'])) {
            unset($_POST['provider']['services']); // on laisse optional() du modèle faire le job proprement
        }

        // ---- interhop[max_patients] : on ne touche à rien ici (géré par ton endpoint upsert) ----

        log_message('debug', '[IH] ProvidersCompat normalized POST for /providers/'.$method);
    }

    /**
     * Transforme n’importe quelle valeur en chaîne JSON valide (pour JSON.parse côté front).
     */
    private static function asJsonString($val, string $fallbackJson): string
    {
        // Déjà string JSON valide ?
        if (is_string($val)) {
            $s = trim($val);
            // on accepte aussi '{}' / '[]' vides
            try { json_decode($s, true, 512, JSON_THROW_ON_ERROR); return $s; }
            catch (Throwable $e) { /* continue */ }
        }

        // Objet/array → stringify
        if (is_array($val) || is_object($val)) {
            try {
                $s = json_encode($val, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if ($s !== false) return $s;
            } catch (Throwable $e) { /* fallback */ }
        }

        // Fallback
        return $fallbackJson;
    }
}

