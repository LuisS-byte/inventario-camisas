<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

define('DB_HOST', (string) env('DB_HOST', 'localhost'));
define('DB_PORT', (int) env('DB_PORT', 3306));
define('DB_NAME', (string) env('DB_NAME', 'inventario_camisas'));
define('DB_USER', (string) env('DB_USER', 'root'));
define('DB_PASSWORD', (string) env('DB_PASSWORD', ''));
define('DB_CHARSET', 'utf8mb4');

function conectarBaseDatos(): PDO
{
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    return new PDO($dsn, DB_USER, DB_PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

try { $pdo = conectarBaseDatos(); }
catch (PDOException $e) { http_response_code(500); exit('No se pudo conectar a MySQL. Revisa config/database.php.'); }