<?php
/**
 * Módulo Principal: Consulta y Listado de Entregas de Prótesis
 */

$pageTitle = 'Listado de Entregas';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/header.php';

$db = get_db();

$search = trim($_GET['q'] ?? '');
$filterEstado = trim($_GET['estado'] ?? '');

// Métricas de resumen rápido
$totalEntregas = 0;
$totalEntregadas = 0;
$totalEnFabricacion = 0;
$totalEnPruebas = 0;

try {
    $totalEntregas = (int)$db->query("SELECT COUNT(*) FROM entrega_protesis")->fetchColumn();
    $totalEntregadas = (int)$db->query("SELECT COUNT(*) FROM entrega_protesis WHERE estado = 'ENTREGADO'")->fetchColumn();
    $totalEnFabricacion = (int)$db->query("SELECT COUNT(*) FROM entrega_protesis WHERE estado = 'EN_FABRICACION'")->fetchColumn();
    $totalEnPruebas = (int)$db->query("SELECT COUNT(*) FROM entrega_protesis WHERE estado = 'EN_PRUEBAS'")->fetchColumn();
} catch (Throwable $t) {}

// Consulta principal con INNER JOIN conectando las 3 tablas
$sql = "
    SELECT ep.id_entrega, ep.codigo_protesis, ep.fecha_entrega, ep.estado, ep.observaciones,
           p.id_paciente, p.numero_documento, p.nombres || ' ' || p.apellidos AS nombre_paciente, p.telefono,
           tp.nombre_tipo
    FROM entrega_protesis ep
    JOIN paciente p ON p.id_paciente = ep.id_paciente
    JOIN tipo_protesis tp ON tp.id_tipo_protesis = ep.id_tipo_protesis
    WHERE 1=1
";
$params = [];

if ($search !== '') {
    $sql .= " AND (ep.codigo_protesis ILIKE :q OR p.numero_documento ILIKE :q OR p.nombres ILIKE :q OR p.apellidos ILIKE :q) ";
    $params[':q'] = "%{$search}%";
}

if ($filterEstado !== '') {
    $sql .= " AND ep.estado = :est ";
    $params[':est'] = $filterEstado;
}

$sql .= " ORDER BY ep.fecha_entrega DESC, ep.id_entrega DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$entregas = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">Gestión de Entregas de Prótesis</h4>
        <p class="text-muted mb-0 small">Control y seguimiento de prótesis adaptadas a pacientes</p>
    </div>
    <a href="/crear.php" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Registrar Nueva Entrega
    </a>
</div>

<!-- Tarjetas de Métricas -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card border-start border-4 border-primary h-100">
            <div class="card-body">
                <span class="text-muted small text-uppercase fw-bold">Total Entregas</span>
                <h3 class="fw-bold mb-0 text-primary mt-1"><?= $totalEntregas ?></h3>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-start border-4 border-success h-100">
            <div class="card-body">
                <span class="text-muted small text-uppercase fw-bold">Entregadas</span>
                <h3 class="fw-bold mb-0 text-success mt-1"><?= $totalEntregadas ?></h3>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-start border-4 border-warning h-100">
            <div class="card-body">
                <span class="text-muted small text-uppercase fw-bold">En Pruebas</span>
                <h3 class="fw-bold mb-0 text-warning mt-1"><?= $totalEnPruebas ?></h3>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-start border-4 border-info h-100">
            <div class="card-body">
                <span class="text-muted small text-uppercase fw-bold">En Fabricación</span>
                <h3 class="fw-bold mb-0 text-info mt-1"><?= $totalEnFabricacion ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Barra de Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-7">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Buscar por código de prótesis, documento o nombre del paciente..." value="<?= e($search) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="estado" class="form-select">
                    <option value="">Todos los estados</option>
                    <option value="ENTREGADO" <?= $filterEstado === 'ENTREGADO' ? 'selected' : '' ?>>Entregado</option>
                    <option value="EN_PRUEBAS" <?= $filterEstado === 'EN_PRUEBAS' ? 'selected' : '' ?>>En Pruebas</option>
                    <option value="EN_FABRICACION" <?= $filterEstado === 'EN_FABRICACION' ? 'selected' : '' ?>>En Fabricación</option>
                    <option value="DADO_DE_BAJA" <?= $filterEstado === 'DADO_DE_BAJA' ? 'selected' : '' ?>>Dado de Baja</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary w-100">Filtrar</button>
                <?php if ($search !== '' || $filterEstado !== ''): ?>
                    <a href="/index.php" class="btn btn-outline-secondary" title="Limpiar"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de Entregas -->
<div class="card">
    <div class="card-header bg-white py-3">
        <h6 class="fw-bold mb-0 text-dark">
            <i class="bi bi-table me-2 text-primary"></i>Registros de Entregas
            <span class="badge bg-light text-secondary border ms-2"><?= count($entregas) ?> registros</span>
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Código Prótesis</th>
                    <th>Paciente Receptor</th>
                    <th>Tipo de Prótesis</th>
                    <th>Fecha Entrega</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($entregas)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            No se encontraron registros de entregas.
                            <?php if ($search !== '' || $filterEstado !== ''): ?>
                                <br><a href="/index.php" class="btn btn-sm btn-outline-primary mt-2">Limpiar filtros</a>
                            <?php else: ?>
                                <br><a href="/crear.php" class="btn btn-sm btn-primary mt-2">Registrar primera entrega</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($entregas as $item): ?>
                        <tr>
                            <td>
                                <a href="/detalle.php?id=<?= (int)$item['id_entrega'] ?>" class="fw-bold font-monospace text-decoration-none">
                                    <?= e($item['codigo_protesis']) ?>
                                </a>
                            </td>
                            <td>
                                <div class="fw-semibold"><?= e($item['nombre_paciente']) ?></div>
                                <small class="text-muted"><?= e($item['numero_documento']) ?></small>
                            </td>
                            <td><?= e($item['nombre_tipo']) ?></td>
                            <td><?= format_date($item['fecha_entrega']) ?></td>
                            <td><?= badge_status($item['estado']) ?></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="/detalle.php?id=<?= (int)$item['id_entrega'] ?>" class="btn btn-outline-secondary" title="Consultar Detalle">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="/editar.php?id=<?= (int)$item['id_entrega'] ?>" class="btn btn-outline-primary" title="Modificar Entrega">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="/eliminar.php?id=<?= (int)$item['id_entrega'] ?>" class="btn btn-outline-danger" title="Eliminar Registro">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
