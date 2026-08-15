<?php

declare(strict_types=1);

define('DB_HOST', 'localhost');
define('DB_NAME', 'inventario_camisas');
define('DB_USER', 'root');
define('DB_PASSWORD', '#Luis2004');
define('DB_CHARSET', 'utf8mb4');

function conectarBaseDatos(): PDO
{
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    return new PDO($dsn, DB_USER, DB_PASSWORD, $options);
}

try {
    $pdo = conectarBaseDatos();
} catch (PDOException $exception) {
    http_response_code(500);
    exit('No se pudo conectar a la base de datos. Revisa config/database.php');
}
