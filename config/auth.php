<?php
declare(strict_types=1);

require_once __DIR__ . '/session_db.php';

activarSesionesEnBd();

// Cookie de sesion segura: HttpOnly siempre, SameSite Lax, y Secure solo
// cuando la peticion llega por HTTPS (en local sin TLS se mantiene false
// para no romper el login en desarrollo).
$esHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? 80) == 443);

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'secure' => $esHttps,
    'samesite' => 'Lax',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function exigirLogin(): void
{
    if (empty($_SESSION['usuario_id'])) {
        header('Location: login.php');
        exit;
    }
}

function usuarioActual(): string
{
    return (string) ($_SESSION['nombre'] ?? $_SESSION['usuario'] ?? 'Usuario');
}