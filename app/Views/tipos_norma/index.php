<?php use App\Core\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><i class="bi bi-tags"></i> Tipos de norma</h1>
    <a href="/tipos-norma/crear" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nuevo tipo</a>
</div>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">Panel</a></li>
        <li class="breadcrumb-item active" aria-current="page">Tipos de norma</li>
    </ol>
</nav>
<div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead><tr><th>Codigo</th><th>Nombre</th><th>Descripcion</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($tipos as $tipo): ?>
            <tr>
                <td><code><?= View::e($tipo['codigo']) ?></code></td>
                <td><?= View::e($tipo['nombre']) ?></td>
                <td><?= View::e($tipo['descripcion'] ?? '') ?></td>
                <td>
                    <?php if ((int) $tipo['estado'] === 1): ?>
                        <span class="badge badge-vigente">Activo</span>
                    <?php else: ?>
                        <span class="badge badge-borrador">Inactivo</span>
                    <?php endif; ?>
                </td>
                <td class="text-end">
                    <a href="/tipos-norma/<?= (int) $tipo['id'] ?>/editar" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                    <form method="post" action="/tipos-norma/<?= (int) $tipo['id'] ?>/estado" class="d-inline"
                          data-confirm="¿Cambiar el estado de este tipo de norma?">
                        <?= $csrf_field ?>
                        <input type="hidden" name="activo" value="<?= (int) $tipo['estado'] === 1 ? '0' : '1' ?>">
                        <button type="submit" class="btn btn-sm <?= (int) $tipo['estado'] === 1 ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                            <?= (int) $tipo['estado'] === 1 ? 'Desactivar' : 'Activar' ?>
                        </button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
