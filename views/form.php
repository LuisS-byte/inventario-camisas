<?php
$esEdicion = isset($camisa['id']);
$titulo = $esEdicion ? 'Editar producto' : 'Registrar producto';
$accion = $esEdicion ? 'edit.php?id=' . (int) $camisa['id'] : 'create.php';
$categorias = ['Camisa', 'Pantalón', 'Short', 'Vestido', 'Chaqueta', 'Sudadera', 'Blusa', 'Otro'];
$tallas = ['XS', 'S', 'M', 'L', 'XL', 'XXL', '28', '30', '32', '34', '36', '38', '40'];
$imagenActual = $esEdicion && !empty($camisa['imagen_actual_url']) ? (string) $camisa['imagen_actual_url'] : '';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $titulo ?> | Inventario</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<main class="shell narrow">
    <header class="topbar">
        <div>
            <p class="eyebrow">Inventario</p>
            <h1><?= $titulo ?></h1>
            <p class="subtitle">Completa la información del producto.</p>
        </div>
        <a class="button" href="index.php">Volver</a>
    </header>

    <?php if ($errores): ?>
        <div class="errors">
            <?php foreach ($errores as $e): ?>
                <p><?= htmlspecialchars($e) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form class="panel form" action="<?= htmlspecialchars($accion) ?>" method="post" enctype="multipart/form-data">

        <h3 class="form-section-title">Información del producto</h3>

        <div class="form-grid">
            <label><span class="required">Nombre</span>
                <input type="text" name="nombre" value="<?= htmlspecialchars((string) $camisa['nombre']) ?>" required>
            </label>
            <label><span class="required">Categoría</span>
                <select name="categoria" required>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>" <?= $camisa['categoria'] === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <div class="form-grid">
            <label><span class="required">Talla</span>
                <select name="talla" required>
                    <?php foreach ($tallas as $t): ?>
                        <option value="<?= $t ?>" <?= $camisa['talla'] === $t ? 'selected' : '' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><span class="required">Color</span>
                <input type="text" name="color" value="<?= htmlspecialchars((string) $camisa['color']) ?>" required>
            </label>
        </div>

        <h3 class="form-section-title">Stock y precio</h3>

        <div class="form-grid">
            <label><span class="required">Cantidad</span>
                <input type="number" name="cantidad" min="0" value="<?= htmlspecialchars((string) $camisa['cantidad']) ?>" required>
            </label>
            <label><span class="required">Precio</span>
                <input type="number" name="precio" min="0" step="0.01" value="<?= htmlspecialchars((string) $camisa['precio']) ?>" required>
            </label>
        </div>

        <h3 class="form-section-title">Detalles e imagen</h3>

        <label>Descripción
            <textarea name="descripcion" rows="5"><?= htmlspecialchars((string) ($camisa['descripcion'] ?? '')) ?></textarea>
        </label>
        <label>Imagen del producto
            <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp,image/gif" aria-describedby="hint-imagen">
            <span class="field-hint" id="hint-imagen">JPG, PNG, WEBP o GIF. Máximo 5 MB.</span>
        </label>
        <?php if ($imagenActual !== ''): ?>
            <div class="image-current">
                <img src="<?= htmlspecialchars($imagenActual) ?>" alt="Imagen actual del producto">
                <div class="image-current-text">
                    <strong>Imagen actual</strong>
                    <small>Sube una nueva para reemplazarla.</small>
                </div>
            </div>
        <?php else: ?>
            <div class="image-current empty">
                <div class="image-current-placeholder" aria-hidden="true">Sin imagen</div>
                <div class="image-current-text">
                    <strong>Sin imagen cargada</strong>
                    <small>Sube una imagen para que aparezca en el listado.</small>
                </div>
            </div>
        <?php endif; ?>
        <div class="form-actions">
            <a class="button" href="index.php">Cancelar</a>
            <button class="button primary" type="submit">Guardar</button>
        </div>
    </form>
</main>
</body>
</html>
