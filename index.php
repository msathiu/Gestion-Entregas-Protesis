<?php
/**
 * Punto de Entrada Principal: Sistema de Gestión de Prótesis y Órtesis
 * Asignatura: Bases de Datos - Ingeniería de Sistemas (ULA)
 *
 * Etapa 1: Arquitectura Base, Conectividad PostgreSQL y Controladores CRUD
 * (Sin interfaz gráfica / Sin vistas HTML según especificación)
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/funciones.php';

// Si se solicita JSON o se ejecuta desde curl/postman/cli
$esJson = (isset($_GET['format']) && $_GET['format'] === 'json') ||
          (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

$estadoConexion = Database::testConnection();

$metricas = [
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

$infoSistema = [
    'proyecto'    => 'Sistema de Gestión de Adaptaciones y Mantenimiento de Prótesis y Órtesis',
    'asignatura'  => 'Bases de Datos',
    'carrera'     => 'Ingeniería de Sistemas - Universidad de Los Andes (ULA)',
    'etapa'       => 'Fase 1: Base Técnica, Modelo Relacional PostgreSQL y Lógica CRUD (Sin UI)',
    'servidor'    => [
        'php_version' => PHP_VERSION,
        'sapi'        => php_sapi_name(),
        'conexion_bd' => $estadoConexion
    ],
    'modulos_crud' => [
        'pacientes'    => ['modelo' => 'models/Paciente.php', 'controlador' => 'controllers/PacienteController.php'],
        'evaluaciones' => ['modelo' => 'models/Evaluacion.php', 'controlador' => 'controllers/EvaluacionController.php'],
        'dispositivos' => ['modelo' => 'models/Dispositivo.php', 'controlador' => 'controllers/DispositivoController.php'],
        'componentes'  => ['modelo' => 'models/Componente.php', 'controlador' => 'controllers/ComponenteController.php'],
        'ajustes'      => ['modelo' => 'models/HistorialAjuste.php', 'controlador' => 'controllers/HistorialAjusteController.php'],
        'mantenimiento'=> ['modelo' => 'models/Mantenimiento.php', 'controlador' => 'controllers/MantenimientoController.php'],
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
echo " Asignatura: {$infoSistema['asignatura']} | {$infoSistema['etapa']}\n";
echo "===========================================================================\n\n";

echo "ESTADO DE CONEXIÓN CON POSTGRESQL:\n";
echo " • Éxito: " . ($estadoConexion['success'] ? 'SÍ (Conectado)' : 'NO') . "\n";
echo " • Mensaje: " . ($estadoConexion['message'] ?? '-') . "\n";
if (!empty($estadoConexion['version'])) {
    echo " • Versión PostgreSQL: " . $estadoConexion['version'] . "\n";
}
echo "\n";

echo "MÉTRICAS DEL MODELO RELACIONAL (Registros actuales en BD):\n";
echo " • Pacientes registrados:           {$metricas['pacientes']}\n";
echo " • Evaluaciones biomecánicas:       {$metricas['evaluaciones']}\n";
echo " • Dispositivos protésicos:         {$metricas['dispositivos']}\n";
echo " • Componentes físicos:             {$metricas['componentes']}\n";
echo " • Historial de ajustes técnicos:   {$metricas['ajustes']}\n";
echo " • Mantenimientos preventivos:      {$metricas['mantenimientos']}\n";
echo "\n";

echo "MÓDULOS CRUD IMPLEMENTADOS (Sin UI):\n";
foreach ($infoSistema['modulos_crud'] as $nombre => $archivos) {
    echo " • " . str_pad(ucfirst($nombre), 15) . " -> " . $archivos['modelo'] . " | " . $archivos['controlador'] . "\n";
}
echo "\n";

echo "EJECUCIÓN DEL BANCO DE PRUEBAS:\n";
echo " Para ejecutar las pruebas automatizadas de conexión, integridad referencial y JOINs:\n";
echo "   Terminal local / Contenedor:  php tests/probar_conexion.php\n";
echo "   Desde Docker:                 docker exec -it protesis_app php tests/probar_conexion.php\n";
echo "   Desde navegador:              http://localhost:8080/tests/probar_conexion.php\n";
echo "   Salida JSON de este estado:   http://localhost:8080/?format=json\n";
echo "===========================================================================\n";
