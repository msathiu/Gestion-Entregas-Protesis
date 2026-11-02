<?php
/**
 * Cabecera HTML y Barra de Navegación
 */
require_once __DIR__ . '/config.php';
$pageTitle = $pageTitle ?? APP_NAME;
$currentUri = $_SERVER['REQUEST_URI'] ?? '/';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/bootstrap-icons.min.css">
    <style>
        body { background-color: #f8fafc; color: #1e293b; min-height: 100vh; display: flex; flex-direction: column; }
        .main-content { flex: 1; padding: 2rem 0; }
        .navbar-brand { font-weight: 700; letter-spacing: -0.5px; }
        .card { border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .table-hover tbody tr:hover { background-color: #f1f5f9; }
        footer { background: #ffffff; border-top: 1px solid #e2e8f0; padding: 1.25rem 0; font-size: 0.875rem; color: #64748b; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="/index.php">
            <i class="bi bi-box-seam-fill text-primary"></i>
            <span>Prótesis & Órtesis</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= ($currentUri === '/' || str_contains($currentUri, 'index.php')) ? 'active fw-semibold' : '' ?>" href="/index.php">
                        <i class="bi bi-box-seam me-1"></i> Entregas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($currentUri, 'crear.php') ? 'active fw-semibold' : '' ?>" href="/crear.php">
                        <i class="bi bi-plus-circle me-1"></i> Registrar Entrega
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($currentUri, 'pacientes.php') ? 'active fw-semibold' : '' ?>" href="/pacientes.php">
                        <i class="bi bi-people me-1"></i> Pacientes
                    </a>
                </li>
            </ul>
            <div class="d-flex align-items-center gap-2 text-white-50 small">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">PostgreSQL 15</span>
                <span>Bases de Datos &bull; ULA</span>
            </div>
        </div>
    </div>
</nav>

<main class="main-content">
    <div class="container">
        <?= render_flashes() ?>

