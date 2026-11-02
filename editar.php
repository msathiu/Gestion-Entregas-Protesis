<?php
/**
 * Operación Actualizar: Modificar Entrega de Prótesis Existente
 */

$pageTitle = 'Modificar Entrega';
require_once __DIR__ . '/config.php';

$db = get_db();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM entrega_protesis WHERE id_entrega = :id");
$stmt->execute([':id' => $id]);
$entrega = $stmt->fetch();

if (!$entrega) {
    set_flash('danger', 'El registro de entrega solicitado no existe.');
    redirect('/index.php');
}

$pacientes = $db->query("SELECT id_paciente, numero_documento, nombres || ' ' || apellidos AS nombre_completo FROM paciente ORDER BY apellidos, nombres")->fetchAll();
$tipos = $db->query("SELECT id_tipo_protesis, nombre_tipo FROM tipo_protesis ORDER BY nombre_tipo")->fetchAll();

$errors = [];

if (is_post()) {
    verify_csrf();

    $codigo_protesis  = strtoupper(trim($_POST['codigo_protesis'] ?? ''));
    $id_paciente      = (int)($_POST['id_paciente'] ?? 0);
    $id_tipo_protesis = (int)($_POST['id_tipo_protesis'] ?? 0);
    $fecha_entrega    = trim($_POST['fecha_entrega'] ?? '');
    $estado           = trim($_POST['estado'] ?? 'ENTREGADO');
    $observaciones    = trim($_POST['observaciones'] ?? '');

    if ($codigo_protesis === '') $errors[] = 'El código de la prótesis es obligatorio.';
    if (!$id_paciente) $errors[] = 'Debe seleccionar un paciente receptor.';
    if (!$id_tipo_protesis) $errors[] = 'Debe seleccionar el tipo de prótesis.';
    if ($fecha_entrega === '') $errors[] = 'La fecha de entrega es obligatoria.';

    // Validar unicidad de código excluyendo el registro actual
    if (empty($errors)) {
        $chk = $db->prepare("SELECT id_entrega FROM entrega_protesis WHERE codigo_protesis = :cod AND id_entrega <> :id");
        $chk->execute([':cod' => $codigo_protesis, ':id' => $id]);
        if ($chk->fetch()) {
            $errors[] = "El código '{$codigo_protesis}' ya pertenece a otro registro.";
        }
    }

    if (empty($errors)) {
        try {
            $stmtUp = $db->prepare("
                UPDATE entrega_protesis SET
                    codigo_protesis  = :cod,
                    id_paciente      = :id_pac,
                    id_tipo_protesis = :id_tipo,
                    fecha_entrega    = :fecha,
                    estado           = :est,
                    observaciones    = :obs
                WHERE id_entrega = :id
            ");
            $stmtUp->execute([
                ':cod'     => $codigo_protesis,
                ':id_pac'  => $id_paciente,
                ':id_tipo' => $id_tipo_protesis,
                ':fecha'   => $fecha_entrega,
                ':est'     => $estado,
                ':obs'     => $observaciones ?: null,
                ':id'      => $id,
            ]);

            set_flash('success', "Datos de la entrega {$codigo_protesis} actualizados exitosamente.");
            redirect("/detalle.php?id={$id}");
        } catch (Throwable $t) {
            $errors[] = 'Error al actualizar en la base de datos: ' . $t->getMessage();
        }
    }
} else {
    $_POST = $entrega;
}

require_once __DIR__ . '/header.php';
?>

<div class="mb-4">
    <h4 class="fw-bold mb-1">Modificar Entrega &mdash; <?= e($entrega['codigo_protesis']) ?></h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="/index.php">Inicio</a></li>
            <li class="breadcrumb-item"><a href="/detalle.php?id=<?= $id ?>"><?= e($entrega['codigo_protesis']) ?></a></li>
            <li class="breadcrumb-item active">Modificar</li>
        </ol>
    </nav>
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

<form method="POST" novalidate>
    <?= csrf_field() ?>

    <div class="card mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="bi bi-pencil-square me-2 text-primary"></i>Actualización de la Entrega
            </h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="codigo_protesis" class="form-label fw-semibold">Código de la Prótesis <span class="text-danger">*</span></label>
                    <input type="text" class="form-control font-monospace" id="codigo_protesis" name="codigo_protesis"
                           value="<?= e($_POST['codigo_protesis'] ?? '') ?>" required>
                </div>

                <div class="col-md-6">
                    <label for="id_paciente" class="form-label fw-semibold">Paciente Receptor <span class="text-danger">*</span></label>
                    <select class="form-select" id="id_paciente" name="id_paciente" required>
                        <?php foreach ($pacientes as $pac): ?>
                            <option value="<?= (int)$pac['id_paciente'] ?>"
                                <?= (int)($_POST['id_paciente'] ?? 0) === (int)$pac['id_paciente'] ? 'selected' : '' ?>>
                                <?= e($pac['nombre_completo']) ?> [<?= e($pac['numero_documento']) ?>]
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="id_tipo_protesis" class="form-label fw-semibold">Tipo de Prótesis <span class="text-danger">*</span></label>
                    <select class="form-select" id="id_tipo_protesis" name="id_tipo_protesis" required>
                        <?php foreach ($tipos as $tp): ?>
                            <option value="<?= (int)$tp['id_tipo_protesis'] ?>"
                                <?= (int)($_POST['id_tipo_protesis'] ?? 0) === (int)$tp['id_tipo_protesis'] ? 'selected' : '' ?>>
                                <?= e($tp['nombre_tipo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="fecha_entrega" class="form-label fw-semibold">Fecha de Entrega <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="fecha_entrega" name="fecha_entrega"
                           value="<?= e($_POST['fecha_entrega'] ?? '') ?>" required>
                </div>

                <div class="col-md-6">
                    <label for="estado" class="form-label fw-semibold">Estado de la Entrega <span class="text-danger">*</span></label>
                    <select class="form-select" id="estado" name="estado" required>
                        <option value="ENTREGADO" <?= ($_POST['estado'] ?? '') === 'ENTREGADO' ? 'selected' : '' ?>>Entregado</option>
                        <option value="EN_PRUEBAS" <?= ($_POST['estado'] ?? '') === 'EN_PRUEBAS' ? 'selected' : '' ?>>En Pruebas</option>
                        <option value="EN_FABRICACION" <?= ($_POST['estado'] ?? '') === 'EN_FABRICACION' ? 'selected' : '' ?>>En Fabricación</option>
                        <option value="DADO_DE_BAJA" <?= ($_POST['estado'] ?? '') === 'DADO_DE_BAJA' ? 'selected' : '' ?>>Dado de Baja</option>
                    </select>
                </div>

                <div class="col-12">
                    <label for="observaciones" class="form-label fw-semibold">Observaciones Clínicas y Técnicas</label>
                    <textarea class="form-control" id="observaciones" name="observaciones" rows="3"><?= e($_POST['observaciones'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mb-5">
        <a href="/detalle.php?id=<?= $id ?>" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-save me-1"></i> Guardar Cambios
        </button>
    </div>
</form>

<?php require_once __DIR__ . '/footer.php'; ?>

