# Sistema: Inventario de Camisas

Referencia técnica del estado actual del sistema. Útil como log para planificar la
migración a AWS (Actividad Práctica Integradora).

## Stack tecnológico

| Capa | Tecnología | Detalle |
|---|---|---|
| Lenguaje | PHP | 8.2.12 (CLI, XAMPP) |
| Servidor web | PHP built-in server | `php -S localhost:8000 -t public` |
| Base de datos | MySQL | 9.5.0 en `localhost:3306` |
| BD específica | `inventario_camisas` | Charset `utf8mb4` |
| AWS SDK | aws/aws-sdk-php v3 | vía Composer (Guzzle 8) |
| Estilo | CSS | Estático en `public/assets/styles.css` |
| Frontend | HTML/JS | PHP embebido, sin framework de JS |

> Nota: PHP y MySQL provienen de la instalación de XAMPP
> (`C:\xampp\php\php.exe` y MySQL 9.5.0). El servidor actualmente corre en
> background en `http://localhost:8000`.

## Estructura del proyecto

```
inventario-camisas/
├── .env                    # Variables de entorno (NO se sube al repo)
├── .env.example            # Plantilla con valores de ejemplo
├── composer.json           # Dependencias (AWS SDK)
├── config/
│   ├── env.php             # Cargador de .env (sin librerias externas)
│   ├── database.php        # Conexion PDO + credenciales desde .env
│   ├── auth.php            # Sesion, exigirLogin(), usuarioActual()
│   ├── session_db.php      # Handler de sesiones en BD (multi-EC2)
│   ├── aws.php             # Cliente S3Client desde .env
│   └── s3.php              # Helpers: subirImagen, eliminarObjeto, urlFirmada
├── database/
│   └── schema.sql          # Tablas: usuarios, camisas (+imagen), sesiones
├── public/                 # Docroot del servidor
│   ├── index.php           # Listado, busqueda, filtros, stats, imagenes
│   ├── create.php          # Alta de producto (sube imagen a S3)
│   ├── edit.php            # Modificacion (reemplaza imagen en S3)
│   ├── delete.php          # Eliminacion (borra objeto en S3)
│   ├── login.php           # Autenticacion (bcrypt)
│   ├── logout.php          # Cierre de sesion
│   ├── test_conexion.php
│   └── assets/styles.css
└── views/
    └── form.php            # Formulario compartido (alta/edicion + imagen)
```

## Base de datos

### Tabla `usuarios`
- Campos: `id`, `usuario`, `password`, `nombre`, `creado_en`.
- `password` con `password_hash()` (bcrypt, cost 12).
- Usuarios actuales: `Lsarmiento` (Luis Sarmiento) y un usuario de prueba.
  (Las credenciales se gestionan en el `.env`/BD, no se documentan aquí.)

### Tabla `camisas`
- Campos: `id`, `nombre`, `categoria`, `talla`, `color`, `cantidad`, `precio`,
  `descripcion`, `imagen_key`, `imagen_url`, `creado_en`, `actualizado_en`.
- `imagen_key`: clave del objeto en S3 (null si no hay imagen).
- `imagen_url`: contiene la misma key; la URL firmada se genera al vuelo.
- Contiene 3 registros semilla (schema.sql).

### Tabla `sesiones`
- Almacena la sesion PHP en BD para que sea compartida entre instancias EC2.
- Campos: `session_id` (PK), `usuario_id`, `datos`, `ip`, `user_agent`,
  `ultimo_acceso`, `expiracion`.

### Conexion
- Archivo: `config/database.php` (PDO, `ERRMODE_EXCEPTION`, prepared statements).
- Credenciales desde `.env` (DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD).

## Qué HACE el sistema

### Autenticacion
- Login con usuario y contrasena (`login.php`), sesion PHP.
- **Sesiones en BD** (`config/session_db.php`): sobreviven al balanceo entre
  multiples EC2 y al failover (el estado no vive en el disco del servidor).
- `exigirLogin()` protege `index.php`, `create.php`, `edit.php`, `delete.php`.
- Cierre de sesion (`logout.php`).

### CRUD de inventario (sobre `camisas`)
- **Registrar (Create):** `create.php` → INSERT.
- **Consultar (Read):** `index.php` → listado completo (`SELECT *`).
- **Modificar (Update):** `edit.php` → UPDATE.
- **Eliminar (Delete):** `delete.php` → DELETE.

### Busqueda y filtros
- Busqueda (`buscar`) por nombre, talla, color y descripcion con `LIKE`.
- Filtro por categoria.

### Estadisticas
- Conteo de productos, total de unidades y valor total del inventario.

### Imagenes en S3 (requisito obligatorio del enunciado)
- Campo de imagen en el formulario (`views/form.php`).
- Subida a S3 con `PutObject` (clave `productos/{fecha}-{aleatorio}.{ext}`).
- **Validacion de doble capa** (`validarImagenSubida()`):
  - Extensión del nombre: JPG/PNG/WEBP/GIF (barrera rápida).
  - Contenido real: MIME detectado con `fileinfo` + `getimagesize()` + coherencia
    extension/MIME. Bloquea archivos renombrados (p. ej. un `.php` disfrazado de `.jpg`).
  - Tamano maximo 5 MB.
- **Bucket privado**: la imagen se muestra con URL firmada temporal
  (`urlFirmadaImagen()`), acceso controlado via IAM.
- Al editar: reemplaza el objeto (borra el anterior en S3).
- Al eliminar: borra el objeto en S3 antes del DELETE.
- Sin credenciales S3 configuradas, la app funciona igual (ignora la imagen).

### Validacion de datos
- Campos obligatorios (nombre, categoria, talla, color).
- `cantidad`: entero positivo (`ctype_digit`).
- `precio`: numerico mayor o igual a 0.
- Escapado de salida con `htmlspecialchars()`.

### SQL seguro
- Prepared statements (`PDO::prepare` + parametros) en todas las consultas.

### Configuracion por entorno
- `.env` con variables de entorno (BD y AWS).
- Mismo codigo corre local y en EC2: solo cambia el `.env`.
- En EC2, si no hay access keys, el SDK usa el rol IAM de la instancia.

## Qué NO HACE el sistema

> Aspectos relevantes para el enunciado de la actividad AWS.

### No hace (funcional)
- ❌ No admite **documentos/archivos** por producto, solo imagenes.
- ❌ No hay **paginacion** del listado.
- ❌ No hay **exportacion** de datos (CSV/PDF) ni reportes.
- ❌ No maneja **multiusuario con roles/permisos** (solo login simple).
- ❌ No hay **auditoria / bitacora** de acciones.
- ❌ No valida **duplicados** de nombre/categoria al crear.
- ❌ Sin **respaldo automatizado de BD** (pendiente: script + tarea programada).

### No hace (seguridad / arquitectura)
- ❌ Sin tokens **CSRF** en los formularios.
- ❌ Sin manejo central de errores (PDO conecta al incluir el archivo).
- ❌ No diferencia accesos administrativos vs. de aplicacion (requisito AWS).
- ❌ `test_conexion.php` accesible sin login (util para pruebas de infra).

## Estado actual de ejecucion

- Servidor PHP built-in corriendo en `http://localhost:8000` (docroot `public/`).
- BD `inventario_camisas` accesible (credenciales en `.env`, no versionadas).
- Pruebas automatizadas OK: login, crear, buscar, logout, sesion en BD,
  failover de sesion, create con imagen sin credenciales S3.
- **S3 REAL configurado y probado** (bucket `lab2-p1-988563522867-us-east-2-an`,
  region `us-east-2`, usuario IAM `lab2-s3-local`):
  - PutObject (subida), GetObject (URL firmada descarga 200), DeleteObject
    (borrado) verificados contra el bucket real.
  - Flujo web completo OK: login -> crear con imagen -> ver URL firmada en
    index -> editar (reemplaza objeto) -> eliminar (borra objeto y registro).
  - `s3:ListBucket` NO esta en la politica (la app no lo usa); S3 devuelve
    403/NotFound cuando no existe el objeto — comportamiento esperado.

## Pendiente para AWS (no es codigo)

- ~~Crear bucket S3 privado + politica IAM de minimo privilegio~~ **HECHO**
  (bucket `lab2-p1-988563522867-us-east-2-an`, usuario IAM `lab2-s3-local`
  con `s3:PutObject`/`GetObject`/`DeleteObject` sobre `productos/`).
- Completar `.env` en las EC2 con credenciales/rol IAM, region y bucket.
- Instalar en EC2: Apache/Nginx + PHP-FPM, `composer install`, extensiones.
- RDS: host y credenciales en `.env` de las EC2; ejecutar schema.sql.
- Activar sticky sessions en el ALB (capa extra; la sesion ya vive en BD).
- Script de respaldo automatizado a S3 (requisito "Automatizacion").

## Relevancia para la actividad AWS

Requisitos del enunciado (punto 5) cubiertos: registrar, consultar, modificar,
eliminar, buscar, persistir en BD **y cargar/consultar archivos en S3**
(imagenes, bucket privado, URL firmada).