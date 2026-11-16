<?php
/**
 * Módulo: Historial de Ajustes Técnicos
 * Asignatura: Bases de Datos - Ingeniería de Sistemas (ULA)
 */

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/controllers/HistorialAjusteController.php';

$pageTitle = 'Historial de Ajustes';
$activeMenu = 'ajustes';

requerir_permiso('ajustes', 'listar');

$ajusteCtrl = new HistorialAjusteController();
$accion = $_GET['accion'] ?? 'listar';
$error = null;

// PROCESAMIENTO POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($accion === 'crear') {
        requerir_permiso('ajustes', 'crear');
        $datos = [
            'id_dispositivo_protesico'   => (int)($_POST['id_dispositivo_protesico'] ?? 0),
            'id_tipo_ajuste'             => (int)($_POST['id_tipo_ajuste'] ?? 0),
            'id_usuario_sistema_tecnico' => $usuarioActual['id_usuario_sistema'],
            'fecha_ajuste'               => trim($_POST['fecha_ajuste'] ?? date('Y-m-d')),
            'descripcion_ajuste'         => trim($_POST['descripcion_ajuste'] ?? '')
        ];

        $res = $ajusteCtrl->store($datos);
        if ($res['success']) {
            $_SESSION['flash_success'] = 'Ajuste técnico registrado exitosamente.';
            header('Location: ajustes.php');
            exit;
        } else {
            $error = $res['mensaje'] . (isset($res['sqlstate']) ? " (SQLSTATE {$res['sqlstate']})" : '');
        }
    }

    if ($accion === 'eliminar') {
        requerir_permiso('ajustes', 'eliminar');
        $id = (int)($_POST['id_ajuste'] ?? 0);
        $res = $ajusteCtrl->destroy($id);
        if ($res['success']) {
            $_SESSION['flash_success'] = 'Registro de ajuste eliminado correctamente.';
        } else {
            $_SESSION['flash_error'] = $res['mensaje'];
        }
        header('Location: ajustes.php');
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($accion === 'crear'): ?>
    <?php
    $cat = $ajusteCtrl->catalogos();
    ?>
    <div class="card card-custom p-4" style="max-width: 750px; margin: 0 auto;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0" style="color: var(--turquesa-oscuro);">Registrar Ajuste Técnico</h5>
            <a href="ajustes.php" class="btn btn-sm btn-outline-secondary">Cancelar</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger small py-2 px-3 mb-3"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="ajustes.php?accion=crear">
            <div class="row g-3">
                <div class="col-md-12">
                    <label class="form-label">Dispositivo Protésico <span class="text-danger">*</span></label>
                    <select name="id_dispositivo_protesico" class="form-select" required>
                        <option value="">-- Seleccionar prótesis a intervenir --</option>
                        <?php foreach ($cat['dispositivos'] as $d): ?>
                            <option value="<?= $d['id_dispositivo_protesico'] ?>">
                                <?= htmlspecialchars("{$d['codigo_dispositivo']} (Paciente: {$d['paciente']})") ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Tipo de Ajuste <span class="text-danger">*</span></label>
                    <select name="id_tipo_ajuste" class="form-select" required>
                        <option value="">-- Seleccionar tipo de ajuste --</option>
                        <?php foreach ($cat['tipos'] as $t): ?>
                            <option value="<?= $t['id_tipo_ajuste'] ?>">
                                <?= htmlspecialchars($t['nombre_ajuste']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Fecha de Realización <span class="text-danger">*</span></label>
                    <input type="date" name="fecha_ajuste" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="col-12">
                    <label class="form-label">Descripción Técnica del Ajuste <span class="text-danger">*</span></label>
                    <textarea name="descripcion_ajuste" class="form-control" rows="3" required
                              placeholder="Indique las modificaciones realizadas: ajuste de socket, media de ajuste, alineación..."></textarea>
                </div>

                <div class="col-12 mt-4 text-end">
                    <a href="ajustes.php" class="btn btn-outline-secondary me-2">Cancelar</a>
                    <button type="submit" class="btn btn-turquesa px-4">
                        <i class="bi bi-save me-1"></i> Guardar Ajuste
                    </button>
                </div>
            </div>
        </form>
    </div>

<?php elseif ($accion === 'ver'): ?>
    <?php
    $idVer = (int)($_GET['id'] ?? 0);
    $resShow = $ajusteCtrl->show($idVer);
    if (!$resShow['success']) {
        echo "<div class='alert alert-danger'>{$resShow['mensaje']}</div>";
        require_once __DIR__ . '/includes/footer.php';
        exit;
    }
    $a = $resShow['data'];
    ?>
    <div class="card card-custom p-4 mb-4" style="max-width: 800px; margin: 0 auto;">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <div>
                <h5 class="fw-bold mb-0" style="color: var(--turquesa-oscuro);">
                    <i class="bi bi-tools me-2"></i>Ajuste Técnico #<?= $a['id_historial_ajuste'] ?>
                </h5>
                <span class="text-muted small">Realizado el <?= formatear_fecha($a['fecha_ajuste']) ?></span>
            </div>
            <a href="ajustes.php" class="btn btn-sm btn-outline-secondary">Volver al listado</a>
        </div>

        <div class="row g-3">
            <div class="col-sm-6">
                <span class="text-muted small d-block">Dispositivo Protésico</span>
                <span class="fw-semibold"><code><?= htmlspecialchars($a['codigo_dispositivo']) ?></code></span>
            </div>
            <div class="col-sm-6">
                <span class="text-muted small d-block">Paciente</span>
                <span class="fw-semibold"><?= htmlspecialchars($a['nombre_paciente']) ?> [<?= htmlspecialchars($a['numero_documento']) ?>]</span>
            </div>
            <div class="col-sm-6">
                <span class="text-muted small d-block">Tipo de Ajuste</span>
                <span class="badge badge-turquesa"><?= htmlspecialchars($a['nombre_ajuste']) ?></span>
            </div>
            <div class="col-sm-6">
                <span class="text-muted small d-block">Técnico Ortopédico Responsable</span>
                <span class="fw-semibold"><?= htmlspecialchars($a['tecnico']) ?></span>
            </div>
            <div class="col-12 mt-3">
                <span class="text-muted small d-block">Descripción Detallada del Ajuste</span>
                <div class="p-3 bg-light rounded mt-1 border">
                    <?= nl2br(htmlspecialchars($a['descripcion_ajuste'])) ?>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- LISTADO DE AJUSTES -->
    <?php
    $ajustes = $ajusteCtrl->index()['data'] ?? [];
    ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-0" style="color: var(--turquesa-oscuro);">Historial de Ajustes Técnicos</h4>
            <p class="text-muted small mb-0">Total intervenciones registradas: <?= count($ajustes) ?></p>
        </div>
        <?php if (Permisos::puede($rolActual, 'ajustes', 'crear')): ?>
            <a href="ajustes.php?accion=crear" class="btn btn-turquesa">
                <i class="bi bi-plus-circle me-1"></i> Registrar Ajuste
            </a>
        <?php endif; ?>
    </div>

    <div class="card card-custom p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-custom mb-0">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Dispositivo</th>
                        <th>Paciente</th>
                        <th>Tipo de Ajuste</th>
                        <th>Técnico Responsable</th>
                        <th>Descripción</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ajustes)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No se registran ajustes técnicos en el historial.</td></tr>
                    <?php else: ?>
                        <?php foreach ($ajustes as $aj): ?>
                            <tr>
                                <td class="fw-semibold"><?= formatear_fecha($aj['fecha_ajuste']) ?></td>
                                <td><code><?= htmlspecialchars($aj['codigo_dispositivo']) ?></code></td>
                                <td><?= htmlspecialchars($aj['nombre_paciente']) ?> [<?= htmlspecialchars($aj['numero_documento']) ?>]</td>
                                <td><span class="badge badge-turquesa"><?= htmlspecialchars($aj['nombre_ajuste']) ?></span></td>
                                <td><?= htmlspecialchars($aj['tecnico']) ?></td>
                                <td class="small text-muted text-truncate" style="max-width: 280px;">
                                    <?= htmlspecialchars($aj['descripcion_ajuste']) ?>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="ajustes.php?accion=ver&id=<?= $aj['id_historial_ajuste'] ?>" class="btn btn-sm btn-outline-secondary" title="Ver detalle">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if (Permisos::puede($rolActual, 'ajustes', 'eliminar')): ?>
                                        <form method="POST" action="ajustes.php?accion=eliminar" class="d-inline" onsubmit="return confirm('¿Está seguro de eliminar este registro de ajuste?');">
                                            <input type="hidden" name="id_ajuste" value="<?= $aj['id_historial_ajuste'] ?>">
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
