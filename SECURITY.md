# Seguridad - Sistema Juridico Ecuador

## Controles implementados

### Autenticacion
- Contrasenas con `password_hash(PASSWORD_DEFAULT)` (bcrypt); nunca texto
  plano ni cifrado reversible. Verificacion con `password_verify()` y
  reemigracion automatica con `password_needs_rehash()`.
- Bloqueo temporal tras `LOGIN_MAX_INTENTOS` fallos (`.env`), por
  `LOGIN_BLOQUEO_MINUTOS`. Contador y expiracion en
  `usuarios.intentos_fallidos` / `usuarios.bloqueado_hasta`.
- Mensajes de error de login genericos ("Credenciales invalidas") para no
  revelar si un correo existe.

### Sesiones
- `session.use_strict_mode=1`, `use_only_cookies=1`, cookies `HttpOnly`,
  `SameSite=Lax`, `Secure` cuando `SESSION_SECURE_COOKIE=true` (activar en
  produccion con HTTPS).
- Regeneracion de ID de sesion en login (`session_regenerate_id(true)`).
- Expiracion por inactividad y duracion maxima absoluta, configurables via
  `.env` (`SESSION_LIFETIME_MINUTES`, `SESSION_ABSOLUTE_LIFETIME_MINUTES`).
- `CuentaActivaMiddleware` revoca la sesion si un administrador desactiva la
  cuenta mientras el usuario sigue conectado.

### CSRF
- Token aleatorio de 32 bytes (`random_bytes`) por sesion, comparado con
  `hash_equals()`. Verificado en todo `POST`/`PUT`/`PATCH`/`DELETE` via
  `CsrfMiddleware`. Se rota en cada login.

### Autorizacion
- Verificada en el backend (`RoleMiddleware::for([...])` por ruta), nunca
  solo ocultando botones en la vista.
- Principio de minimo privilegio: cuatro roles (`ADMINISTRADOR`,
  `EDITOR_JURIDICO`, `REVISOR`, `CONSULTA`) con permisos diferenciados por
  modulo en `routes/web.php`.

### Base de datos
- PDO con `ERRMODE_EXCEPTION`, `ATTR_EMULATE_PREPARES => false`.
- 100% de las consultas con datos variables usan sentencias preparadas con
  parametros nombrados (ningun valor de usuario se concatena en SQL).
- Ordenamientos dinamicos (ver `NormaRepository::buscarPaginado`,
  `BusquedaService`) usan listas blancas explicitas de columnas permitidas,
  nunca el valor crudo del usuario como identificador SQL.
- Transacciones (`PDO::beginTransaction/commit/rollBack`) para operaciones
  multi-tabla: alta de usuario + roles, norma + publicacion + articulos.

### XSS
- Toda salida HTML pasa por `View::e()` (`htmlspecialchars` con
  `ENT_QUOTES`, UTF-8).
- El contenido de articulos se trata como texto plano (`<textarea>`), no se
  renderiza como HTML; si en el futuro se habilita un editor enriquecido,
  **debe** sanitizarse en el servidor con una biblioteca mantenida (p. ej.
  HTML Purifier) y una lista blanca de etiquetas/atributos antes de este
  cambio.

### Cabeceras HTTP
`SecurityHeadersMiddleware` agrega en cada respuesta:
`X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`,
`Referrer-Policy: same-origin`, `Permissions-Policy` restrictiva, y una
`Content-Security-Policy` que solo permite scripts/estilos propios y de
`cdn.jsdelivr.net` (Bootstrap/Bootstrap Icons).

### IDOR
- Todo acceso a un recurso por `id` verifica su existencia en el repositorio
  antes de operar, y la autorizacion por rol se aplica antes de llegar al
  controlador. No hay endpoints que expongan datos de otro usuario sin pasar
  por `RoleMiddleware`.

### Logs
- `App\Core\Logger` redacta automaticamente claves como `password`,
  `password_hash`, `csrf_token`, `token`, `secret`, `authorization` antes de
  escribir al log (`storage/logs/app.log`, fuera de `public/`).
- Los errores no controlados se registran con detalle tecnico en el log,
  pero al usuario solo se le muestra un mensaje generico salvo que
  `APP_DEBUG=true` (solo para desarrollo).

### Archivos y despliegue
- `public/` es el unico directorio servido; `.htaccess` deniega acceso
  directo a `.env`, `.sql`, `.log`, `.md` y desactiva listado de
  directorios.
- `.env`, `storage/logs/*.log` y `vendor/` estan en `.gitignore`.
- El primer administrador se crea solo por CLI (`bin/create-admin.php`),
  que rechaza ejecutarse via web (`PHP_SAPI !== 'cli'`).

### Vigencia juridica (integridad de la informacion)
- `VigenciaService` nunca muestra "vigente" solo porque
  `normas.estado = 'VIGENTE'`: exige ademas fecha de vigencia alcanzada,
  ausencia de relaciones normativas de derogacion/sustitucion con fecha de
  efecto cumplida, y que un usuario con rol `REVISOR` haya marcado la norma
  como revisada (`normas.revisado_por` / `fecha_revision`). En cualquier
  otro caso se muestra explicitamente "Vigencia pendiente de verificacion".

## Pruebas de seguridad ejecutadas

**Se levanto una instancia real de MySQL 8.4 (compatible con MariaDB) y se
ejecuto la suite completa: `vendor/bin/phpunit` -> 42/42 pruebas OK, 63
assertions**, incluyendo `tests/Integration/SeguridadIntegrationTest.php`
(inyeccion SQL en login y en listados, XSS almacenado y verificacion de
escape de salida, manipulacion de identificadores inexistentes/no
numericos).

Ademas, se verificaron manualmente con el servidor embebido de PHP y una
base de datos real, con cookies de sesion reales (no simulado):

- Login real (cookie de sesion + CSRF) -> dashboard autenticado.
- Flujo completo: crear tipo de norma -> norma -> publicacion -> articulo
  -> vigencia "pendiente de verificacion" -> marcar revisada -> "Vigente
  (verificada)".
- Busqueda juridica `FULLTEXT` encuentra el articulo por contenido.
- Inyeccion SQL en login (`' OR '1'='1`) y en filtros de busqueda/normas
  (`UNION SELECT ...`, `DROP TABLE ...`): ninguna altero el resultado ni
  afecto las tablas; el texto se devuelve escapado en los formularios.
- XSS almacenado (`<script>`, `<img onerror=...>`) en titulo de norma y
  contenido de articulo: se guarda como texto plano y se muestra siempre
  escapado (`&lt;script&gt;`, `&lt;img ...`), nunca como HTML ejecutable.
- Manipulacion de identificadores: IDs inexistentes (`/normas/999999`) y
  no numericos (`/normas/abc`, payloads SQL en la URL) -> `303` graceful,
  sin error 500 y sin afectar otras tablas.
- `POST /login` sin `csrf_token` -> `419`.
- Rutas protegidas sin sesion -> `303` a `/login`.
- Usuario con rol `CONSULTA`: lectura permitida (`/normas`, `/busqueda` ->
  200), escritura denegada incluso enviando el `POST` directamente sin
  pasar por el boton de la interfaz (`/normas/crear` GET y `POST /normas`
  -> 403 en ambos casos, `/usuarios` -> 403).
- Acceso directo a archivos fuera de `public/` (`.env`, `composer.json`,
  `database/...`, `storage/logs/...`) -> `404` (el docroot del servidor
  nunca incluye esos directorios).
- Con `APP_DEBUG=false` y credenciales de base de datos rotas a proposito:
  el usuario recibe una pagina generica de error (`500`) sin rutas,
  contrasenas ni SQL visibles; el detalle tecnico completo queda en
  `storage/logs/app.log` (fuera de `public/`).
- Ruta inexistente -> `404` sin revelar informacion interna.

### Bugs reales encontrados y corregidos al probar contra una base de datos real

Estos no se habrian detectado solo con lectura de codigo o con pruebas
unitarias sin base de datos:

1. **`Session::start()` nunca se invocaba** en ningun punto del ciclo de
   vida de la peticion: el login generaba el token CSRF y parecia
   funcionar, pero la cookie de sesion jamas se enviaba al navegador.
   Corregido en `public/index.php`.
2. **Placeholders nombrados repetidos** en consultas preparadas con
   `PDO::ATTR_EMULATE_PREPARES => false` (MySQL/MariaDB no permite
   reutilizar el mismo `:nombre` dos veces en una consulta real): ocurria
   en el INSERT de articulos (`:creado_por` usado para dos columnas), en
   la busqueda juridica (`:texto_like` usado dos veces), en la busqueda de
   usuarios (`:busqueda` usado dos veces) y en el filtro de normas
   (`:texto` usado tres veces). Las cuatro producian un error 500 real al
   usarlas. Corregido con nombres de parametro distintos por aparicion.
3. **Repositorios conectaban a la base de datos en el constructor** aunque
   nunca llegaran a ejecutar una consulta, lo que rompia cualquier prueba
   que instanciara un servicio sin BD disponible. Se refactorizo a
   resolucion perezosa de la conexion.
4. **El formulario de creacion de usuarios rechazaba siempre la
   contrasena** ("La contrasena es obligatorio") aunque se enviara
   correctamente: el validador se construia con un array que nunca
   incluia el campo `password`. Esto habria impedido crear cualquier
   usuario desde la interfaz web. Corregido en `UsuarioController::crear`.

Estos hallazgos refuerzan por que el requerimiento pide no afirmar que las
pruebas "pasaron" sin ejecutarlas: los cuatro bugs eran invisibles en una
revision estatica del codigo y solo aparecieron al ejecutar peticiones
HTTP reales contra una base de datos real.

## Recomendaciones no implementadas (pendientes, priorizadas)

1. **Exportacion a PDF/Excel** de normas/articulos autorizados - no
   implementada en esta version.
2. **Importacion de documentos PDF** como referencia - no implementada.
3. **Control de concurrencia optimista** al editar articulos (dos editores
   podrian sobrescribirse; se mitiga parcialmente con el aviso de
   confirmacion antes de sustituir el texto vigente, pero no hay bloqueo
   ni verificacion de version).
4. **Revocacion activa de sesiones por un administrador** (hoy solo se
   revoca al desactivar la cuenta del propio usuario conectado, via
   `CuentaActivaMiddleware`, en la siguiente peticion de ese usuario).
5. **2FA** no solicitado por el requerimiento; no implementado.
6. **API externa** explicitamente deshabilitada para esta primera version,
   conforme al requerimiento.

## Reportar un problema de seguridad

Este es un proyecto academico/interno. Si encuentra una vulnerabilidad,
notifique directamente al responsable del repositorio antes de divulgarla
publicamente.
