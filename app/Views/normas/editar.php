<?php use App\Core\View; ?>
<h1 class="h4 mb-3">Editar norma</h1>
<?php if (!empty($errores)): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errores as $m): foreach ($m as $msg): ?><li><?= View::e($msg) ?></li><?php endforeach; endforeach; ?></ul></div>
<?php endif; ?>
<form method="post" action="/normas/<?= (int) $norma['id'] ?>" class="row g-3" data-confirm="¿Guardar los cambios de esta norma?">
    <?= $csrf_field ?>
    <div class="col-md-4">
        <label for="tipo_norma_id" class="form-label">Tipo de norma</label>
        <select class="form-select" id="tipo_norma_id" name="tipo_norma_id" required>
            <?php foreach ($tipos as $tipo): ?>
                <option value="<?= (int) $tipo['id'] ?>" <?= (string) $norma['tipo_norma_id'] === (string) $tipo['id'] ? 'selected' : '' ?>><?= View::e($tipo['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-8">
        <label for="titulo" class="form-label">Titulo completo</label>
        <input type="text" class="form-control" id="titulo" name="titulo" maxlength="500" required value="<?= View::e($norma['titulo']) ?>">
    </div>
    <div class="col-md-3">
        <label for="numero_norma" class="form-label">Numero / codigo</label>
        <input type="text" class="form-control" id="numero_norma" name="numero_norma" maxlength="100" value="<?= View::e($norma['numero_norma'] ?? '') ?>">
    </div>
    <div class="col-md-3">
        <label for="siglas" class="form-label">Siglas</label>
        <input type="text" class="form-control" id="siglas" name="siglas" maxlength="100" value="<?= View::e($norma['siglas'] ?? '') ?>">
    </div>
    <div class="col-md-6">
        <label for="entidad_emisora" class="form-label">Entidad emisora</label>
        <input type="text" class="form-control" id="entidad_emisora" name="entidad_emisora" maxlength="255" value="<?= View::e($norma['entidad_emisora'] ?? '') ?>">
    </div>
    <div class="col-md-3">
        <label for="fecha_emision" class="form-label">Fecha de emision</label>
        <input type="date" class="form-control" id="fecha_emision" name="fecha_emision" value="<?= View::e($norma['fecha_emision'] ?? '') ?>">
    </div>
    <div class="col-md-3">
        <label for="fecha_publicacion" class="form-label">Fecha de publicacion</label>
        <input type="date" class="form-control" id="fecha_publicacion" name="fecha_publicacion" value="<?= View::e($norma['fecha_publicacion'] ?? '') ?>">
    </div>
    <div class="col-md-3">
        <label for="fecha_vigencia" class="form-label">Fecha de vigencia</label>
        <input type="date" class="form-control" id="fecha_vigencia" name="fecha_vigencia" value="<?= View::e($norma['fecha_vigencia'] ?? '') ?>">
    </div>
    <div class="col-md-3">
        <label for="fecha_derogatoria" class="form-label">Fecha de derogatoria</label>
        <input type="date" class="form-control" id="fecha_derogatoria" name="fecha_derogatoria" value="<?= View::e($norma['fecha_derogatoria'] ?? '') ?>">
    </div>
    <div class="col-md-4">
        <label for="estado" class="form-label">Estado administrativo</label>
        <select class="form-select" id="estado" name="estado">
            <?php foreach (['BORRADOR','PUBLICADA','VIGENTE','DEROGADA','ARCHIVADA'] as $estadoOpt): ?>
                <option value="<?= $estadoOpt ?>" <?= $norma['estado'] === $estadoOpt ? 'selected' : '' ?>><?= $estadoOpt ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-12">
        <label for="observaciones" class="form-label">Observaciones</label>
        <textarea class="form-control" id="observaciones" name="observaciones"><?= View::e($norma['observaciones'] ?? '') ?></textarea>
    </div>
    <div class="col-12">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar cambios</button>
        <a href="/normas/<?= (int) $norma['id'] ?>" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>
