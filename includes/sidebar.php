<?php
/**
 * Menú Lateral (Sidebar) con Filtrado de Módulos por Rol
 * Asignatura: Bases de Datos - Ingeniería de Sistemas (ULA)
 *
 * Módulos visibles según especificación:
 * 1. Inicio
 * 2. Pacientes
 * 3. Evaluaciones
 * 4. Registro de piezas
 * 5. Entrega de piezas
 * 6. Usuarios (solo administrador)
 */

$menuActual = $activeMenu ?? 'inicio';
?>
<aside class="sidebar d-none d-md-block flex-shrink-0">
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?= ($menuActual === 'inicio') ? 'active' : '' ?>" href="index.php">
                <i class="bi bi-house-door"></i> Inicio
            </a>
        </li>

        <li class="nav-heading">Gestión Clínica</li>

        <?php if (Permisos::puede($rolActual, 'pacientes', 'listar')): ?>
        <li class="nav-item">
            <a class="nav-link <?= ($menuActual === 'pacientes') ? 'active' : '' ?>" href="pacientes.php">
                <i class="bi bi-people"></i> Pacientes
            </a>
        </li>
        <?php endif; ?>

        <?php if (Permisos::puede($rolActual, 'evaluaciones', 'listar')): ?>
        <li class="nav-item">
            <a class="nav-link <?= ($menuActual === 'evaluaciones') ? 'active' : '' ?>" href="evaluaciones.php">
                <i class="bi bi-clipboard2-pulse"></i> Evaluaciones
            </a>
        </li>
        <?php endif; ?>

        <li class="nav-heading">Gestión de Prótesis</li>

        <?php if (Permisos::puede($rolActual, 'piezas', 'listar')): ?>
        <li class="nav-item">
            <a class="nav-link <?= ($menuActual === 'piezas') ? 'active' : '' ?>" href="piezas.php">
                <i class="bi bi-tools"></i> Registro de Piezas
            </a>
        </li>
        <?php endif; ?>

        <?php if (Permisos::puede($rolActual, 'entregas', 'listar')): ?>
        <li class="nav-item">
            <a class="nav-link <?= ($menuActual === 'entregas') ? 'active' : '' ?>" href="entregas.php">
                <i class="bi bi-box-seam"></i> Entrega de Piezas
            </a>
        </li>
        <?php endif; ?>

        <?php if (Permisos::puede($rolActual, 'usuarios', 'listar')): ?>
        <li class="nav-heading">Administración</li>
        <li class="nav-item">
            <a class="nav-link <?= ($menuActual === 'usuarios') ? 'active' : '' ?>" href="usuarios.php">
                <i class="bi bi-shield-lock"></i> Usuarios y Roles
            </a>
        </li>
        <?php endif; ?>

        <li class="nav-heading">Sesión</li>
        <li class="nav-item">
            <a class="nav-link text-danger" href="logout.php">
                <i class="bi bi-box-arrow-left text-danger"></i> Cerrar Sesión
            </a>
        </li>
    </ul>
</aside>
