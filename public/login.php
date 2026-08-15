<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!empty($_SESSION['usuario_id'])) { header('Location: index.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($usuario === '' || $password === '') { $error = 'Completa el usuario y la contraseña.'; }
    else {
        $stmt = $pdo->prepare('SELECT id, usuario, password, nombre FROM usuarios WHERE usuario = :usuario LIMIT 1');
        $stmt->execute(['usuario' => $usuario]); $u = $stmt->fetch();
        if ($u && password_verify($password, $u['password'])) {
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = (int)$u['id']; $_SESSION['usuario'] = $u['usuario']; $_SESSION['nombre'] = $u['nombre'];
            header('Location: index.php'); exit;
        }
        $error = 'Usuario o contraseña incorrectos.';
    }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Iniciar sesión | Inventario</title><link rel="stylesheet" href="assets/styles.css"></head>
<body class="login-page"><main class="login-card"><div class="login-logo">IR</div><p class="eyebrow">Inventario de ropa</p><h1>Iniciar sesión</h1><p class="subtitle">Accede para administrar el inventario.</p><?php if($error): ?><div class="errors"><p><?=htmlspecialchars($error)?></p></div><?php endif; ?><form class="form" method="post"><label>Usuario<input type="text" name="usuario" autocomplete="username" required></label><label>Contraseña<input type="password" name="password" autocomplete="current-password" required></label><button class="button primary full" type="submit">Entrar</button></form></main></body></html>
