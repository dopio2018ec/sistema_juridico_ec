<?php use App\Core\View; use App\Core\Auth; ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href=".">Panel</a></li>
        <li class="breadcrumb-item"><a href="normas">Normas</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= View::e($norma['titulo']) ?></li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h1 class="h4 mb-1"><?= View::e($norma['titulo']) ?></h1>
        <div class="text-muted"><?= View::e($norma['tipo_norma_nombre']) ?> <?= $norma['numero_norma'] ? '- N.° ' . View::e($norma['numero_norma']) : '' ?></div>
    </div>
    <div class="text-end">
        <span class="badge <?= $vigencia['clase_css'] ?>"><?= View::e($vigencia['etiqueta']) ?></span>
        <div class="mt-2">
            <a href="normas/<?= (int) $norma['id'] ?>/editar" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Editar</a>
            <?php if (Auth::hasAnyRole(['ADMINISTRADOR', 'REVISOR']) && !$vigencia['verificada']): ?>
            <form method="post" action="normas/<?= (int) $norma['id'] ?>/revisar" class="d-inline"
                  data-confirm="¿Confirma que verificó la vigencia de esta norma con fuentes oficiales?">
                <?= $csrf_field ?>
                <button type="submit" class="btn btn-sm btn-outline-success"><i class="bi bi-patch-check"></i> Marcar revisada</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!$vigencia['verificada']): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i>
        La vigencia de esta norma esta <strong>pendiente de verificacion</strong>. No se debe asumir que esta vigente
        unicamente por su estado administrativo.
    </div>
<?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-md-3"><strong>Siglas:</strong> <?= View::e($norma['siglas'] ?? '-') ?></div>
    <div class="col-md-5"><strong>Entidad emisora:</strong> <?= View::e($norma['entidad_emisora'] ?? '-') ?></div>
    <div class="col-md-4"><strong>Estado administrativo:</strong> <?= View::e($norma['estado']) ?></div>
    <div class="col-md-3"><strong>Fecha de emision:</strong> <?= View::e($norma['fecha_emision'] ?? '-') ?></div>
    <div class="col-md-3"><strong>Fecha de publicacion:</strong> <?= View::e($norma['fecha_publicacion'] ?? '-') ?></div>
    <div class="col-md-3"><strong>Fecha de vigencia:</strong> <?= View::e($norma['fecha_vigencia'] ?? '-') ?></div>
    <div class="col-md-3"><strong>Fecha de derogatoria:</strong> <?= View::e($norma['fecha_derogatoria'] ?? '-') ?></div>
    <?php if (!empty($norma['observaciones'])): ?>
        <div class="col-12"><strong>Observaciones:</strong> <?= nl2br(View::e($norma['observaciones'])) ?></div>
    <?php endif; ?>
</div>

<ul class="nav nav-tabs" id="tabsNorma" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-articulos" type="button">Articulos y disposiciones</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-publicaciones" type="button">Publicaciones oficiales</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-relaciones" type="button">Relaciones normativas</button></li>
</ul>
<div class="tab-content border border-top-0 p-3">

    <div class="tab-pane fade show active" id="tab-articulos">
        <div class="table-responsive mb-3">
            <table class="table table-sm align-middle">
                <thead><tr><th>N.°</th><th>Tipo</th><th>Titulo</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                <tbody>
                <?php foreach ($articulos as $articulo): ?>
                    <tr>
                        <td><?= View::e($articulo['articulo_numero']) ?></td>
                        <td><?= View::e($articulo['tipo_contenido']) ?></td>
                        <td><?= View::e($articulo['titulo'] ?? '-') ?></td>
                        <td>
                            <?php if ((int) $articulo['estado'] === 1): ?>
                                <span class="badge badge-vigente">Activo</span>
                            <?php else: ?>
                                <span class="badge badge-borrador">Desactivado</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="articulos/<?= (int) $articulo['id'] ?>/editar" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form method="post" action="articulos/<?= (int) $articulo['id'] ?>/estado" class="d-inline"
                                  data-confirm="¿Cambiar el estado de este articulo?">
                                <?= $csrf_field ?>
                                <input type="hidden" name="activo" value="<?= (int) $articulo['estado'] === 1 ? '0' : '1' ?>">
                                <button type="submit" class="btn btn-sm <?= (int) $articulo['estado'] === 1 ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                                    <?= (int) $articulo['estado'] === 1 ? 'Desactivar' : 'Activar' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($articulos === []): ?>
                    <tr><td colspan="5" class="text-center text-muted py-3">Sin articulos registrados.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <details>
            <summary class="mb-2">Agregar articulo o disposicion</summary>
            <form method="post" action="normas/<?= (int) $norma['id'] ?>/articulos" class="row g-2">
                <?= $csrf_field ?>
                <div class="col-md-2">
                    <label class="form-label">Numero</label>
                    <input type="text" name="articulo_numero" class="form-control" maxlength="50" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tipo</label>
                    <select name="tipo_contenido" class="form-select">
                        <?php foreach (['CONSIDERANDO','ARTICULO','DISPOSICION_GENERAL','DISPOSICION_TRANSITORIA','DISPOSICION_REFORMATORIA','DISPOSICION_DEROGATORIA','DISPOSICION_FINAL','OTRO'] as $tipoContenido): ?>
                            <option value="<?= $tipoContenido ?>"><?= $tipoContenido ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Titulo (opcional)</label>
                    <input type="text" name="titulo" class="form-control" maxlength="500">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Orden</label>
                    <input type="number" name="orden" class="form-control" value="0" min="0">
                </div>
                <div class="col-12">
                    <label class="form-label">Contenido vigente</label>
                    <textarea name="contenido" class="form-control" rows="4" required></textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-sm">Agregar</button>
                </div>
            </form>
        </details>
    </div>

    <div class="tab-pane fade" id="tab-publicaciones">
        <div class="table-responsive mb-3">
            <table class="table table-sm align-middle">
                <thead><tr><th>Publicacion</th><th>N.°</th><th>Fecha</th><th>Fuente</th></tr></thead>
                <tbody>
                <?php foreach ($publicaciones as $publicacion): ?>
                    <tr>
                        <td><?= View::e($publicacion['nombre_publicacion']) ?></td>
                        <td><?= View::e($publicacion['numero'] ?? '-') ?><?= $publicacion['suplemento'] ? ' (Supl. ' . View::e($publicacion['suplemento']) . ')' : '' ?></td>
                        <td><?= View::e($publicacion['fecha_publicacion'] ?? '-') ?></td>
                        <td>
                            <?php if (!empty($publicacion['url_fuente'])): ?>
                                <a href="<?= View::e($publicacion['url_fuente']) ?>" target="_blank" rel="noopener noreferrer">
                                    <i class="bi bi-box-arrow-up-right"></i> Ver fuente
                                </a>
                            <?php else: ?>
                                <span class="text-muted">Sin URL</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($publicaciones === []): ?>
                    <tr><td colspan="4" class="text-center text-muted py-3">Sin publicaciones registradas.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <details>
            <summary class="mb-2">Agregar publicacion oficial</summary>
            <form method="post" action="normas/<?= (int) $norma['id'] ?>/publicaciones" class="row g-2">
                <?= $csrf_field ?>
                <div class="col-md-4">
                    <label class="form-label">Nombre de la publicacion</label>
                    <input type="text" name="nombre_publicacion" class="form-control" value="Registro Oficial">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Numero</label>
                    <input type="text" name="numero" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Suplemento</label>
                    <input type="text" name="suplemento" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Fecha</label>
                    <input type="date" name="fecha_publicacion" class="form-control">
                </div>
                <div class="col-md-1">
                    <label class="form-label">Pag. ini.</label>
                    <input type="text" name="pagina_inicial" class="form-control">
                </div>
                <div class="col-md-1">
                    <label class="form-label">Pag. fin.</label>
                    <input type="text" name="pagina_final" class="form-control">
                </div>
                <div class="col-md-8">
                    <label class="form-label">URL de la fuente oficial</label>
                    <input type="url" name="url_fuente" class="form-control" placeholder="https://...">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-sm">Agregar</button>
                </div>
            </form>
        </details>
    </div>

    <div class="tab-pane fade" id="tab-relaciones">
        <h3 class="h6">Esta norma reforma/deroga/sustituye a:</h3>
        <ul class="list-group mb-3">
            <?php foreach ($relacionesOrigen as $relacion): ?>
                <li class="list-group-item">
                    <span class="badge bg-secondary"><?= View::e($relacion['tipo_relacion']) ?></span>
                    <a href="normas/<?= (int) $relacion['norma_destino_id'] ?>"><?= View::e($relacion['norma_destino_titulo']) ?></a>
                    <?php if ($relacion['fecha_efecto']): ?> (vigor: <?= View::e($relacion['fecha_efecto']) ?>)<?php endif; ?>
                </li>
            <?php endforeach; ?>
            <?php if ($relacionesOrigen === []): ?><li class="list-group-item text-muted">Sin registros.</li><?php endif; ?>
        </ul>

        <h3 class="h6">Esta norma fue afectada por:</h3>
        <ul class="list-group mb-3">
            <?php foreach ($relacionesDestino as $relacion): ?>
                <li class="list-group-item">
                    <span class="badge bg-secondary"><?= View::e($relacion['tipo_relacion']) ?></span>
                    <a href="normas/<?= (int) $relacion['norma_origen_id'] ?>"><?= View::e($relacion['norma_origen_titulo']) ?></a>
                    <?php if ($relacion['fecha_efecto']): ?> (vigor: <?= View::e($relacion['fecha_efecto']) ?>)<?php endif; ?>
                </li>
            <?php endforeach; ?>
            <?php if ($relacionesDestino === []): ?><li class="list-group-item text-muted">Sin registros.</li><?php endif; ?>
        </ul>

        <details>
            <summary class="mb-2">Registrar relacion normativa</summary>
            <form method="post" action="normas/<?= (int) $norma['id'] ?>/relaciones" class="row g-2">
                <?= $csrf_field ?>
                <div class="col-md-5">
                    <label class="form-label">Norma afectada</label>
                    <select name="norma_destino_id" class="form-select" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($normasParaRelacionar as $otraNorma): ?>
                            <option value="<?= (int) $otraNorma['id'] ?>"><?= View::e($otraNorma['titulo']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tipo de relacion</label>
                    <select name="tipo_relacion" class="form-select">
                        <?php foreach (['REFORMA','DEROGACION_TOTAL','DEROGACION_PARCIAL','SUSTITUCION','ACLARACION','OTRA'] as $tipoRelacion): ?>
                            <option value="<?= $tipoRelacion ?>"><?= $tipoRelacion ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Fecha de efecto</label>
                    <input type="date" name="fecha_efecto" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label">Descripcion</label>
                    <textarea name="descripcion" class="form-control" rows="2"></textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-sm">Registrar relacion</button>
                </div>
            </form>
        </details>
    </div>
</div>
