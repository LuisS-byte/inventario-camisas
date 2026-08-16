<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/s3.php';

exigirLogin();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: index.php?mensaje=Producto no encontrado'); exit; }

$s = $pdo->prepare('SELECT * FROM camisas WHERE id = :id');
$s->execute(['id' => $id]);
$camisaDb = $s->fetch();
if (!$camisaDb) { header('Location: index.php?mensaje=Producto no encontrado'); exit; }

$errores = [];
$imagenKeyActual = (string) ($camisaDb['imagen_key'] ?? '');
$imagenActualUrl = $imagenKeyActual !== '' ? urlFirmadaImagen($imagenKeyActual) : null;

$camisa = [
    'id' => $id,
    'nombre' => $camisaDb['nombre'],
    'categoria' => $camisaDb['categoria'],
    'talla' => $camisaDb['talla'],
    'color' => $camisaDb['color'],
    'cantidad' => $camisaDb['cantidad'],
    'precio' => $camisaDb['precio'],
    'descripcion' => $camisaDb['descripcion'],
    'imagen_key' => $imagenKeyActual,
    'imagen_url' => $imagenKeyActual,
    'imagen_actual_url' => $imagenActualUrl,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $camisa = [
        'id' => $id,
        'nombre' => trim($_POST['nombre'] ?? ''),
        'categoria' => trim($_POST['categoria'] ?? ''),
        'talla' => trim($_POST['talla'] ?? ''),
        'color' => trim($_POST['color'] ?? ''),
        'cantidad' => trim($_POST['cantidad'] ?? ''),
        'precio' => trim($_POST['precio'] ?? ''),
        'descripcion' => trim($_POST['descripcion'] ?? ''),
    ];

    if ($camisa['nombre'] === '') $errores[] = 'El nombre es obligatorio.';
    if ($camisa['categoria'] === '') $errores[] = 'La categoría es obligatoria.';
    if ($camisa['talla'] === '') $errores[] = 'La talla es obligatoria.';
    if ($camisa['color'] === '') $errores[] = 'El color es obligatorio.';
    if (!ctype_digit($camisa['cantidad'])) $errores[] = 'La cantidad debe ser un entero positivo.';
    if (!is_numeric($camisa['precio']) || (float) $camisa['precio'] < 0) $errores[] = 'El precio debe ser un número válido.';

    // Subir/reemplazar imagen en S3 SOLO si los campos son validos.
    // Si hay errores de validacion no se sube la nueva ni se borra la
    // anterior: el producto conserva su imagen y no quedan huerfanos.
    $imagenKeyNueva = $imagenKeyActual;
    if (!$errores) {
        $hayArchivo = isset($_FILES['imagen']) && ($_FILES['imagen']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;
        if ($hayArchivo) {
            try {
                [$nueva] = subirImagen($_FILES['imagen']);
                if ($nueva !== null) {
                    $imagenKeyNueva = $nueva;
                    if ($imagenKeyActual !== '') {
                        eliminarObjeto($imagenKeyActual);
                    }
                }
            } catch (RuntimeException $e) {
                $errores[] = $e->getMessage();
            }
        }
    }

    if (!$errores) {
        $s = $pdo->prepare(
            'UPDATE camisas
             SET nombre = :nombre, categoria = :categoria, talla = :talla, color = :color,
                 cantidad = :cantidad, precio = :precio, descripcion = :descripcion,
                 imagen_key = :imagen_key, imagen_url = :imagen_url
             WHERE id = :id'
        );
        $s->execute([
            'id' => $id,
            'nombre' => $camisa['nombre'],
            'categoria' => $camisa['categoria'],
            'talla' => $camisa['talla'],
            'color' => $camisa['color'],
            'cantidad' => $camisa['cantidad'],
            'precio' => $camisa['precio'],
            'descripcion' => $camisa['descripcion'],
            'imagen_key' => $imagenKeyNueva,
            'imagen_url' => $imagenKeyNueva,
        ]);
        header('Location: index.php?mensaje=Producto actualizado correctamente');
        exit;
    }

    // Re-render con los datos del POST (para mostrar errores)
    $camisa['imagen_key'] = $imagenKeyActual;
    $camisa['imagen_url'] = $imagenKeyActual;
    $camisa['imagen_actual_url'] = $imagenActualUrl;
}

require __DIR__ . '/../views/form.php';