# Sistema Juridico Ecuador

Aplicacion web para registrar, organizar, consultar, buscar y actualizar
normas juridicas ecuatorianas y sus articulos, conservando unicamente el
texto vigente de cada articulo. Ver tambien [SECURITY.md](SECURITY.md).

## Tecnologias

- PHP 8.3+ (sin framework; arquitectura por capas propia: Controllers /
  Services / Repositories / Validators / Views).
- MariaDB 10.11+ (compatible con MySQL 8.x; probado en este entorno de
  desarrollo contra MySQL 8.4).
- PDO con consultas preparadas, `ERRMODE_EXCEPTION`, emulacion desactivada.
- Bootstrap 5 + Bootstrap Icons (via CDN).
- PHPUnit 11 para pruebas unitarias y de integracion.
- Composer unicamente para autoload PSR-4 y PHPUnit (sin dependencias de
  produccion adicionales).

## 1. Instalar dependencias

```bash
composer install
```

Requiere las extensiones PHP: `pdo_mysql`, `mbstring`, `openssl`, `json`.
Si usa el PHP de Windows (WinGet/XAMPP/similar), habilite esas extensiones
en `php.ini` (quitar el `;` de `extension=openssl`, `extension=mbstring`,
`extension=fileinfo`) antes de ejecutar Composer.

## 2. Configurar el entorno

```bash
cp .env.example .env
```

Edite `.env` con los datos reales de su base de datos y un `APP_KEY`
aleatorio. **Nunca** suba `.env` al repositorio (ya esta en `.gitignore`).

## 3. Crear o importar la base de datos

El esquema se versiona en `database/migrations/`, en orden:

```bash
mysql -u root -p < database/migrations/0000_schema_base.sql
mysql -u root -p sistema_juridico_ec < database/migrations/0001_add_auth_columns.sql
mysql -u root -p sistema_juridico_ec < database/migrations/0002_add_revision_normas.sql
```

(`mariadb` en lugar de `mysql` si usa el cliente de MariaDB). Cree ademas un
usuario de aplicacion con privilegios limitados (sin `DROP`/`GRANT`) y
use esas credenciales en `.env`:

```sql
CREATE USER 'sistema_juridico_app'@'localhost' IDENTIFIED BY 'una_contrasena_fuerte';
GRANT SELECT, INSERT, UPDATE, DELETE ON sistema_juridico_ec.* TO 'sistema_juridico_app'@'localhost';
FLUSH PRIVILEGES;
```

> Nota sobre el esquema: `database/migrations/0000_schema_base.sql` es una
> copia identica del `sistema_juridico_ec.sql` original (no se modifico
> ninguna columna existente). Las migraciones `0001` y `0002` son
> **aditivas**: agregan `usuarios.ultimo_acceso`, `usuarios.intentos_fallidos`,
> `usuarios.bloqueado_hasta` (bloqueo temporal de login) y
> `normas.revisado_por` / `normas.fecha_revision` (marca de revision de
> vigencia por un REVISOR). Ver el encabezado de cada archivo para la
> justificacion.

## 4. Configurar el servidor web

El unico directorio que debe exponer el servidor web es `public/`.

### Apache

```apache
<VirtualHost *:80>
    ServerName sistema-juridico.local
    DocumentRoot "/ruta/al/proyecto/public"
    <Directory "/ruta/al/proyecto/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Requiere `mod_rewrite` habilitado (el `.htaccess` ya esta incluido en
`public/`).

### Nginx (ejemplo)

```nginx
server {
    listen 80;
    server_name sistema-juridico.local;
    root /ruta/al/proyecto/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\.(env|git) {
        deny all;
    }
}
```

### Servidor embebido de PHP (solo desarrollo)

```bash
php -S 127.0.0.1:8000 -t public
```

## 5. Crear el primer administrador de forma segura

**No existe un instalador web.** El primer administrador se crea solo por
linea de comandos:

```bash
php bin/create-admin.php "Nombre Completo" admin@dominio.ec "ContrasenaSegura_de_al_menos_12_caracteres"
```

El script rechaza ejecutarse fuera de CLI, valida el formato del correo y
exige una contrasena de al menos 12 caracteres.

## 6. Ejecutar pruebas

```bash
composer test
# o
vendor/bin/phpunit
```

- `tests/Unit`: no requieren base de datos (Validator, CSRF, hashing de
  contrasenas, ramas puras de `VigenciaService`).
- `tests/Integration`: requieren una base de datos real. Configure
  `.env.testing` con una instancia de pruebas (recomendado: una base de
  datos separada, p. ej. `sistema_juridico_ec_test`) para que se ejecuten;
  si no hay conexion disponible, se omiten automaticamente con
  `markTestSkipped()` en lugar de darse por aprobadas sin ejecutarse.

**Se levanto una instancia real de MySQL 8.4 (compatible con MariaDB) en
este entorno de desarrollo y se ejecuto la suite completa**, incluyendo
`tests/Integration/`: **42/42 pruebas OK**. Ver [SECURITY.md](SECURITY.md)
para el detalle de las pruebas de seguridad ejecutadas (inyeccion SQL,
XSS, manipulacion de identificadores, CSRF, autorizacion por rol,
exposicion de errores) y los bugs reales que se encontraron y corrigieron
al ejecutarlas contra datos reales.

## 7. Habilitar HTTPS

En produccion, TLS debe terminar en el servidor web (Apache/Nginx) o en un
balanceador/proxy inverso delante de el. Una vez con HTTPS activo:

- Establezca `SESSION_SECURE_COOKIE=true` en `.env` para que la cookie de
  sesion se marque `Secure`.
- Redirija todo el trafico HTTP a HTTPS a nivel de servidor web.

## 8. Permisos de archivos

```bash
chmod -R 750 storage
chmod -R 640 .env
```

`storage/logs` y `storage/cache` deben ser escribibles por el usuario del
servidor web pero no accesibles publicamente (estan fuera de `public/`).

## 9. Copias de seguridad

Un usuario con rol `ADMINISTRADOR` puede generar y descargar un respaldo
completo (estructura + datos) con un clic desde el Panel principal
("Respaldar base de datos"), sin necesidad de acceso al servidor. Queda
registrado en `auditoria` (accion `RESPALDO`); vease
[SECURITY.md](SECURITY.md) para el detalle de como se genera.

Alternativamente, desde la linea de comandos:

```bash
mysqldump -u sistema_juridico_app -p sistema_juridico_ec > backup_$(date +%Y%m%d).sql
```

Recomendado: automatizar con `cron`, retener al menos 30 dias, y probar la
restauracion periodicamente en un entorno aislado:

```bash
mysql -u root -p sistema_juridico_ec_restauracion < backup_20260101.sql
```

## 10. Actualizar la aplicacion de forma segura

1. Respalde la base de datos (paso 9).
2. `git pull` sobre un checkout limpio (o despliegue por artefacto).
3. `composer install --no-dev --optimize-autoloader` en produccion.
4. Aplique las migraciones nuevas en `database/migrations/` en orden,
   una sola vez cada una.
5. Ejecute `composer test` en un entorno de staging antes de promover a
   produccion.
6. Limpie cache de opcode (`opcache_reset()`) si aplica.

## Estructura del proyecto

```
app/
  Core/            Router, PDO, Sesion, CSRF, Vistas, manejo de errores
  Http/Controllers Controladores HTTP
  Http/Middleware  Autenticacion, autorizacion por rol, CSRF, cabeceras
  Services/        Logica de negocio y transacciones
  Repositories/     Acceso a datos (PDO + consultas preparadas)
  Validators/      Validacion del lado del servidor
  Views/           Plantillas PHP + Bootstrap 5
config/            Configuracion de app y base de datos
database/migrations Esquema versionado (base + migraciones aditivas)
public/            Unico directorio expuesto por el servidor web
routes/web.php     Definicion de rutas y middleware por rol
bin/create-admin.php  Creacion segura del primer administrador (CLI)
tests/Unit          Pruebas sin dependencia de base de datos
tests/Integration    Pruebas que requieren MariaDB/MySQL real
```

## Roles

| Rol              | Alcance                                                      |
|------------------|---------------------------------------------------------------|
| ADMINISTRADOR    | Usuarios, roles, catalogos, configuracion, todo lo juridico   |
| EDITOR_JURIDICO  | Crea y actualiza normas, publicaciones y articulos            |
| REVISOR          | Revisa/valida vigencia, consulta auditoria                    |
| CONSULTA         | Solo lectura (normas, articulos, busqueda)                    |

La autorizacion se verifica en el backend (`RoleMiddleware`), no solo en la
interfaz.

## Limitaciones conocidas y pendientes

- Las pruebas de integracion (incluidas las de seguridad) se ejecutaron
  contra una instancia real de MySQL 8.4 levantada en este entorno de
  desarrollo (puerto 3307, datos de prueba aislados de cualquier otra
  base de datos de este equipo). Si usted las ejecuta contra otra
  instancia, configure primero `.env.testing`.
- El hash de contrasenas usa `PASSWORD_DEFAULT` (bcrypt) en lugar de
  Argon2id explicito porque la extension `sodium` no esta disponible en
  este entorno; el codigo ya usa `password_needs_rehash()` para migrar
  automaticamente si en el futuro se cambia el algoritmo por defecto de PHP.
  Ver [SECURITY.md](SECURITY.md).
- No se implemento exportacion a PDF/Excel, importacion de documentos PDF,
  ni control de concurrencia optimista en la edicion de articulos (ver lista
  de pendientes en SECURITY.md, seccion "Recomendaciones no implementadas").
- El editor de contenido de articulos es un `<textarea>` de texto plano (no
  HTML enriquecido), para evitar el riesgo de XSS sin depender de una
  biblioteca de sanitizacion adicional. Si se requiere HTML enriquecido,
  integrar un sanitizador mantenido (p. ej. HTML Purifier) antes de
  habilitarlo.
