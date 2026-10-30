<?php

declare(strict_types=1);

use App\Core\Router;
use App\Http\Controllers\ArticuloController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BusquedaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NormaController;
use App\Http\Controllers\PublicacionController;
use App\Http\Controllers\RelacionController;
use App\Http\Controllers\RespaldoController;
use App\Http\Controllers\TipoNormaController;
use App\Http\Controllers\UsuarioController;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\CsrfMiddleware;
use App\Http\Middleware\CuentaActivaMiddleware;
use App\Http\Middleware\RoleMiddleware;

return function (Router $router): void {
    // Autenticacion (publico)
    $router->get('/login', [AuthController::class, 'mostrarLogin']);
    $router->post('/login', [AuthController::class, 'login'], [CsrfMiddleware::class]);
    $router->post('/logout', [AuthController::class, 'logout'], [AuthMiddleware::class, CsrfMiddleware::class]);

    $autenticado = [AuthMiddleware::class, CuentaActivaMiddleware::class];
    $soloAdmin = array_merge($autenticado, [RoleMiddleware::for(['ADMINISTRADOR'])]);
    $escrituraJuridica = array_merge($autenticado, [RoleMiddleware::for(['ADMINISTRADOR', 'EDITOR_JURIDICO'])]);
    $lecturaJuridica = array_merge($autenticado, [RoleMiddleware::for(['ADMINISTRADOR', 'EDITOR_JURIDICO', 'REVISOR', 'CONSULTA'])]);

    // Panel principal
    $router->get('/', [DashboardController::class, 'index'], $autenticado);

    // Respaldo de la base de datos (solo administrador)
    $router->post('/respaldos', [RespaldoController::class, 'generar'], array_merge($soloAdmin, [CsrfMiddleware::class]));

    // Usuarios (solo administrador)
    $router->get('/usuarios', [UsuarioController::class, 'index'], $soloAdmin);
    $router->get('/usuarios/crear', [UsuarioController::class, 'crearFormulario'], $soloAdmin);
    $router->post('/usuarios', [UsuarioController::class, 'crear'], array_merge($soloAdmin, [CsrfMiddleware::class]));
    $router->get('/usuarios/{id}/editar', [UsuarioController::class, 'editarFormulario'], $soloAdmin);
    $router->post('/usuarios/{id}', [UsuarioController::class, 'actualizar'], array_merge($soloAdmin, [CsrfMiddleware::class]));
    $router->post('/usuarios/{id}/estado', [UsuarioController::class, 'cambiarEstado'], array_merge($soloAdmin, [CsrfMiddleware::class]));
    $router->post('/usuarios/{id}/password', [UsuarioController::class, 'restablecerPassword'], array_merge($soloAdmin, [CsrfMiddleware::class]));

    // Auditoria (administrador y revisor)
    $router->get('/auditoria', [AuditoriaController::class, 'index'], array_merge($autenticado, [RoleMiddleware::for(['ADMINISTRADOR', 'REVISOR'])]));

    // Tipos de norma (solo administrador)
    $router->get('/tipos-norma', [TipoNormaController::class, 'index'], $soloAdmin);
    $router->get('/tipos-norma/crear', [TipoNormaController::class, 'crearFormulario'], $soloAdmin);
    $router->post('/tipos-norma', [TipoNormaController::class, 'crear'], array_merge($soloAdmin, [CsrfMiddleware::class]));
    $router->get('/tipos-norma/{id}/editar', [TipoNormaController::class, 'editarFormulario'], $soloAdmin);
    $router->post('/tipos-norma/{id}', [TipoNormaController::class, 'actualizar'], array_merge($soloAdmin, [CsrfMiddleware::class]));
    $router->post('/tipos-norma/{id}/estado', [TipoNormaController::class, 'cambiarEstado'], array_merge($soloAdmin, [CsrfMiddleware::class]));

    // Busqueda juridica (lectura, todos los roles autenticados)
    $router->get('/busqueda', [BusquedaController::class, 'index'], $lecturaJuridica);

    // Normas (lectura amplia, escritura solo administrador/editor juridico)
    $router->get('/normas', [NormaController::class, 'index'], $lecturaJuridica);
    $router->get('/normas/crear', [NormaController::class, 'crearFormulario'], $escrituraJuridica);
    $router->post('/normas', [NormaController::class, 'crear'], array_merge($escrituraJuridica, [CsrfMiddleware::class]));
    $router->get('/normas/{id}', [NormaController::class, 'mostrar'], $lecturaJuridica);
    $router->get('/normas/{id}/editar', [NormaController::class, 'editarFormulario'], $escrituraJuridica);
    $router->post('/normas/{id}', [NormaController::class, 'actualizar'], array_merge($escrituraJuridica, [CsrfMiddleware::class]));
    $router->post(
        '/normas/{id}/revisar',
        [NormaController::class, 'marcarRevisada'],
        array_merge($autenticado, [RoleMiddleware::for(['ADMINISTRADOR', 'REVISOR']), CsrfMiddleware::class])
    );

    // Articulos y disposiciones
    $router->post('/normas/{norma_id}/articulos', [ArticuloController::class, 'crear'], array_merge($escrituraJuridica, [CsrfMiddleware::class]));
    $router->get('/articulos/{id}/editar', [ArticuloController::class, 'editarFormulario'], $escrituraJuridica);
    $router->post('/articulos/{id}', [ArticuloController::class, 'actualizar'], array_merge($escrituraJuridica, [CsrfMiddleware::class]));
    $router->post('/articulos/{id}/estado', [ArticuloController::class, 'cambiarEstado'], array_merge($escrituraJuridica, [CsrfMiddleware::class]));

    // Publicaciones oficiales
    $router->post('/normas/{norma_id}/publicaciones', [PublicacionController::class, 'crear'], array_merge($escrituraJuridica, [CsrfMiddleware::class]));

    // Relaciones normativas
    $router->post('/normas/{norma_id}/relaciones', [RelacionController::class, 'crear'], array_merge($escrituraJuridica, [CsrfMiddleware::class]));
};
