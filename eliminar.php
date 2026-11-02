<?php
/**
 * Operación Eliminar: Baja Lógica o Eliminación Física con Confirmación
 */

$pageTitle = 'Eliminar Entrega';
require_once __DIR__ . '/config.php';

$db = get_db();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("
    SELECT ep.*, p.nombres || ' ' || p.apellidos AS nombre_paciente, p.numero_documento, tp.nombre_tipo
    FROM entrega_protesis ep
    JOIN paciente p ON p.id_paciente = ep.id_paciente
    JOIN tipo_protesis tp ON tp.id_tipo_protesis = ep.id_tipo_protesis
    WHERE ep.id_entrega = :id
");
$stmt->execute([':id' => $id]);
$entrega = $stmt->fetch();

if (!$entrega) {
    set_flash('danger', 'El registro de entrega no existe.');
    redirect('/index.php');
}

if (is_post()) {
    verify_csrf();
    $accion = $_POST['accion'] ?? '';

    try {
        if ($accion === 'baja_logica') {
            $stmtBaja = $db->prepare("UPDATE entrega_protesis SET estado = 'DADO_DE_BAJA' WHERE id_entrega = :id");
            $stmtBaja->execute([':id' => $id]);
            set_flash('success', "Entrega {$entrega['codigo_protesis']} dada de baja correctamente (baja lógica).");
        } elseif ($accion === 'eliminar_fisico') {
            $stmtDel = $db->prepare("DELETE FROM entrega_protesis WHERE id_entrega = :id");
            $stmtDel->execute([':id' => $id]);
            set_flash('success', "Entrega {$entrega['codigo_protesis']} eliminada definitivamente de la base de datos.");
        }
        redirect('/index.php');
    } catch (Throwable $t) {
        set_flash('danger', 'Error al procesar la eliminación: ' . $t->getMessage());
        redirect("/detalle.php?id={$id}");
    }
}

require_once __DIR__ . '/header.php';
?>

<div class="mb-4">
    <h4 class="fw-bold mb-1">Confirmar Eliminación de Entrega</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="/index.php">Inicio</a></li>
            <li class="breadcrumb-item"><a href="/detalle.php?id=<?= $id ?>"><?= e($entrega['codigo_protesis']) ?></a></li>
            <li class="breadcrumb-item active">Eliminar</li>
        </ol>
    </nav>
</div>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card border-danger shadow-sm">
            <div class="card-header bg-danger text-white py-3">
                <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill"></i> ¿Desea eliminar este registro?
                </h5>
            </div>
            <div class="card-body">
                <p class="text-secondary">
                    Está a punto de retirar del sistema el registro de entrega de prótesis con el código:
                </p>

                <div class="alert alert-light border p-3">
                    <div class="row g-2">
                        <div class="col-sm-5 text-muted small">Código de Prótesis:</div>
                        <div class="col-sm-7 fw-bold font-monospace text-primary"><?= e($entrega['codigo_protesis']) ?></div>

                        <div class="col-sm-5 text-muted small">Paciente:</div>
                        <div class="col-sm-7 fw-semibold"><?= e($entrega['nombre_paciente']) ?> (<?= e($entrega['numero_documento']) ?>)</div>

                        <div class="col-sm-5 text-muted small">Tipo:</div>
                        <div class="col-sm-7"><?= e($entrega['nombre_tipo']) ?></div>

                        <div class="col-sm-5 text-muted small">Estado Actual:</div>
                        <div class="col-sm-7"><?= badge_status($entrega['estado']) ?></div>
                    </div>
                </div>

                <form method="POST">
                    <?= csrf_field() ?>

                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" name="accion" value="baja_logica" class="btn btn-warning text-dark fw-semibold py-2">
                            <i class="bi bi-archive me-1"></i> Dar de Baja Lógica (Recomendado)
                        </button>
                        <button type="submit" name="accion" value="eliminar_fisico" class="btn btn-outline-danger py-2" onclick="return confirm('¿Está absolutamente seguro de borrar permanentemente este registro de PostgreSQL?');">
                            <i class="bi bi-trash me-1"></i> Eliminar Definitivamente (DELETE en BD)
                        </button>
                        <a href="/detalle.php?id=<?= $id ?>" class="btn btn-outline-secondary py-2">
                            Cancelar y Volver
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>

