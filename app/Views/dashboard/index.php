<?php use App\Core\Auth; use App\Core\View; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0"><i class="bi bi-speedometer2"></i> Panel principal</h1>
    <?php if (Auth::hasAnyRole(['ADMINISTRADOR'])): ?>
        <form method="post" action="respaldos" data-confirm="¿Generar y descargar un respaldo completo de la base de datos?">
            <?= $csrf_field ?? '' ?>
            <button type="submit" class="btn btn-outline-primary">
                <i class="bi bi-database-down"></i> Respaldar base de datos
            </button>
        </form>
    <?php endif; ?>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card text-bg-primary">
            <div class="card-body">
                <div class="small">Normas registradas</div>
                <div class="display-6"><?= (int) $totalNormas ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-info">
            <div class="card-body">
                <div class="small">Articulos activos</div>
                <div class="display-6"><?= (int) $totalArticulos ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Normas por tipo</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($normasPorTipo as $fila): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <?= View::e($fila['nombre']) ?>
                        <span class="badge bg-secondary"><?= (int) $fila['total'] ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Normas por estado</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($normasPorEstado as $fila): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <?= View::e($fila['estado']) ?>
                        <span class="badge bg-secondary"><?= (int) $fila['total'] ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Ultimas normas registradas</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($ultimasNormas as $norma): ?>
                    <li class="list-group-item">
                        <a href="normas/<?= (int) $norma['id'] ?>"><?= View::e($norma['titulo']) ?></a>
                        <div class="small text-muted"><?= View::e($norma['fecha_creacion']) ?></div>
                    </li>
                <?php endforeach; ?>
                <?php if ($ultimasNormas === []): ?>
                    <li class="list-group-item text-muted">Sin registros.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Ultimos articulos actualizados</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($ultimosArticulos as $articulo): ?>
                    <li class="list-group-item">
                        <a href="normas/<?= (int) $articulo['norma_id'] ?>">
                            <?= View::e($articulo['norma_titulo']) ?> - Art. <?= View::e($articulo['articulo_numero']) ?>
                        </a>
                        <div class="small text-muted"><?= View::e($articulo['fecha_actualizacion']) ?></div>
                    </li>
                <?php endforeach; ?>
                <?php if ($ultimosArticulos === []): ?>
                    <li class="list-group-item text-muted">Sin registros.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>
