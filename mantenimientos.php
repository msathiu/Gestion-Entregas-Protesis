<?php
/**
 * Módulo: Mantenimiento Preventivo
 * Asignatura: Bases de Datos - Ingeniería de Sistemas (ULA)
 */

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/controllers/MantenimientoController.php';

$pageTitle = 'Mantenimiento Preventivo';
$activeMenu = 'mantenimientos';

requerir_permiso('mantenimientos', 'listar');

$mantCtrl = new MantenimientoController();
$accion = $_GET['accion'] ?? 'listar';
$error = null;

// PROCESAMIENTO POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($accion === 'crear') {
        requerir_permiso('mantenimientos', 'crear');
        $datos = [
            'id_dispositivo_protesico'   => (int)($_POST['id_dispositivo_protesico'] ?? 0),
            'id_tipo_mantenimiento'      => (int)($_POST['id_tipo_mantenimiento'] ?? 0),
            'id_estado_mantenimiento'    => (int)($_POST['id_estado_mantenimiento'] ?? 1),
            'id_usuario_sistema_tecnico' => !empty($_POST['id_tecnico']) ? (int)$_POST['id_tecnico'] : null,
            'fecha_programada'           => trim($_POST['fecha_programada'] ?? date('Y-m-d')),
            'observaciones'              => trim($_POST['observaciones'] ?? '')
        ];

        $res = $mantCtrl->store($datos);
        if ($res['success']) {
            $_SESSION['flash_success'] = 'Mantenimiento preventivo programado exitosamente.';
            header('Location: mantenimientos.php');
            exit;
        } else {
            $error = $res['mensaje'] . (isset($res['sqlstate']) ? " (SQLSTATE {$res['sqlstate']})" : '');
        }
    }

    if ($accion === 'marcar_vencidos') {
        requerir_permiso('mantenimientos', 'marcar_vencidos');
        $res = $mantCtrl->marcarVencidos();
        $_SESSION['flash_success'] = "Función PL/pgSQL ejecutada. Se actualizaron {$res['afectados']} mantenimientos vencidos.";
        header('Location: mantenimientos.php');
        exit;
    }

    if ($accion === 'completar') {
        requerir_permiso('mantenimientos', 'editar');
        $idMant = (int)($_POST['id_mantenimiento'] ?? 0);
        $resShow = $mantCtrl->show($idMant);
        if ($resShow['success']) {
            $m = $resShow['data'];
            // Obtener ID del estado REALIZADO
            $db = Database::getConnection();
            $idEstadoRealizado = (int)$db->query("SELECT id_estado_mantenimiento FROM estado_mantenimiento WHERE nombre_estado = 'REALIZADO'")->fetchColumn();

            $datos = [
                'id_dispositivo_protesico'   => $m['id_dispositivo_protesico'],
                'id_tipo_mantenimiento'      => $m['id_tipo_mantenimiento'],
                'id_estado_mantenimiento'    => $idEstadoRealizado,
                'id_usuario_sistema_tecnico' => $usuarioActual['id_usuario_sistema'],
                'fecha_programada'           => $m['fecha_programada'],
                'fecha_realizacion'          => date('Y-m-d'),
                'observaciones'              => trim($_POST['observaciones'] ?? $m['observaciones'])
            ];
            $resUpdate = $mantCtrl->update($idMant, $datos);
            if ($resUpdate['success']) {
                $_SESSION['flash_success'] = 'Mantenimiento marcado como REALIZADO.';
            } else {
                $_SESSION['flash_error'] = $resUpdate['mensaje'];
            }
        }
        header("Location: mantenimientos.php?accion=ver&id={$idMant}");
        exit;
    }

    if ($accion === 'eliminar') {
        requerir_permiso('mantenimientos', 'eliminar');
        $id = (int)($_POST['id_mantenimiento'] ?? 0);
        $res = $mantCtrl->destroy($id);
        if ($res['success']) {
            $_SESSION['flash_success'] = 'Mantenimiento eliminado correctamente.';
        } else {
            $_SESSION['flash_error'] = $res['mensaje'];
        }
        header('Location: mantenimientos.php');
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($accion === 'crear'): ?>
    <?php
    $cat = $mantCtrl->catalogos();
    ?>
    <div class="card card-custom p-4" style="max-width: 750px; margin: 0 auto;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0" style="color: var(--turquesa-oscuro);">Programar Mantenimiento Preventivo</h5>
            <a href="mantenimientos.php" class="btn btn-sm btn-outline-secondary">Cancelar</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger small py-2 px-3 mb-3"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="mantenimientos.php?accion=crear">
            <div class="row g-3">
                <div class="col-md-12">
                    <label class="form-label">Dispositivo Protésico <span class="text-danger">*</span></label>
                    <select name="id_dispositivo_protesico" class="form-select" required>
                        <option value="">-- Seleccionar dispositivo a programar --</option>
                        <?php foreach ($cat['dispositivos'] as $d): ?>
                            <option value="<?= $d['id_dispositivo_protesico'] ?>">
                                <?= htmlspecialchars("{$d['codigo_dispositivo']} (Paciente: {$d['paciente']})") ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Tipo de Mantenimiento <span class="text-danger">*</span></label>
                    <select name="id_tipo_mantenimiento" class="form-select" required>
                        <option value="">-- Seleccionar tipo --</option>
                        <?php foreach ($cat['tipos'] as $t): ?>
                            <option value="<?= $t['id_tipo_mantenimiento'] ?>">
                                <?= htmlspecialchars("{$t['nombre_mantenimiento']} (Sugerido: cada {$t['intervalo_dias_sugerido']} días)") ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Fecha Programada <span class="text-danger">*</span></label>
                    <input type="date" name="fecha_programada" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Técnico Ortopédico Asignado</label>
                    <select name="id_tecnico" class="form-select">
                        <option value="">-- Sin técnico asignado (Pendiente) --</option>
                        <?php foreach ($cat['tecnicos'] as $tec): ?>
                            <option value="<?= $tec['id_usuario_sistema'] ?>" <?= $tec['id_usuario_sistema'] == $usuarioActual['id_usuario_sistema'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tec['nombre_completo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Estado Inicial <span class="text-danger">*</span></label>
                    <select name="id_estado_mantenimiento" class="form-select" required>
                        <?php foreach ($cat['estados'] as $e): ?>
                            <option value="<?= $e['id_estado_mantenimiento'] ?>" <?= $e['nombre_estado'] === 'PROGRAMADO' ? 'selected' : '' ?>>
                                <?= htmlspecialchars($e['nombre_estado']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Observaciones y Puntos Críticos a Revisar</label>
                    <textarea name="observaciones" class="form-control" rows="3" placeholder="Revisar pernos de anclaje, alineación, desgaste de suela..."></textarea>
                </div>

                <div class="col-12 mt-4 text-end">
                    <a href="mantenimientos.php" class="btn btn-outline-secondary me-2">Cancelar</a>
                    <button type="submit" class="btn btn-turquesa px-4">
                        <i class="bi bi-save me-1"></i> Programar Mantenimiento
                    </button>
                </div>
            </div>
        </form>
    </div>

<?php elseif ($accion === 'ver'): ?>
    <?php
    $idVer = (int)($_GET['id'] ?? 0);
    $resShow = $mantCtrl->show($idVer);
    if (!$resShow['success']) {
        echo "<div class='alert alert-danger'>{$resShow['mensaje']}</div>";
        require_once __DIR__ . '/includes/footer.php';
        exit;
    }
    $m = $resShow['data'];
    ?>
    <div class="card card-custom p-4 mb-4" style="max-width: 800px; margin: 0 auto;">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <div>
                <h5 class="fw-bold mb-0" style="color: var(--turquesa-oscuro);">
                    <i class="bi bi-calendar-check me-2"></i>Mantenimiento #<?= $m['id_mantenimiento_preventivo'] ?>
                </h5>
                <span class="text-muted small"><?= htmlspecialchars($m['nombre_mantenimiento']) ?></span>
            </div>
            <a href="mantenimientos.php" class="btn btn-sm btn-outline-secondary">Volver al listado</a>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-md-4">
                <span class="text-muted small d-block">Dispositivo</span>
                <span class="fw-semibold"><code><?= htmlspecialchars($m['codigo_dispositivo']) ?></code></span>
            </div>
            <div class="col-sm-6 col-md-4">
                <span class="text-muted small d-block">Paciente</span>
                <span class="fw-semibold"><?= htmlspecialchars($m['nombre_paciente']) ?></span>
            </div>
            <div class="col-sm-6 col-md-4">
                <span class="text-muted small d-block">Estado</span>
                <?php
                $badgeCls = match ($m['nombre_estado']) {
                    'REALIZADO'  => 'bg-success',
                    'PROGRAMADO' => 'bg-info text-dark',
                    'VENCIDO'    => 'bg-danger',
                    default      => 'bg-secondary'
                };
                ?>
                <span class="badge <?= $badgeCls ?>"><?= htmlspecialchars($m['nombre_estado']) ?></span>
            </div>
            <div class="col-sm-6 col-md-4">
                <span class="text-muted small d-block">Fecha Programada</span>
                <span class="fw-semibold"><?= formatear_fecha($m['fecha_programada']) ?></span>
            </div>
            <div class="col-sm-6 col-md-4">
                <span class="text-muted small d-block">Fecha de Realización</span>
                <span class="fw-semibold"><?= formatear_fecha($m['fecha_realizacion']) ?></span>
            </div>
            <div class="col-sm-6 col-md-4">
                <span class="text-muted small d-block">Técnico Responsable</span>
                <span class="fw-semibold"><?= htmlspecialchars($m['tecnico'] ?? 'No asignado') ?></span>
            </div>
            <div class="col-12 mt-2">
                <span class="text-muted small d-block">Observaciones</span>
                <div class="p-3 bg-light rounded mt-1 border">
                    <?= nl2br(htmlspecialchars($m['observaciones'] ?? 'Sin observaciones')) ?>
                </div>
            </div>
        </div>

        <!-- COMPLETAR MANTENIMIENTO (Si está programado o vencido) -->
        <?php if ($m['nombre_estado'] !== 'REALIZADO' && Permisos::puede($rolActual, 'mantenimientos', 'editar')): ?>
            <div class="card card-custom p-3 bg-light mt-3">
                <h6 class="fw-bold small mb-2 text-primary">Registrar Ejecución del Mantenimiento:</h6>
                <form method="POST" action="mantenimientos.php?accion=completar">
                    <input type="hidden" name="id_mantenimiento" value="<?= $idVer ?>">
                    <div class="mb-2">
                        <label class="form-label small">Informe de revisión técnica:</label>
                        <textarea name="observaciones" class="form-control form-control-sm" rows="2" placeholder="Describa el trabajo realizado, calibraciones o ajustes..."><?= htmlspecialchars($m['observaciones'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-sm btn-turquesa">
                        <i class="bi bi-check-lg me-1"></i> Marcar como Realizado Hoy (<?= date('d/m/Y') ?>)
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>

<?php else: ?>
    <!-- LISTADO DE MANTENIMIENTOS -->
    <?php
    $mantenimientos = $mantCtrl->index()['data'] ?? [];
    ?>
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0" style="color: var(--turquesa-oscuro);">Mantenimiento Preventivo</h4>
            <p class="text-muted small mb-0">Total revisiones registradas: <?= count($mantenimientos) ?></p>
        </div>
        <div class="d-flex gap-2">
            <?php if (Permisos::puede($rolActual, 'mantenimientos', 'marcar_vencidos')): ?>
                <form method="POST" action="mantenimientos.php?accion=marcar_vencidos" class="d-inline">
                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Ejecutar función fn_marcar_mantenimientos_vencidos() en PostgreSQL">
                        <i class="bi bi-arrow-clockwise me-1"></i> Comprobar Vencidos (PL/pgSQL)
                    </button>
                </form>
            <?php endif; ?>

            <?php if (Permisos::puede($rolActual, 'mantenimientos', 'crear')): ?>
                <a href="mantenimientos.php?accion=crear" class="btn btn-turquesa btn-sm">
                    <i class="bi bi-calendar-plus me-1"></i> Programar Mantenimiento
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card card-custom p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-custom mb-0">
                <thead>
                    <tr>
                        <th>Fecha Prog.</th>
                        <th>Dispositivo</th>
                        <th>Paciente</th>
                        <th>Tipo Mantenimiento</th>
                        <th>Técnico</th>
                        <th>Estado</th>
                        <th>Fecha Realización</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($mantenimientos)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">No se registran órdenes de mantenimiento preventivo.</td></tr>
                    <?php else: ?>
                        <?php foreach ($mantenimientos as $m): ?>
                            <tr>
                                <td class="fw-semibold"><?= formatear_fecha($m['fecha_programada']) ?></td>
                                <td><code><?= htmlspecialchars($m['codigo_dispositivo']) ?></code></td>
                                <td><?= htmlspecialchars($m['nombre_paciente']) ?></td>
                                <td><?= htmlspecialchars($m['nombre_mantenimiento']) ?></td>
                                <td><?= htmlspecialchars($m['tecnico'] ?? 'Sin asignar') ?></td>
                                <td>
                                    <?php
                                    $bCls = match ($m['nombre_estado']) {
                                        'REALIZADO'  => 'bg-success',
                                        'PROGRAMADO' => 'bg-info text-dark',
                                        'VENCIDO'    => 'bg-danger',
                                        default      => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?= $bCls ?>"><?= htmlspecialchars($m['nombre_estado']) ?></span>
                                </td>
                                <td><?= formatear_fecha($m['fecha_realizacion']) ?></td>
                                <td class="text-end text-nowrap">
                                    <a href="mantenimientos.php?accion=ver&id=<?= $m['id_mantenimiento_preventivo'] ?>" class="btn btn-sm btn-outline-secondary" title="Ver detalle">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if (Permisos::puede($rolActual, 'mantenimientos', 'eliminar')): ?>
                                        <form method="POST" action="mantenimientos.php?accion=eliminar" class="d-inline" onsubmit="return confirm('¿Está seguro de eliminar esta orden de mantenimiento?');">
                                            <input type="hidden" name="id_mantenimiento" value="<?= $m['id_mantenimiento_preventivo'] ?>">
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
