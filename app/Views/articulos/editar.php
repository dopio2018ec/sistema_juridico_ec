<?php use App\Core\View; ?>
<h1 class="h4 mb-3">Editar articulo / disposicion</h1>
<?php if (!empty($errores)): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errores as $m): foreach ($m as $msg): ?><li><?= View::e($msg) ?></li><?php endforeach; endforeach; ?></ul></div>
<?php endif; ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle"></i>
    Al guardar, el texto vigente anterior sera sustituido y no estara disponible en el sistema. Este esquema
    no conserva historial de versiones.
</div>
<form method="post" action="/articulos/<?= (int) $articulo['id'] ?>" class="row g-3"
      data-confirm="¿Confirma reemplazar el texto vigente de este articulo? El texto anterior no quedara disponible.">
    <?= $csrf_field ?>
    <div class="col-md-3">
        <label for="articulo_numero" class="form-label">Numero</label>
        <input type="text" class="form-control" id="articulo_numero" name="articulo_numero" maxlength="50" required value="<?= View::e($articulo['articulo_numero']) ?>">
    </div>
    <div class="col-md-4">
        <label for="tipo_contenido" class="form-label">Tipo de contenido</label>
        <select class="form-select" id="tipo_contenido" name="tipo_contenido">
            <?php foreach (['CONSIDERANDO','ARTICULO','DISPOSICION_GENERAL','DISPOSICION_TRANSITORIA','DISPOSICION_REFORMATORIA','DISPOSICION_DEROGATORIA','DISPOSICION_FINAL','OTRO'] as $tipoContenido): ?>
                <option value="<?= $tipoContenido ?>" <?= $articulo['tipo_contenido'] === $tipoContenido ? 'selected' : '' ?>><?= $tipoContenido ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-5">
        <label for="titulo" class="form-label">Titulo (opcional)</label>
        <input type="text" class="form-control" id="titulo" name="titulo" maxlength="500" value="<?= View::e($articulo['titulo'] ?? '') ?>">
    </div>
    <div class="col-md-2">
        <label for="orden" class="form-label">Orden</label>
        <input type="number" class="form-control" id="orden" name="orden" min="0" value="<?= (int) $articulo['orden'] ?>">
    </div>
    <div class="col-12">
        <label for="contenido" class="form-label">Contenido vigente</label>
        <textarea class="form-control" id="contenido" name="contenido" rows="10" required><?= View::e($articulo['contenido']) ?></textarea>
    </div>
    <div class="col-12">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar texto vigente</button>
        <a href="/normas/<?= (int) $articulo['norma_id'] ?>" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>
