<?php
/**
 * Módulo: Gestión de Dispositivos Protésicos y Órtesis
 * Asignatura: Bases de Datos - Ingeniería de Sistemas (ULA)
 */

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/controllers/DispositivoController.php';

$pageTitle = 'Dispositivos Protésicos';
$activeMenu = 'dispositivos';

requerir_permiso('dispositivos', 'listar');

$dispCtrl = new DispositivoController();
$accion = $_GET['accion'] ?? 'listar';
$error = null;

// PROCESAMIENTO POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($accion === 'crear') {
        requerir_permiso('dispositivos', 'crear');
        $evalSeleccionada = (int)($_POST['id_evaluacion_biomecanica'] ?? 0);
        $condicionId = (int)($_POST['id_paciente_condicion'] ?? 0);

        // Si id_paciente_condicion no vino explícito, lo inferimos de la evaluación
        if ($condicionId <= 0 && $evalSeleccionada > 0) {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT id_paciente_condicion FROM evaluacion_biomecanica WHERE id_evaluacion_biomecanica = :id");
            $stmt->execute([':id' => $evalSeleccionada]);
            $condicionId = (int)$stmt->fetchColumn();
        }

        $datos = [
            'id_paciente_condicion'     => $condicionId,
            'id_evaluacion_biomecanica' => $evalSeleccionada,
            'id_tipo_dispositivo'       => (int)($_POST['id_tipo_dispositivo'] ?? 0),
            'id_estado_dispositivo'     => (int)($_POST['id_estado_dispositivo'] ?? 1),
            'codigo_dispositivo'        => trim($_POST['codigo_dispositivo'] ?? ''),
            'fecha_entrega'             => !empty($_POST['fecha_entrega']) ? trim($_POST['fecha_entrega']) : null,
            'fecha_baja'                => !empty($_POST['fecha_baja']) ? trim($_POST['fecha_baja']) : null,
        ];

        $res = $dispCtrl->store($datos);
        if ($res['success']) {
            $_SESSION['flash_success'] = 'Dispositivo protésico registrado exitosamente.';
            header('Location: dispositivos.php?accion=ver&id=' . $res['id']);
            exit;
        } else {
            $error = $res['mensaje'] . (isset($res['sqlstate']) ? " (SQLSTATE {$res['sqlstate']})" : '');
        }
    }

    if ($accion === 'cambiar_estado') {
        requerir_permiso('dispositivos', 'cambiar_estado');
        $idDisp = (int)($_POST['id_dispositivo'] ?? 0);
        $idNuevo = (int)($_POST['id_estado_dispositivo'] ?? 0);
        $res = $dispCtrl->cambiarEstado($idDisp, $idNuevo);
        if ($res['success']) {
            $_SESSION['flash_success'] = 'Estado operativo del dispositivo actualizado.';
        } else {
            $_SESSION['flash_error'] = $res['mensaje'];
        }
        header("Location: dispositivos.php?accion=ver&id={$idDisp}");
        exit;
    }

    if ($accion === 'eliminar') {
        requerir_permiso('dispositivos', 'eliminar');
        $id = (int)($_POST['id_dispositivo'] ?? 0);
        $res = $dispCtrl->destroy($id);
        if ($res['success']) {
            $_SESSION['flash_success'] = 'Dispositivo eliminado correctamente.';
        } else {
            $_SESSION['flash_error'] = $res['mensaje'];
        }
        header('Location: dispositivos.php');
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($accion === 'crear'): ?>
    <?php
    $cat = $dispCtrl->catalogos();
    ?>
    <div class="card card-custom p-4" style="max-width: 800px; margin: 0 auto;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0" style="color: var(--turquesa-oscuro);">Registrar Nuevo Dispositivo</h5>
            <a href="dispositivos.php" class="btn btn-sm btn-outline-secondary">Cancelar</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger small py-2 px-3 mb-3"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="dispositivos.php?accion=crear">
            <div class="row g-3">
                <div class="col-md-12">
                    <label class="form-label">Evaluación Biomecánica y Paciente Asociado <span class="text-danger">*</span></label>
                    <select name="id_evaluacion_biomecanica" class="form-select" required>
                        <option value="">-- Seleccione evaluación clínica previa --</option>
                        <?php foreach ($cat['evaluaciones'] as $ev): ?>
                            <option value="<?= $ev['id_evaluacion_biomecanica'] ?>">
                                <?= htmlspecialchars("Eval #{$ev['id_evaluacion_biomecanica']} ({$ev['fecha_evaluacion']}) - {$ev['nombre_paciente']} [{$ev['numero_documento']}] - {$ev['nombre_segmento']} ({$ev['lateralidad']})") ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Código Único del Dispositivo <span class="text-danger">*</span></label>
                    <input type="text" name="codigo_dispositivo" class="form-control" required
                           placeholder="Ej: PRT-2026-0002"
                           value="<?= 'PRT-' . date('Y') . '-' . str_pad((string)rand(100, 999), 4, '0', STR_PAD_LEFT) ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Tipo de Dispositivo <span class="text-danger">*</span></label>
                    <select name="id_tipo_dispositivo" class="form-select" required>
                        <option value="">-- Seleccionar tipo --</option>
                        <?php foreach ($cat['tipos'] as $t): ?>
                            <option value="<?= $t['id_tipo_dispositivo'] ?>">
                                <?= htmlspecialchars($t['nombre_tipo'] . ' (' . $t['categoria_dispositivo'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Estado Inicial <span class="text-danger">*</span></label>
                    <select name="id_estado_dispositivo" class="form-select" required>
                        <?php foreach ($cat['estados'] as $e): ?>
                            <option value="<?= $e['id_estado_dispositivo'] ?>" <?= $e['nombre_estado'] === 'EN_FABRICACION' ? 'selected' : '' ?>>
                                <?= htmlspecialchars($e['nombre_estado']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Fecha Prevista de Entrega</label>
                    <input type="date" name="fecha_entrega" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>

                <div class="col-12 mt-4 text-end">
                    <a href="dispositivos.php" class="btn btn-outline-secondary me-2">Cancelar</a>
                    <button type="submit" class="btn btn-turquesa px-4">
                        <i class="bi bi-save me-1"></i> Registrar Dispositivo
                    </button>
                </div>
            </div>
        </form>
    </div>

<?php elseif ($accion === 'ver'): ?>
    <?php
    $idVer = (int)($_GET['id'] ?? 0);
    $resShow = $dispCtrl->show($idVer);
    if (!$resShow['success']) {
        echo "<div class='alert alert-danger'>{$resShow['mensaje']}</div>";
        require_once __DIR__ . '/includes/footer.php';
        exit;
    }
    $d = $resShow['data'];
    $componentes = $dispCtrl->componentes($idVer)['data'] ?? [];
    $estados = $dispCtrl->catalogos()['estados'] ?? [];
    ?>
    <div class="card card-custom p-4 mb-4" style="max-width: 850px; margin: 0 auto;">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <div>
                <h5 class="fw-bold mb-0" style="color: var(--turquesa-oscuro);">
                    <i class="bi bi-bandaid me-2"></i>Dispositivo: <?= htmlspecialchars($d['codigo_dispositivo']) ?>
                </h5>
                <span class="text-muted small"><?= htmlspecialchars($d['nombre_tipo']) ?> (<?= htmlspecialchars($d['categoria_dispositivo']) ?>)</span>
            </div>
            <div>
                <a href="dispositivos.php" class="btn btn-sm btn-outline-secondary">Volver al listado</a>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-md-4">
                <span class="text-muted small d-block">Paciente Asignado</span>
                <span class="fw-semibold"><?= htmlspecialchars($d['nombre_paciente']) ?> [<?= htmlspecialchars($d['numero_documento']) ?>]</span>
            </div>
            <div class="col-sm-6 col-md-4">
                <span class="text-muted small d-block">Segmento / Lado</span>
                <span class="fw-semibold"><?= htmlspecialchars($d['nombre_segmento'] . ' (' . $d['lateralidad'] . ')') ?></span>
            </div>
            <div class="col-sm-6 col-md-4">
                <span class="text-muted small d-block">Estado Actual</span>
                <span class="badge badge-turquesa"><?= htmlspecialchars($d['nombre_estado']) ?></span>
            </div>
            <div class="col-sm-6 col-md-4">
                <span class="text-muted small d-block">Fecha de Entrega</span>
                <span class="fw-semibold"><?= formatear_fecha($d['fecha_entrega']) ?></span>
            </div>
            <div class="col-sm-6 col-md-4">
                <span class="text-muted small d-block">Fecha de Baja</span>
                <span class="fw-semibold"><?= formatear_fecha($d['fecha_baja']) ?></span>
            </div>
            <div class="col-sm-6 col-md-4">
                <span class="text-muted small d-block">Evaluación Vinculada</span>
                <a href="evaluaciones.php?accion=ver&id=<?= $d['id_evaluacion_biomecanica'] ?>" class="fw-semibold">
                    Eval #<?= $d['id_evaluacion_biomecanica'] ?> (<?= formatear_fecha($d['fecha_evaluacion']) ?>)
                </a>
            </div>
        </div>

        <!-- CAMBIO RÁPIDO DE ESTADO (Para Técnicos o Administradores) -->
        <?php if (Permisos::puede($rolActual, 'dispositivos', 'cambiar_estado')): ?>
            <div class="card card-custom p-3 bg-light mb-4">
                <form method="POST" action="dispositivos.php?accion=cambiar_estado" class="row g-2 align-items-center">
                    <input type="hidden" name="id_dispositivo" value="<?= $idVer ?>">
                    <div class="col-auto">
                        <label class="form-label mb-0 fw-semibold small">Cambiar Estado Operativo:</label>
                    </div>
                    <div class="col-auto">
                        <select name="id_estado_dispositivo" class="form-select form-select-sm">
                            <?php foreach ($estados as $est): ?>
                                <option value="<?= $est['id_estado_dispositivo'] ?>" <?= $est['id_estado_dispositivo'] == $d['id_estado_dispositivo'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($est['nombre_estado']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-turquesa">Actualizar Estado</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- COMPONENTES FÍSICOS INSTALADOS (Relación N:M) -->
        <h6 class="fw-bold mb-3" style="color: var(--turquesa-oscuro);">Componentes Físicos Instalados (Relación N:M)</h6>
        <?php if (empty($componentes)): ?>
            <div class="alert alert-light border small text-muted">No se registran piezas instaladas en esta prótesis. Puede asignarlas desde el módulo de Componentes.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>N° de Serie</th>
                            <th>Modelo y Categoría</th>
                            <th>Fabricante</th>
                            <th>Vida Útil (h)</th>
                            <th>Instalación</th>
                            <th>Estado de Instalación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($componentes as $comp): ?>
                            <tr>
                                <td class="fw-semibold"><code><?= htmlspecialchars($comp['numero_serie']) ?></code></td>
                                <td><?= htmlspecialchars($comp['nombre_modelo']) ?> <span class="badge badge-turquesa"><?= htmlspecialchars($comp['nombre_categoria']) ?></span></td>
                                <td><?= htmlspecialchars($comp['nombre_fabricante']) ?></td>
                                <td><?= htmlspecialchars($comp['vida_util_horas']) ?> h</td>
                                <td><?= formatear_fecha($comp['fecha_instalacion']) ?></td>
                                <td>
                                    <?php if ($comp['fecha_retiro'] === null): ?>
                                        <span class="badge bg-success">Actualmente Instalado</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Retirado el <?= formatear_fecha($comp['fecha_retiro']) ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

<?php else: ?>
    <!-- LISTADO DE DISPOSITIVOS -->
    <?php
    $dispositivos = $dispCtrl->index()['data'] ?? [];
    ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-0" style="color: var(--turquesa-oscuro);">Dispositivos Protésicos y Órtesis</h4>
            <p class="text-muted small mb-0">Total dispositivos registrados: <?= count($dispositivos) ?></p>
        </div>
        <?php if (Permisos::puede($rolActual, 'dispositivos', 'crear')): ?>
            <a href="dispositivos.php?accion=crear" class="btn btn-turquesa">
                <i class="bi bi-plus-circle me-1"></i> Registrar Dispositivo
            </a>
        <?php endif; ?>
    </div>

    <div class="card card-custom p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-custom mb-0">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Tipo de Dispositivo</th>
                        <th>Paciente</th>
                        <th>Segmento / Lado</th>
                        <th>Fecha Entrega</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dispositivos)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No se registran dispositivos protésicos.</td></tr>
                    <?php else: ?>
                        <?php foreach ($dispositivos as $dp): ?>
                            <tr>
                                <td class="fw-semibold"><code><?= htmlspecialchars($dp['codigo_dispositivo']) ?></code></td>
                                <td><?= htmlspecialchars($dp['nombre_tipo']) ?></td>
                                <td><?= htmlspecialchars($dp['nombre_paciente']) ?> [<?= htmlspecialchars($dp['numero_documento']) ?>]</td>
                                <td><?= htmlspecialchars($dp['nombre_segmento'] . ' (' . $dp['lateralidad'] . ')') ?></td>
                                <td><?= formatear_fecha($dp['fecha_entrega']) ?></td>
                                <td>
                                    <?php
                                    $badgeCls = match ($dp['nombre_estado']) {
                                        'ENTREGADO'      => 'bg-success',
                                        'EN_PRUEBAS'     => 'bg-info text-dark',
                                        'EN_FABRICACION' => 'bg-warning text-dark',
                                        'DE_BAJA'        => 'bg-secondary',
                                        default          => 'badge-turquesa'
                                    };
                                    ?>
                                    <span class="badge <?= $badgeCls ?>"><?= htmlspecialchars($dp['nombre_estado']) ?></span>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="dispositivos.php?accion=ver&id=<?= $dp['id_dispositivo_protesico'] ?>" class="btn btn-sm btn-outline-secondary" title="Ver detalle y componentes">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if (Permisos::puede($rolActual, 'dispositivos', 'eliminar')): ?>
                                        <form method="POST" action="dispositivos.php?accion=eliminar" class="d-inline" onsubmit="return confirm('¿Está seguro de eliminar el dispositivo <?= htmlspecialchars($dp['codigo_dispositivo']) ?>?');">
                                            <input type="hidden" name="id_dispositivo" value="<?= $dp['id_dispositivo_protesico'] ?>">
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
