<?php
declare(strict_types=1);

require_once __DIR__ . '/session_db.php';

activarSesionesEnBd();

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