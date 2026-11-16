<?php
/**
 * Módulo: Gestión de Pacientes
 */

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/controllers/PacienteController.php';

$pageTitle = 'Pacientes';
$activeMenu = 'pacientes';

requerir_permiso('pacientes', 'listar');

$pacienteCtrl = new PacienteController();
$accion = $_GET['accion'] ?? 'listar';
$error = null;

// PROCESAMIENTO DE ACCIONES POST (Crear / Editar / Eliminar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($accion === 'crear') {
        requerir_permiso('pacientes', 'crear');
        $datos = [
            'numero_documento' => trim($_POST['numero_documento'] ?? ''),
            'nombres'          => trim($_POST['nombres'] ?? ''),
            'apellidos'        => trim($_POST['apellidos'] ?? ''),
            'fecha_nacimiento' => trim($_POST['fecha_nacimiento'] ?? ''),
            'sexo'             => trim($_POST['sexo'] ?? 'M'),
            'telefono'         => trim($_POST['telefono'] ?? ''),
            'correo'           => trim($_POST['correo'] ?? ''),
            'direccion'        => trim($_POST['direccion'] ?? ''),
            'activo'           => isset($_POST['activo'])
        ];

        $res = $pacienteCtrl->store($datos);
        if ($res['success']) {
            $_SESSION['flash_success'] = 'Paciente registrado exitosamente en la base de datos.';
            header('Location: pacientes.php');
            exit;
        } else {
            $error = $res['mensaje'] . (isset($res['sqlstate']) ? " (SQLSTATE {$res['sqlstate']})" : '');
        }
    }

    if ($accion === 'editar') {
        requerir_permiso('pacientes', 'editar');
        $id = (int)($_POST['id_paciente'] ?? 0);
        $datos = [
            'numero_documento' => trim($_POST['numero_documento'] ?? ''),
            'nombres'          => trim($_POST['nombres'] ?? ''),
            'apellidos'        => trim($_POST['apellidos'] ?? ''),
            'fecha_nacimiento' => trim($_POST['fecha_nacimiento'] ?? ''),
            'sexo'             => trim($_POST['sexo'] ?? 'M'),
            'telefono'         => trim($_POST['telefono'] ?? ''),
            'correo'           => trim($_POST['correo'] ?? ''),
            'direccion'        => trim($_POST['direccion'] ?? ''),
            'activo'           => isset($_POST['activo'])
        ];

        $res = $pacienteCtrl->update($id, $datos);
        if ($res['success']) {
            $_SESSION['flash_success'] = 'Datos del paciente actualizados correctamente.';
            header('Location: pacientes.php');
            exit;
        } else {
            $error = $res['mensaje'] . (isset($res['sqlstate']) ? " (SQLSTATE {$res['sqlstate']})" : '');
        }
    }

    if ($accion === 'eliminar') {
        requerir_permiso('pacientes', 'eliminar');
        $id = (int)($_POST['id_paciente'] ?? 0);
        $res = $pacienteCtrl->destroy($id);
        if ($res['success']) {
            $_SESSION['flash_success'] = 'Paciente eliminado de la base de datos.';
        } else {
            $_SESSION['flash_error'] = $res['mensaje'];
        }
        header('Location: pacientes.php');
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($accion === 'crear' || $accion === 'editar'): ?>
    <?php
    $pacienteEditar = null;
    if ($accion === 'editar') {
        $idEditar = (int)($_GET['id'] ?? 0);
        $resShow = $pacienteCtrl->show($idEditar);
        if (!$resShow['success']) {
            echo "<div class='alert alert-danger'>{$resShow['mensaje']}</div>";
            require_once __DIR__ . '/includes/footer.php';
            exit;
        }
        $pacienteEditar = $resShow['data'];
    }
    ?>
    <div class="card card-custom p-4" style="max-width: 800px; margin: 0 auto;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0" style="color: var(--turquesa-oscuro);">
                <?= $accion === 'crear' ? 'Registrar Nuevo Paciente' : 'Modificar Datos de Paciente' ?>
            </h5>
            <a href="pacientes.php" class="btn btn-sm btn-outline-secondary">Cancelar</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger small py-2 px-3 mb-3"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="pacientes.php?accion=<?= $accion ?>">
            <?php if ($pacienteEditar): ?>
                <input type="hidden" name="id_paciente" value="<?= $pacienteEditar['id_paciente'] ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Número de Documento Identidad <span class="text-danger">*</span></label>
                    <input type="text" name="numero_documento" class="form-control" required
                           value="<?= htmlspecialchars($_POST['numero_documento'] ?? $pacienteEditar['numero_documento'] ?? '') ?>"
                           placeholder="Ej: V-12345678">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha Nacimiento <span class="text-danger">*</span></label>
                    <input type="date" name="fecha_nacimiento" class="form-control" required
                           value="<?= htmlspecialchars($_POST['fecha_nacimiento'] ?? $pacienteEditar['fecha_nacimiento'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sexo <span class="text-danger">*</span></label>
                    <select name="sexo" class="form-select" required>
                        <?php $s = $_POST['sexo'] ?? $pacienteEditar['sexo'] ?? 'M'; ?>
                        <option value="M" <?= $s === 'M' ? 'selected' : '' ?>>Masculino (M)</option>
                        <option value="F" <?= $s === 'F' ? 'selected' : '' ?>>Femenino (F)</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Nombres <span class="text-danger">*</span></label>
                    <input type="text" name="nombres" class="form-control" required
                           value="<?= htmlspecialchars($_POST['nombres'] ?? $pacienteEditar['nombres'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Apellidos <span class="text-danger">*</span></label>
                    <input type="text" name="apellidos" class="form-control" required
                           value="<?= htmlspecialchars($_POST['apellidos'] ?? $pacienteEditar['apellidos'] ?? '') ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
                    <input type="email" name="correo" class="form-control" required
                           value="<?= htmlspecialchars($_POST['correo'] ?? $pacienteEditar['correo'] ?? '') ?>"
                           placeholder="correo@ejemplo.com">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Teléfono de Contacto</label>
                    <input type="text" name="telefono" class="form-control"
                           value="<?= htmlspecialchars($_POST['telefono'] ?? $pacienteEditar['telefono'] ?? '') ?>"
                           placeholder="0414-1234567">
                </div>

                <div class="col-12">
                    <label class="form-label">Dirección de Habitación</label>
                    <textarea name="direccion" class="form-control" rows="2"><?= htmlspecialchars($_POST['direccion'] ?? $pacienteEditar['direccion'] ?? '') ?></textarea>
                </div>

                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="activo" id="activo"
                               <?= (!isset($pacienteEditar) || $pacienteEditar['activo']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="activo">Paciente activo en el sistema</label>
                    </div>
                </div>

                <div class="col-12 mt-4 text-end">
                    <a href="pacientes.php" class="btn btn-outline-secondary me-2">Cancelar</a>
                    <button type="submit" class="btn btn-turquesa px-4">
                        <i class="bi bi-save me-1"></i> <?= $accion === 'crear' ? 'Registrar Paciente' : 'Guardar Cambios' ?>
                    </button>
                </div>
            </div>
        </form>
    </div>

<?php elseif ($accion === 'ver'): ?>
    <?php
    $idVer = (int)($_GET['id'] ?? 0);
    $resShow = $pacienteCtrl->show($idVer);
    if (!$resShow['success']) {
        echo "<div class='alert alert-danger'>{$resShow['mensaje']}</div>";
        require_once __DIR__ . '/includes/footer.php';
        exit;
    }
    $p = $resShow['data'];
    $condiciones = $pacienteCtrl->condiciones($idVer)['data'] ?? [];
    ?>
    <div class="card card-custom p-4 mb-4" style="max-width: 850px; margin: 0 auto;">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <h5 class="fw-bold mb-0" style="color: var(--turquesa-oscuro);">
                <i class="bi bi-person-badge me-2"></i>Ficha del Paciente: <?= htmlspecialchars($p['nombres'] . ' ' . $p['apellidos']) ?>
            </h5>
            <div>
                <?php if (Permisos::puede($rolActual, 'pacientes', 'editar')): ?>
                    <a href="pacientes.php?accion=editar&id=<?= $p['id_paciente'] ?>" class="btn btn-sm btn-outline-turquesa me-1">
                        <i class="bi bi-pencil"></i> Editar
                    </a>
                <?php endif; ?>
                <a href="pacientes.php" class="btn btn-sm btn-outline-secondary">Volver al listado</a>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-md-4">
                <span class="text-muted small d-block">Documento Identidad</span>
                <span class="fw-semibold"><?= htmlspecialchars($p['numero_documento']) ?></span>
            </div>
            <div class="col-sm-6 col-md-4">
                <span class="text-muted small d-block">Fecha de Nacimiento</span>
                <span class="fw-semibold"><?= formatear_fecha($p['fecha_nacimiento']) ?></span>
            </div>
            <div class="col-sm-6 col-md-4">
                <span class="text-muted small d-block">Sexo</span>
                <span class="fw-semibold"><?= $p['sexo'] === 'M' ? 'Masculino' : 'Femenino' ?></span>
            </div>
            <div class="col-sm-6 col-md-4">
                <span class="text-muted small d-block">Correo</span>
                <span class="fw-semibold"><?= htmlspecialchars($p['correo']) ?></span>
            </div>
            <div class="col-sm-6 col-md-4">
                <span class="text-muted small d-block">Teléfono</span>
                <span class="fw-semibold"><?= htmlspecialchars($p['telefono'] ?? '-') ?></span>
            </div>
            <div class="col-sm-6 col-md-4">
                <span class="text-muted small d-block">Estado</span>
                <span class="badge <?= $p['activo'] ? 'bg-success' : 'bg-secondary' ?>"><?= $p['activo'] ? 'Activo' : 'Inactivo' ?></span>
            </div>
            <div class="col-12">
                <span class="text-muted small d-block">Dirección</span>
                <span><?= htmlspecialchars($p['direccion'] ?? 'Sin dirección registrada') ?></span>
            </div>
        </div>

        <h6 class="fw-bold mt-2 mb-3" style="color: var(--turquesa-oscuro);">Condiciones Clínicas y Amputaciones</h6>
        <?php if (empty($condiciones)): ?>
            <div class="alert alert-light border small text-muted">No se registran condiciones clínicas asociadas a este paciente.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Segmento Corporal</th>
                            <th>Lateralidad</th>
                            <th>Causa</th>
                            <th>Fecha Condición</th>
                            <th>Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($condiciones as $c): ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($c['nombre_segmento']) ?></td>
                                <td><span class="badge badge-turquesa"><?= htmlspecialchars($c['lateralidad']) ?></span></td>
                                <td><?= htmlspecialchars($c['nombre_causa']) ?></td>
                                <td><?= formatear_fecha($c['fecha_condicion']) ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($c['observaciones'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

<?php else: ?>
    <!-- LISTADO DE PACIENTES -->
    <?php
    $resLista = $pacienteCtrl->index(null); // listar todos
    $pacientes = $resLista['data'] ?? [];
    ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-0" style="color: var(--turquesa-oscuro);">Registro de Pacientes</h4>
            <p class="text-muted small mb-0">Total registrados: <?= count($pacientes) ?></p>
        </div>
        <?php if (Permisos::puede($rolActual, 'pacientes', 'crear')): ?>
            <a href="pacientes.php?accion=crear" class="btn btn-turquesa">
                <i class="bi bi-person-plus me-1"></i> Registrar Paciente
            </a>
        <?php endif; ?>
    </div>

    <div class="card card-custom p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-custom mb-0">
                <thead>
                    <tr>
                        <th>Documento Identidad</th>
                        <th>Nombres y Apellidos</th>
                        <th>Nacimiento</th>
                        <th>Sexo</th>
                        <th>Teléfono</th>
                        <th>Correo</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pacientes)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">No hay pacientes registrados en el sistema.</td></tr>
                    <?php else: ?>
                        <?php foreach ($pacientes as $pac): ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($pac['numero_documento']) ?></td>
                                <td><?= htmlspecialchars($pac['apellidos'] . ', ' . $pac['nombres']) ?></td>
                                <td><?= formatear_fecha($pac['fecha_nacimiento']) ?></td>
                                <td><?= htmlspecialchars($pac['sexo']) ?></td>
                                <td><?= htmlspecialchars($pac['telefono'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($pac['correo']) ?></td>
                                <td>
                                    <span class="badge <?= $pac['activo'] ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= $pac['activo'] ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="pacientes.php?accion=ver&id=<?= $pac['id_paciente'] ?>" class="btn btn-sm btn-outline-secondary" title="Consultar detalle">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if (Permisos::puede($rolActual, 'pacientes', 'editar')): ?>
                                        <a href="pacientes.php?accion=editar&id=<?= $pac['id_paciente'] ?>" class="btn btn-sm btn-outline-turquesa" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (Permisos::puede($rolActual, 'pacientes', 'eliminar')): ?>
                                        <form method="POST" action="pacientes.php?accion=eliminar" class="d-inline" onsubmit="return confirm('¿Está seguro de eliminar al paciente <?= htmlspecialchars($pac['nombres'] . ' ' . $pac['apellidos']) ?>? La operación será rechazada si posee registros dependientes.');">
                                            <input type="hidden" name="id_paciente" value="<?= $pac['id_paciente'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
