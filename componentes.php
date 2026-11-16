<?php
/**
 * Módulo: Gestión de Componentes y Piezas Físicas
 * Asignatura: Bases de Datos - Ingeniería de Sistemas (ULA)
 */

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/controllers/ComponenteController.php';

$pageTitle = 'Componentes Protésicos';
$activeMenu = 'componentes';

requerir_permiso('componentes', 'listar');

$compCtrl = new ComponenteController();
$accion = $_GET['accion'] ?? 'listar';
$error = null;

// PROCESAMIENTO POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($accion === 'crear') {
        requerir_permiso('componentes', 'crear');
        $datos = [
            'id_modelo_componente'      => (int)($_POST['id_modelo_componente'] ?? 0),
            'id_estado_componente'      => (int)($_POST['id_estado_componente'] ?? 1),
            'numero_serie'              => trim($_POST['numero_serie'] ?? ''),
            'fecha_adquisicion'         => trim($_POST['fecha_adquisicion'] ?? date('Y-m-d')),
            'horas_desgaste_acumuladas' => (float)($_POST['horas_desgaste_acumuladas'] ?? 0),
        ];

        $res = $compCtrl->store($datos);
        if ($res['success']) {
            $_SESSION['flash_success'] = 'Componente físico registrado exitosamente.';
            header('Location: componentes.php');
            exit;
        } else {
            $error = $res['mensaje'] . (isset($res['sqlstate']) ? " (SQLSTATE {$res['sqlstate']})" : '');
        }
    }

    if ($accion === 'instalar') {
        requerir_permiso('componentes', 'instalar');
        $idComp = (int)($_POST['id_componente'] ?? 0);
        $idDisp = (int)($_POST['id_dispositivo'] ?? 0);
        $fecha  = trim($_POST['fecha_instalacion'] ?? date('Y-m-d'));

        $res = $compCtrl->instalar($idComp, $idDisp, $fecha);
        if ($res['success']) {
            // Actualizar estado del componente a INSTALADO
            $db = Database::getConnection();
            $db->exec("UPDATE componente SET id_estado_componente = (SELECT id_estado_componente FROM estado_componente WHERE nombre_estado = 'INSTALADO') WHERE id_componente = {$idComp}");
            $_SESSION['flash_success'] = 'Pieza instalada satisfactoriamente en el dispositivo.';
        } else {
            $_SESSION['flash_error'] = $res['mensaje'];
        }
        header('Location: componentes.php');
        exit;
    }

    if ($accion === 'eliminar') {
        requerir_permiso('componentes', 'eliminar');
        $id = (int)($_POST['id_componente'] ?? 0);
        $res = $compCtrl->destroy($id);
        if ($res['success']) {
            $_SESSION['flash_success'] = 'Componente eliminado de la base de datos.';
        } else {
            $_SESSION['flash_error'] = $res['mensaje'];
        }
        header('Location: componentes.php');
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($accion === 'crear'): ?>
    <?php
    $cat = $compCtrl->catalogos();
    ?>
    <div class="card card-custom p-4" style="max-width: 750px; margin: 0 auto;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0" style="color: var(--turquesa-oscuro);">Registrar Nuevo Componente Físico</h5>
            <a href="componentes.php" class="btn btn-sm btn-outline-secondary">Cancelar</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger small py-2 px-3 mb-3"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="componentes.php?accion=crear">
            <div class="row g-3">
                <div class="col-md-12">
                    <label class="form-label">Modelo y Tipo de Componente <span class="text-danger">*</span></label>
                    <select name="id_modelo_componente" class="form-select" required>
                        <option value="">-- Seleccione el modelo técnico --</option>
                        <?php foreach ($cat['modelos'] as $m): ?>
                            <option value="<?= $m['id_modelo_componente'] ?>">
                                <?= htmlspecialchars("{$m['nombre_modelo']} ({$m['nombre_categoria']}) - Fabricante: {$m['nombre_fabricante']} [Vida útil: {$m['vida_util_horas']} h]") ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Número de Serie Único <span class="text-danger">*</span></label>
                    <input type="text" name="numero_serie" class="form-control" required
                           placeholder="Ej: PIE-TAL-0002"
                           value="CMP-<?= strtoupper(substr(md5(uniqid()), 0, 8)) ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Estado Inicial <span class="text-danger">*</span></label>
                    <select name="id_estado_componente" class="form-select" required>
                        <?php foreach ($cat['estados'] as $e): ?>
                            <option value="<?= $e['id_estado_componente'] ?>" <?= $e['nombre_estado'] === 'DISPONIBLE' ? 'selected' : '' ?>>
                                <?= htmlspecialchars($e['nombre_estado']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Fecha de Adquisición</label>
                    <input type="date" name="fecha_adquisicion" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Horas de Desgaste Iniciales</label>
                    <input type="number" step="0.1" min="0" name="horas_desgaste_acumuladas" class="form-control" value="0.0">
                </div>

                <div class="col-12 mt-4 text-end">
                    <a href="componentes.php" class="btn btn-outline-secondary me-2">Cancelar</a>
                    <button type="submit" class="btn btn-turquesa px-4">
                        <i class="bi bi-save me-1"></i> Registrar Componente
                    </button>
                </div>
            </div>
        </form>
    </div>

<?php elseif ($accion === 'ver'): ?>
    <?php
    $idVer = (int)($_GET['id'] ?? 0);
    $resShow = $compCtrl->show($idVer);
    if (!$resShow['success']) {
        echo "<div class='alert alert-danger'>{$resShow['mensaje']}</div>";
        require_once __DIR__ . '/includes/footer.php';
        exit;
    }
    $c = $resShow['data'];
    $compatibles = $compCtrl->compatibles($c['id_modelo_componente'])['data'] ?? [];
    $dispositivos = $compCtrl->catalogos()['dispositivos'] ?? [];
    ?>
    <div class="card card-custom p-4 mb-4" style="max-width: 850px; margin: 0 auto;">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <div>
                <h5 class="fw-bold mb-0" style="color: var(--turquesa-oscuro);">
                    <i class="bi bi-cpu me-2"></i>Componente: <code><?= htmlspecialchars($c['numero_serie']) ?></code>
                </h5>
                <span class="text-muted small"><?= htmlspecialchars($c['nombre_modelo']) ?> (<?= htmlspecialchars($c['nombre_categoria']) ?>)</span>
            </div>
            <a href="componentes.php" class="btn btn-sm btn-outline-secondary">Volver al listado</a>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-md-4">
                <span class="text-muted small d-block">Fabricante</span>
                <span class="fw-semibold"><?= htmlspecialchars($c['nombre_fabricante']) ?></span>
            </div>
            <div class="col-sm-6 col-md-4">
                <span class="text-muted small d-block">Estado Físico</span>
                <span class="badge badge-turquesa"><?= htmlspecialchars($c['nombre_estado']) ?></span>
            </div>
            <div class="col-sm-6 col-md-4">
                <span class="text-muted small d-block">Fecha de Adquisición</span>
                <span class="fw-semibold"><?= formatear_fecha($c['fecha_adquisicion']) ?></span>
            </div>
            <div class="col-sm-6 col-md-4">
                <span class="text-muted small d-block">Horas Desgaste / Vida Útil</span>
                <span class="fw-semibold"><?= htmlspecialchars($c['horas_desgaste_acumuladas']) ?> h / <?= htmlspecialchars($c['vida_util_horas']) ?> h</span>
            </div>
            <div class="col-sm-6 col-md-4">
                <span class="text-muted small d-block">Dispositivo Actual</span>
                <?php if (!empty($c['codigo_dispositivo'])): ?>
                    <span class="badge bg-success">Instalado en <?= htmlspecialchars($c['codigo_dispositivo']) ?></span>
                <?php else: ?>
                    <span class="badge bg-secondary">Sin instalar (En almacén)</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- INSTALAR EN DISPOSITIVO (Si está disponible y tiene permiso) -->
        <?php if (empty($c['codigo_dispositivo']) && Permisos::puede($rolActual, 'componentes', 'instalar')): ?>
            <div class="card card-custom p-3 bg-light mb-4">
                <h6 class="fw-bold small mb-2 text-primary">Instalar esta pieza en un Dispositivo:</h6>
                <form method="POST" action="componentes.php?accion=instalar" class="row g-2 align-items-center">
                    <input type="hidden" name="id_componente" value="<?= $idVer ?>">
                    <div class="col-md-7">
                        <select name="id_dispositivo" class="form-select form-select-sm" required>
                            <option value="">-- Seleccionar prótesis destino --</option>
                            <?php foreach ($dispositivos as $disp): ?>
                                <option value="<?= $disp['id_dispositivo_protesico'] ?>">
                                    <?= htmlspecialchars("{$disp['codigo_dispositivo']} (Paciente: {$disp['paciente']})") ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="date" name="fecha_instalacion" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-turquesa w-100">Instalar</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- MODELOS COMPATIBLES -->
        <h6 class="fw-bold mb-3" style="color: var(--turquesa-oscuro);">Modelos Compatibles y Acoples</h6>
        <?php if (empty($compatibles)): ?>
            <div class="alert alert-light border small text-muted">No se registran restricciones de acople o compatibilidad directa para este modelo.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Modelo Compatible</th>
                            <th>Fabricante</th>
                            <th>Descripción del Acople</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($compatibles as $comp): ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($comp['modelo_compatible']) ?></td>
                                <td><?= htmlspecialchars($comp['nombre_fabricante']) ?></td>
                                <td class="text-muted small"><?= htmlspecialchars($comp['descripcion'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

<?php else: ?>
    <!-- LISTADO DE COMPONENTES -->
    <?php
    $componentes = $compCtrl->index()['data'] ?? [];
    ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-0" style="color: var(--turquesa-oscuro);">Catálogo de Componentes Físicos</h4>
            <p class="text-muted small mb-0">Total componentes registrados: <?= count($componentes) ?></p>
        </div>
        <?php if (Permisos::puede($rolActual, 'componentes', 'crear')): ?>
            <a href="componentes.php?accion=crear" class="btn btn-turquesa">
                <i class="bi bi-plus-circle me-1"></i> Registrar Componente
            </a>
        <?php endif; ?>
    </div>

    <div class="card card-custom p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-custom mb-0">
                <thead>
                    <tr>
                        <th>N° de Serie</th>
                        <th>Modelo</th>
                        <th>Categoría</th>
                        <th>Fabricante</th>
                        <th>Desgaste / Vida Útil</th>
                        <th>Estado</th>
                        <th>Instalado En</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($componentes)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">No se registran componentes físicos.</td></tr>
                    <?php else: ?>
                        <?php foreach ($componentes as $c): ?>
                            <tr>
                                <td class="fw-semibold"><code><?= htmlspecialchars($c['numero_serie']) ?></code></td>
                                <td><?= htmlspecialchars($c['nombre_modelo']) ?></td>
                                <td><span class="badge badge-turquesa"><?= htmlspecialchars($c['nombre_categoria']) ?></span></td>
                                <td><?= htmlspecialchars($c['nombre_fabricante']) ?></td>
                                <td><?= htmlspecialchars($c['horas_desgaste_acumuladas']) ?> h / <?= htmlspecialchars($c['vida_util_horas']) ?> h</td>
                                <td>
                                    <?php
                                    $bCls = match ($c['nombre_estado']) {
                                        'DISPONIBLE' => 'bg-info text-dark',
                                        'INSTALADO'  => 'bg-success',
                                        'EN_REVISION'=> 'bg-warning text-dark',
                                        'DESCARTADO' => 'bg-secondary',
                                        default      => 'badge-turquesa'
                                    };
                                    ?>
                                    <span class="badge <?= $bCls ?>"><?= htmlspecialchars($c['nombre_estado']) ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($c['codigo_dispositivo'])): ?>
                                        <a href="dispositivos.php?accion=ver&id=<?= $c['id_dispositivo_protesico'] ?>" class="text-success fw-semibold">
                                            <i class="bi bi-link-45deg"></i> <?= htmlspecialchars($c['codigo_dispositivo']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">Almacén</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="componentes.php?accion=ver&id=<?= $c['id_componente'] ?>" class="btn btn-sm btn-outline-secondary" title="Ver detalle y compatibilidad">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if (Permisos::puede($rolActual, 'componentes', 'eliminar')): ?>
                                        <form method="POST" action="componentes.php?accion=eliminar" class="d-inline" onsubmit="return confirm('¿Está seguro de eliminar el componente <?= htmlspecialchars($c['numero_serie']) ?>?');">
                                            <input type="hidden" name="id_componente" value="<?= $c['id_componente'] ?>">
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
