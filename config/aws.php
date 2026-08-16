<?php
declare(strict_types=1);

/**
 * Configuración del cliente AWS S3.
 *
 * Las credenciales se leen de variables de entorno (archivo .env).
 * En AWS EC2 se puede usar el rol IAM de instance profile: si las
 * variables AWS_ACCESS_KEY_ID / AWS_SECRET_ACCESS_KEY están vacías,
 * el SDK resuelve las credenciales automáticamente desde el rol.
 */

require_once __DIR__ . '/env.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

use Aws\S3\S3Client;

function crearClienteS3(): ?S3Client
{
    $region = (string) env('AWS_REGION', 'us-east-1');

    // Si no hay access key configurada, se usa el rol IAM de la instancia
    // (el SDK lo resuelve solo); igualmente se devuelve el cliente.
    $opciones = [
        'version' => 'latest',
        'region' => $region,
    ];

    $accessKey = (string) env('AWS_ACCESS_KEY_ID', '');
    $secretKey = (string) env('AWS_SECRET_ACCESS_KEY', '');

    if ($accessKey !== '' && $secretKey !== '') {
        $opciones['credentials'] = [
            'key' => $accessKey,
            'secret' => $secretKey,
        ];
    }

    return new S3Client($opciones);
}

function bucketS3(): string
{
    return (string) env('S3_BUCKET', '');
}

function expiracionUrlS3(): int
{
    return (int) env('S3_URL_EXPIRY', 300);
}

/** Verdadero si hay bucket configurado (es decir, S3 está habilitado). */
function s3Habilitado(): bool
{
    return bucketS3() !== '';
}