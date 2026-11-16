<?php
/**
 * Consulta de Detalle por ID (Compatibilidad con URLs directas)
 * Asignatura: Bases de Datos - Ingeniería de Sistemas (ULA)
 *
 * Resuelve peticiones directas como detalle.php?id=1 o detalle.php?modulo=pacientes&id=1
 * devolviendo los datos estructurados sin vistas HTML complejas.
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/funciones.php';
require_once __DIR__ . '/auth/Auth.php';
require_once __DIR__ . '/controllers/PacienteController.php';
require_once __DIR__ . '/controllers/EvaluacionController.php';
require_once __DIR__ . '/controllers/DispositivoController.php';
require_once __DIR__ . '/controllers/ComponenteController.php';
require_once __DIR__ . '/controllers/HistorialAjusteController.php';
require_once __DIR__ . '/controllers/MantenimientoController.php';

// Para visualización académica directa, si no hay sesión activa se inicia como administrador
if (!Auth::estaAutenticado()) {
    Auth::iniciarSesionComo('admin');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$modulo = strtolower($_GET['modulo'] ?? 'dispositivos');
$esJson = (isset($_GET['format']) && $_GET['format'] === 'json') ||
          (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

if ($id <= 0) {
    $res = [
        'success' => false,
        'mensaje' => 'Debe especificar un parámetro ID numérico válido mayor a cero (ejemplo: detalle.php?id=1 o detalle.php?modulo=pacientes&id=1).'
    ];
    if ($esJson) json_response($res, 400);

    header('Content-Type: text/plain; charset=utf-8', true, 400);
    echo "ERROR: {$res['mensaje']}\n";
    exit;
}

$controlador = match ($modulo) {
    'pacientes'      => new PacienteController(),
    'evaluaciones'   => new EvaluacionController(),
    'dispositivos'   => new DispositivoController(),
    'componentes'    => new ComponenteController(),
    'ajustes'        => new HistorialAjusteController(),
    'mantenimientos' => new MantenimientoController(),
    default          => null
};

if (!$controlador) {
    $res = [
        'success' => false,
        'mensaje' => "Módulo '{$modulo}' no reconocido. Módulos disponibles: pacientes, evaluaciones, dispositivos, componentes, ajustes, mantenimientos."
    ];
    if ($esJson) json_response($res, 404);

    header('Content-Type: text/plain; charset=utf-8', true, 404);
    echo "ERROR: {$res['mensaje']}\n";
    exit;
}

$resultado = $controlador->show($id);

if ($esJson) {
    json_response($resultado, $resultado['success'] ? 200 : 404);
}

// Presentación estructurada en texto plano
header('Content-Type: text/plain; charset=utf-8');
echo "===========================================================================\n";
echo " CONSULTA DE DETALLE POR ID - MÓDULO: " . strtoupper($modulo) . "\n";
echo " Asignatura: Bases de Datos - Ingeniería de Sistemas (ULA)\n";
echo " Usuario en sesión: " . Auth::usuarioActual()['nombre_usuario'] . " (" . Auth::rolActual() . ")\n";
echo "===========================================================================\n\n";

if (!$resultado['success']) {
    echo " [✗ NO ENCONTRADO] {$resultado['mensaje']}\n";
} else {
    echo " [✓ ENCONTRADO] Datos del registro ID {$id}:\n";
    foreach ($resultado['data'] as $columna => $valor) {
        $valorStr = is_bool($valor) ? ($valor ? 'TRUE' : 'FALSE') : (string)($valor ?? 'NULL');
        echo "   • " . str_pad($columna, 28) . ": {$valorStr}\n";
    }

    // Si es un dispositivo, mostrar también sus componentes instalados
    if ($modulo === 'dispositivos' && $controlador instanceof DispositivoController) {
        $comps = $controlador->componentes($id);
        if ($comps['success'] && !empty($comps['data'])) {
            echo "\n Componentes Físicos Instalados en este Dispositivo:\n";
            foreach ($comps['data'] as $c) {
                echo "   └─ [Serie: {$c['numero_serie']}] {$c['nombre_modelo']} ({$c['nombre_categoria']}) - Fabricante: {$c['nombre_fabricante']} (Instalado: {$c['fecha_instalacion']})\n";
            }
        }
    }
}

echo "\n===========================================================================\n";
