<?php use App\Core\View; $totalPaginas = (int) ceil(max(1, $total) / $porPagina); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><i class="bi bi-people"></i> Usuarios</h1>
    <a href="/usuarios/crear" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nuevo usuario</a>
</div>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">Panel</a></li>
        <li class="breadcrumb-item active" aria-current="page">Usuarios</li>
    </ol>
</nav>

<form method="get" action="/usuarios" class="row g-2 mb-3">
    <div class="col-md-5">
        <label class="visually-hidden" for="q">Buscar</label>
        <input type="text" id="q" name="q" class="form-control" placeholder="Buscar por nombre o correo" value="<?= View::e($busqueda) ?>">
    </div>
    <div class="col-md-3">
        <label class="visually-hidden" for="estado">Estado</label>
        <select id="estado" name="estado" class="form-select">
            <option value="">Todos los estados</option>
            <option value="1" <?= $estado === '1' ? 'selected' : '' ?>>Activo</option>
            <option value="0" <?= $estado === '0' ? 'selected' : '' ?>>Inactivo</option>
        </select>
    </div>
    <div class="col-md-2">
        <button class="btn btn-outline-secondary w-100" type="submit"><i class="bi bi-search"></i> Filtrar</button>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead>
        <tr>
            <th>Nombre</th>
            <th>Correo</th>
            <th>Estado</th>
            <th>Ultimo acceso</th>
            <th class="text-end">Acciones</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($usuarios === []): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">No se encontraron usuarios.</td></tr>
        <?php endif; ?>
        <?php foreach ($usuarios as $usuario): ?>
            <tr>
                <td><?= View::e($usuario['nombre']) ?></td>
                <td><?= View::e($usuario['correo']) ?></td>
                <td>
                    <?php if ((int) $usuario['estado'] === 1): ?>
                        <span class="badge badge-vigente">Activo</span>
                    <?php else: ?>
                        <span class="badge badge-borrador">Inactivo</span>
                    <?php endif; ?>
                </td>
                <td><?= View::e($usuario['ultimo_acceso'] ?? 'Nunca') ?></td>
                <td class="text-end">
                    <a href="/usuarios/<?= (int) $usuario['id'] ?>/editar" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-pencil"></i> Editar
                    </a>
                    <form method="post" action="/usuarios/<?= (int) $usuario['id'] ?>/estado" class="d-inline"
                          data-confirm="<?= (int) $usuario['estado'] === 1 ? '¿Desactivar este usuario?' : '¿Activar este usuario?' ?>">
                        <?= $csrf_field ?>
                        <input type="hidden" name="activo" value="<?= (int) $usuario['estado'] === 1 ? '0' : '1' ?>">
                        <button type="submit" class="btn btn-sm <?= (int) $usuario['estado'] === 1 ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                            <?= (int) $usuario['estado'] === 1 ? 'Desactivar' : 'Activar' ?>
                        </button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPaginas > 1): ?>
<nav aria-label="Paginacion de usuarios">
    <ul class="pagination">
        <?php for ($p = 1; $p <= $totalPaginas; $p++): ?>
            <li class="page-item <?= $p === $pagina ? 'active' : '' ?>">
                <a class="page-link" href="/usuarios?pagina=<?= $p ?>&q=<?= urlencode($busqueda) ?>&estado=<?= urlencode($estado) ?>"><?= $p ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
