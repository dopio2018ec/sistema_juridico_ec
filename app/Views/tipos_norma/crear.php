<?php use App\Core\View; ?>
<h1 class="h4 mb-3">Nuevo tipo de norma</h1>
<?php if (!empty($errores)): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errores as $m): foreach ($m as $msg): ?><li><?= View::e($msg) ?></li><?php endforeach; endforeach; ?></ul></div>
<?php endif; ?>
<form method="post" action="/tipos-norma" class="row g-3">
    <?= $csrf_field ?>
    <div class="col-md-4">
        <label for="codigo" class="form-label">Codigo</label>
        <input type="text" class="form-control" id="codigo" name="codigo" maxlength="30" required value="<?= View::e($valores['codigo'] ?? '') ?>">
    </div>
    <div class="col-md-8">
        <label for="nombre" class="form-label">Nombre</label>
        <input type="text" class="form-control" id="nombre" name="nombre" maxlength="100" required value="<?= View::e($valores['nombre'] ?? '') ?>">
    </div>
    <div class="col-12">
        <label for="descripcion" class="form-label">Descripcion</label>
        <textarea class="form-control" id="descripcion" name="descripcion" maxlength="255"><?= View::e($valores['descripcion'] ?? '') ?></textarea>
    </div>
    <div class="col-12">
        <button type="submit" class="btn btn-primary">Crear</button>
        <a href="/tipos-norma" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>
