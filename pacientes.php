<?php
/**
 * Catálogo de Soporte: Consulta y Registro Rápido de Pacientes
 * Asignatura: Bases de Datos
 */

$pageTitle = 'Catálogo de Pacientes';
require_once __DIR__ . '/config.php';

$db = get_db();
$errors = [];

// Procesar alta rápida de paciente
if (is_post() && isset($_POST['crear_paciente'])) {
    verify_csrf();

    $doc  = strtoupper(trim($_POST['numero_documento'] ?? ''));
    $nom  = trim($_POST['nombres'] ?? '');
    $ape  = trim($_POST['apellidos'] ?? '');
    $tel  = trim($_POST['telefono'] ?? '');
    $mail = trim($_POST['correo'] ?? '');

    if ($doc === '') $errors[] = 'El documento de identidad es obligatorio.';
    if ($nom === '') $errors[] = 'El nombre es obligatorio.';
    if ($ape === '') $errors[] = 'El apellido es obligatorio.';

    if (empty($errors)) {
        try {
            $stmt = $db->prepare("
                INSERT INTO paciente (numero_documento, nombres, apellidos, telefono, correo)
                VALUES (:doc, :nom, :ape, :tel, :mail)
            ");
            $stmt->execute([
                ':doc'  => $doc,
                ':nom'  => $nom,
                ':ape'  => $ape,
                ':tel'  => $tel ?: null,
                ':mail' => $mail ?: null,
            ]);
            set_flash('success', "Paciente {$nom} {$ape} registrado con éxito.");
            redirect('/pacientes.php');
        } catch (PDOException $e) {
            if ($e->getCode() === '23505') {
                $errors[] = "El documento '{$doc}' ya se encuentra registrado.";
            } else {
                $errors[] = 'Error al registrar: ' . $e->getMessage();
            }
        }
    }
}

// Procesar eliminación con prueba de Integridad Referencial
if (is_post() && isset($_POST['eliminar_paciente'])) {
    verify_csrf();
    $idPac = (int)($_POST['id_paciente'] ?? 0);

    try {
        $stmtDel = $db->prepare("DELETE FROM paciente WHERE id_paciente = :id");
        $stmtDel->execute([':id' => $idPac]);
        set_flash('success', "Paciente eliminado correctamente.");
        redirect('/pacientes.php');
    } catch (PDOException $e) {
        if ($e->getCode() === '23503') {
            set_flash('danger', "PostgreSQL bloqueó la eliminación (Integridad Referencial): El paciente tiene prótesis asociadas.");
        } else {
            set_flash('danger', 'Error al eliminar: ' . $e->getMessage());
        }
        redirect('/pacientes.php');
    }
}

// Listar pacientes con conteo de entregas asociadas
$pacientes = $db->query("
    SELECT p.*,
           (SELECT COUNT(*) FROM entrega_protesis ep WHERE ep.id_paciente = p.id_paciente) AS total_entregas
    FROM paciente p
    ORDER BY p.apellidos, p.nombres
")->fetchAll();

require_once __DIR__ . '/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">Catálogo de Pacientes</h4>
        <p class="text-muted mb-0 small">Pacientes receptores de prótesis &bull; Integridad referencial en PostgreSQL</p>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoPaciente">
        <i class="bi bi-person-plus me-1"></i> Registrar Paciente
    </button>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger mb-4">
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header bg-white py-3">
        <h6 class="fw-bold mb-0 text-dark">
            <i class="bi bi-people me-2 text-primary"></i>Listado de Pacientes
            <span class="badge bg-light text-secondary border ms-2"><?= count($pacientes) ?> registrados</span>
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Documento</th>
                    <th>Nombre Completo</th>
                    <th>Teléfono</th>
                    <th>Correo</th>
                    <th>Prótesis Asignadas</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pacientes)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No hay pacientes registrados.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pacientes as $p): ?>
                        <tr>
                            <td class="font-monospace fw-bold"><?= e($p['numero_documento']) ?></td>
                            <td><?= e($p['nombres'] . ' ' . $p['apellidos']) ?></td>
                            <td><?= e($p['telefono'] ?: '-') ?></td>
                            <td><?= e($p['correo'] ?: '-') ?></td>
                            <td>
                                <?php if ($p['total_entregas'] > 0): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        <?= (int)$p['total_entregas'] ?> entrega(s)
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border">Sin entregas</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <form method="POST" class="d-inline" onsubmit="return confirm('¿Desea eliminar a este paciente? Si tiene prótesis, PostgreSQL rechazará la acción.');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id_paciente" value="<?= (int)$p['id_paciente'] ?>">
                                    <button type="submit" name="eliminar_paciente" value="1" class="btn btn-outline-danger btn-sm" title="Eliminar Paciente">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal para Registrar Nuevo Paciente -->
<div class="modal fade" id="modalNuevoPaciente" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="crear_paciente" value="1">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalLabel"><i class="bi bi-person-plus me-2 text-primary"></i>Registrar Paciente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="numero_documento" class="form-label fw-semibold">Número de Documento / Cédula <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="numero_documento" name="numero_documento" required placeholder="Ej: V-12345678">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="nombres" class="form-label fw-semibold">Nombres <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nombres" name="nombres" required>
                        </div>
                        <div class="col-6">
                            <label for="apellidos" class="form-label fw-semibold">Apellidos <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="apellidos" name="apellidos" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="telefono" class="form-label fw-semibold">Teléfono</label>
                        <input type="text" class="form-control" id="telefono" name="telefono" placeholder="Ej: 0414-1234567">
                    </div>
                    <div class="mb-3">
                        <label for="correo" class="form-label fw-semibold">Correo Electrónico</label>
                        <input type="email" class="form-control" id="correo" name="correo" placeholder="ejemplo@correo.com">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Paciente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>

