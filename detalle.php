<?php
/**
 * Operación Consultar Detalle: Ficha de la Entrega de Prótesis
 */

$pageTitle = 'Detalle de Entrega';
require_once __DIR__ . '/config.php';

$db = get_db();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("
    SELECT ep.*,
           p.id_paciente, p.numero_documento, p.nombres || ' ' || p.apellidos AS nombre_paciente,
           p.telefono, p.correo,
           tp.nombre_tipo, tp.descripcion AS descripcion_tipo
    FROM entrega_protesis ep
    JOIN paciente p ON p.id_paciente = ep.id_paciente
    JOIN tipo_protesis tp ON tp.id_tipo_protesis = ep.id_tipo_protesis
    WHERE ep.id_entrega = :id
");
$stmt->execute([':id' => $id]);
$entrega = $stmt->fetch();

if (!$entrega) {
    set_flash('danger', 'El registro de entrega solicitado no existe.');
    redirect('/index.php');
}

require_once __DIR__ . '/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">Entrega: <?= e($entrega['codigo_protesis']) ?></h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="/index.php">Inicio</a></li>
                <li class="breadcrumb-item active"><?= e($entrega['codigo_protesis']) ?></li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="/editar.php?id=<?= $id ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-pencil me-1"></i> Modificar Entrega
        </a>
        <a href="/eliminar.php?id=<?= $id ?>" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-trash me-1"></i> Eliminar Registro
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Datos de la Prótesis -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-box-seam me-2 text-primary"></i>Datos del Dispositivo</h6>
                <?= badge_status($entrega['estado']) ?>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Código de Prótesis:</span>
                        <span class="fw-bold font-monospace fs-5 text-primary"><?= e($entrega['codigo_protesis']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Tipo de Prótesis:</span>
                        <span class="fw-semibold"><?= e($entrega['nombre_tipo']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Fecha de Entrega:</span>
                        <span><?= format_date($entrega['fecha_entrega']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Fecha de Registro en BD:</span>
                        <span class="small text-muted"><?= e($entrega['fecha_registro']) ?></span>
                    </li>
                </ul>
                <?php if (!empty($entrega['descripcion_tipo'])): ?>
                    <div class="mt-3 p-3 bg-light rounded small text-muted">
                        <strong>Descripción técnica del tipo:</strong><br>
                        <?= e($entrega['descripcion_tipo']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Datos del Paciente Receptor -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-person me-2 text-primary"></i>Paciente Receptor</h6>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Nombre Completo:</span>
                        <span class="fw-bold"><?= e($entrega['nombre_paciente']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Cédula / Documento:</span>
                        <span class="font-monospace"><?= e($entrega['numero_documento']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Teléfono de Contacto:</span>
                        <span><?= e($entrega['telefono'] ?: 'No registrado') ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Correo Electrónico:</span>
                        <span><?= e($entrega['correo'] ?: 'No registrado') ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Observaciones Clínicas -->
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-chat-left-text me-2 text-primary"></i>Observaciones Clínicas y Técnicas</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($entrega['observaciones'])): ?>
                    <p class="mb-0 text-secondary" style="white-space: pre-line;"><?= e($entrega['observaciones']) ?></p>
                <?php else: ?>
                    <span class="text-muted fst-italic">Sin observaciones registradas para esta entrega.</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between">
    <a href="/index.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Volver al Listado
    </a>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>

