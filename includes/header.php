<?php
/**
 * Cabecera Común de la Interfaz Web
 * Asignatura: Bases de Datos - Ingeniería de Sistemas (ULA)
 */

require_once __DIR__ . '/auth_check.php';

$pageTitle = $pageTitle ?? 'Sistema de Prótesis y Órtesis';
$activeMenu = $activeMenu ?? 'inicio';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | Gestión de Prótesis y Órtesis</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <!-- Bootstrap 5 y Bootstrap Icons Locales -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/bootstrap-icons.min.css">
    <!-- Estilos de Identidad Visual Turquesa -->
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>

<!-- Barra Superior (Topbar) -->
<nav class="topbar d-flex align-items-center justify-content-between px-3 px-md-4">
    <div class="topbar-brand">
        <i class="bi bi-diagram-3-fill text-primary" style="color: var(--turquesa) !important;"></i>
        <span>Sistema de Prótesis y Órtesis</span>
    </div>
    <div class="d-flex align-items-center gap-3">
        <div class="d-none d-sm-block text-end">
            <div class="fw-semibold text-dark small"><?= htmlspecialchars($usuarioActual['nombre_completo']) ?></div>
            <span class="badge badge-turquesa"><?= htmlspecialchars($rolActual) ?></span>
        </div>
        <a href="logout.php" class="btn btn-sm btn-outline-secondary" title="Cerrar sesión">
            <i class="bi bi-box-arrow-right"></i> <span class="d-none d-md-inline">Salir</span>
        </a>
    </div>
</nav>

<!-- Estructura Principal con Menú Lateral -->
<div class="d-flex">
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <main class="flex-grow-1 p-3 p-md-4" style="background-color: var(--bg-app); min-width: 0;">
        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show card-custom py-2 px-3 mb-3" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_SESSION['flash_success']) ?>
                <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show card-custom py-2 px-3 mb-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?>
                <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>
