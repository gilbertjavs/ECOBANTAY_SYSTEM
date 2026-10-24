<?php

$env = static function (string $name, string $default): string {
    $value = getenv($name);
    return $value === false ? $default : $value;
};

if (!defined('DB_HOST')) {
    define('DB_HOST', $env('ECOBANTAY_DB_HOST', '127.0.0.1'));
}
if (!defined('DB_PORT')) {
    define('DB_PORT', $env('ECOBANTAY_DB_PORT', '3306'));
}
if (!defined('DB_NAME')) {
    define('DB_NAME', $env('ECOBANTAY_DB_NAME', 'ecobantay_system'));
}
if (!defined('DB_USER')) {
    define('DB_USER', $env('ECOBANTAY_DB_USER', 'root'));
}
if (!defined('DB_PASS')) {
    define('DB_PASS', $env('ECOBANTAY_DB_PASS', ''));
}

if (!defined('BASE_URL')) {
    $projectRoot = realpath(dirname(__DIR__));
    $documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    $baseUrl = '';

    if ($projectRoot !== false && $documentRoot !== false) {
        $projectRoot = rtrim(str_replace('\\', '/', $projectRoot), '/');
        $documentRoot = rtrim(str_replace('\\', '/', $documentRoot), '/');

        if (strcasecmp($projectRoot, $documentRoot) === 0) {
            $baseUrl = '';
        } elseif (strncasecmp($projectRoot, $documentRoot . '/', strlen($documentRoot) + 1) === 0) {
            $baseUrl = '/' . substr($projectRoot, strlen($documentRoot) + 1);
        }
    }

    define('BASE_URL', rtrim($baseUrl, '/'));
}

function db(): PDO
{
    static $connection = null;

    if ($connection === null) {
        $dsn = 'mysql:host=' . DB_HOST
            . ';port=' . DB_PORT
            . ';dbname=' . DB_NAME
            . ';charset=utf8mb4';

        $connection = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    return $connection;
}
