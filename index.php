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
        'proyecto'   => 'Sistema de Gestión de Prótesis y Órtesis (CRUD y Entregas)',
        'asignatura' => 'Bases de Datos (Ingeniería de Sistemas - ULA)',
        'usuario'    => Auth::usuarioActual()['nombre_usuario'] ?? 'Invitado',
        'rol'        => Auth::rolActual(),
        'conexion'   => Database::testConnection(),
        'metricas'   => [
            'pacientes'    => (int)$db->query("SELECT COUNT(*) FROM paciente")->fetchColumn(),
            'evaluaciones' => (int)$db->query("SELECT COUNT(*) FROM evaluacion_biomecanica")->fetchColumn(),
            'piezas'       => (int)$db->query("SELECT COUNT(*) FROM pieza")->fetchColumn(),
            'entregas'     => (int)$db->query("SELECT COUNT(*) FROM entrega_pieza")->fetchColumn(),
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
$totalPiezas         = (int)$db->query("SELECT COUNT(*) FROM pieza")->fetchColumn();
$piezasDisponibles   = (int)$db->query("SELECT COUNT(*) FROM pieza WHERE estado = 'DISPONIBLE'")->fetchColumn();
$totalEntregas       = (int)$db->query("SELECT COUNT(*) FROM entrega_pieza")->fetchColumn();

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1" style="color: var(--turquesa-oscuro);">Panel Principal</h3>
        <p class="text-muted small mb-0">Bienvenido(a), <strong><?= htmlspecialchars($usuarioActual['nombre_completo']) ?></strong> (<?= htmlspecialchars($rolActual) ?>)</p>
    </div>
</div>

<!-- Tarjetas de Métricas -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
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

    <div class="col-sm-6 col-xl-3">
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

    <div class="col-sm-6 col-xl-3">
        <div class="card card-custom card-stat p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small text-uppercase fw-semibold">Piezas Protésicas</div>
                    <div class="fs-3 fw-bold text-dark mt-1"><?= $totalPiezas ?></div>
                    <small class="text-success fw-semibold"><?= $piezasDisponibles ?> disponibles</small>
                </div>
                <div class="rounded-circle p-3" style="background-color: var(--turquesa-claro); color: var(--turquesa);">
                    <i class="bi bi-tools fs-4"></i>
                </div>
            </div>
            <?php if (Permisos::puede($rolActual, 'piezas', 'listar')): ?>
                <a href="piezas.php" class="small mt-2 d-inline-block text-decoration-none">Ver catálogo de piezas &rarr;</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card card-custom card-stat p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small text-uppercase fw-semibold">Entregas Realizadas</div>
                    <div class="fs-3 fw-bold text-dark mt-1"><?= $totalEntregas ?></div>
                </div>
                <div class="rounded-circle p-3" style="background-color: var(--turquesa-claro); color: var(--turquesa);">
                    <i class="bi bi-box-seam fs-4"></i>
                </div>
            </div>
            <?php if (Permisos::puede($rolActual, 'entregas', 'listar')): ?>
                <a href="entregas.php" class="small mt-2 d-inline-block text-decoration-none">Ver historial de entregas &rarr;</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
