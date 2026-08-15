# Inventario de Camisas

Aplicacion web sencilla en PHP y MySQL para administrar solamente inventario de camisas.

## Requisitos

- PHP 8.0 o superior
- MySQL o MariaDB
- Servidor local como XAMPP, Laragon o WAMP
- Visual Studio Code

## Instalacion

1. Abre MySQL o phpMyAdmin y ejecuta el archivo `database/schema.sql`.
2. Revisa la conexion en `config/database.php`.
3. Copia esta carpeta dentro de `htdocs` si usas XAMPP, o dentro de la carpeta web de tu servidor local.
4. Prueba la conexion en el navegador:

```text
http://localhost/inventario-camisas/public/test_conexion.php
```

5. Abre el sistema:

```text
http://localhost/inventario-camisas/public/
```

## Datos de conexion por defecto

```text
Base de datos: inventario_camisas
Usuario: root
Clave: vacia
Host: localhost
```

Si tu MySQL tiene clave, cambia esta linea en `config/database.php`:

```php
define('DB_PASSWORD', '');
```

Por ejemplo:

```php
define('DB_PASSWORD', 'tu_clave');
```

## Funciones

- Ver inventario de camisas
- Agregar camisas
- Editar camisas
- Eliminar camisas
- Registrar talla, color, precio, cantidad y descripcion
