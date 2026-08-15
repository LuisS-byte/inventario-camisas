<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);

if ($id > 0) {
    $stmt = $pdo->prepare('DELETE FROM camisas WHERE id = :id');
    $stmt->execute(['id' => $id]);
}

header('Location: index.php?mensaje=Camisa eliminada correctamente');
exit;

