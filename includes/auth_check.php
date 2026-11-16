<?php
/**
 * Verificación de Autenticación y Control de Acceso para Vistas Web

 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/Auth.php';
require_once __DIR__ . '/../auth/Permisos.php';
require_once __DIR__ . '/funciones.php';

// Si no está autenticado, redirigir a login
if (!Auth::estaAutenticado()) {
    header('Location: login.php');
    exit;
}

$usuarioActual = Auth::usuarioActual();
$rolActual     = Auth::rolActual();

/**
 * Función para requerir permisos específicos en una vista
 */
function requerir_permiso(string $modulo, string $accion): void {
    $error = Auth::validarPermiso($modulo, $accion);
    if ($error) {
        $_SESSION['flash_error'] = $error['mensaje'];
        header('Location: index.php');
        exit;
    }
}
