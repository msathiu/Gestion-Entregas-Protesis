<?php
/**
 * Módulo: Gestión de Usuarios y Roles (Exclusivo ADMINISTRADOR)
 */

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/controllers/UsuarioController.php';

$pageTitle = 'Usuarios y Roles';
$activeMenu = 'usuarios';

requerir_permiso('usuarios', 'listar');

$userCtrl = new UsuarioController();
$accion = $_GET['accion'] ?? 'listar';
$error = null;

// PROCESAMIENTO POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($accion === 'crear') {
        requerir_permiso('usuarios', 'crear');
        $datos = [
            'id_rol'          => (int)($_POST['id_rol'] ?? 0),
            'nombre_usuario'  => trim($_POST['nombre_usuario'] ?? ''),
            'nombre_completo' => trim($_POST['nombre_completo'] ?? ''),
            'correo'          => trim($_POST['correo'] ?? ''),
            'clave'           => trim($_POST['clave'] ?? ''),
            'activo'          => isset($_POST['activo'])
        ];

        $res = $userCtrl->store($datos);
        if ($res['success']) {
            $_SESSION['flash_success'] = 'Usuario registrado exitosamente con contraseña cifrada.';
            header('Location: usuarios.php');
            exit;
        } else {
            $error = $res['mensaje'] . (isset($res['sqlstate']) ? " (SQLSTATE {$res['sqlstate']})" : '');
        }
    }

    if ($accion === 'editar') {
        requerir_permiso('usuarios', 'editar');
        $id = (int)($_POST['id_usuario'] ?? 0);
        $datos = [
            'id_rol'          => (int)($_POST['id_rol'] ?? 0),
            'nombre_completo' => trim($_POST['nombre_completo'] ?? ''),
            'correo'          => trim($_POST['correo'] ?? ''),
            'clave'           => trim($_POST['clave'] ?? ''),
            'activo'          => isset($_POST['activo'])
        ];

        $res = $userCtrl->update($id, $datos);
        if ($res['success']) {
            $_SESSION['flash_success'] = 'Usuario actualizado correctamente.';
            header('Location: usuarios.php');
            exit;
        } else {
            $error = $res['mensaje'] . (isset($res['sqlstate']) ? " (SQLSTATE {$res['sqlstate']})" : '');
        }
    }

    if ($accion === 'eliminar') {
        requerir_permiso('usuarios', 'eliminar');
        $id = (int)($_POST['id_usuario'] ?? 0);
        $res = $userCtrl->destroy($id);
        if ($res['success']) {
            $_SESSION['flash_success'] = 'Usuario eliminado de la base de datos.';
        } else {
            $_SESSION['flash_error'] = $res['mensaje'];
        }
        header('Location: usuarios.php');
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($accion === 'crear' || $accion === 'editar'): ?>
    <?php
    $roles = $userCtrl->roles()['data'] ?? [];
    $usuarioEditar = null;
    if ($accion === 'editar') {
        $idEditar = (int)($_GET['id'] ?? 0);
        $resShow = $userCtrl->show($idEditar);
        if (!$resShow['success']) {
            echo "<div class='alert alert-danger'>{$resShow['mensaje']}</div>";
            require_once __DIR__ . '/includes/footer.php';
            exit;
        }
        $usuarioEditar = $resShow['data'];
    }
    ?>
    <div class="card card-custom p-4" style="max-width: 700px; margin: 0 auto;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0" style="color: var(--turquesa-oscuro);">
                <?= $accion === 'crear' ? 'Registrar Nuevo Usuario' : 'Modificar Usuario' ?>
            </h5>
            <a href="usuarios.php" class="btn btn-sm btn-outline-secondary">Cancelar</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger small py-2 px-3 mb-3"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="usuarios.php?accion=<?= $accion ?>">
            <?php if ($usuarioEditar): ?>
                <input type="hidden" name="id_usuario" value="<?= $usuarioEditar['id_usuario_sistema'] ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre de Usuario (Login) <span class="text-danger">*</span></label>
                    <input type="text" name="nombre_usuario" class="form-control" required
                           value="<?= htmlspecialchars($_POST['nombre_usuario'] ?? $usuarioEditar['nombre_usuario'] ?? '') ?>"
                           <?= $usuarioEditar ? 'readonly' : '' ?> placeholder="Ej: rmartinez">
                    <?php if ($usuarioEditar): ?><div class="form-text small">El identificador de usuario no es modificable.</div><?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Rol del Sistema <span class="text-danger">*</span></label>
                    <select name="id_rol" class="form-select" required>
                        <?php foreach ($roles as $r): ?>
                            <?php $sel = ($usuarioEditar && $usuarioEditar['id_rol'] == $r['id_rol']) ? 'selected' : ''; ?>
                            <option value="<?= $r['id_rol'] ?>" <?= $sel ?>>
                                <?= htmlspecialchars($r['nombre_rol'] . ' - ' . $r['descripcion']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                    <input type="text" name="nombre_completo" class="form-control" required
                           value="<?= htmlspecialchars($_POST['nombre_completo'] ?? $usuarioEditar['nombre_completo'] ?? '') ?>"
                           placeholder="Ej: Lic. Ricardo Martínez">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
                    <input type="email" name="correo" class="form-control" required
                           value="<?= htmlspecialchars($_POST['correo'] ?? $usuarioEditar['correo'] ?? '') ?>"
                           placeholder="rmartinez@protesis.local">
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        Contraseña <?= $usuarioEditar ? '(Dejar en blanco para no cambiar)' : '<span class="text-danger">*</span>' ?>
                    </label>
                    <input type="password" name="clave" class="form-control" <?= $usuarioEditar ? '' : 'required' ?>
                           placeholder="<?= $usuarioEditar ? 'Nueva contraseña...' : 'Mínimo 6 caracteres' ?>">
                </div>

                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="activo" id="activoUser"
                               <?= (!isset($usuarioEditar) || $usuarioEditar['activo']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="activoUser">Cuenta de usuario activa</label>
                    </div>
                </div>

                <div class="col-12 mt-4 text-end">
                    <a href="usuarios.php" class="btn btn-outline-secondary me-2">Cancelar</a>
                    <button type="submit" class="btn btn-turquesa px-4">
                        <i class="bi bi-save me-1"></i> <?= $accion === 'crear' ? 'Registrar Usuario' : 'Guardar Cambios' ?>
                    </button>
                </div>
            </div>
        </form>
    </div>

<?php else: ?>
    <!-- LISTADO DE USUARIOS -->
    <?php
    $usuarios = $userCtrl->index()['data'] ?? [];
    ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-0" style="color: var(--turquesa-oscuro);">Gestión de Usuarios y Roles</h4>
            <p class="text-muted small mb-0">Total cuentas activas en PostgreSQL: <?= count($usuarios) ?></p>
        </div>
        <a href="usuarios.php?accion=crear" class="btn btn-turquesa">
            <i class="bi bi-person-plus me-1"></i> Registrar Usuario
        </a>
    </div>

    <div class="card card-custom p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-custom mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario (Login)</th>
                        <th>Nombre Completo</th>
                        <th>Correo</th>
                        <th>Rol Asignado</th>
                        <th>Estado</th>
                        <th>Fecha Registro</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td><?= $u['id_usuario_sistema'] ?></td>
                            <td class="fw-semibold"><code><?= htmlspecialchars($u['nombre_usuario']) ?></code></td>
                            <td><?= htmlspecialchars($u['nombre_completo']) ?></td>
                            <td><?= htmlspecialchars($u['correo']) ?></td>
                            <td><span class="badge badge-turquesa"><?= htmlspecialchars($u['nombre_rol']) ?></span></td>
                            <td>
                                <span class="badge <?= $u['activo'] ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= $u['activo'] ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </td>
                            <td><?= formatear_fecha($u['fecha_creacion']) ?></td>
                            <td class="text-end text-nowrap">
                                <a href="usuarios.php?accion=editar&id=<?= $u['id_usuario_sistema'] ?>" class="btn btn-sm btn-outline-turquesa" title="Modificar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if ($u['id_usuario_sistema'] > 1 && $u['id_usuario_sistema'] != $usuarioActual['id_usuario_sistema']): ?>
                                    <form method="POST" action="usuarios.php?accion=eliminar" class="d-inline" onsubmit="return confirm('¿Está seguro de eliminar a <?= htmlspecialchars($u['nombre_usuario']) ?>?');">
                                        <input type="hidden" name="id_usuario" value="<?= $u['id_usuario_sistema'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
