<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
function exigirLogin(): void { if (empty($_SESSION['usuario_id'])) { header('Location: login.php'); exit; } }
function usuarioActual(): string { return (string)($_SESSION['nombre'] ?? $_SESSION['usuario'] ?? 'Usuario'); }
