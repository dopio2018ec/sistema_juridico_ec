<?php use App\Core\View; $totalPaginas = (int) ceil(max(1, $total) / $porPagina); ?>
<h1 class="h4 mb-3"><i class="bi bi-search"></i> Busqueda juridica</h1>

<form method="get" action="busqueda" class="row g-2 mb-4">
    <div class="col-md-4">
        <label class="form-label">Texto o palabras clave</label>
        <input type="text" name="texto" class="form-control" value="<?= View::e($filtros['texto']) ?>">
    </div>
    <div class="col-md-4">
        <label class="form-label">Titulo de la norma</label>
        <input type="text" name="titulo_norma" class="form-control" value="<?= View::e($filtros['titulo_norma']) ?>">
    </div>
    <div class="col-md-4">
        <label class="form-label">Tipo de norma</label>
        <select name="tipo_norma_id" class="form-select">
            <option value="">Todos</option>
            <?php foreach ($tipos as $tipo): ?>
                <option value="<?= (int) $tipo['id'] ?>" <?= (string) $filtros['tipo_norma_id'] === (string) $tipo['id'] ? 'selected' : '' ?>><?= View::e($tipo['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Numero de norma</label>
        <input type="text" name="numero_norma" class="form-control" value="<?= View::e($filtros['numero_norma']) ?>">
    </div>
    <div class="col-md-3">
        <label class="form-label">Numero de articulo</label>
        <input type="text" name="articulo_numero" class="form-control" value="<?= View::e($filtros['articulo_numero']) ?>">
    </div>
    <div class="col-md-3">
        <label class="form-label">Entidad emisora</label>
        <input type="text" name="entidad_emisora" class="form-control" value="<?= View::e($filtros['entidad_emisora']) ?>">
    </div>
    <div class="col-md-3">
        <label class="form-label">Fecha de publicacion</label>
        <input type="date" name="fecha_publicacion" class="form-control" value="<?= View::e($filtros['fecha_publicacion']) ?>">
    </div>
    <div class="col-12">
        <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Buscar</button>
    </div>
</form>

<?php if ($hayFiltros): ?>
    <p class="text-muted"><?= (int) $total ?> resultado(s) encontrado(s).</p>
    <div class="list-group mb-3">
        <?php foreach ($resultados as $fila): ?>
            <a href="normas/<?= (int) $fila['norma_id'] ?>" class="list-group-item list-group-item-action">
                <div class="d-flex justify-content-between">
                    <strong><?= View::e($fila['norma_titulo']) ?></strong>
                    <span class="badge bg-secondary">Art. <?= View::e($fila['articulo_numero']) ?></span>
                </div>
                <div class="small text-muted"><?= View::e(strip_tags((string) $fila['fragmento'])) ?>...</div>
            </a>
        <?php endforeach; ?>
        <?php if ($resultados === []): ?>
            <div class="list-group-item text-muted">No se encontraron resultados para los filtros indicados.</div>
        <?php endif; ?>
    </div>

    <?php if ($totalPaginas > 1): ?>
    <nav aria-label="Paginacion de resultados">
        <ul class="pagination">
            <?php for ($p = 1; $p <= $totalPaginas; $p++): ?>
                <li class="page-item <?= $p === $pagina ? 'active' : '' ?>">
                    <a class="page-link" href="busqueda?pagina=<?= $p ?>&texto=<?= urlencode($filtros['texto']) ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
<?php else: ?>
    <p class="text-muted">Ingrese al menos un criterio de busqueda.</p>
<?php endif; ?>
