<?php
declare(strict_types=1);

/**
 * Cargador liviano de variables de entorno.
 *
 * Lee el archivo .env del proyecto y expone cada variable en
 * getenv() / $_ENV. No depende de librerias externas.
 *
 * Formato soportado:
 *   CLAVE=valor
 *   # comentario
 *   CLAVE="valor con espacios"
 */

final class EnvLoader
{
    private static bool $cargado = false;

    public static function cargar(string $ruta = ''): void
    {
        if (self::$cargado) {
            return;
        }

        $archivo = $ruta !== '' ? $ruta : dirname(__DIR__) . '/.env';

        if (!is_file($archivo)) {
            self::$cargado = true;
            return;
        }

        $lineas = file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lineas === false) {
            self::$cargado = true;
            return;
        }

        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if ($linea === '' || str_starts_with($linea, '#')) {
                continue;
            }

            $pos = strpos($linea, '=');
            if ($pos === false) {
                continue;
            }

            $clave = trim(substr($linea, 0, $pos));
            $valor = trim(substr($linea, $pos + 1));

            // Quitar comillas envolventes ("valor" o 'valor')
            if (strlen($valor) >= 2) {
                $primera = $valor[0];
                $ultima = $valor[strlen($valor) - 1];
                if (($primera === '"' && $ultima === '"') || ($primera === "'" && $ultima === "'")) {
                    $valor = substr($valor, 1, -1);
                }
            }

            if ($clave === '') {
                continue;
            }

            $_ENV[$clave] = $valor;
            putenv($clave . '=' . $valor);
        }

        self::$cargado = true;
    }
}

/**
 * Obtiene una variable de entorno con valor por defecto.
 */
function env(string $clave, mixed $default = null): mixed
{
    $valor = getenv($clave);
    if ($valor === false || $valor === '') {
        return $default;
    }
    return $valor;
}

EnvLoader::cargar();