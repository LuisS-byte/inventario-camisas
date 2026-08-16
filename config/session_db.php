<?php
declare(strict_types=1);

/**
 * Almacenamiento de sesiones en base de datos.
 *
 * Permite que la sesión sea compartida entre múltiples instancias EC2:
 * no depende del disco local de cada servidor. Sobrevive al balanceo
 * de carga y al failover de una instancia.
 */

require_once __DIR__ . '/database.php';

function sesionBd(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = conectarBaseDatos();
    }
    return $pdo;
}

function sesionBdAbrir(string $savePath, string $sessionName): bool
{
    return true;
}

function sesionBdCerrar(): bool
{
    return true;
}

function sesionBdLeer(string $sessionId): string
{
    $stmt = sesionBd()->prepare(
        'SELECT datos FROM sesiones WHERE session_id = :id AND expiracion > :ahora LIMIT 1'
    );
    $stmt->execute(['id' => $sessionId, 'ahora' => time()]);
    $fila = $stmt->fetch();
    return $fila ? (string) $fila['datos'] : '';
}

function sesionBdEscribir(string $sessionId, string $datos): bool
{
    $stmt = sesionBd()->prepare(
        'INSERT INTO sesiones (session_id, usuario_id, datos, ip, user_agent, ultimo_acceso, expiracion)
         VALUES (:id, :usuario, :datos, :ip, :ua, :ultimo, :expiracion)
         ON DUPLICATE KEY UPDATE
            usuario_id = VALUES(usuario_id),
            datos = VALUES(datos),
            ip = VALUES(ip),
            user_agent = VALUES(user_agent),
            ultimo_acceso = VALUES(ultimo_acceso),
            expiracion = VALUES(expiracion)'
    );

    $usuarioId = null;
    if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['usuario_id'])) {
        $usuarioId = (int) $_SESSION['usuario_id'];
    } elseif ($datos !== '') {
        // Respaldo: parsear el formato "clave|valor" de la sesión PHP
        if (preg_match('/usuario_id\|i:(\d+);/', $datos, $m)) {
            $usuarioId = (int) $m[1];
        }
    }

    $stmt->execute([
        'id' => $sessionId,
        'usuario' => $usuarioId,
        'datos' => $datos,
        'ip' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
        'ua' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        'ultimo' => time(),
        'expiracion' => time() + (int) ini_get('session.gc_maxlifetime'),
    ]);

    return true;
}

function sesionBdDestruir(string $sessionId): bool
{
    $stmt = sesionBd()->prepare('DELETE FROM sesiones WHERE session_id = :id');
    $stmt->execute(['id' => $sessionId]);
    return true;
}

function sesionBdGc(int $maxLifetime): bool
{
    $stmt = sesionBd()->prepare('DELETE FROM sesiones WHERE expiracion < :ahora');
    $stmt->execute(['ahora' => time()]);
    return true;
}

/**
 * Configura PHP para usar las sesiones en BD.
 */
function activarSesionesEnBd(): void
{
    static $activado = false;
    if ($activado) {
        return;
    }

    session_set_save_handler(
        'sesionBdAbrir',
        'sesionBdCerrar',
        'sesionBdLeer',
        'sesionBdEscribir',
        'sesionBdDestruir',
        'sesionBdGc'
    );
    register_shutdown_function('session_write_close');

    $activado = true;
}