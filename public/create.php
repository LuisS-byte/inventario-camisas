<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

$errores = [];
$camisa = [
    'nombre' => '',
    'talla' => '',
    'color' => '',
    'cantidad' => '',
    'precio' => '',
    'descripcion' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $camisa = [
        'nombre' => trim($_POST['nombre'] ?? ''),
        'talla' => trim($_POST['talla'] ?? ''),
        'color' => trim($_POST['color'] ?? ''),
        'cantidad' => trim($_POST['cantidad'] ?? ''),
        'precio' => trim($_POST['precio'] ?? ''),
        'descripcion' => trim($_POST['descripcion'] ?? ''),
    ];

    if ($camisa['nombre'] === '') {
        $errores[] = 'El nombre es obligatorio.';
    }

    if ($camisa['talla'] === '') {
        $errores[] = 'La talla es obligatoria.';
    }

    if ($camisa['color'] === '') {
        $errores[] = 'El color es obligatorio.';
    }

    if (!ctype_digit($camisa['cantidad'])) {
        $errores[] = 'La cantidad debe ser un numero entero positivo.';
    }

    if (!is_numeric($camisa['precio']) || (float) $camisa['precio'] < 0) {
        $errores[] = 'El precio debe ser un numero positivo.';
    }

    if (count($errores) === 0) {
        $stmt = $pdo->prepare(
            'INSERT INTO camisas (nombre, talla, color, cantidad, precio, descripcion)
             VALUES (:nombre, :talla, :color, :cantidad, :precio, :descripcion)'
        );

        $stmt->execute([
            'nombre' => $camisa['nombre'],
            'talla' => $camisa['talla'],
            'color' => $camisa['color'],
            'cantidad' => (int) $camisa['cantidad'],
            'precio' => (float) $camisa['precio'],
            'descripcion' => $camisa['descripcion'],
        ]);

        header('Location: index.php?mensaje=Camisa agregada correctamente');
        exit;
    }
}

require __DIR__ . '/../views/form.php';

