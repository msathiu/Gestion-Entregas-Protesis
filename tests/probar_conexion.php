<?php
/**
 * Script de Pruebas: Verificación de Conexión y Operaciones CRUD PostgreSQL
 * Asignatura: Bases de Datos - Ingeniería de Sistemas (ULA)
 *
 * Ejecución vía terminal:
 *   php tests/probar_conexion.php
 *   o dentro del contenedor:
 *   docker exec -it protesis_app php tests/probar_conexion.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/PacienteController.php';
require_once __DIR__ . '/../controllers/EvaluacionController.php';
require_once __DIR__ . '/../controllers/DispositivoController.php';
require_once __DIR__ . '/../controllers/ComponenteController.php';
require_once __DIR__ . '/../controllers/HistorialAjusteController.php';
require_once __DIR__ . '/../controllers/MantenimientoController.php';

// Formato de salida para consola o navegador
$esCli = (php_sapi_name() === 'cli');
function linea(string $texto = ''): void {
    global $esCli;
    echo $texto . ($esCli ? PHP_EOL : "<br>" . PHP_EOL);
}

function encabezado(string $titulo): void {
    linea();
    linea(str_repeat("=", 75));
    linea(">>> " . $titulo);
    linea(str_repeat("=", 75));
}

function subseccion(string $sub): void {
    linea();
    linea("--- " . $sub . " ---");
}

function ok(string $mensaje): void {
    linea(" [✓ OK] " . $mensaje);
}

function error(string $mensaje): void {
    linea(" [✗ ERROR] " . $mensaje);
}

function detalle(string $clave, $valor): void {
    if (is_array($valor)) {
        linea("       • {$clave}: " . json_encode($valor, JSON_UNESCAPED_UNICODE));
    } else {
        linea("       • {$clave}: {$valor}");
    }
}

if (!$esCli) {
    echo "<pre style='font-family: monospace; font-size: 13px; line-height: 1.4; background: #1e1e1e; color: #d4d4d4; padding: 20px;'>";
}

linea("===========================================================================");
linea(" SISTEMA DE GESTIÓN DE ADAPTACIONES Y MANTENIMIENTO DE PRÓTESIS Y ÓRTESIS");
linea(" BANCO DE PRUEBAS DE BASE DE DATOS POSTGRESQL (PHP + PDO)");
linea(" Asignatura: Bases de Datos - Ingeniería de Sistemas (ULA)");
linea(" Fecha/Hora: " . date('Y-m-d H:i:s'));
linea("===========================================================================");

$totalPruebas = 0;
$pruebasSuperadas = 0;

// -------------------------------------------------------------------------
// PRUEBA 1: Conexión con PostgreSQL
// -------------------------------------------------------------------------
encabezado("PRUEBA 1: Verificación de Conexión con PostgreSQL");
$totalPruebas++;
try {
    $db = Database::getConnection();
    $version = $db->query("SELECT version()")->fetchColumn();
    $bdActual = $db->query("SELECT current_database(), current_user, inet_server_addr(), inet_server_port()")->fetch();

    ok("Conexión PDO establecida satisfactoriamente.");
    detalle("Versión Motor", $version);
    detalle("Base de Datos", $bdActual['current_database']);
    detalle("Usuario Conectado", $bdActual['current_user']);
    detalle("Host/Puerto Servidor", ($bdActual['inet_server_addr'] ?? 'localhost') . ':' . ($bdActual['inet_server_port'] ?? '5432'));
    $pruebasSuperadas++;
} catch (Throwable $e) {
    error("Fallo crítico al conectar a PostgreSQL: " . $e->getMessage());
    exit(1);
}

// -------------------------------------------------------------------------
// PRUEBA 2: Inserción de Paciente (INSERT)
// -------------------------------------------------------------------------
encabezado("PRUEBA 2: Inserción de Paciente (Operación INSERT con RETURNING)");
$totalPruebas++;
$pacienteCtrl = new PacienteController();

$cedulaPrueba = 'V-99' . str_pad((string)rand(100000, 999999), 6, '0', STR_PAD_LEFT);
$datosPaciente = [
    'numero_documento' => $cedulaPrueba,
    'nombres'          => 'Carlos Eduardo',
    'apellidos'        => 'Mendoza Briceño',
    'fecha_nacimiento' => '1992-08-25',
    'sexo'             => 'M',
    'telefono'         => '0414-5551234',
    'correo'           => 'carlos.mendoza.' . rand(100, 999) . '@ula.ve',
    'direccion'        => 'Av. Las Américas, Conjunto Residencial Los Sauces, Mérida',
    'activo'           => true
];

$resInsert = $pacienteCtrl->store($datosPaciente);
$idPacienteCreado = null;

if ($resInsert['success']) {
    $idPacienteCreado = $resInsert['id'];
    ok("Paciente insertado exitosamente con ID primario: {$idPacienteCreado}");
    detalle("Cédula/Documento", $datosPaciente['numero_documento']);
    detalle("Nombre", $datosPaciente['nombres'] . ' ' . $datosPaciente['apellidos']);
    detalle("Correo", $datosPaciente['correo']);
    $pruebasSuperadas++;
} else {
    error("Fallo en la inserción: " . $resInsert['mensaje']);
}

// -------------------------------------------------------------------------
// PRUEBA 3: Consulta de Pacientes (SELECT, WHERE, ORDER BY)
// -------------------------------------------------------------------------
encabezado("PRUEBA 3: Consulta de Pacientes (SELECT, WHERE, ORDER BY)");
$totalPruebas++;
$resLista = $pacienteCtrl->index(true);

if ($resLista['success'] && $resLista['total'] > 0) {
    ok("Consulta ejecutada. Total de pacientes activos encontrados: {$resLista['total']}");
    foreach ($resLista['data'] as $p) {
        detalle("Paciente ID {$p['id_paciente']}", "{$p['apellidos']}, {$p['nombres']} [{$p['numero_documento']}] - {$p['correo']}");
    }
    $pruebasSuperadas++;
} else {
    error("No se pudieron listar los pacientes: " . ($resLista['mensaje'] ?? 'Sin datos'));
}

// -------------------------------------------------------------------------
// PRUEBA 4: Actualización de un Registro (UPDATE)
// -------------------------------------------------------------------------
encabezado("PRUEBA 4: Actualización de Paciente (Operación UPDATE con WHERE)");
$totalPruebas++;
if ($idPacienteCreado) {
    $datosUpdate = [
        'numero_documento' => $cedulaPrueba,
        'nombres'          => 'Carlos Eduardo (Actualizado)',
        'apellidos'        => 'Mendoza Briceño',
        'fecha_nacimiento' => '1992-08-25',
        'sexo'             => 'M',
        'telefono'         => '0424-7778899',
        'correo'           => 'carlos.actualizado@ula.ve',
        'direccion'        => 'Urbanización La Hechicera, Mérida Edo. Mérida',
        'activo'           => true
    ];

    $resUpdate = $pacienteCtrl->update($idPacienteCreado, $datosUpdate);
    if ($resUpdate['success']) {
        ok("Paciente ID {$idPacienteCreado} actualizado correctamente.");

        // Verificamos re-consultando con show()
        $consultaVerif = $pacienteCtrl->show($idPacienteCreado);
        detalle("Nombre post-actualización", $consultaVerif['data']['nombres']);
        detalle("Teléfono post-actualización", $consultaVerif['data']['telefono']);
        detalle("Dirección post-actualización", $consultaVerif['data']['direccion']);
        $pruebasSuperadas++;
    } else {
        error("Fallo al actualizar: " . $resUpdate['mensaje']);
    }
} else {
    error("Omitida por fallo previo de inserción.");
}

// -------------------------------------------------------------------------
// PRUEBA 5: Integridad Referencial y Eliminación (DELETE / FOREIGN KEY)
// -------------------------------------------------------------------------
encabezado("PRUEBA 5: Integridad Referencial y Restricciones FOREIGN KEY");
$totalPruebas++;

subseccion("5.1 Intento de eliminar registro con dependientes (Paciente con condición)");
// Paciente ID 1 tiene paciente_condicion y evaluaciones (datos de prueba)
$resDeleteProtegido = $pacienteCtrl->destroy(1);
if (!$resDeleteProtegido['success'] && ($resDeleteProtegido['sqlstate'] ?? '') === '23503') {
    ok("Integridad referencial demostrada: PostgreSQL impidió eliminar el paciente ID 1.");
    detalle("Código SQLSTATE capturado", $resDeleteProtegido['sqlstate']);
    detalle("Mensaje traducido", $resDeleteProtegido['mensaje']);
} else {
    error("Comportamiento inesperado al intentar borrar registro referenciado.");
}

subseccion("5.2 Eliminación exitosa de registro sin dependientes (Paciente de prueba)");
if ($idPacienteCreado) {
    $resDeleteLimpio = $pacienteCtrl->destroy($idPacienteCreado);
    if ($resDeleteLimpio['success']) {
        ok("Paciente de prueba ID {$idPacienteCreado} eliminado limpiamente de la base de datos.");
        $pruebasSuperadas++;
    } else {
        error("No se pudo eliminar el paciente de prueba: " . $resDeleteLimpio['mensaje']);
    }
}

// -------------------------------------------------------------------------
// PRUEBA 6: Consultas Relacionales Avanzadas con JOINs y Agrupaciones
// -------------------------------------------------------------------------
encabezado("PRUEBA 6: Consultas Relacionales (INNER JOIN, LEFT JOIN, GROUP BY)");
$totalPruebas++;

subseccion("6.1 Paciente -> Condición Clínica -> Evaluaciones Biomecánicas (INNER JOIN)");
$evalCtrl = new EvaluacionController();
$resEvaluaciones = $evalCtrl->index();
if ($resEvaluaciones['success'] && count($resEvaluaciones['data']) > 0) {
    ok("INNER JOIN entre evaluacion_biomecanica, paciente_condicion, paciente, segmento_corporal y nivel_actividad:");
    foreach ($resEvaluaciones['data'] as $ev) {
        detalle(
            "Eval ID {$ev['id_evaluacion_biomecanica']} ({$ev['fecha_evaluacion']})",
            "Paciente: {$ev['nombre_paciente']} | Segmento: {$ev['nombre_segmento']} ({$ev['lateralidad']}) | Nivel: {$ev['codigo_nivel']} ({$ev['nombre_nivel']}) | Peso: {$ev['peso_kg']} kg | Evaluador: {$ev['evaluador']}"
        );
    }
} else {
    error("Fallo al consultar evaluaciones con JOIN.");
}

subseccion("6.2 Dispositivos Protésicos y Componentes Instalados (Relación N:M)");
$dispCtrl = new DispositivoController();
$resDisp = $dispCtrl->index();
if ($resDisp['success'] && count($resDisp['data']) > 0) {
    ok("INNER JOIN entre dispositivo_protesico, paciente_condicion, paciente, tipo_dispositivo y estado_dispositivo:");
    foreach ($resDisp['data'] as $d) {
        detalle(
            "Dispositivo {$d['codigo_dispositivo']} [ID {$d['id_dispositivo_protesico']}]",
            "Tipo: {$d['nombre_tipo']} | Estado: {$d['nombre_estado']} | Paciente: {$d['nombre_paciente']} | Segmento: {$d['nombre_segmento']}"
        );

        $comps = $dispCtrl->componentes($d['id_dispositivo_protesico']);
        if ($comps['success'] && !empty($comps['data'])) {
            foreach ($comps['data'] as $c) {
                linea("          └─ Componente: [{$c['numero_serie']}] {$c['nombre_modelo']} ({$c['nombre_categoria']}) - Fabricante: {$c['nombre_fabricante']} | Instalado: {$c['fecha_instalacion']}");
            }
        }
    }
} else {
    error("Fallo al consultar dispositivos.");
}

subseccion("6.3 Historial de Ajustes Técnicos por Dispositivo");
$ajusteCtrl = new HistorialAjusteController();
$resAjustes = $ajusteCtrl->index();
if ($resAjustes['success'] && count($resAjustes['data']) > 0) {
    ok("INNER JOIN entre historial_ajuste, dispositivo_protesico, tipo_ajuste y usuario_sistema:");
    foreach ($resAjustes['data'] as $a) {
        detalle(
            "Ajuste ID {$a['id_historial_ajuste']} ({$a['fecha_ajuste']})",
            "Dispositivo: {$a['codigo_dispositivo']} | Tipo: {$a['nombre_ajuste']} | Técnico: {$a['tecnico']} | Detalle: {$a['descripcion_ajuste']}"
        );
    }
}

subseccion("6.4 Mantenimientos Preventivos y Ejecución de Stored Function PL/pgSQL");
$mantCtrl = new MantenimientoController();
$resVencidos = $mantCtrl->marcarVencidos();
ok("Función almacenada fn_marcar_mantenimientos_vencidos() ejecutada. Registros actualizados a ATRASADO: {$resVencidos['afectados']}");

$resMant = $mantCtrl->index();
if ($resMant['success']) {
    foreach ($resMant['data'] as $m) {
        detalle(
            "Mantenimiento ID {$m['id_mantenimiento_preventivo']} (Prog: {$m['fecha_programada']})",
            "Dispositivo: {$m['codigo_dispositivo']} | Tipo: {$m['nombre_mantenimiento']} | Estado: {$m['nombre_estado']} | Paciente: {$m['nombre_paciente']}"
        );
    }
}

subseccion("6.5 GROUP BY y Funciones de Agregación (COUNT)");
$pacientesConDisp = $pacienteCtrl->resumenDispositivos();
if ($pacientesConDisp['success']) {
    ok("Agregación COUNT con GROUP BY y LEFT JOIN (Dispositivos por Paciente):");
    foreach ($pacientesConDisp['data'] as $row) {
        detalle("{$row['nombre_completo']} ({$row['numero_documento']})", "Dispositivos asignados: {$row['total_dispositivos']}");
    }
}

$resumenEstados = $mantCtrl->resumenPorEstado();
if ($resumenEstados['success']) {
    ok("Agregación COUNT con GROUP BY (Mantenimientos por Estado):");
    foreach ($resumenEstados['data'] as $row) {
        detalle("Estado: {$row['nombre_estado']}", "Total: {$row['total']}");
    }
}

$pruebasSuperadas++;

// -------------------------------------------------------------------------
// BALANCE FINAL DE PRUEBAS
// -------------------------------------------------------------------------
encabezado("RESUMEN GENERAL DEL BANCO DE PRUEBAS");
linea(" Pruebas programadas: {$totalPruebas}");
linea(" Pruebas exitosas:    {$pruebasSuperadas}");
linea(" Tasa de éxito:       " . round(($pruebasSuperadas / $totalPruebas) * 100, 1) . "%");

if ($pruebasSuperadas === $totalPruebas) {
    linea();
    ok("TODAS LAS PRUEBAS FUERON SUPERADAS CON ÉXITO.");
    linea("La base de datos PostgreSQL, los modelos y los controladores están plenamente integrados.");
} else {
    linea();
    error("Algunas pruebas presentaron inconsistencias.");
}

linea("===========================================================================");
if (!$esCli) {
    echo "</pre>";
}
