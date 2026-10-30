<?php use App\Core\View; ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href=".">Panel</a></li>
        <li class="breadcrumb-item"><a href="usuarios">Usuarios</a></li>
        <li class="breadcrumb-item active" aria-current="page">Nuevo usuario</li>
    </ol>
</nav>

<h1 class="h4 mb-3">Nuevo usuario</h1>

<?php if (!empty($errores)): ?>
    <div class="alert alert-danger" role="alert">
        <ul class="mb-0">
            <?php foreach ($errores as $campo => $mensajes): foreach ($mensajes as $mensaje): ?>
                <li><?= View::e($mensaje) ?></li>
            <?php endforeach; endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" action="usuarios" class="row g-3" novalidate>
    <?= $csrf_field ?>
    <div class="col-md-6">
        <label for="nombre" class="form-label">Nombre completo</label>
        <input type="text" class="form-control" id="nombre" name="nombre" maxlength="150" required
               value="<?= View::e($valores['nombre'] ?? '') ?>">
    </div>
    <div class="col-md-6">
        <label for="correo" class="form-label">Correo electronico</label>
        <input type="email" class="form-control" id="correo" name="correo" maxlength="190" required
               value="<?= View::e($valores['correo'] ?? '') ?>">
    </div>
    <div class="col-md-6">
        <label for="password" class="form-label">Contrasena inicial</label>
        <input type="password" class="form-control" id="password" name="password" minlength="8" required>
        <div class="form-text">Minimo 8 caracteres.</div>
    </div>
    <div class="col-md-6">
        <label class="form-label" id="roles-label">Roles</label>
        <div role="group" aria-labelledby="roles-label">
            <?php foreach ($roles as $rol): ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="roles[]" value="<?= (int) $rol['id'] ?>" id="rol<?= (int) $rol['id'] ?>">
                    <label class="form-check-label" for="rol<?= (int) $rol['id'] ?>"><?= View::e($rol['nombre']) ?></label>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="col-12">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Crear usuario</button>
        <a href="usuarios" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>
