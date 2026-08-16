<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/s3.php';

exigirLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }

$id = (int) ($_POST['id'] ?? 0);
if ($id > 0) {
    // Obtener la imagen actual antes de borrar el registro
    $s = $pdo->prepare('SELECT imagen_key FROM camisas WHERE id = :id');
    $s->execute(['id' => $id]);
    $fila = $s->fetch();

    if ($fila && !empty($fila['imagen_key'])) {
        eliminarObjeto((string) $fila['imagen_key']);
    }

    $s = $pdo->prepare('DELETE FROM camisas WHERE id = :id');
    $s->execute(['id' => $id]);
}

header('Location: index.php?mensaje=Producto eliminado correctamente');
exit;