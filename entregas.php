<?php
/**
 * Módulo: Entrega de Piezas Protésicas
 * Asignatura: Bases de Datos - Ingeniería de Sistemas (ULA)
 */

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/controllers/EntregaController.php';

$pageTitle = 'Entrega de Piezas';
$activeMenu = 'entregas';

requerir_permiso('entregas', 'listar');

$entregaCtrl = new EntregaController();
$accion = $_GET['accion'] ?? 'listar';
$error = null;

// PROCESAMIENTO POST (Crear / Editar / Eliminar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($accion === 'crear') {
        requerir_permiso('entregas', 'crear');
        $datos = [
            'id_paciente'         => (int)($_POST['id_paciente'] ?? 0),
            'id_pieza'            => (int)($_POST['id_pieza'] ?? 0),
            'id_usuario_sistema'  => $usuarioActual['id_usuario_sistema'],
            'fecha_entrega'       => trim($_POST['fecha_entrega'] ?? date('Y-m-d')),
            'estado_entrega'      => trim($_POST['estado_entrega'] ?? 'ENTREGADO'),
            'observaciones'       => trim($_POST['observaciones'] ?? '')
        ];

        $res = $entregaCtrl->store($datos);
        if ($res['success']) {
            $_SESSION['flash_success'] = 'Entrega de pieza registrada exitosamente en el historial.';
            header('Location: entregas.php?accion=ver&id=' . $res['id']);
            exit;
        } else {
            $error = $res['mensaje'] . (isset($res['sqlstate']) ? " (SQLSTATE {$res['sqlstate']})" : '');
        }
    }

    if ($accion === 'editar') {
        requerir_permiso('entregas', 'editar');
        $id = (int)($_POST['id_entrega'] ?? 0);
        $datos = [
            'id_paciente'    => (int)($_POST['id_paciente'] ?? 0),
            'id_pieza'       => (int)($_POST['id_pieza'] ?? 0),
            'fecha_entrega'  => trim($_POST['fecha_entrega'] ?? date('Y-m-d')),
            'estado_entrega' => trim($_POST['estado_entrega'] ?? 'ENTREGADO'),
            'observaciones'  => trim($_POST['observaciones'] ?? '')
        ];

        $res = $entregaCtrl->update($id, $datos);
        if ($res['success']) {
            $_SESSION['flash_success'] = 'Registro de entrega actualizado correctamente.';
            header('Location: entregas.php');
            exit;
        } else {
            $error = $res['mensaje'] . (isset($res['sqlstate']) ? " (SQLSTATE {$res['sqlstate']})" : '');
        }
    }

    if ($accion === 'eliminar') {
        requerir_permiso('entregas', 'eliminar');
        $id = (int)($_POST['id_entrega'] ?? 0);
        $res = $entregaCtrl->destroy($id);
        if ($res['success']) {
            $_SESSION['flash_success'] = 'Entrega eliminada del historial y pieza liberada a inventario disponible.';
        } else {
            $_SESSION['flash_error'] = $res['mensaje'];
        }
        header('Location: entregas.php');
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($accion === 'crear' || $accion === 'editar'): ?>
    <?php
    $entregaEditar = null;
    $piezaSeleccionadaId = null;
    if ($accion === 'editar') {
        $idEditar = (int)($_GET['id'] ?? 0);
        $resShow = $entregaCtrl->show($idEditar);
        if ($resShow['success']) {
            $entregaEditar = $resShow['data'];
            $piezaSeleccionadaId = (int)$entregaEditar['id_pieza'];
        } else {
            $_SESSION['flash_error'] = $resShow['mensaje'];
            header('Location: entregas.php');
            exit;
        }
    }
    $pacientes = $entregaCtrl->getPacientes();
    $piezasDisponibles = $entregaCtrl->getPiezasDisponibles($piezaSeleccionadaId);
    ?>
    <div class="card card-custom p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1" style="color: var(--turquesa-oscuro);">
                    <?= ($accion === 'crear') ? 'Registrar Entrega de Pieza a Paciente' : 'Editar Entrega #' . $entregaEditar['id_entrega'] ?>
                </h4>
                <p class="text-muted small mb-0">Asociación de prótesis o piezas protésicas con el paciente receptor</p>
            </div>
            <a href="entregas.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver al Historial
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger mb-4 py-2 small">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="entregas.php?accion=<?= $accion ?>" class="row g-3">
            <?php if ($accion === 'editar'): ?>
                <input type="hidden" name="id_entrega" value="<?= $entregaEditar['id_entrega'] ?>">
            <?php endif; ?>

            <div class="col-md-6">
                <label class="form-label">Paciente Receptor <span class="text-danger">*</span></label>
                <select name="id_paciente" class="form-select" required>
                    <option value="">-- Seleccione Paciente --</option>
                    <?php foreach ($pacientes as $pac): ?>
                        <?php 
                        $sel = (isset($_POST['id_paciente']) && $_POST['id_paciente'] == $pac['id_paciente']) 
                            || (isset($entregaEditar['id_paciente']) && $entregaEditar['id_paciente'] == $pac['id_paciente']);
                        ?>
                        <option value="<?= $pac['id_paciente'] ?>" <?= $sel ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pac['apellidos'] . ', ' . $pac['nombres']) ?> [<?= htmlspecialchars($pac['numero_documento']) ?>]
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Únicamente se muestran pacientes activos en el sistema.</div>
            </div>

            <div class="col-md-6">
                <label class="form-label">Pieza Protésica a Entregar <span class="text-danger">*</span></label>
                <select name="id_pieza" class="form-select" required>
                    <option value="">-- Seleccione Pieza Disponible --</option>
                    <?php foreach ($piezasDisponibles as $pz): ?>
                        <?php 
                        $sel = (isset($_POST['id_pieza']) && $_POST['id_pieza'] == $pz['id_pieza']) 
                            || (isset($entregaEditar['id_pieza']) && $entregaEditar['id_pieza'] == $pz['id_pieza']);
                        ?>
                        <option value="<?= $pz['id_pieza'] ?>" <?= $sel ? 'selected' : '' ?>>
                            [<?= htmlspecialchars($pz['codigo_pieza']) ?>] <?= htmlspecialchars($pz['nombre_pieza']) ?> (<?= htmlspecialchars($pz['nombre_tipo']) ?>) - SN: <?= htmlspecialchars($pz['numero_serie']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Solo aparecen piezas en estado DISPONIBLE.</div>
            </div>

            <div class="col-md-6">
                <label class="form-label">Fecha de Entrega <span class="text-danger">*</span></label>
                <input type="date" name="fecha_entrega" class="form-control" required
                       value="<?= htmlspecialchars($_POST['fecha_entrega'] ?? $entregaEditar['fecha_entrega'] ?? date('Y-m-d')) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Estado de la Entrega <span class="text-danger">*</span></label>
                <select name="estado_entrega" class="form-select" required>
                    <?php
                    $estados = ['ENTREGADO', 'EN_PRUEBA', 'DEVUELTO', 'FINALIZADO'];
                    $estActual = $_POST['estado_entrega'] ?? $entregaEditar['estado_entrega'] ?? 'ENTREGADO';
                    foreach ($estados as $est):
                    ?>
                        <option value="<?= $est ?>" <?= ($estActual === $est) ? 'selected' : '' ?>>
                            <?= $est ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label">Observaciones de la Entrega</label>
                <textarea name="observaciones" class="form-control" rows="3" 
                          placeholder="Indique detalles de adaptación, alineación o condiciones de entrega al paciente..."><?= htmlspecialchars($_POST['observaciones'] ?? $entregaEditar['observaciones'] ?? '') ?></textarea>
            </div>

            <div class="col-12 mt-4 text-end">
                <a href="entregas.php" class="btn btn-outline-secondary me-2">Cancelar</a>
                <button type="submit" class="btn btn-turquesa">
                    <i class="bi bi-save me-1"></i> Guardar Entrega
                </button>
            </div>
        </form>
    </div>

<?php elseif ($accion === 'ver'): ?>
    <?php
    $idVer = (int)($_GET['id'] ?? 0);
    $resShow = $entregaCtrl->show($idVer);
    if (!$resShow['success']) {
        $_SESSION['flash_error'] = $resShow['mensaje'];
        header('Location: entregas.php');
        exit;
    }
    $e = $resShow['data'];
    ?>
    <div class="card card-custom p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1" style="color: var(--turquesa-oscuro);">
                    Detalle de Entrega #<?= $e['id_entrega'] ?>
                </h4>
                <p class="text-muted small mb-0">Fecha de entrega: <strong><?= htmlspecialchars($e['fecha_entrega']) ?></strong></p>
            </div>
            <div>
                <?php if (Permisos::puede($rolActual, 'entregas', 'editar')): ?>
                    <a href="entregas.php?accion=editar&id=<?= $e['id_entrega'] ?>" class="btn btn-sm btn-outline-primary me-2">
                        <i class="bi bi-pencil me-1"></i> Editar
                    </a>
                <?php endif; ?>
                <a href="entregas.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Volver al Historial
                </a>
            </div>
        </div>

        <div class="row g-4">
            <!-- Datos del Paciente -->
            <div class="col-md-6">
                <div class="card h-100 border">
                    <div class="card-header bg-white fw-bold" style="color: var(--turquesa-oscuro);">
                        <i class="bi bi-person me-1"></i> Información del Paciente
                    </div>
                    <div class="card-body">
                        <h5 class="fw-bold text-dark"><?= htmlspecialchars($e['paciente_apellidos'] . ', ' . $e['paciente_nombres']) ?></h5>
                        <p class="text-muted mb-2">Cédula / Documento: <strong><?= htmlspecialchars($e['numero_documento']) ?></strong></p>
                        <p class="text-muted mb-2">Teléfono: <?= htmlspecialchars($e['telefono'] ?? 'No registrado') ?></p>
                        <p class="text-muted mb-0">Correo: <?= htmlspecialchars($e['correo'] ?? 'No registrado') ?></p>
                    </div>
                </div>
            </div>

            <!-- Datos de la Pieza -->
            <div class="col-md-6">
                <div class="card h-100 border">
                    <div class="card-header bg-white fw-bold" style="color: var(--turquesa-oscuro);">
                        <i class="bi bi-tools me-1"></i> Información de la Pieza Entregada
                    </div>
                    <div class="card-body">
                        <h5 class="fw-bold text-dark"><?= htmlspecialchars($e['nombre_pieza']) ?></h5>
                        <p class="text-muted mb-2">Código: <code><?= htmlspecialchars($e['codigo_pieza']) ?></code></p>
                        <p class="text-muted mb-2">Tipo / Categoría: <strong><?= htmlspecialchars($e['nombre_tipo']) ?></strong></p>
                        <p class="text-muted mb-2">Fabricante: <?= htmlspecialchars($e['nombre_fabricante']) ?></p>
                        <p class="text-muted mb-0">N° Serie: <span class="font-monospace"><?= htmlspecialchars($e['numero_serie']) ?></span></p>
                    </div>
                </div>
            </div>

            <!-- Datos del Registro -->
            <div class="col-12">
                <div class="p-3 rounded bg-light border">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="text-muted small">Estado de Entrega</div>
                            <div><span class="badge badge-turquesa fs-6"><?= htmlspecialchars($e['estado_entrega']) ?></span></div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Registrado por</div>
                            <div class="fw-semibold text-dark"><?= htmlspecialchars($e['tecnico_nombre'] ?? $e['nombre_usuario']) ?> (<code><?= htmlspecialchars($e['nombre_usuario']) ?></code>)</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Fecha y Hora de Registro</div>
                            <div class="fw-semibold text-dark"><?= htmlspecialchars($e['fecha_registro']) ?></div>
                        </div>
                        <?php if (!empty($e['observaciones'])): ?>
                        <div class="col-12">
                            <div class="text-muted small mb-1">Observaciones</div>
                            <div class="p-2 bg-white rounded border"><?= nl2br(htmlspecialchars($e['observaciones'])) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <?php
    $buscar = trim($_GET['buscar'] ?? '');
    $estadoFiltro = trim($_GET['estado'] ?? '');

    $res = $entregaCtrl->index($buscar ?: null, $estadoFiltro ?: null);
    $entregas = $res['success'] ? $res['data'] : [];
    ?>

    <div class="card card-custom p-4 mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h4 class="fw-bold mb-1" style="color: var(--turquesa-oscuro);">Historial de Entrega de Piezas</h4>
                <p class="text-muted small mb-0">Registro y trazabilidad de piezas protésicas entregadas a pacientes</p>
            </div>
            <?php if (Permisos::puede($rolActual, 'entregas', 'crear')): ?>
                <div>
                    <a href="entregas.php?accion=crear" class="btn btn-turquesa">
                        <i class="bi bi-plus-lg me-1"></i> Nueva Entrega
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Búsqueda y Filtros con SQL -->
        <form method="GET" action="entregas.php" class="row g-2 mb-4">
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="buscar" class="form-control" 
                           placeholder="Buscar por paciente, cédula, modelo o código de pieza..." 
                           value="<?= htmlspecialchars($buscar) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="estado" class="form-select">
                    <option value="">-- Todos los Estados --</option>
                    <option value="ENTREGADO" <?= ($estadoFiltro === 'ENTREGADO') ? 'selected' : '' ?>>ENTREGADO</option>
                    <option value="EN_PRUEBA" <?= ($estadoFiltro === 'EN_PRUEBA') ? 'selected' : '' ?>>EN_PRUEBA</option>
                    <option value="DEVUELTO" <?= ($estadoFiltro === 'DEVUELTO') ? 'selected' : '' ?>>DEVUELTO</option>
                    <option value="FINALIZADO" <?= ($estadoFiltro === 'FINALIZADO') ? 'selected' : '' ?>>FINALIZADO</option>
                </select>
            </div>
            <div class="col-md-1 d-grid">
                <button type="submit" class="btn btn-outline-secondary" title="Filtrar">
                    <i class="bi bi-funnel"></i>
                </button>
            </div>
        </form>

        <!-- Tabla de Entregas -->
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Paciente</th>
                        <th>Pieza Protésica</th>
                        <th>Tipo / Fabricante</th>
                        <th>N° Serie</th>
                        <th>Registrado por</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($entregas)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>
                                No se encontraron registros de entrega con los criterios especificados.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($entregas as $ent): ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($ent['fecha_entrega']) ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($ent['apellidos'] . ', ' . $ent['nombres']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($ent['numero_documento']) ?></small>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($ent['nombre_pieza']) ?></div>
                                    <code><?= htmlspecialchars($ent['codigo_pieza']) ?></code>
                                </td>
                                <td>
                                    <div><?= htmlspecialchars($ent['nombre_tipo']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($ent['nombre_fabricante']) ?></small>
                                </td>
                                <td class="font-monospace small"><?= htmlspecialchars($ent['numero_serie']) ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($ent['tecnico_nombre'] ?? $ent['nombre_usuario']) ?></td>
                                <td>
                                    <?php if ($ent['estado_entrega'] === 'ENTREGADO'): ?>
                                        <span class="badge badge-turquesa">ENTREGADO</span>
                                    <?php elseif ($ent['estado_entrega'] === 'EN_PRUEBA'): ?>
                                        <span class="badge bg-warning text-dark">EN_PRUEBA</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($ent['estado_entrega']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="entregas.php?accion=ver&id=<?= $ent['id_entrega'] ?>" 
                                           class="btn btn-outline-secondary" title="Ver Detalle">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (Permisos::puede($rolActual, 'entregas', 'editar')): ?>
                                            <a href="entregas.php?accion=editar&id=<?= $ent['id_entrega'] ?>" 
                                               class="btn btn-outline-primary" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (Permisos::puede($rolActual, 'entregas', 'eliminar')): ?>
                                            <form method="POST" action="entregas.php?accion=eliminar" class="d-inline"
                                                  onsubmit="return confirm('¿Está seguro de eliminar esta entrega? La pieza volverá a estar disponible.');">
                                                <input type="hidden" name="id_entrega" value="<?= $ent['id_entrega'] ?>">
                                                <button type="submit" class="btn btn-outline-danger" title="Eliminar">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
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
