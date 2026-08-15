<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

$mensaje = $_GET['mensaje'] ?? '';
$busqueda = trim($_GET['buscar'] ?? '');
$categoria = trim($_GET['categoria'] ?? '');

/*
|--------------------------------------------------------------------------
| Obtener categorías
|--------------------------------------------------------------------------
*/

$stmtCategorias = $pdo->query(
    'SELECT DISTINCT categoria
     FROM camisas
     WHERE categoria IS NOT NULL
     AND categoria <> ""
     ORDER BY categoria ASC'
);

$categorias = $stmtCategorias->fetchAll(PDO::FETCH_COLUMN);

/*
|--------------------------------------------------------------------------
| Buscar productos
|--------------------------------------------------------------------------
*/

$sql = 'SELECT * FROM camisas WHERE 1=1';
$params = [];

if ($busqueda !== '') {
    $sql .= ' AND (
        nombre LIKE :buscar_nombre
        OR talla LIKE :buscar_talla
        OR color LIKE :buscar_color
        OR descripcion LIKE :buscar_descripcion
    )';

    $termino = '%' . $busqueda . '%';

    $params[':buscar_nombre'] = $termino;
    $params[':buscar_talla'] = $termino;
    $params[':buscar_color'] = $termino;
    $params[':buscar_descripcion'] = $termino;
}

if ($categoria !== '') {
    $sql .= ' AND categoria = :categoria';
    $params[':categoria'] = $categoria;
}

$sql .= ' ORDER BY creado_en DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$camisas = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Estadísticas
|--------------------------------------------------------------------------
*/

$totalUnidades = array_sum(
    array_map(
        static fn ($camisa) => (int) $camisa['cantidad'],
        $camisas
    )
);

$valorInventario = array_sum(
    array_map(
        static fn ($camisa) =>
            (int) $camisa['cantidad'] * (float) $camisa['precio'],
        $camisas
    )
);

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Inventario de Ropa</title>

    <link
        rel="stylesheet"
        href="assets/styles.css"
    >
</head>

<body>

<main class="shell">

    <!-- =====================================================
         ENCABEZADO
    ====================================================== -->

    <header class="topbar">

        <div>

            <p class="eyebrow">
                Inventario
            </p>

            <h1>
                Inventario de Ropa
            </h1>

            <p class="subtitle">
                Control de existencias, precios y productos disponibles.
            </p>

        </div>

        <a
            class="button primary"
            href="create.php"
        >
            Agregar producto
        </a>

    </header>


    <!-- =====================================================
         MENSAJE
    ====================================================== -->

    <?php if ($mensaje !== ''): ?>

        <div class="notice">
            <?= htmlspecialchars($mensaje) ?>
        </div>

    <?php endif; ?>


    <!-- =====================================================
         ESTADÍSTICAS
    ====================================================== -->

    <section
        class="stats"
        aria-label="Resumen del inventario"
    >

        <article>

            <span>
                Productos
            </span>

            <strong>
                <?= count($camisas) ?>
            </strong>

        </article>


        <article>

            <span>
                Unidades
            </span>

            <strong>
                <?= $totalUnidades ?>
            </strong>

        </article>


        <article>

            <span>
                Valor total
            </span>

            <strong>
                $<?= number_format($valorInventario, 2) ?>
            </strong>

        </article>

    </section>


    <!-- =====================================================
         BUSCADOR
    ====================================================== -->

    <section class="panel">

        <div class="panel-heading">

            <div>
                
                <h2>
                    Buscar productos
                </h2>

            </div>

        </div>


        <form
            method="GET"
            action="index.php"
            class="search-form"
        >

            <div>

                <label for="buscar">
                    Buscar
                </label>

                <input
                    type="text"
                    id="buscar"
                    name="buscar"
                    placeholder="Nombre, talla, color o descripción..."
                    value="<?= htmlspecialchars($busqueda) ?>"
                >

            </div>


            <div>

                <label for="categoria">
                    Categoría
                </label>

                <select
                    id="categoria"
                    name="categoria"
                >

                    <option value="">
                        Todas las categorías
                    </option>

                    <?php foreach ($categorias as $cat): ?>

                        <option
                            value="<?= htmlspecialchars($cat) ?>"
                            <?= $categoria === $cat ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($cat) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="search-actions">

                <button
                    type="submit"
                    class="button primary"
                >
                    Buscar
                </button>

                <a
                    href="index.php"
                    class="button"
                >
                    Limpiar
                </a>

            </div>

        </form>

    </section>


    <!-- =====================================================
         INVENTARIO
    ====================================================== -->

    <section class="panel inventory-panel">

        <div class="panel-heading">

            <div>

                <p class="eyebrow">
                    Listado
                </p>

                <h2>
                    Inventario actual
                </h2>

            </div>

            <span class="pill">
                <?= count($camisas) ?> registros
            </span>

        </div>


        <?php if (count($camisas) === 0): ?>

            <p class="empty">
                No se encontraron productos.
            </p>

        <?php else: ?>

            <div class="table-wrap">

                <table>

                    <thead>

                        <tr>

                            <th>
                                Nombre
                            </th>

                            <th>
                                Categoría
                            </th>

                            <th>
                                Talla
                            </th>

                            <th>
                                Color
                            </th>

                            <th>
                                Cantidad
                            </th>

                            <th>
                                Precio
                            </th>

                            <th>
                                Descripción
                            </th>

                            <th>
                                Acciones
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php foreach ($camisas as $camisa): ?>

                        <tr>

                            <td>

                                <strong class="product-name">
                                    <?= htmlspecialchars($camisa['nombre']) ?>
                                </strong>

                            </td>


                            <td>

                                <?= htmlspecialchars($camisa['categoria']) ?>

                            </td>


                            <td>

                                <span class="size-badge">
                                    <?= htmlspecialchars($camisa['talla']) ?>
                                </span>

                            </td>


                            <td>

                                <?= htmlspecialchars($camisa['color']) ?>

                            </td>


                            <td>

                                <span
                                    class="stock-badge <?= (int) $camisa['cantidad'] <= 5 ? 'low' : '' ?>"
                                >

                                    <?= (int) $camisa['cantidad'] ?>

                                    unidades

                                </span>

                            </td>


                            <td>

                                <strong>
                                    $<?= number_format((float) $camisa['precio'], 2) ?>
                                </strong>

                            </td>


                            <td class="description">

                                <?= htmlspecialchars($camisa['descripcion'] ?? '') ?>

                            </td>


                            <td class="actions">

                                <a
                                    class="button small"
                                    href="edit.php?id=<?= (int) $camisa['id'] ?>"
                                >
                                    Editar
                                </a>


                                <form
                                    action="delete.php"
                                    method="POST"
                                    onsubmit="return confirm('¿Deseas eliminar este producto?');"
                                >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int) $camisa['id'] ?>"
                                    >

                                    <button
                                        class="button danger small"
                                        type="submit"
                                    >
                                        Eliminar
                                    </button>

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