<?php use App\Core\View; $totalPaginas = (int) ceil(max(1, $total) / $porPagina); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><i class="bi bi-journal-text"></i> Normas</h1>
    <a href="/normas/crear" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nueva norma</a>
</div>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">Panel</a></li>
        <li class="breadcrumb-item active" aria-current="page">Normas</li>
    </ol>
</nav>

<form method="get" action="/normas" class="row g-2 mb-3">
    <div class="col-md-4">
        <input type="text" name="q" class="form-control" placeholder="Titulo, numero o siglas" value="<?= View::e($filtros['texto']) ?>">
    </div>
    <div class="col-md-3">
        <select name="tipo_norma_id" class="form-select">
            <option value="">Todos los tipos</option>
            <?php foreach ($tipos as $tipo): ?>
                <option value="<?= (int) $tipo['id'] ?>" <?= (string) $filtros['tipo_norma_id'] === (string) $tipo['id'] ? 'selected' : '' ?>><?= View::e($tipo['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <select name="estado" class="form-select">
            <option value="">Todos los estados</option>
            <?php foreach (['BORRADOR','PUBLICADA','VIGENTE','DEROGADA','ARCHIVADA'] as $estadoOpt): ?>
                <option value="<?= $estadoOpt ?>" <?= $filtros['estado'] === $estadoOpt ? 'selected' : '' ?>><?= $estadoOpt ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <button class="btn btn-outline-secondary w-100" type="submit"><i class="bi bi-search"></i> Filtrar</button>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead><tr><th>Titulo</th><th>Tipo</th><th>Numero</th><th>Publicacion</th><th>Estado</th></tr></thead>
        <tbody>
        <?php foreach ($normas as $norma): ?>
            <tr>
                <td><a href="/normas/<?= (int) $norma['id'] ?>"><?= View::e($norma['titulo']) ?></a></td>
                <td><?= View::e($norma['tipo_norma_nombre']) ?></td>
                <td><?= View::e($norma['numero_norma'] ?? '-') ?></td>
                <td><?= View::e($norma['fecha_publicacion'] ?? '-') ?></td>
                <td><span class="badge bg-secondary"><?= View::e($norma['estado']) ?></span></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($normas === []): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">No se encontraron normas.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPaginas > 1): ?>
<nav aria-label="Paginacion de normas">
    <ul class="pagination">
        <?php for ($p = 1; $p <= $totalPaginas; $p++): ?>
            <li class="page-item <?= $p === $pagina ? 'active' : '' ?>">
                <a class="page-link" href="/normas?pagina=<?= $p ?>&q=<?= urlencode($filtros['texto']) ?>">
                    <?= $p ?>
                </a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
