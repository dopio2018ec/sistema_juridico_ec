<?php use App\Core\View; ?>
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3 text-center"><i class="bi bi-bank2"></i> Sistema Juridico Ecuador</h1>
                <?php if (!empty($flash_error)): ?>
                    <div class="alert alert-danger" role="alert"><?= View::e($flash_error) ?></div>
                <?php endif; ?>
                <?php if (!empty($flash_exito)): ?>
                    <div class="alert alert-success" role="alert"><?= View::e($flash_exito) ?></div>
                <?php endif; ?>
                <form method="post" action="/login" novalidate>
                    <?= $csrf_field ?>
                    <div class="mb-3">
                        <label for="correo" class="form-label">Correo electronico</label>
                        <input type="email" class="form-control" id="correo" name="correo" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Contrasena</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-box-arrow-in-right"></i> Ingresar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
