<?php
declare(strict_types=1);

/**
 * Genera un reporte CSV del inventario de camisas y lo almacena en S3.
 *
 * Requisito "Automatizacion y respaldos" (enunciado AWS):
 *  1. Conecta a la base de datos MySQL (PDO directo; NO incluye
 *     config/database.php, que esta pensado para HTTP y sale con exit + HTML).
 *  2. Exporta las camisas a un CSV temporal en tmp_reportes/.
 *  3. Agrega al final una fila de totales: "TOTAL" en la columna categoria,
 *     la cantidad total en la columna cantidad y el valor total
 *     (suma de precio * cantidad) en la columna precio.
 *  4. Sube el CSV a S3 bajo reportes/YYYY/MM/ con cifrado AES256
 *     (solo si S3_BUCKET esta configurado en .env).
 *  5. Registra la ejecucion en logs/reportes/reporte-YYYYMMDD.log y deja el
 *     ultimo estado en logs/reportes/reporte.last (linea unica OK|FAIL).
 *  6. Termina con exit code 0 (exito) o 1 (fallo) para que una tarea
 *     programada (cron) pueda comprobar si el proceso fue exitoso o fallo.
 *
 * Nota: no se vuelca la base de datos cruda: RDS ya tiene snapshots
 * automaticos. Este script respalda el REPORTE del inventario en S3.
 *
 * Si S3 no esta configurado (sin S3_BUCKET), el reporte se guarda como
 * respaldo local en logs/reportes/ y la ejecucion se considera exitosa.
 *
 * Uso (CLI):
 *   php scripts/generar_reporte.php
 *
 * Cron sugerido (EC2 Amazon Linux 2023). Ejecutar en UNA sola instancia,
 * porque el codigo vive en EFS compartido entre varias EC2:
 *   30 2 * * * /usr/bin/php /var/www/html/inventario-camisas/scripts/generar_reporte.php >> /var/log/inventario_reporte.log 2>&1
 *
 * IAM: el rol de la instancia EC2 debe permitir s3:PutObject sobre
 * reportes/* del bucket, por ejemplo:
 *   arn:aws:s3:::TU_BUCKET/reportes/*
 */

require_once dirname(__DIR__) . '/config/env.php';
require_once dirname(__DIR__) . '/config/aws.php';

use Aws\Exception\AwsException;

/**
 * Escribe una linea con marca de tiempo en el log del dia.
 */
function escribirLog(string $rutaLog, string $mensaje): void
{
    file_put_contents(
        $rutaLog,
        '[' . date('Y-m-d H:i:s') . '] ' . $mensaje . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}

/**
 * Sobrescribe logs/reportes/reporte.last con una unica linea:
 * OK|FAIL|Y-m-d H:i:s|detalle.
 */
function escribirEstado(string $rutaEstado, string $estado, string $detalle): void
{
    $detalle = str_replace(["\r", "\n"], ' ', $detalle);
    file_put_contents($rutaEstado, $estado . '|' . date('Y-m-d H:i:s') . '|' . $detalle . PHP_EOL, LOCK_EX);
}

/**
 * Registra un fallo (log del dia + reporte.last), lo muestra en STDERR
 * y devuelve el codigo de salida 1.
 */
function fallo(string $rutaLog, string $rutaEstado, string $detalle): int
{
    escribirLog($rutaLog, 'FAIL: ' . $detalle);
    escribirEstado($rutaEstado, 'FAIL', $detalle);
    fwrite(STDERR, 'FAIL: ' . $detalle . PHP_EOL);
    return 1;
}

function main(): int
{
    $raiz = dirname(__DIR__);
    $dirLogs = $raiz . '/logs/reportes';
    $dirTmp = $raiz . '/tmp_reportes';
    $marca = date('Ymd-His');
    $rutaLog = $dirLogs . '/reporte-' . date('Ymd') . '.log';
    $rutaEstado = $dirLogs . '/reporte.last';

    try {
        // Directorio de logs (se crea si no existe)
        if (!is_dir($dirLogs) && !@mkdir($dirLogs, 0777, true)) {
            fwrite(STDERR, 'No se pudo crear el directorio de logs: ' . $dirLogs . PHP_EOL);
            return 1;
        }

        escribirLog($rutaLog, 'Inicio del reporte');

        // Conexion propia a la BD (NO se incluye config/database.php)
        $dsn = 'mysql:host=' . env('DB_HOST', 'localhost')
            . ';port=' . env('DB_PORT', 3306)
            . ';dbname=' . env('DB_NAME', 'inventario_camisas')
            . ';charset=utf8mb4';

        try {
            $pdo = new PDO($dsn, (string) env('DB_USER', 'root'), (string) env('DB_PASSWORD', ''), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            return fallo($rutaLog, $rutaEstado, 'Error al conectar a MySQL: ' . $e->getMessage());
        }

        // Consulta de camisas
        try {
            $stmt = $pdo->query(
                'SELECT id, nombre, categoria, talla, color, cantidad, precio, descripcion, creado_en '
                . 'FROM camisas ORDER BY creado_en DESC'
            );
            $productos = $stmt->fetchAll();
        } catch (PDOException $e) {
            return fallo($rutaLog, $rutaEstado, 'Error al consultar la tabla camisas: ' . $e->getMessage());
        }
        $pdo = null;

        // Directorio temporal para el CSV (se limpia al final)
        if (!is_dir($dirTmp) && !@mkdir($dirTmp, 0777, true)) {
            return fallo($rutaLog, $rutaEstado, 'No se pudo crear el directorio temporal: ' . $dirTmp);
        }

        // Generar CSV
        $nombreCsv = 'reporte-' . $marca . '.csv';
        $rutaCsv = $dirTmp . '/' . $nombreCsv;
        $fh = @fopen($rutaCsv, 'wb');
        if ($fh === false) {
            return fallo($rutaLog, $rutaEstado, 'No se pudo crear el CSV temporal: ' . $rutaCsv);
        }

        $ok = fputcsv($fh, ['id', 'nombre', 'categoria', 'talla', 'color', 'cantidad', 'precio', 'descripcion', 'creado_en']);
        $totalCantidad = 0;
        $totalValor = 0.0;
        foreach ($productos as $fila) {
            $ok = $ok !== false && fputcsv($fh, [
                $fila['id'],
                $fila['nombre'],
                $fila['categoria'],
                $fila['talla'],
                $fila['color'],
                $fila['cantidad'],
                $fila['precio'],
                $fila['descripcion'],
                $fila['creado_en'],
            ]) !== false;
            $totalCantidad += (int) $fila['cantidad'];
            $totalValor += (float) $fila['precio'] * (int) $fila['cantidad'];
        }
        // Fila de totales: "TOTAL" en categoria, cantidad total en cantidad,
        // valor total (precio * cantidad) en precio.
        $ok = $ok !== false && fputcsv($fh, [
            '', '', 'TOTAL', '', '', $totalCantidad, number_format($totalValor, 2, '.', ''), '', '',
        ]) !== false;
        fclose($fh);

        if (!$ok) {
            @unlink($rutaCsv);
            @rmdir($dirTmp);
            return fallo($rutaLog, $rutaEstado, 'Error al escribir el CSV: ' . $rutaCsv);
        }

        // Almacenar en S3 (si esta habilitado) o guardar como respaldo local
        if (s3Habilitado()) {
            $key = 'reportes/' . date('Y') . '/' . date('m') . '/' . $nombreCsv;

            $contenidoCsv = @file_get_contents($rutaCsv);
            if ($contenidoCsv === false) {
                @unlink($rutaCsv);
                @rmdir($dirTmp);
                return fallo($rutaLog, $rutaEstado, 'No se pudo leer el CSV temporal: ' . $rutaCsv);
            }

            try {
                crearClienteS3()->putObject([
                    'Bucket' => bucketS3(),
                    'Key' => $key,
                    'Body' => $contenidoCsv,
                    'ContentType' => 'text/csv',
                    'ServerSideEncryption' => 'AES256',
                ]);
            } catch (AwsException $e) {
                @unlink($rutaCsv);
                @rmdir($dirTmp);
                return fallo($rutaLog, $rutaEstado, 'Error al subir el reporte a S3: ' . $e->getMessage());
            }

            $detalleOk = 'reporte generado (' . count($productos) . ' productos) y subido a s3://' . bucketS3() . '/' . $key;
        } else {
            escribirLog($rutaLog, 'S3 no configurado, reporte solo local');
            $destinoLocal = $dirLogs . '/' . $nombreCsv;
            if (!@copy($rutaCsv, $destinoLocal)) {
                @unlink($rutaCsv);
                @rmdir($dirTmp);
                return fallo($rutaLog, $rutaEstado, 'No se pudo guardar el reporte local: ' . $destinoLocal);
            }
            $detalleOk = 'reporte generado (' . count($productos) . ' productos) guardado local en ' . $destinoLocal;
        }

        // Limpieza del archivo temporal y del directorio tmp
        @unlink($rutaCsv);
        @rmdir($dirTmp);

        // Registro de exito
        escribirLog($rutaLog, 'OK: ' . $detalleOk);
        escribirEstado($rutaEstado, 'OK', $detalleOk);
        echo 'OK: ' . $detalleOk . PHP_EOL;

        return 0;
    } catch (\Throwable $e) {
        // Red de seguridad: cualquier excepcion inesperada termina en FAIL.
        if (isset($rutaCsv)) {
            @unlink($rutaCsv);
            @rmdir($dirTmp);
        }
        return fallo($rutaLog, $rutaEstado, 'Error inesperado: ' . $e->getMessage());
    }
}

exit(main());
