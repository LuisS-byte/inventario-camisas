<?php

$esEdicion = isset($camisa['id']);
$titulo = $esEdicion ? 'Editar camisa' : 'Agregar camisa';
$accion = $esEdicion ? "edit.php?id={$camisa['id']}" : 'create.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?> | Inventario de Camisas</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <main class="shell narrow">
        <header class="topbar">
            <div>
                <p class="eyebrow">Inventario</p>
                <h1><?= $titulo ?></h1>
                <p class="subtitle">
                    <?= $esEdicion ? 'Actualiza los datos de esta camisa en el inventario.' : 'Registra una nueva camisa con su talla, color, precio y cantidad.' ?>
                </p>
            </div>
            <a class="button" href="index.php">Volver</a>
        </header>

        <?php if (count($errores) > 0): ?>
            <div class="errors">
                <?php foreach ($errores as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form class="panel form" action="<?= htmlspecialchars($accion) ?>" method="POST">
            <div class="panel-heading compact">
                <div>
                    <p class="eyebrow">Detalle</p>
                    <h2>Informacion de la camisa</h2>
                </div>
            </div>

            <label>
                Nombre
                <input type="text" name="nombre" placeholder="Ej. Camisa formal manga larga" value="<?= htmlspecialchars((string) $camisa['nombre']) ?>" required>
            </label>

            <div class="form-grid">
                <label>
                    Talla
                    <select name="talla" required>
                        <?php foreach (['XS', 'S', 'M', 'L', 'XL', 'XXL'] as $talla): ?>
                            <option value="<?= $talla ?>" <?= $camisa['talla'] === $talla ? 'selected' : '' ?>>
                                <?= $talla ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    Color
                    <input type="text" name="color" placeholder="Ej. Blanco, azul, negro" value="<?= htmlspecialchars((string) $camisa['color']) ?>" required>
                </label>
            </div>

            <div class="form-grid">
                <label>
                    Cantidad
                    <input type="number" name="cantidad" min="0" placeholder="0" value="<?= htmlspecialchars((string) $camisa['cantidad']) ?>" required>
                </label>

                <label>
                    Precio
                    <input type="number" name="precio" min="0" step="0.01" placeholder="0.00" value="<?= htmlspecialchars((string) $camisa['precio']) ?>" required>
                </label>
            </div>

            <label>
                Descripcion
                <textarea name="descripcion" rows="4" placeholder="Notas cortas sobre material, estilo o proveedor"><?= htmlspecialchars((string) ($camisa['descripcion'] ?? '')) ?></textarea>
            </label>

            <div class="form-actions">
                <a class="button" href="index.php">Cancelar</a>
                <button class="button primary" type="submit">Guardar</button>
            </div>
        </form>
    </main>
</body>
</html>
