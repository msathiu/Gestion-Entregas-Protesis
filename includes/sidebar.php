<?php
/**
 * Menú Lateral (Sidebar) con Filtrado de Módulos por Rol
 * Asignatura: Bases de Datos - Ingeniería de Sistemas (ULA)
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

        <li class="nav-heading">Módulos Clínicos</li>

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

        <li class="nav-heading">Prótesis y Taller</li>

        <?php if (Permisos::puede($rolActual, 'dispositivos', 'listar')): ?>
        <li class="nav-item">
            <a class="nav-link <?= ($menuActual === 'dispositivos') ? 'active' : '' ?>" href="dispositivos.php">
                <i class="bi bi-bandaid"></i> Dispositivos
            </a>
        </li>
        <?php endif; ?>

        <?php if (Permisos::puede($rolActual, 'componentes', 'listar')): ?>
        <li class="nav-item">
            <a class="nav-link <?= ($menuActual === 'componentes') ? 'active' : '' ?>" href="componentes.php">
                <i class="bi bi-cpu"></i> Componentes
            </a>
        </li>
        <?php endif; ?>

        <?php if (Permisos::puede($rolActual, 'ajustes', 'listar')): ?>
        <li class="nav-item">
            <a class="nav-link <?= ($menuActual === 'ajustes') ? 'active' : '' ?>" href="ajustes.php">
                <i class="bi bi-wrench"></i> Historial de Ajustes
            </a>
        </li>
        <?php endif; ?>

        <?php if (Permisos::puede($rolActual, 'mantenimientos', 'listar')): ?>
        <li class="nav-item">
            <a class="nav-link <?= ($menuActual === 'mantenimientos') ? 'active' : '' ?>" href="mantenimientos.php">
                <i class="bi bi-calendar-check"></i> Mantenimiento
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
