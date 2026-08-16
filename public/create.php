<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/s3.php';

exigirLogin();

$errores = [];
$camisa = ['nombre' => '', 'categoria' => 'Camisa', 'talla' => 'M', 'color' => '', 'cantidad' => '', 'precio' => '', 'descripcion' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $camisa = [
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

    // Subir imagen a S3 SOLO si los campos son validos; si hay errores de
    // validacion no se sube nada para evitar objetos huerfanos en el bucket.
    $imagenKey = null;
    if (!$errores) {
        $hayArchivo = isset($_FILES['imagen']) && ($_FILES['imagen']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;
        if ($hayArchivo) {
            try {
                [$imagenKey] = subirImagen($_FILES['imagen']);
            } catch (RuntimeException $e) {
                $errores[] = $e->getMessage();
            }
        }
    }

    if (!$errores) {
        $s = $pdo->prepare(
            'INSERT INTO camisas (nombre, categoria, talla, color, cantidad, precio, descripcion, imagen_key, imagen_url)
             VALUES (:nombre, :categoria, :talla, :color, :cantidad, :precio, :descripcion, :imagen_key, :imagen_url)'
        );
        $s->execute([
            'nombre' => $camisa['nombre'],
            'categoria' => $camisa['categoria'],
            'talla' => $camisa['talla'],
            'color' => $camisa['color'],
            'cantidad' => $camisa['cantidad'],
            'precio' => $camisa['precio'],
            'descripcion' => $camisa['descripcion'],
            'imagen_key' => $imagenKey,
            'imagen_url' => $imagenKey,
        ]);
        header('Location: index.php?mensaje=Producto registrado correctamente');
        exit;
    }
}

require __DIR__ . '/../views/form.php';