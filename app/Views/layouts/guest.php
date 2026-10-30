<?php use App\Core\Url; use App\Core\View; ?>
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
<body class="bg-light">
    <main class="container py-5">
        <?= $content ?? '' ?>
    </main>
</body>
</html>
