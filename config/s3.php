<?php
declare(strict_types=1);

/**
 * Helpers para operar con S3 en la aplicación.
 *
 * Todas las funciones son seguras cuando S3 no está configurado:
 * devuelven null / false sin romper el flujo, de modo que la app
 * funciona en local sin credenciales.
 */

require_once __DIR__ . '/aws.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

/** Extensiones permitidas para imágenes de productos. */
const IMAGENES_PERMITIDAS = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif'];

/** Tamaño máximo de imagen: 5 MB. */
const IMAGEN_MAX_BYTES = 5 * 1024 * 1024;

/**
 * Valida un archivo subido como imagen y devuelve [contenido, mime, extension].
 *
 * Validación de doble capa:
 *  1. Extensión del nombre (barrera rápida).
 *  2. Contenido real del archivo (MIME detectado + verificación de imagen),
 *     para impedir archivos renombrados (p. ej. un .php disfrazado de .jpg).
 *
 * @param array $archivo $_FILES['imagen']
 * @return array{0: string, 1: string, 2: string} [contenido, mime, extension]
 * @throws RuntimeException si el archivo no es una imagen válida.
 */
function validarImagenSubida(array $archivo): array
{
    $nombre = $archivo['name'] ?? '';
    $tmp = $archivo['tmp_name'] ?? '';
    $tamano = (int) ($archivo['size'] ?? 0);

    if ($nombre === '' || $tmp === '') {
        throw new RuntimeException('No se recibió ningún archivo.');
    }

    // --- 1. Barrera rápida: extensión del nombre ---
    $extension = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
    if (!isset(IMAGENES_PERMITIDAS[$extension])) {
        throw new RuntimeException('Formato de imagen no permitido. Usa JPG, PNG, WEBP o GIF.');
    }

    // --- 2. Tamaño máximo ---
    if ($tamano > IMAGEN_MAX_BYTES) {
        throw new RuntimeException('La imagen supera el tamaño máximo de 5 MB.');
    }

    // --- 3. Validación de contenido real (MIME detectado) ---
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeDetectado = $finfo->file($tmp);

    if ($mimeDetectado === false || !in_array($mimeDetectado, IMAGENES_PERMITIDAS, true)) {
        throw new RuntimeException(
            'El contenido del archivo no corresponde a una imagen válida (se detectó "' . ($mimeDetectado ?: 'desconocido') . '").'
        );
    }

    // Coherencia: la extensión declarada debe coincidir con el MIME real
    if (IMAGENES_PERMITIDAS[$extension] !== $mimeDetectado) {
        throw new RuntimeException('El archivo no coincide con su extensión. Renómbralo o usa un archivo real.');
    }

    // --- 4. Verificar que es una imagen decodificable ---
    $infoImagen = @getimagesize($tmp);
    if ($infoImagen === false) {
        throw new RuntimeException('El archivo no es una imagen válida o está corrupto.');
    }

    $contenido = file_get_contents($tmp);
    if ($contenido === false) {
        throw new RuntimeException('No se pudo leer el archivo de imagen.');
    }

    return [$contenido, $mimeDetectado, $extension];
}

/**
 * Valida y sube una imagen a S3. Devuelve [key, null] o [null, null].
 *
 * La validación de contenido ocurre ANTES de tocar S3 (validarImagenSubida).
 * La URL de visualización se genera al vuelo con urlFirmadaImagen().
 *
 * @param array $archivo $_FILES['imagen']
 * @return array{0: ?string, 1: ?string}
 * @throws RuntimeException si el archivo no es una imagen válida o falla S3.
 */
function subirImagen(array $archivo): array
{
    if (!s3Habilitado() || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return [null, null];
    }

    $tmp = $archivo['tmp_name'] ?? '';
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return [null, null];
    }

    // Validación de contenido (extensión, tamaño, MIME real, imagen decodificable)
    [$contenido, $mimeDetectado, $extension] = validarImagenSubida($archivo);

    // --- Subida a S3 (fuera de la validación) ---
    $cliente = crearClienteS3();
    $bucket = bucketS3();
    $key = 'productos/' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;

    try {
        $cliente->putObject([
            'Bucket' => $bucket,
            'Key' => $key,
            'Body' => $contenido,
            'ContentType' => $mimeDetectado,
            'ServerSideEncryption' => 'AES256',
        ]);
    } catch (AwsException $e) {
        throw new RuntimeException('Error al subir la imagen a S3: ' . $e->getMessage());
    }

    return [$key, null];
}

/**
 * Elimina un objeto de S3. Devuelve true si se eliminó o no existía.
 */
function eliminarObjeto(?string $key): bool
{
    if ($key === null || $key === '' || !s3Habilitado()) {
        return true;
    }

    try {
        crearClienteS3()->deleteObject([
            'Bucket' => bucketS3(),
            'Key' => $key,
        ]);
        return true;
    } catch (AwsException $e) {
        return false;
    }
}

/**
 * Genera una URL firmada temporal para acceder a un objeto privado.
 * Devuelve null si no hay S3 o la imagen no existe.
 */
function urlFirmadaImagen(?string $key): ?string
{
    if ($key === null || $key === '' || !s3Habilitado()) {
        return null;
    }

    try {
        $comando = crearClienteS3()->getCommand('GetObject', [
            'Bucket' => bucketS3(),
            'Key' => $key,
        ]);
        $request = crearClienteS3()->createPresignedRequest($comando, '+' . expiracionUrlS3() . ' seconds');
        return (string) $request->getUri();
    } catch (AwsException $e) {
        return null;
    }
}