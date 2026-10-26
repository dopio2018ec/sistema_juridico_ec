<?php use App\Core\View; ?>
<h1 class="h4 mb-3">Nueva norma</h1>
<?php if (!empty($errores)): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errores as $m): foreach ($m as $msg): ?><li><?= View::e($msg) ?></li><?php endforeach; endforeach; ?></ul></div>
<?php endif; ?>
<form method="post" action="/normas" class="row g-3" data-confirm="¿Registrar esta norma?">
    <?= $csrf_field ?>
    <div class="col-md-4">
        <label for="tipo_norma_id" class="form-label">Tipo de norma</label>
        <select class="form-select" id="tipo_norma_id" name="tipo_norma_id" required>
            <option value="">Seleccione...</option>
            <?php foreach ($tipos as $tipo): ?>
                <option value="<?= (int) $tipo['id'] ?>" <?= (string) ($valores['tipo_norma_id'] ?? '') === (string) $tipo['id'] ? 'selected' : '' ?>><?= View::e($tipo['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-8">
        <label for="titulo" class="form-label">Titulo completo</label>
        <input type="text" class="form-control" id="titulo" name="titulo" maxlength="500" required value="<?= View::e($valores['titulo'] ?? '') ?>">
    </div>
    <div class="col-md-3">
        <label for="numero_norma" class="form-label">Numero / codigo</label>
        <input type="text" class="form-control" id="numero_norma" name="numero_norma" maxlength="100" value="<?= View::e($valores['numero_norma'] ?? '') ?>">
    </div>
    <div class="col-md-3">
        <label for="siglas" class="form-label">Siglas</label>
        <input type="text" class="form-control" id="siglas" name="siglas" maxlength="100" value="<?= View::e($valores['siglas'] ?? '') ?>">
    </div>
    <div class="col-md-6">
        <label for="entidad_emisora" class="form-label">Entidad emisora</label>
        <input type="text" class="form-control" id="entidad_emisora" name="entidad_emisora" maxlength="255" value="<?= View::e($valores['entidad_emisora'] ?? '') ?>">
    </div>
    <div class="col-md-3">
        <label for="fecha_emision" class="form-label">Fecha de emision</label>
        <input type="date" class="form-control" id="fecha_emision" name="fecha_emision" value="<?= View::e($valores['fecha_emision'] ?? '') ?>">
    </div>
    <div class="col-md-3">
        <label for="fecha_publicacion" class="form-label">Fecha de publicacion</label>
        <input type="date" class="form-control" id="fecha_publicacion" name="fecha_publicacion" value="<?= View::e($valores['fecha_publicacion'] ?? '') ?>">
    </div>
    <div class="col-md-3">
        <label for="fecha_vigencia" class="form-label">Fecha de vigencia</label>
        <input type="date" class="form-control" id="fecha_vigencia" name="fecha_vigencia" value="<?= View::e($valores['fecha_vigencia'] ?? '') ?>">
    </div>
    <div class="col-md-3">
        <label for="fecha_derogatoria" class="form-label">Fecha de derogatoria</label>
        <input type="date" class="form-control" id="fecha_derogatoria" name="fecha_derogatoria" value="<?= View::e($valores['fecha_derogatoria'] ?? '') ?>">
    </div>
    <div class="col-md-4">
        <label for="estado" class="form-label">Estado administrativo</label>
        <select class="form-select" id="estado" name="estado">
            <?php foreach (['BORRADOR','PUBLICADA','VIGENTE','DEROGADA','ARCHIVADA'] as $estadoOpt): ?>
                <option value="<?= $estadoOpt ?>" <?= ($valores['estado'] ?? 'BORRADOR') === $estadoOpt ? 'selected' : '' ?>><?= $estadoOpt ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-12">
        <label for="observaciones" class="form-label">Observaciones</label>
        <textarea class="form-control" id="observaciones" name="observaciones"><?= View::e($valores['observaciones'] ?? '') ?></textarea>
    </div>

    <div class="col-12"><hr><h2 class="h6">Primera publicacion oficial (opcional)</h2></div>
    <div class="col-md-4">
        <label for="publicacion_numero" class="form-label">Numero Registro Oficial</label>
        <input type="text" class="form-control" id="publicacion_numero" name="publicacion_numero">
    </div>
    <div class="col-md-3">
        <label for="publicacion_suplemento" class="form-label">Suplemento</label>
        <input type="text" class="form-control" id="publicacion_suplemento" name="publicacion_suplemento">
    </div>
    <div class="col-md-3">
        <label for="publicacion_fecha" class="form-label">Fecha de publicacion</label>
        <input type="date" class="form-control" id="publicacion_fecha" name="publicacion_fecha">
    </div>
    <div class="col-md-2">
        <label for="publicacion_pagina_inicial" class="form-label">Pagina</label>
        <input type="text" class="form-control" id="publicacion_pagina_inicial" name="publicacion_pagina_inicial">
    </div>
    <div class="col-md-8">
        <label for="publicacion_url" class="form-label">URL de la fuente oficial</label>
        <input type="url" class="form-control" id="publicacion_url" name="publicacion_url" placeholder="https://www.registroficial.gob.ec/...">
    </div>

    <div class="col-12">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Registrar norma</button>
        <a href="/normas" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>
