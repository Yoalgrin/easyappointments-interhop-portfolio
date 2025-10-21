<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
define('BASEPATH', true); // simule CodeIgniter
require __DIR__ . '/application/config/email.php';
file_put_contents(__DIR__.'/application/logs/test-write.log', "PHP can write logs\n");
echo "✅ Test terminé.\n";
