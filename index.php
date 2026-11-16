<?php
/**
 * Punto de Entrada Principal: Sistema de Gestión de Prótesis y Órtesis
 * Asignatura: Bases de Datos - Ingeniería de Sistemas (ULA)
 *
 * Arquitectura Backend, Conectividad PostgreSQL, Roles, Permisos y Controladores CRUD
 * (Sin interfaz gráfica / Sin vistas HTML según especificación)
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/funciones.php';
require_once __DIR__ . '/auth/Auth.php';
require_once __DIR__ . '/auth/Permisos.php';

// Controladores
require_once __DIR__ . '/controllers/UsuarioController.php';
require_once __DIR__ . '/controllers/PacienteController.php';
require_once __DIR__ . '/controllers/EvaluacionController.php';
require_once __DIR__ . '/controllers/DispositivoController.php';
require_once __DIR__ . '/controllers/ComponenteController.php';
require_once __DIR__ . '/controllers/HistorialAjusteController.php';
require_once __DIR__ . '/controllers/MantenimientoController.php';
require_once __DIR__ . '/controllers/AuthController.php';

$esJson = (isset($_GET['format']) && $_GET['format'] === 'json') ||
          (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

// -------------------------------------------------------------------------
// SOPORTE PARA CONSULTA DE DETALLE POR ID (Rutas /index.php?modulo=...&id=...)
// -------------------------------------------------------------------------
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    $modulo = strtolower($_GET['modulo'] ?? 'dispositivos');

    // Para fines de consulta académica si no hay sesión activa iniciamos temporalmente como admin
    if (!Auth::estaAutenticado()) {
        Auth::iniciarSesionComo('admin');
    }

    $controlador = match ($modulo) {
        'pacientes'      => new PacienteController(),
        'evaluaciones'   => new EvaluacionController(),
        'dispositivos'   => new DispositivoController(),
        'componentes'    => new ComponenteController(),
        'ajustes'        => new HistorialAjusteController(),
        'mantenimientos' => new MantenimientoController(),
        'usuarios'       => new UsuarioController(),
        default          => null
    };

    if ($controlador) {
        $resultado = $controlador->show($id);
        if ($esJson) json_response($resultado, $resultado['success'] ? 200 : 404);

        header('Content-Type: text/plain; charset=utf-8');
        echo "===========================================================================\n";
        echo " DETALLE DEL REGISTRO ID {$id} EN MÓDULO: " . strtoupper($modulo) . "\n";
        echo "===========================================================================\n\n";
        if (!$resultado['success']) {
            echo " [✗ NO ENCONTRADO] {$resultado['mensaje']}\n";
        } else {
            foreach ($resultado['data'] as $k => $v) {
                echo " • " . str_pad($k, 25) . ": " . (is_bool($v) ? ($v ? 'TRUE' : 'FALSE') : (string)($v ?? 'NULL')) . "\n";
            }
        }
        echo "\n===========================================================================\n";
        exit;
    }
}

// -------------------------------------------------------------------------
// ACCIONES DE AUTENTICACIÓN DIRECTA (Opcional por GET/POST)
// -------------------------------------------------------------------------
$accion = $_GET['accion'] ?? null;
if ($accion === 'login' && isset($_GET['usuario'], $_GET['clave'])) {
    $resLogin = Auth::login($_GET['usuario'], $_GET['clave']);
    if ($esJson) json_response($resLogin);
    header('Content-Type: text/plain; charset=utf-8');
    echo ($resLogin['success'] ? "[✓ OK] " : "[✗ ERROR] ") . $resLogin['mensaje'] . "\n";
    exit;
}

if ($accion === 'logout') {
    Auth::logout();
    $res = ['success' => true, 'mensaje' => 'Sesión cerrada exitosamente.'];
    if ($esJson) json_response($res);
    header('Content-Type: text/plain; charset=utf-8');
    echo "[✓ OK] Sesión cerrada.\n";
    exit;
}

$estadoConexion = Database::testConnection();

$metricas = [
    'usuarios'        => 0,
    'pacientes'       => 0,
    'evaluaciones'    => 0,
    'dispositivos'    => 0,
    'componentes'     => 0,
    'ajustes'         => 0,
    'mantenimientos'  => 0,
];

if ($estadoConexion['success']) {
    try {
        $db = Database::getConnection();
        $metricas['usuarios']       = (int)$db->query("SELECT COUNT(*) FROM usuario_sistema")->fetchColumn();
        $metricas['pacientes']      = (int)$db->query("SELECT COUNT(*) FROM paciente")->fetchColumn();
        $metricas['evaluaciones']   = (int)$db->query("SELECT COUNT(*) FROM evaluacion_biomecanica")->fetchColumn();
        $metricas['dispositivos']   = (int)$db->query("SELECT COUNT(*) FROM dispositivo_protesico")->fetchColumn();
        $metricas['componentes']    = (int)$db->query("SELECT COUNT(*) FROM componente")->fetchColumn();
        $metricas['ajustes']        = (int)$db->query("SELECT COUNT(*) FROM historial_ajuste")->fetchColumn();
        $metricas['mantenimientos'] = (int)$db->query("SELECT COUNT(*) FROM mantenimiento_preventivo")->fetchColumn();
    } catch (Throwable $e) {
        $metricas['error'] = $e->getMessage();
    }
}

$usuarioSesion = Auth::usuarioActual();

$infoSistema = [
    'proyecto'    => 'Sistema de Gestión de Adaptaciones y Mantenimiento de Prótesis y Órtesis',
    'asignatura'  => 'Bases de Datos',
    'carrera'     => 'Ingeniería de Sistemas - Universidad de Los Andes (ULA)',
    'servidor'    => [
        'php_version' => PHP_VERSION,
        'sapi'        => php_sapi_name(),
        'conexion_bd' => $estadoConexion
    ],
    'sesion_actual' => [
        'autenticado' => Auth::estaAutenticado(),
        'usuario'     => $usuarioSesion['nombre_usuario'] ?? 'Ninguno (Invitado)',
        'rol'         => Auth::rolActual(),
    ],
    'roles_del_sistema' => [
        'ADMINISTRADOR'      => 'Acceso total y administración de usuarios',
        'TECNICO_ORTOPEDICO' => 'Gestión de componentes, ajustes y mantenimientos preventivos',
        'EVALUADOR_CLINICO'  => 'Registro de pacientes, evaluaciones biomecánicas y medidas'
    ],
    'cuentas_demo' => [
        ['usuario' => 'admin',      'rol' => 'ADMINISTRADOR',      'clave' => 'Admin123*'],
        ['usuario' => 'jperez',     'rol' => 'TECNICO_ORTOPEDICO', 'clave' => 'Tecnico123*'],
        ['usuario' => 'mrodriguez', 'rol' => 'EVALUADOR_CLINICO',  'clave' => 'Clinico123*'],
    ],
    'modulos_crud' => [
        'usuarios'       => ['modelo' => 'models/Usuario.php', 'controlador' => 'controllers/UsuarioController.php'],
        'pacientes'      => ['modelo' => 'models/Paciente.php', 'controlador' => 'controllers/PacienteController.php'],
        'evaluaciones'   => ['modelo' => 'models/Evaluacion.php', 'controlador' => 'controllers/EvaluacionController.php'],
        'dispositivos'   => ['modelo' => 'models/Dispositivo.php', 'controlador' => 'controllers/DispositivoController.php'],
        'componentes'    => ['modelo' => 'models/Componente.php', 'controlador' => 'controllers/ComponenteController.php'],
        'ajustes'        => ['modelo' => 'models/HistorialAjuste.php', 'controlador' => 'controllers/HistorialAjusteController.php'],
        'mantenimientos' => ['modelo' => 'models/Mantenimiento.php', 'controlador' => 'controllers/MantenimientoController.php'],
    ],
    'metricas_base_datos' => $metricas,
    'banco_de_pruebas'    => [
        'ruta'    => 'tests/probar_conexion.php',
        'comando' => 'docker exec -it protesis_app php tests/probar_conexion.php'
    ]
];

if ($esJson) {
    json_response($infoSistema);
}

// Presentación estructurada en texto plano / consola
header('Content-Type: text/plain; charset=utf-8');
echo "===========================================================================\n";
echo " {$infoSistema['proyecto']}\n";
echo " {$infoSistema['carrera']}\n";
echo " Asignatura: {$infoSistema['asignatura']}\n";
echo "===========================================================================\n\n";

echo "ESTADO DE CONEXIÓN CON POSTGRESQL:\n";
echo " • Éxito: " . ($estadoConexion['success'] ? 'SÍ (Conectado)' : 'NO') . "\n";
echo " • Versión PostgreSQL: " . ($estadoConexion['version'] ?? 'N/A') . "\n";
echo " • Sesión activa actual: " . ($usuarioSesion ? "{$usuarioSesion['nombre_usuario']} [{$usuarioSesion['nombre_rol']}]" : "Ninguna (Invitado)") . "\n";
echo "\n";

echo "ROLES Y CUENTAS DEMO (Cifradas con password_hash / BCRYPT):\n";
foreach ($infoSistema['cuentas_demo'] as $cta) {
    echo " • Usuario: " . str_pad($cta['usuario'], 12) . " | Rol: " . str_pad($cta['rol'], 20) . " | Clave: {$cta['clave']}\n";
}
echo "\n";

echo "MÉTRICAS DEL MODELO RELACIONAL (Registros en PostgreSQL):\n";
echo " • Usuarios del sistema:            {$metricas['usuarios']}\n";
echo " • Pacientes registrados:           {$metricas['pacientes']}\n";
echo " • Evaluaciones biomecánicas:       {$metricas['evaluaciones']}\n";
echo " • Dispositivos protésicos:         {$metricas['dispositivos']}\n";
echo " • Componentes físicos:             {$metricas['componentes']}\n";
echo " • Historial de ajustes técnicos:   {$metricas['ajustes']}\n";
echo " • Mantenimientos preventivos:      {$metricas['mantenimientos']}\n";
echo "\n";

echo "CONSULTA DE DETALLES POR ID (Sin vistas HTML complejas):\n";
echo " • Dispositivo por ID:   http://localhost:8080/detalle.php?id=1\n";
echo " • O mediante index:     http://localhost:8080/?modulo=pacientes&id=1\n";
echo " • Salida en JSON:       http://localhost:8080/?format=json\n";
echo "\n";

echo "EJECUCIÓN DEL BANCO DE PRUEBAS:\n";
echo "   Desde Docker:         docker exec -it protesis_app php tests/probar_conexion.php\n";
echo "   Desde navegador:      http://localhost:8080/tests/probar_conexion.php\n";
echo "===========================================================================\n";
