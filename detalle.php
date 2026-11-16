<?php
/**
 * Consulta de Detalle por ID (Compatibilidad con URLs directas)
 *
 * Resuelve peticiones directas como detalle.php?id=1 o detalle.php?modulo=piezas&id=1
 * devolviendo los datos estructurados en JSON o texto plano.
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/funciones.php';
require_once __DIR__ . '/auth/Auth.php';
require_once __DIR__ . '/controllers/PacienteController.php';
require_once __DIR__ . '/controllers/EvaluacionController.php';
require_once __DIR__ . '/controllers/PiezaController.php';
require_once __DIR__ . '/controllers/EntregaController.php';
require_once __DIR__ . '/controllers/UsuarioController.php';

// Si no hay sesión activa se inicia temporalmente como administrador para consulta directa
if (!Auth::estaAutenticado()) {
    Auth::iniciarSesionComo('admin');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$modulo = strtolower($_GET['modulo'] ?? 'piezas');
$esJson = (isset($_GET['format']) && $_GET['format'] === 'json') ||
          (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

if ($id <= 0) {
    $res = [
        'success' => false,
        'mensaje' => 'Debe especificar un parámetro ID numérico válido mayor a cero (ejemplo: detalle.php?id=1 o detalle.php?modulo=piezas&id=1).'
    ];
    if ($esJson) json_response($res, 400);

    header('Content-Type: text/plain; charset=utf-8', true, 400);
    echo "ERROR: {$res['mensaje']}\n";
    exit;
}

$controlador = match ($modulo) {
    'pacientes'    => new PacienteController(),
    'evaluaciones' => new EvaluacionController(),
    'piezas'       => new PiezaController(),
    'entregas'     => new EntregaController(),
    'usuarios'     => new UsuarioController(),
    default        => null
};

if (!$controlador) {
    $res = [
        'success' => false,
        'mensaje' => "El módulo '{$modulo}' no es válido. Módulos soportados: pacientes, evaluaciones, piezas, entregas, usuarios."
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

// Salida en texto plano legible
header('Content-Type: text/plain; charset=utf-8');
if (!$resultado['success']) {
    echo "CONSULTA DE DETALLE FALLIDA\n";
    echo "Mensaje: {$resultado['mensaje']}\n";
    exit;
}

echo "===========================================================================\n";
echo " DETALLE DEL REGISTRO [MÓDULO: " . strtoupper($modulo) . " | ID: {$id}]\n";
echo "===========================================================================\n";
foreach ($resultado['data'] as $campo => $valor) {
    if (is_array($valor)) {
        echo sprintf(" %-28s : %s\n", $campo, json_encode($valor, JSON_UNESCAPED_UNICODE));
    } else {
        echo sprintf(" %-28s : %s\n", $campo, (string)$valor);
    }
}
echo "===========================================================================\n";
