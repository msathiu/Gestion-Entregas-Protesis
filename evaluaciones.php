<?php
/**
 * Módulo: Evaluaciones Biomecánicas
 */

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/controllers/EvaluacionController.php';

$pageTitle = 'Evaluaciones Biomecánicas';
$activeMenu = 'evaluaciones';

requerir_permiso('evaluaciones', 'listar');

$evalCtrl = new EvaluacionController();
$accion = $_GET['accion'] ?? 'listar';
$error = null;

// PROCESAMIENTO POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($accion === 'crear') {
        requerir_permiso('evaluaciones', 'crear');
        $datos = [
            'id_paciente_condicion'        => (int)($_POST['id_paciente_condicion'] ?? 0),
            'id_nivel_actividad'           => (int)($_POST['id_nivel_actividad'] ?? 0),
            'id_usuario_sistema_evaluador' => $usuarioActual['id_usuario_sistema'],
            'fecha_evaluacion'             => trim($_POST['fecha_evaluacion'] ?? date('Y-m-d')),
            'peso_kg'                      => (float)($_POST['peso_kg'] ?? 0),
            'altura_cm'                    => (float)($_POST['altura_cm'] ?? 0),
            'observaciones'                => trim($_POST['observaciones'] ?? '')
        ];

        $res = $evalCtrl->store($datos);
        if ($res['success']) {
            $_SESSION['flash_success'] = 'Evaluación biomecánica registrada exitosamente.';
            header('Location: evaluaciones.php?accion=ver&id=' . $res['id']);
            exit;
        } else {
            $error = $res['mensaje'] . (isset($res['sqlstate']) ? " (SQLSTATE {$res['sqlstate']})" : '');
        }
    }

    if ($accion === 'agregar_medida') {
        requerir_permiso('evaluaciones', 'editar');
        $idEval = (int)($_POST['id_evaluacion'] ?? 0);
        $idTipo = (int)($_POST['id_tipo_medida'] ?? 0);
        $valor  = (float)($_POST['valor_medida'] ?? 0);

        if ($idEval > 0 && $idTipo > 0 && $valor > 0) {
            $res = $evalCtrl->registrarMedida($idEval, $idTipo, $valor);
            if ($res['success']) {
                $_SESSION['flash_success'] = 'Medida anatómica agregada correctamente.';
            } else {
                $_SESSION['flash_error'] = $res['mensaje'];
            }
        }
        header("Location: evaluaciones.php?accion=ver&id={$idEval}");
        exit;
    }

    if ($accion === 'eliminar') {
        requerir_permiso('evaluaciones', 'eliminar');
        $id = (int)($_POST['id_evaluacion'] ?? 0);
        $res = $evalCtrl->destroy($id);
        if ($res['success']) {
            $_SESSION['flash_success'] = 'Evaluación eliminada correctamente.';
        } else {
            $_SESSION['flash_error'] = $res['mensaje'];
        }
        header('Location: evaluaciones.php');
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($accion === 'crear'): ?>
    <?php
    $catalogos = $evalCtrl->catalogos();
    $condiciones = $catalogos['condiciones'];
    $niveles     = $catalogos['niveles'];
    ?>
    <div class="card card-custom p-4" style="max-width: 800px; margin: 0 auto;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0" style="color: var(--turquesa-oscuro);">Nueva Evaluación Biomecánica</h5>
            <a href="evaluaciones.php" class="btn btn-sm btn-outline-secondary">Cancelar</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger small py-2 px-3 mb-3"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="evaluaciones.php?accion=crear">
            <div class="row g-3">
                <div class="col-md-12">
                    <label class="form-label">Paciente y Condición Clínica <span class="text-danger">*</span></label>
                    <select name="id_paciente_condicion" class="form-select" required>
                        <option value="">-- Seleccione el paciente y condición anatómica --</option>
                        <?php foreach ($condiciones as $cond): ?>
                            <option value="<?= $cond['id_paciente_condicion'] ?>">
                                <?= htmlspecialchars($cond['nombre_paciente'] . ' [' . $cond['numero_documento'] . '] - ' . $cond['nombre_segmento'] . ' (' . $cond['lateralidad'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Si el paciente no aparece, verifique que tenga registrada una condición clínica.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Nivel de Actividad Funcional (K-Level) <span class="text-danger">*</span></label>
                    <select name="id_nivel_actividad" class="form-select" required>
                        <option value="">-- Seleccione nivel funcional --</option>
                        <?php foreach ($niveles as $niv): ?>
                            <option value="<?= $niv['id_nivel_actividad'] ?>">
                                <?= htmlspecialchars($niv['codigo_nivel'] . ' - ' . $niv['nombre_nivel'] . ' (Factor: ' . $niv['factor_desgaste'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Fecha de Evaluación <span class="text-danger">*</span></label>
                    <input type="date" name="fecha_evaluacion" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Peso Corporal (kg) <span class="text-danger">*</span></label>
                    <input type="number" step="0.1" min="10" max="300" name="peso_kg" class="form-control" required placeholder="Ej: 68.5">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Altura (cm) <span class="text-danger">*</span></label>
                    <input type="number" step="0.5" min="50" max="250" name="altura_cm" class="form-control" required placeholder="Ej: 165">
                </div>

                <div class="col-12">
                    <label class="form-label">Observaciones Biomecánicas y Marcha</label>
                    <textarea name="observaciones" class="form-control" rows="3" placeholder="Detalles de movilidad, volumen del muñón, postura..."></textarea>
                </div>

                <div class="col-12 mt-4 text-end">
                    <a href="evaluaciones.php" class="btn btn-outline-secondary me-2">Cancelar</a>
                    <button type="submit" class="btn btn-turquesa px-4">
                        <i class="bi bi-save me-1"></i> Guardar Evaluación
                    </button>
                </div>
            </div>
        </form>
    </div>

<?php elseif ($accion === 'ver'): ?>
    <?php
    $idVer = (int)($_GET['id'] ?? 0);
    $resShow = $evalCtrl->show($idVer);
    if (!$resShow['success']) {
        echo "<div class='alert alert-danger'>{$resShow['mensaje']}</div>";
        require_once __DIR__ . '/includes/footer.php';
        exit;
    }
    $ev = $resShow['data'];
    $medidas = $evalCtrl->medidas($idVer)['data'] ?? [];
    $tiposMedida = $evalCtrl->catalogos()['tiposMedida'] ?? [];
    ?>
    <div class="card card-custom p-4 mb-4" style="max-width: 850px; margin: 0 auto;">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <div>
                <h5 class="fw-bold mb-0" style="color: var(--turquesa-oscuro);">
                    <i class="bi bi-clipboard2-pulse me-2"></i>Evaluación Biomecánica #<?= $ev['id_evaluacion_biomecanica'] ?>
                </h5>
                <span class="text-muted small">Realizada el <?= formatear_fecha($ev['fecha_evaluacion']) ?></span>
            </div>
            <a href="evaluaciones.php" class="btn btn-sm btn-outline-secondary">Volver al listado</a>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-md-4">
                <span class="text-muted small d-block">Paciente</span>
                <span class="fw-semibold"><?= htmlspecialchars($ev['nombre_paciente']) ?> [<?= htmlspecialchars($ev['numero_documento']) ?>]</span>
            </div>
            <div class="col-sm-6 col-md-4">
                <span class="text-muted small d-block">Segmento / Lado</span>
                <span class="fw-semibold"><?= htmlspecialchars($ev['nombre_segmento'] . ' (' . $ev['lateralidad'] . ')') ?></span>
            </div>
            <div class="col-sm-6 col-md-4">
                <span class="text-muted small d-block">Nivel de Actividad</span>
                <span class="badge badge-turquesa"><?= htmlspecialchars($ev['codigo_nivel'] . ' - ' . $ev['nombre_nivel']) ?></span>
            </div>
            <div class="col-sm-6 col-md-4">
                <span class="text-muted small d-block">Peso Corporal</span>
                <span class="fw-semibold"><?= htmlspecialchars($ev['peso_kg']) ?> kg</span>
            </div>
            <div class="col-sm-6 col-md-4">
                <span class="text-muted small d-block">Altura</span>
                <span class="fw-semibold"><?= htmlspecialchars($ev['altura_cm']) ?> cm</span>
            </div>
            <div class="col-sm-6 col-md-4">
                <span class="text-muted small d-block">Evaluador Responsable</span>
                <span class="fw-semibold"><?= htmlspecialchars($ev['evaluador']) ?></span>
            </div>
            <div class="col-12">
                <span class="text-muted small d-block">Observaciones</span>
                <span><?= htmlspecialchars($ev['observaciones'] ?? 'Sin observaciones adicionales') ?></span>
            </div>
        </div>

        <!-- MEDIDAS ANATÓMICAS -->
        <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
            <h6 class="fw-bold mb-0" style="color: var(--turquesa-oscuro);">Medidas Anatómicas del Muñón</h6>
            <?php if (Permisos::puede($rolActual, 'evaluaciones', 'editar')): ?>
                <button type="button" class="btn btn-sm btn-outline-turquesa" data-bs-toggle="collapse" data-bs-target="#formMedida">
                    <i class="bi bi-plus-lg me-1"></i> Añadir Medida
                </button>
            <?php endif; ?>
        </div>

        <?php if (Permisos::puede($rolActual, 'evaluaciones', 'editar')): ?>
            <div class="collapse mb-3" id="formMedida">
                <div class="card card-custom p-3 bg-light">
                    <form method="POST" action="evaluaciones.php?accion=agregar_medida">
                        <input type="hidden" name="id_evaluacion" value="<?= $idVer ?>">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-6">
                                <label class="form-label small">Tipo de Medida</label>
                                <select name="id_tipo_medida" class="form-select form-select-sm" required>
                                    <option value="">-- Seleccionar medida --</option>
                                    <?php foreach ($tiposMedida as $tm): ?>
                                        <option value="<?= $tm['id_tipo_medida'] ?>">
                                            <?= htmlspecialchars($tm['nombre_medida'] . ' (' . $tm['unidad_medida'] . ')') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Valor Numérico</label>
                                <input type="number" step="0.01" min="0.1" name="valor_medida" class="form-control form-control-sm" required placeholder="Ej: 36.5">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-sm btn-turquesa w-100">Guardar</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($medidas)): ?>
            <div class="alert alert-light border small text-muted">No se han registrado medidas anatómicas para esta evaluación.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Nombre de la Medida</th>
                            <th>Valor Registrado</th>
                            <th>Unidad</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($medidas as $m): ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($m['nombre_medida']) ?></td>
                                <td class="fw-bold" style="color: var(--turquesa-oscuro);"><?= htmlspecialchars($m['valor_medida']) ?></td>
                                <td><?= htmlspecialchars($m['unidad_medida']) ?></td>
                                <td class="text-muted small"><?= htmlspecialchars($m['descripcion'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

<?php else: ?>
    <!-- LISTADO DE EVALUACIONES -->
    <?php
    $evaluaciones = $evalCtrl->index()['data'] ?? [];
    ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-0" style="color: var(--turquesa-oscuro);">Evaluaciones Biomecánicas</h4>
            <p class="text-muted small mb-0">Total evaluaciones registradas: <?= count($evaluaciones) ?></p>
        </div>
        <?php if (Permisos::puede($rolActual, 'evaluaciones', 'crear')): ?>
            <a href="evaluaciones.php?accion=crear" class="btn btn-turquesa">
                <i class="bi bi-clipboard2-plus me-1"></i> Nueva Evaluación
            </a>
        <?php endif; ?>
    </div>

    <div class="card card-custom p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-custom mb-0">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Paciente</th>
                        <th>Segmento / Lado</th>
                        <th>Nivel Funcional</th>
                        <th>Peso (kg)</th>
                        <th>Altura (cm)</th>
                        <th>Evaluador</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($evaluaciones)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">No se registran evaluaciones clínicas.</td></tr>
                    <?php else: ?>
                        <?php foreach ($evaluaciones as $ev): ?>
                            <tr>
                                <td class="fw-semibold"><?= formatear_fecha($ev['fecha_evaluacion']) ?></td>
                                <td><?= htmlspecialchars($ev['nombre_paciente']) ?> [<?= htmlspecialchars($ev['numero_documento']) ?>]</td>
                                <td><?= htmlspecialchars($ev['nombre_segmento'] . ' (' . $ev['lateralidad'] . ')') ?></td>
                                <td><span class="badge badge-turquesa"><?= htmlspecialchars($ev['codigo_nivel'] . ' - ' . $ev['nombre_nivel']) ?></span></td>
                                <td><?= htmlspecialchars($ev['peso_kg']) ?></td>
                                <td><?= htmlspecialchars($ev['altura_cm']) ?></td>
                                <td><?= htmlspecialchars($ev['evaluador']) ?></td>
                                <td class="text-end text-nowrap">
                                    <a href="evaluaciones.php?accion=ver&id=<?= $ev['id_evaluacion_biomecanica'] ?>" class="btn btn-sm btn-outline-secondary" title="Ver detalle y medidas">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if (Permisos::puede($rolActual, 'evaluaciones', 'eliminar')): ?>
                                        <form method="POST" action="evaluaciones.php?accion=eliminar" class="d-inline" onsubmit="return confirm('¿Está seguro de eliminar esta evaluación?');">
                                            <input type="hidden" name="id_evaluacion" value="<?= $ev['id_evaluacion_biomecanica'] ?>">
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
