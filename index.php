<?php
/**
 * Vista de Inicio (Dashboard Principal)
 * Asignatura: Bases de Datos - Ingeniería de Sistemas (ULA)
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/funciones.php';
require_once __DIR__ . '/auth/Auth.php';
require_once __DIR__ . '/auth/Permisos.php';

// Si es ejecución CLI o solicitud explícita de JSON / API
$esJson = (isset($_GET['format']) && $_GET['format'] === 'json') ||
          (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

if ($esJson || (php_sapi_name() === 'cli' && empty($_SERVER['HTTP_HOST']))) {
    $db = Database::getConnection();
    $info = [
        'proyecto'   => 'Sistema de Gestión de Adaptaciones y Mantenimiento de Prótesis y Órtesis',
        'asignatura' => 'Bases de Datos (Ingeniería de Sistemas - ULA)',
        'usuario'    => Auth::usuarioActual()['nombre_usuario'] ?? 'Invitado',
        'rol'        => Auth::rolActual(),
        'conexion'   => Database::testConnection(),
        'metricas'   => [
            'pacientes'      => (int)$db->query("SELECT COUNT(*) FROM paciente")->fetchColumn(),
            'evaluaciones'   => (int)$db->query("SELECT COUNT(*) FROM evaluacion_biomecanica")->fetchColumn(),
            'dispositivos'   => (int)$db->query("SELECT COUNT(*) FROM dispositivo_protesico")->fetchColumn(),
            'componentes'    => (int)$db->query("SELECT COUNT(*) FROM componente")->fetchColumn(),
            'ajustes'        => (int)$db->query("SELECT COUNT(*) FROM historial_ajuste")->fetchColumn(),
            'mantenimientos' => (int)$db->query("SELECT COUNT(*) FROM mantenimiento_preventivo")->fetchColumn(),
        ]
    ];
    if ($esJson) json_response($info);
    print_r($info);
    exit;
}

// Para la interfaz web, requerir autenticación
require_once __DIR__ . '/includes/auth_check.php';

$pageTitle = 'Inicio';
$activeMenu = 'inicio';

$db = Database::getConnection();
$totalPacientes      = (int)$db->query("SELECT COUNT(*) FROM paciente")->fetchColumn();
$totalEvaluaciones   = (int)$db->query("SELECT COUNT(*) FROM evaluacion_biomecanica")->fetchColumn();
$totalDispositivos   = (int)$db->query("SELECT COUNT(*) FROM dispositivo_protesico")->fetchColumn();
$totalComponentes    = (int)$db->query("SELECT COUNT(*) FROM componente")->fetchColumn();
$totalAjustes        = (int)$db->query("SELECT COUNT(*) FROM historial_ajuste")->fetchColumn();
$totalMantenimientos = (int)$db->query("SELECT COUNT(*) FROM mantenimiento_preventivo")->fetchColumn();

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1" style="color: var(--turquesa-oscuro);">Panel Principal</h3>
        <p class="text-muted small mb-0">Bienvenido(a), <strong><?= htmlspecialchars($usuarioActual['nombre_completo']) ?></strong> (<?= htmlspecialchars($rolActual) ?>)</p>
    </div>
    <div>
        <a href="tests/probar_conexion.php" target="_blank" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-cpu me-1"></i> Banco de Pruebas
        </a>
    </div>
</div>

<!-- Tarjetas de Métricas -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-4">
        <div class="card card-custom card-stat p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small text-uppercase fw-semibold">Pacientes</div>
                    <div class="fs-3 fw-bold text-dark mt-1"><?= $totalPacientes ?></div>
                </div>
                <div class="rounded-circle p-3" style="background-color: var(--turquesa-claro); color: var(--turquesa);">
                    <i class="bi bi-people fs-4"></i>
                </div>
            </div>
            <?php if (Permisos::puede($rolActual, 'pacientes', 'listar')): ?>
                <a href="pacientes.php" class="small mt-2 d-inline-block text-decoration-none">Ver pacientes &rarr;</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-sm-6 col-xl-4">
        <div class="card card-custom card-stat p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small text-uppercase fw-semibold">Evaluaciones</div>
                    <div class="fs-3 fw-bold text-dark mt-1"><?= $totalEvaluaciones ?></div>
                </div>
                <div class="rounded-circle p-3" style="background-color: var(--turquesa-claro); color: var(--turquesa);">
                    <i class="bi bi-clipboard2-pulse fs-4"></i>
                </div>
            </div>
            <?php if (Permisos::puede($rolActual, 'evaluaciones', 'listar')): ?>
                <a href="evaluaciones.php" class="small mt-2 d-inline-block text-decoration-none">Ver evaluaciones &rarr;</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-sm-6 col-xl-4">
        <div class="card card-custom card-stat p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small text-uppercase fw-semibold">Dispositivos</div>
                    <div class="fs-3 fw-bold text-dark mt-1"><?= $totalDispositivos ?></div>
                </div>
                <div class="rounded-circle p-3" style="background-color: var(--turquesa-claro); color: var(--turquesa);">
                    <i class="bi bi-bandaid fs-4"></i>
                </div>
            </div>
            <?php if (Permisos::puede($rolActual, 'dispositivos', 'listar')): ?>
                <a href="dispositivos.php" class="small mt-2 d-inline-block text-decoration-none">Ver prótesis &rarr;</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-sm-6 col-xl-4">
        <div class="card card-custom card-stat p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small text-uppercase fw-semibold">Componentes Físicos</div>
                    <div class="fs-3 fw-bold text-dark mt-1"><?= $totalComponentes ?></div>
                </div>
                <div class="rounded-circle p-3" style="background-color: var(--turquesa-claro); color: var(--turquesa);">
                    <i class="bi bi-cpu fs-4"></i>
                </div>
            </div>
            <?php if (Permisos::puede($rolActual, 'componentes', 'listar')): ?>
                <a href="componentes.php" class="small mt-2 d-inline-block text-decoration-none">Ver catálogo &rarr;</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-sm-6 col-xl-4">
        <div class="card card-custom card-stat p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small text-uppercase fw-semibold">Ajustes Realizados</div>
                    <div class="fs-3 fw-bold text-dark mt-1"><?= $totalAjustes ?></div>
                </div>
                <div class="rounded-circle p-3" style="background-color: var(--turquesa-claro); color: var(--turquesa);">
                    <i class="bi bi-wrench fs-4"></i>
                </div>
            </div>
            <?php if (Permisos::puede($rolActual, 'ajustes', 'listar')): ?>
                <a href="ajustes.php" class="small mt-2 d-inline-block text-decoration-none">Ver ajustes &rarr;</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-sm-6 col-xl-4">
        <div class="card card-custom card-stat p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small text-uppercase fw-semibold">Mantenimientos</div>
                    <div class="fs-3 fw-bold text-dark mt-1"><?= $totalMantenimientos ?></div>
                </div>
                <div class="rounded-circle p-3" style="background-color: var(--turquesa-claro); color: var(--turquesa);">
                    <i class="bi bi-calendar-check fs-4"></i>
                </div>
            </div>
            <?php if (Permisos::puede($rolActual, 'mantenimientos', 'listar')): ?>
                <a href="mantenimientos.php" class="small mt-2 d-inline-block text-decoration-none">Ver mantenimientos &rarr;</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Accesos Directos según Rol -->
<div class="card card-custom p-4 mb-4">
    <h5 class="fw-bold mb-3" style="color: var(--turquesa-oscuro);">Módulos Disponibles para su Rol (<?= htmlspecialchars($rolActual) ?>)</h5>
    <div class="row g-3">
        <?php if (Permisos::puede($rolActual, 'pacientes', 'crear')): ?>
        <div class="col-md-4">
            <a href="pacientes.php?accion=crear" class="btn btn-outline-turquesa w-100 py-3 text-start">
                <i class="bi bi-person-plus fs-5 me-2 text-primary"></i><strong>Registrar Paciente</strong>
                <div class="text-muted small mt-1">Registrar nueva ficha clínica de paciente</div>
            </a>
        </div>
        <?php endif; ?>

        <?php if (Permisos::puede($rolActual, 'evaluaciones', 'crear')): ?>
        <div class="col-md-4">
            <a href="evaluaciones.php?accion=crear" class="btn btn-outline-turquesa w-100 py-3 text-start">
                <i class="bi bi-clipboard2-plus fs-5 me-2 text-primary"></i><strong>Nueva Evaluación</strong>
                <div class="text-muted small mt-1">Registrar medidas biomecánicas y nivel de actividad</div>
            </a>
        </div>
        <?php endif; ?>

        <?php if (Permisos::puede($rolActual, 'dispositivos', 'crear')): ?>
        <div class="col-md-4">
            <a href="dispositivos.php?accion=crear" class="btn btn-outline-turquesa w-100 py-3 text-start">
                <i class="bi bi-bandaid fs-5 me-2 text-primary"></i><strong>Registrar Dispositivo</strong>
                <div class="text-muted small mt-1">Asignar nueva prótesis u órtesis a un paciente</div>
            </a>
        </div>
        <?php endif; ?>

        <?php if (Permisos::puede($rolActual, 'componentes', 'crear')): ?>
        <div class="col-md-4">
            <a href="componentes.php?accion=crear" class="btn btn-outline-turquesa w-100 py-3 text-start">
                <i class="bi bi-plus-circle fs-5 me-2 text-primary"></i><strong>Nuevo Componente</strong>
                <div class="text-muted small mt-1">Registrar pieza física por número de serie</div>
            </a>
        </div>
        <?php endif; ?>

        <?php if (Permisos::puede($rolActual, 'ajustes', 'crear')): ?>
        <div class="col-md-4">
            <a href="ajustes.php?accion=crear" class="btn btn-outline-turquesa w-100 py-3 text-start">
                <i class="bi bi-tools fs-5 me-2 text-primary"></i><strong>Registrar Ajuste</strong>
                <div class="text-muted small mt-1">Modificación o ajuste de socket en taller</div>
            </a>
        </div>
        <?php endif; ?>

        <?php if (Permisos::puede($rolActual, 'mantenimientos', 'crear')): ?>
        <div class="col-md-4">
            <a href="mantenimientos.php?accion=crear" class="btn btn-outline-turquesa w-100 py-3 text-start">
                <i class="bi bi-calendar-plus fs-5 me-2 text-primary"></i><strong>Programar Mantenimiento</strong>
                <div class="text-muted small mt-1">Revisión periódica o preventiva de prótesis</div>
            </a>
        </div>
        <?php endif; ?>

        <?php if (Permisos::puede($rolActual, 'usuarios', 'listar')): ?>
        <div class="col-md-4">
            <a href="usuarios.php" class="btn btn-outline-turquesa w-100 py-3 text-start">
                <i class="bi bi-people fs-5 me-2 text-primary"></i><strong>Administrar Usuarios</strong>
                <div class="text-muted small mt-1">Control de roles, cuentas y seguridad</div>
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Información Técnica de la Base de Datos -->
<div class="card card-custom p-4">
    <h6 class="fw-bold text-muted text-uppercase mb-2">Información del Motor Relacional</h6>
    <div class="row g-2 text-muted small">
        <div class="col-md-4"><strong>Motor:</strong> PostgreSQL 15 (Docker)</div>
        <div class="col-md-4"><strong>Base de Datos:</strong> protesis_ortesis</div>
        <div class="col-md-4"><strong>Esquema:</strong> 29 tablas normalizadas</div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
