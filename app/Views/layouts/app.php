<?php
use App\Core\Url;
use App\Core\View;
$usuario_actual = $usuario_actual ?? null;
?>
<!doctype html>
<html lang="es">
<head>
    <base href="<?= View::e(Url::basePath() . '/') ?>">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema Juridico Ecuador</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3">
    <a class="navbar-brand" href=".">
        <i class="bi bi-bank2"></i> Sistema Juridico Ecuador
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarMain">
        <ul class="navbar-nav me-auto"></ul>
        <?php if ($usuario_actual): ?>
            <span class="navbar-text text-light me-3">
                <i class="bi bi-person-circle"></i> <?= View::e($usuario_actual['nombre']) ?>
            </span>
            <form method="post" action="logout" class="d-inline">
                <?= $csrf_field ?? '' ?>
                <button type="submit" class="btn btn-outline-light btn-sm">Cerrar sesion</button>
            </form>
        <?php endif; ?>
    </div>
</nav>
<div class="d-flex">
    <?php if ($usuario_actual): ?>
    <nav class="sidebar bg-white border-end p-3" style="min-width: 230px;">
        <ul class="nav nav-pills flex-column gap-1">
            <li class="nav-item"><a class="nav-link" href="."><i class="bi bi-speedometer2"></i> Panel</a></li>
            <li class="nav-item"><a class="nav-link" href="normas"><i class="bi bi-journal-text"></i> Normas</a></li>
            <li class="nav-item"><a class="nav-link" href="busqueda"><i class="bi bi-search"></i> Busqueda juridica</a></li>
            <li class="nav-item"><a class="nav-link" href="tipos-norma"><i class="bi bi-tags"></i> Tipos de norma</a></li>
            <li class="nav-item"><a class="nav-link" href="usuarios"><i class="bi bi-people"></i> Usuarios</a></li>
            <li class="nav-item"><a class="nav-link" href="auditoria"><i class="bi bi-clipboard-data"></i> Auditoria</a></li>
        </ul>
    </nav>
    <?php endif; ?>
    <main class="flex-fill p-4">
        <?php if (!empty($flash_exito)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= View::e($flash_exito) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (!empty($flash_error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= View::e($flash_error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?= $content ?? '' ?>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
