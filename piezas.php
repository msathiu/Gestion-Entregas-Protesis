<?php
/**
 * Módulo: Registro de Piezas Protésicas
 * Asignatura: Bases de Datos - Ingeniería de Sistemas (ULA)
 */

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/controllers/PiezaController.php';

$pageTitle = 'Registro de Piezas';
$activeMenu = 'piezas';

requerir_permiso('piezas', 'listar');

$piezaCtrl = new PiezaController();
$accion = $_GET['accion'] ?? 'listar';
$error = null;

// PROCESAMIENTO POST (Crear / Editar / Eliminar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($accion === 'crear') {
        requerir_permiso('piezas', 'crear');
        $datos = [
            'codigo_pieza'   => trim($_POST['codigo_pieza'] ?? ''),
            'nombre_pieza'   => trim($_POST['nombre_pieza'] ?? ''),
            'id_tipo_pieza'  => (int)($_POST['id_tipo_pieza'] ?? 0),
            'id_fabricante'  => (int)($_POST['id_fabricante'] ?? 0),
            'numero_serie'   => trim($_POST['numero_serie'] ?? ''),
            'estado'         => trim($_POST['estado'] ?? 'DISPONIBLE'),
            'costo'          => (float)($_POST['costo'] ?? 0),
            'descripcion'    => trim($_POST['descripcion'] ?? ''),
            'fecha_registro' => trim($_POST['fecha_registro'] ?? date('Y-m-d'))
        ];

        $res = $piezaCtrl->store($datos);
        if ($res['success']) {
            $_SESSION['flash_success'] = 'Pieza protésica registrada exitosamente en el sistema.';
            header('Location: piezas.php');
            exit;
        } else {
            $error = $res['mensaje'] . (isset($res['sqlstate']) ? " (SQLSTATE {$res['sqlstate']})" : '');
        }
    }

    if ($accion === 'editar') {
        requerir_permiso('piezas', 'editar');
        $id = (int)($_POST['id_pieza'] ?? 0);
        $datos = [
            'codigo_pieza'  => trim($_POST['codigo_pieza'] ?? ''),
            'nombre_pieza'  => trim($_POST['nombre_pieza'] ?? ''),
            'id_tipo_pieza' => (int)($_POST['id_tipo_pieza'] ?? 0),
            'id_fabricante' => (int)($_POST['id_fabricante'] ?? 0),
            'numero_serie'  => trim($_POST['numero_serie'] ?? ''),
            'estado'        => trim($_POST['estado'] ?? 'DISPONIBLE'),
            'costo'         => (float)($_POST['costo'] ?? 0),
            'descripcion'   => trim($_POST['descripcion'] ?? '')
        ];

        $res = $piezaCtrl->update($id, $datos);
        if ($res['success']) {
            $_SESSION['flash_success'] = 'Pieza protésica actualizada correctamente.';
            header('Location: piezas.php');
            exit;
        } else {
            $error = $res['mensaje'] . (isset($res['sqlstate']) ? " (SQLSTATE {$res['sqlstate']})" : '');
        }
    }

    if ($accion === 'eliminar') {
        requerir_permiso('piezas', 'eliminar');
        $id = (int)($_POST['id_pieza'] ?? 0);
        $res = $piezaCtrl->destroy($id);
        if ($res['success']) {
            $_SESSION['flash_success'] = 'Pieza eliminada correctamente de la base de datos.';
        } else {
            $_SESSION['flash_error'] = $res['mensaje'];
        }
        header('Location: piezas.php');
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($accion === 'crear' || $accion === 'editar'): ?>
    <?php
    $piezaEditar = null;
    if ($accion === 'editar') {
        $idEditar = (int)($_GET['id'] ?? 0);
        $resShow = $piezaCtrl->show($idEditar);
        if ($resShow['success']) {
            $piezaEditar = $resShow['data'];
        } else {
            $_SESSION['flash_error'] = $resShow['mensaje'];
            header('Location: piezas.php');
            exit;
        }
    }
    $tipos = $piezaCtrl->getTipos();
    $fabricantes = $piezaCtrl->getFabricantes();
    ?>
    <div class="card card-custom p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1" style="color: var(--turquesa-oscuro);">
                    <?= ($accion === 'crear') ? 'Registrar Nueva Pieza Protésica' : 'Editar Pieza: ' . htmlspecialchars($piezaEditar['codigo_pieza']) ?>
                </h4>
                <p class="text-muted small mb-0">Gestión de piezas e inventario en PostgreSQL</p>
            </div>
            <a href="piezas.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver al Listado
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger mb-4 py-2 small">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="piezas.php?accion=<?= $accion ?>" class="row g-3">
            <?php if ($accion === 'editar'): ?>
                <input type="hidden" name="id_pieza" value="<?= $piezaEditar['id_pieza'] ?>">
            <?php endif; ?>

            <div class="col-md-4">
                <label class="form-label">Código de Pieza <span class="text-danger">*</span></label>
                <input type="text" name="codigo_pieza" class="form-control" 
                       placeholder="Ej. PIE-001" required
                       value="<?= htmlspecialchars($_POST['codigo_pieza'] ?? $piezaEditar['codigo_pieza'] ?? '') ?>">
            </div>

            <div class="col-md-8">
                <label class="form-label">Nombre o Modelo de la Pieza <span class="text-danger">*</span></label>
                <input type="text" name="nombre_pieza" class="form-control" 
                       placeholder="Ej. Pie protésico Taleo / Articulación 3R80" required
                       value="<?= htmlspecialchars($_POST['nombre_pieza'] ?? $piezaEditar['nombre_pieza'] ?? '') ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Tipo / Categoría de Pieza <span class="text-danger">*</span></label>
                <select name="id_tipo_pieza" class="form-select" required>
                    <option value="">-- Seleccione Tipo --</option>
                    <?php foreach ($tipos as $t): ?>
                        <?php 
                        $sel = (isset($_POST['id_tipo_pieza']) && $_POST['id_tipo_pieza'] == $t['id_tipo_pieza']) 
                            || (isset($piezaEditar['id_tipo_pieza']) && $piezaEditar['id_tipo_pieza'] == $t['id_tipo_pieza']);
                        ?>
                        <option value="<?= $t['id_tipo_pieza'] ?>" <?= $sel ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['nombre_tipo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Fabricante <span class="text-danger">*</span></label>
                <select name="id_fabricante" class="form-select" required>
                    <option value="">-- Seleccione Fabricante --</option>
                    <?php foreach ($fabricantes as $f): ?>
                        <?php 
                        $sel = (isset($_POST['id_fabricante']) && $_POST['id_fabricante'] == $f['id_fabricante']) 
                            || (isset($piezaEditar['id_fabricante']) && $piezaEditar['id_fabricante'] == $f['id_fabricante']);
                        ?>
                        <option value="<?= $f['id_fabricante'] ?>" <?= $sel ? 'selected' : '' ?>>
                            <?= htmlspecialchars($f['nombre_fabricante']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Número de Serie <span class="text-danger">*</span></label>
                <input type="text" name="numero_serie" class="form-control" 
                       placeholder="Ej. SN-884920" required
                       value="<?= htmlspecialchars($_POST['numero_serie'] ?? $piezaEditar['numero_serie'] ?? '') ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label">Estado Inicial <span class="text-danger">*</span></label>
                <select name="estado" class="form-select" required>
                    <?php
                    $estados = ['DISPONIBLE', 'ENTREGADA', 'EN_MANTENIMIENTO', 'BAJA'];
                    $estActual = $_POST['estado'] ?? $piezaEditar['estado'] ?? 'DISPONIBLE';
                    foreach ($estados as $est):
                    ?>
                        <option value="<?= $est ?>" <?= ($estActual === $est) ? 'selected' : '' ?>>
                            <?= $est ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Costo Estimado ($)</label>
                <input type="number" step="0.01" min="0" name="costo" class="form-control" 
                       placeholder="0.00"
                       value="<?= htmlspecialchars($_POST['costo'] ?? $piezaEditar['costo'] ?? '0.00') ?>">
            </div>

            <?php if ($accion === 'crear'): ?>
            <div class="col-md-4">
                <label class="form-label">Fecha de Registro</label>
                <input type="date" name="fecha_registro" class="form-control" 
                       value="<?= htmlspecialchars($_POST['fecha_registro'] ?? date('Y-m-d')) ?>">
            </div>
            <?php endif; ?>

            <div class="col-md-<?= ($accion === 'crear') ? '8' : '12' ?>">
                <label class="form-label">Descripción u Observaciones</label>
                <input type="text" name="descripcion" class="form-control" 
                       placeholder="Detalles sobre características, talla o compatibilidad"
                       value="<?= htmlspecialchars($_POST['descripcion'] ?? $piezaEditar['descripcion'] ?? '') ?>">
            </div>

            <div class="col-12 mt-4 text-end">
                <a href="piezas.php" class="btn btn-outline-secondary me-2">Cancelar</a>
                <button type="submit" class="btn btn-turquesa">
                    <i class="bi bi-save me-1"></i> Guardar Pieza
                </button>
            </div>
        </form>
    </div>

<?php elseif ($accion === 'ver'): ?>
    <?php
    $idVer = (int)($_GET['id'] ?? 0);
    $resShow = $piezaCtrl->show($idVer);
    if (!$resShow['success']) {
        $_SESSION['flash_error'] = $resShow['mensaje'];
        header('Location: piezas.php');
        exit;
    }
    $p = $resShow['data'];
    ?>
    <div class="card card-custom p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1" style="color: var(--turquesa-oscuro);">
                    Detalle de Pieza: <?= htmlspecialchars($p['nombre_pieza']) ?>
                </h4>
                <p class="text-muted small mb-0">Código: <code><?= htmlspecialchars($p['codigo_pieza']) ?></code></p>
            </div>
            <div>
                <?php if (Permisos::puede($rolActual, 'piezas', 'editar')): ?>
                    <a href="piezas.php?accion=editar&id=<?= $p['id_pieza'] ?>" class="btn btn-sm btn-outline-primary me-2">
                        <i class="bi bi-pencil me-1"></i> Editar
                    </a>
                <?php endif; ?>
                <a href="piezas.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Volver
                </a>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <div class="p-3 rounded bg-light border">
                    <div class="text-muted small">Estado</div>
                    <div class="fw-bold fs-5">
                        <?php if ($p['estado'] === 'DISPONIBLE'): ?>
                            <span class="badge bg-success">DISPONIBLE</span>
                        <?php elseif ($p['estado'] === 'ENTREGADA'): ?>
                            <span class="badge badge-turquesa">ENTREGADA</span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><?= htmlspecialchars($p['estado']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 rounded bg-light border">
                    <div class="text-muted small">Tipo / Categoría</div>
                    <div class="fw-bold fs-6 text-dark"><?= htmlspecialchars($p['nombre_tipo']) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 rounded bg-light border">
                    <div class="text-muted small">Fabricante</div>
                    <div class="fw-bold fs-6 text-dark"><?= htmlspecialchars($p['nombre_fabricante']) ?></div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="p-3 rounded bg-light border">
                    <div class="text-muted small">Número de Serie</div>
                    <div class="fw-semibold font-monospace"><?= htmlspecialchars($p['numero_serie']) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 rounded bg-light border">
                    <div class="text-muted small">Costo Registrado</div>
                    <div class="fw-semibold">$<?= number_format((float)$p['costo'], 2) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 rounded bg-light border">
                    <div class="text-muted small">Fecha de Registro</div>
                    <div class="fw-semibold"><?= htmlspecialchars($p['fecha_registro']) ?></div>
                </div>
            </div>

            <?php if (!empty($p['descripcion'])): ?>
            <div class="col-12">
                <div class="p-3 rounded bg-light border">
                    <div class="text-muted small mb-1">Descripción / Notas</div>
                    <div><?= nl2br(htmlspecialchars($p['descripcion'])) ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

<?php else: ?>
    <?php
    $buscar = trim($_GET['buscar'] ?? '');
    $estadoFiltro = trim($_GET['estado'] ?? '');
    $tipoFiltro = (int)($_GET['id_tipo'] ?? 0);

    $res = $piezaCtrl->index($buscar ?: null, $estadoFiltro ?: null, $tipoFiltro ?: null);
    $piezas = $res['success'] ? $res['data'] : [];
    $tipos = $piezaCtrl->getTipos();
    ?>

    <div class="card card-custom p-4 mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h4 class="fw-bold mb-1" style="color: var(--turquesa-oscuro);">Registro de Piezas Protésicas</h4>
                <p class="text-muted small mb-0">Inventario y disponibilidad de piezas protésicas en el taller</p>
            </div>
            <?php if (Permisos::puede($rolActual, 'piezas', 'crear')): ?>
                <div>
                    <a href="piezas.php?accion=crear" class="btn btn-turquesa">
                        <i class="bi bi-plus-lg me-1"></i> Nueva Pieza
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Barra de Búsqueda y Filtros directos en PostgreSQL -->
        <form method="GET" action="piezas.php" class="row g-2 mb-4">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="buscar" class="form-control" 
                           placeholder="Buscar por código, modelo o serial..." 
                           value="<?= htmlspecialchars($buscar) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="estado" class="form-select">
                    <option value="">-- Todos los Estados --</option>
                    <option value="DISPONIBLE" <?= ($estadoFiltro === 'DISPONIBLE') ? 'selected' : '' ?>>DISPONIBLE</option>
                    <option value="ENTREGADA" <?= ($estadoFiltro === 'ENTREGADA') ? 'selected' : '' ?>>ENTREGADA</option>
                    <option value="EN_MANTENIMIENTO" <?= ($estadoFiltro === 'EN_MANTENIMIENTO') ? 'selected' : '' ?>>EN_MANTENIMIENTO</option>
                    <option value="BAJA" <?= ($estadoFiltro === 'BAJA') ? 'selected' : '' ?>>BAJA</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="id_tipo" class="form-select">
                    <option value="">-- Todos los Tipos --</option>
                    <?php foreach ($tipos as $t): ?>
                        <option value="<?= $t['id_tipo_pieza'] ?>" <?= ($tipoFiltro === (int)$t['id_tipo_pieza']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['nombre_tipo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1 d-grid">
                <button type="submit" class="btn btn-outline-secondary" title="Filtrar">
                    <i class="bi bi-funnel"></i>
                </button>
            </div>
        </form>

        <!-- Tabla de Piezas -->
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre / Modelo</th>
                        <th>Tipo</th>
                        <th>Fabricante</th>
                        <th>N° Serie</th>
                        <th>Estado</th>
                        <th>Costo</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($piezas)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>
                                No se encontraron piezas protésicas con los criterios especificados.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($piezas as $pz): ?>
                            <tr>
                                <td class="fw-semibold"><code><?= htmlspecialchars($pz['codigo_pieza']) ?></code></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($pz['nombre_pieza']) ?></td>
                                <td><?= htmlspecialchars($pz['nombre_tipo']) ?></td>
                                <td><?= htmlspecialchars($pz['nombre_fabricante']) ?></td>
                                <td class="font-monospace small"><?= htmlspecialchars($pz['numero_serie']) ?></td>
                                <td>
                                    <?php if ($pz['estado'] === 'DISPONIBLE'): ?>
                                        <span class="badge bg-success">DISPONIBLE</span>
                                    <?php elseif ($pz['estado'] === 'ENTREGADA'): ?>
                                        <span class="badge badge-turquesa">ENTREGADA</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($pz['estado']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>$<?= number_format((float)$pz['costo'], 2) ?></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="piezas.php?accion=ver&id=<?= $pz['id_pieza'] ?>" 
                                           class="btn btn-outline-secondary" title="Ver Detalle">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (Permisos::puede($rolActual, 'piezas', 'editar')): ?>
                                            <a href="piezas.php?accion=editar&id=<?= $pz['id_pieza'] ?>" 
                                               class="btn btn-outline-primary" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (Permisos::puede($rolActual, 'piezas', 'eliminar')): ?>
                                            <form method="POST" action="piezas.php?accion=eliminar" class="d-inline"
                                                  onsubmit="return confirm('¿Está seguro de eliminar la pieza <?= htmlspecialchars($pz['codigo_pieza']) ?>?');">
                                                <input type="hidden" name="id_pieza" value="<?= $pz['id_pieza'] ?>">
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
