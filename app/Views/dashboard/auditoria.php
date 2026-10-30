<?php use App\Core\View; $totalPaginas = (int) ceil(max(1, $total) / $porPagina); ?>
<h1 class="h4 mb-3"><i class="bi bi-clipboard-data"></i> Auditoria</h1>

<div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead>
        <tr>
            <th>Fecha</th>
            <th>Usuario</th>
            <th>Tabla</th>
            <th>Registro</th>
            <th>Accion</th>
            <th>Detalle</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($eventos as $evento): ?>
            <tr>
                <td><?= View::e($evento['fecha_evento']) ?></td>
                <td><?= View::e($evento['usuario_nombre'] ?? 'Sistema') ?></td>
                <td><?= View::e($evento['tabla_afectada']) ?></td>
                <td><?= (int) $evento['registro_id'] ?></td>
                <td><span class="badge bg-secondary"><?= View::e($evento['accion']) ?></span></td>
                <td><code class="small"><?= View::e($evento['detalle'] ?? '') ?></code></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($eventos === []): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Sin eventos registrados.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPaginas > 1): ?>
<nav aria-label="Paginacion de auditoria">
    <ul class="pagination">
        <?php for ($p = 1; $p <= $totalPaginas; $p++): ?>
            <li class="page-item <?= $p === $pagina ? 'active' : '' ?>">
                <a class="page-link" href="auditoria?pagina=<?= $p ?>"><?= $p ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
