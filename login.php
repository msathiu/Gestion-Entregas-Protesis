<?php
/**
 * Vista de Inicio de Sesión
 * Asignatura: Bases de Datos - Ingeniería de Sistemas (ULA)
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth/Auth.php';

if (Auth::estaAutenticado()) {
    header('Location: index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $clave   = $_POST['clave'] ?? '';

    $res = Auth::login($usuario, $clave);
    if ($res['success']) {
        header('Location: index.php');
        exit;
    } else {
        $error = $res['mensaje'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión | Sistema de Prótesis y Órtesis</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height: 100vh; background-color: var(--bg-app);">

<div class="container" style="max-width: 440px;">
    <div class="card card-custom p-4 p-sm-5 shadow-sm">
        <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" 
                 style="width: 56px; height: 56px; background-color: var(--turquesa-claro); color: var(--turquesa);">
                <i class="bi bi-diagram-3-fill fs-3"></i>
            </div>
            <h4 class="fw-bold mb-1" style="color: var(--turquesa-oscuro);">Sistema de Prótesis y Órtesis</h4>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 px-3 small card-custom" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" id="loginForm">
            <div class="mb-3">
                <label for="usuario" class="form-label">Usuario</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control border-start-0" id="usuario" name="usuario" 
                           placeholder="Ingrese su usuario" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label for="clave" class="form-label">Contraseña</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control border-start-0" id="clave" name="clave" 
                           placeholder="Ingrese su contraseña" required>
                </div>
            </div>

            <button type="submit" class="btn btn-turquesa w-100 py-2">
                <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar Sesión
            </button>
        </form>

        <hr class="my-4" style="border-color: var(--borde-suave);">

        <div>
            <label class="form-label text-muted small fw-semibold d-block text-center mb-2">Acceso Rápido con Cuentas Demo:</label>
            <div class="d-flex flex-column gap-2">
                <button type="button" class="btn btn-sm btn-outline-turquesa text-start py-2" onclick="setDemo('admin', 'Admin123*')">
                    <i class="bi bi-shield-check me-2"></i><strong>Administrador:</strong> admin
                </button>
                <button type="button" class="btn btn-sm btn-outline-turquesa text-start py-2" onclick="setDemo('jperez', 'Tecnico123*')">
                    <i class="bi bi-wrench me-2"></i><strong>Técnico Ortopédico:</strong> jperez
                </button>
                <button type="button" class="btn btn-sm btn-outline-turquesa text-start py-2" onclick="setDemo('mrodriguez', 'Clinico123*')">
                    <i class="bi bi-clipboard2-pulse me-2"></i><strong>Evaluador Clínico:</strong> mrodriguez
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function setDemo(u, p) {
    document.getElementById('usuario').value = u;
    document.getElementById('clave').value = p;
    document.getElementById('loginForm').submit();
}
</script>

</body>
</html>
