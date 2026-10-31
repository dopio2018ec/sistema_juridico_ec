# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Descripcion del proyecto

Sistema Juridico Ecuador: aplicacion web para registrar, organizar, consultar
y actualizar normas juridicas ecuatorianas y sus articulos. PHP 8.3+ **sin
framework** (arquitectura por capas propia), MariaDB/MySQL via PDO puro,
Bootstrap 5 (CDN) para la interfaz, PHPUnit 11 para pruebas.

## Comandos

```bash
composer install              # instalar dependencias (solo autoload PSR-4 + PHPUnit, sin deps de produccion)
composer test                 # == vendor/bin/phpunit (ejecuta tests/Unit y tests/Integration)
vendor/bin/phpunit tests/Unit/ValidatorTest.php                       # un archivo
vendor/bin/phpunit --filter testNombreDelMetodo                       # un test puntual
php -S 127.0.0.1:8000 -t public                                       # servidor embebido de desarrollo
php bin/create-admin.php "Nombre Completo" admin@dominio.ec "ContrasenaSegura_12+"  # crear el primer admin (solo CLI)
```

- `tests/Unit`: sin dependencia de base de datos (Validator, CSRF, hashing, ramas puras de `VigenciaService`).
- `tests/Integration`: requieren MariaDB/MySQL real configurada en `.env.testing`; si no hay conexion, se omiten con `markTestSkipped()` (ver `tests/Integration/DatabaseTestCase.php`) en lugar de pasar falsamente.
- Las migraciones de esquema viven en `database/migrations/`, numeradas y aditivas; se aplican en orden con `mysql -u root -p ... < database/migrations/000N_*.sql`.

## Arquitectura

Sin framework: capas propias bajo el namespace `App\`.

```
app/Core/            Router, Request/Response, PDO (Database), Sesion, CSRF, Auth, View, ErrorHandler, Logger
app/Http/Controllers Controladores HTTP (uno por recurso, extienden App\Core\Controller)
app/Http/Middleware  AuthMiddleware, RoleMiddleware, CsrfMiddleware, CuentaActivaMiddleware, SecurityHeadersMiddleware
app/Services/        Logica de negocio, validacion de reglas y transacciones multi-tabla
app/Repositories/    Unico lugar que toca PDO; consultas preparadas con parametros nombrados
app/Validators/      Validacion de entrada del lado del servidor
app/Views/           Plantillas PHP puras (sin motor de templates) + Bootstrap 5
routes/web.php       Unica fuente de verdad de rutas, middleware y permisos por rol
config/              app.php (debug, timezone, etc.) y database.php
bin/create-admin.php Unica forma de crear el primer ADMINISTRADOR (rechaza ejecucion fuera de CLI)
```

Flujo de una peticion: `public/index.php` -> `Router::dispatch()` resuelve la
ruta en `routes/web.php`, construye el pipeline de middleware (en orden
inverso, estilo "onion") y finalmente instancia el Controller y llama a la
accion. Los Controllers solo orquestan: llaman a un Service o Repository,
nunca contienen SQL ni logica de negocio compleja.

Convenciones por capa (seguirlas al anadir codigo nuevo):
- **Controller**: construye datos desde el `Request`, delega validacion/logica a un Service, usa `$this->view()`, `$this->redirectWithSuccess()/redirectWithError()` o `$this->json()` de `App\Core\Controller`. Verifica siempre que un recurso por `id` exista antes de operar (evita IDOR).
- **Service**: contiene reglas de negocio y abre transacciones PDO (`beginTransaction/commit/rollBack`) cuando una operacion toca varias tablas (p. ej. `NormaService::crearConPublicacionYArticulos`, alta de usuario + roles).
- **Repository**: unico lugar con SQL. Siempre sentencias preparadas con parametros **nombrados** y distintos por cada aparicion (MySQL con `ATTR_EMULATE_PREPARES => false` no permite repetir el mismo `:nombre` dos veces en la misma consulta). La conexion a BD se resuelve de forma perezosa (no en el constructor), para que los Services/Repositories sean instanciables sin BD disponible en tests unitarios. Ordenamientos/columnas dinamicas usan listas blancas explicitas, nunca el valor crudo del usuario como identificador SQL.
- **Rutas y roles**: todo endpoint protegido se declara en `routes/web.php` combinando middleware (`AuthMiddleware`, `CuentaActivaMiddleware`, `RoleMiddleware::for([...])`, `CsrfMiddleware` en todo POST/PUT/PATCH/DELETE). La autorizacion se verifica siempre en backend, nunca solo ocultando elementos de la vista.
- **Vistas**: toda salida de datos de usuario pasa por `View::e()` (`htmlspecialchars` con `ENT_QUOTES`). El contenido de articulos es texto plano (`<textarea>`), nunca se renderiza como HTML.

### Roles (`RoleMiddleware::for([...])` en `routes/web.php`)

| Rol              | Alcance                                                    |
|------------------|-------------------------------------------------------------|
| ADMINISTRADOR    | Usuarios, roles, catalogos, configuracion, todo lo juridico |
| EDITOR_JURIDICO  | Crea y actualiza normas, publicaciones y articulos          |
| REVISOR          | Revisa/valida vigencia, consulta auditoria                  |
| CONSULTA         | Solo lectura (normas, articulos, busqueda)                  |

### Vigencia juridica

`VigenciaService` nunca considera una norma "vigente" solo porque
`normas.estado = 'VIGENTE'`: exige ademas que la fecha de vigencia se haya
alcanzado, que no existan relaciones de derogacion/sustitucion con fecha de
efecto cumplida, y que un usuario `REVISOR` la haya marcado como revisada
(`normas.revisado_por` / `fecha_revision`). En cualquier otro caso se
muestra "Vigencia pendiente de verificacion".

## Seguridad (ver SECURITY.md para el detalle completo)

- Contrasenas con `password_hash(PASSWORD_DEFAULT)` + `password_needs_rehash()`; nunca texto plano.
- CSRF: token de 32 bytes por sesion, comparado con `hash_equals()`, verificado por `CsrfMiddleware` en toda mutacion; se rota en cada login.
- PDO siempre con `ERRMODE_EXCEPTION` y `ATTR_EMULATE_PREPARES => false`.
- `App\Core\Logger` redacta automaticamente claves sensibles (`password`, `token`, `secret`, `authorization`, etc.) antes de escribir en `storage/logs/app.log`.
- El respaldo de base de datos (`App\Services\BackupService`) se genera solo via PDO (sin invocar `mysqldump`), restringido a `ADMINISTRADOR`, y queda registrado en la tabla `auditoria`.
- Nunca commitear `.env`; usar `.env.example` como referencia y `.env.testing` para la base de datos de pruebas de integracion.

## Pendientes conocidos (no reimplementar sin discutirlo)

- Sin exportacion a PDF/Excel, sin importacion de PDFs, sin control de concurrencia optimista al editar articulos, sin revocacion activa de sesiones por un admin, sin 2FA, sin API externa (ver SECURITY.md, "Recomendaciones no implementadas").
