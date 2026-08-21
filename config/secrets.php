<?php
declare(strict_types=1);

/**
 * Resolucion de la contrasena de base de datos desde AWS Secrets Manager.
 *
 * Proposito: no exponer la contrasena de RDS en el .env en produccion.
 * En EC2 la instancia asume un rol IAM con minimo privilegio
 * (secretsmanager:GetSecretValue) y el SDK resuelve las credenciales
 * automaticamente desde el instance profile; la contrasena se lee del
 * secreto gestionado por RDS (formato JSON con username/password/host/
 * port/dbname). Esto cubre el requisito del Caso 4 del enunciado.
 *
 * Comportamiento:
 *  1. Si DB_PASSWORD esta definida en .env, se usa esa (modo local/dev).
 *  2. Si DB_PASSWORD esta vacia pero DB_SECRET_NAME esta definido, se
 *     consulta Secrets Manager con el rol IAM de la instancia.
 *  3. Si no hay contrasena ni secreto configurado, devuelve '' (mismo
 *     comportamiento que antes, sin secreto).
 *
 * El nombre del secreto se lee de DB_SECRET_NAME (nunca hardcodeado).
 */

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/aws.php'; // ya carga vendor/autoload.php

use Aws\SecretsManager\SecretsManagerClient;
use Aws\Exception\AwsException;

/**
 * Devuelve la contrasena de la base de datos.
 *
 * Si DB_PASSWORD esta definida se usa esa; si no, se resuelve desde
 * AWS Secrets Manager usando el nombre DB_SECRET_NAME. El resultado se
 * cachea en el proceso (static) para no llamar a AWS en cada request HTTP.
 */
function passwordBaseDatos(): string
{
    static $cache = null;

    if ($cache !== null) {
        return $cache;
    }

    // Prioridad 1: contrasena explicita en .env (modo local/dev).
    $password = (string) env('DB_PASSWORD', '');
    if ($password !== '') {
        $cache = $password;
        return $cache;
    }

    // Prioridad 2: sin secreto configurado, se mantiene el comportamiento
    // actual (contrasena vacia). No hay nada que consultar en AWS.
    $nombreSecreto = (string) env('DB_SECRET_NAME', '');
    if ($nombreSecreto === '') {
        $cache = '';
        return $cache;
    }

    try {
        // Sin credenciales explicitas: el SDK resuelve el rol IAM de la
        // instancia EC2 (mismo patron que crearClienteS3).
        $cliente = new SecretsManagerClient([
            'version' => 'latest',
            'region' => (string) env('AWS_REGION', 'us-east-1'),
        ]);

        $resultado = $cliente->getSecretValue(['SecretId' => $nombreSecreto]);
        $valor = (string) $resultado['SecretString'];

        // Los secretos gestionados por RDS vienen como JSON con
        // username/password/host/port/dbname. Si no es JSON valido, el
        // valor crudo se usa como contrasena (por si el usuario guardo
        // solo el string).
        $datos = json_decode($valor, true);
        if (is_array($datos)) {
            $password = (string) ($datos['password'] ?? '');
        } else {
            $password = $valor;
        }
    } catch (AwsException $e) {
        error_log('Error al obtener secreto de BD desde Secrets Manager: ' . $e->getMessage());
        // No rompe la app: se devuelve '' y la conexion falla con el
        // mensaje normal. Se cachea '' para no repetir llamadas fallidas
        // a AWS en el mismo proceso (cada request HTTP solo deberia
        // intentar una vez; reintentar en bucle solo agrega latencia y
        // riesgo de throttling sin cambiar el resultado).
        $password = '';
    }

    $cache = $password;
    return $cache;
}
