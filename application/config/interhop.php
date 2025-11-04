<?php defined('BASEPATH') or exit('No direct script access allowed');

$config['interhop_providers_limit_enabled'] = getenv('INTERHOP_PROVIDERS_LIMIT_ENABLED') ?: '1';
$config['interhop_providers_limit_default'] = 0; // 0 = illimité

