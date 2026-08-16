<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

exigirLogin();

$stmt = $pdo->query('SELECT DATABASE() AS base_actual, COUNT(*) AS total_camisas FROM camisas');
$resultado = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba de conexion</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <main class="shell narrow">
        <header class="topbar">
            <div>
                <p class="eyebrow">Base de datos</p>
                <h1>Conexion correcta</h1>
            </div>
            <a class="button" href="index.php">Volver</a>
        </header>

        <section class="panel form">
            <p><strong>Base conectada:</strong> <?= htmlspecialchars((string) $resultado['base_actual']) ?></p>
            <p><strong>Camisas registradas:</strong> <?= (int) $resultado['total_camisas'] ?></p>
        </section>
    </main>
</body>
</html>
