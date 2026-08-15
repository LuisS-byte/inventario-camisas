<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

$mensaje = $_GET['mensaje'] ?? '';

$stmt = $pdo->query('SELECT * FROM camisas ORDER BY creado_en DESC');
$camisas = $stmt->fetchAll();

$totalUnidades = array_sum(array_map(static fn ($camisa) => (int) $camisa['cantidad'], $camisas));
$valorInventario = array_sum(array_map(
    static fn ($camisa) => (int) $camisa['cantidad'] * (float) $camisa['precio'],
    $camisas
));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario de Camisas</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <main class="shell">
        <header class="topbar">
            <div>
                <p class="eyebrow">Inventario</p>
                <h1>Camisas</h1>
                <p class="subtitle">Control simple de existencias, precios y modelos disponibles.</p>
            </div>
            <a class="button primary" href="create.php">Agregar camisa</a>
        </header>

        <?php if ($mensaje !== ''): ?>
            <div class="notice"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <section class="stats" aria-label="Resumen del inventario">
            <article>
                <span>Modelos</span>
                <strong><?= count($camisas) ?></strong>
            </article>
            <article>
                <span>Unidades</span>
                <strong><?= $totalUnidades ?></strong>
            </article>
            <article>
                <span>Valor total</span>
                <strong>$<?= number_format($valorInventario, 2) ?></strong>
            </article>
        </section>

        <section class="panel inventory-panel">
            <div class="panel-heading">
                <div>
                    <p class="eyebrow">Listado</p>
                    <h2>Inventario actual</h2>
                </div>
                <span class="pill"><?= count($camisas) ?> registros</span>
            </div>

            <?php if (count($camisas) === 0): ?>
                <p class="empty">Todavia no hay camisas registradas.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Talla</th>
                                <th>Color</th>
                                <th>Cantidad</th>
                                <th>Precio</th>
                                <th>Descripcion</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($camisas as $camisa): ?>
                                <tr>
                                    <td>
                                        <strong class="product-name"><?= htmlspecialchars($camisa['nombre']) ?></strong>
                                    </td>
                                    <td><span class="size-badge"><?= htmlspecialchars($camisa['talla']) ?></span></td>
                                    <td><?= htmlspecialchars($camisa['color']) ?></td>
                                    <td>
                                        <span class="stock-badge <?= (int) $camisa['cantidad'] <= 5 ? 'low' : '' ?>">
                                            <?= (int) $camisa['cantidad'] ?> unidades
                                        </span>
                                    </td>
                                    <td><strong>$<?= number_format((float) $camisa['precio'], 2) ?></strong></td>
                                    <td class="description"><?= htmlspecialchars($camisa['descripcion'] ?? '') ?></td>
                                    <td class="actions">
                                        <a class="button small" href="edit.php?id=<?= (int) $camisa['id'] ?>">Editar</a>
                                        <form action="delete.php" method="POST" onsubmit="return confirm('Deseas eliminar esta camisa?');">
                                            <input type="hidden" name="id" value="<?= (int) $camisa['id'] ?>">
                                            <button class="button danger small" type="submit">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
