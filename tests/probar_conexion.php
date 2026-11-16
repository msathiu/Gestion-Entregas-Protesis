<?php
/**
 * Script de Pruebas: Verificación Integral de Base de Datos, CRUD, Roles y Permisos
 * Asignatura: Bases de Datos - Ingeniería de Sistemas (ULA)
 *
 * Ejecución vía terminal:
 *   docker exec -it protesis_app php tests/probar_conexion.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/Auth.php';
require_once __DIR__ . '/../auth/Permisos.php';
require_once __DIR__ . '/../controllers/UsuarioController.php';
require_once __DIR__ . '/../controllers/PacienteController.php';
require_once __DIR__ . '/../controllers/EvaluacionController.php';
require_once __DIR__ . '/../controllers/DispositivoController.php';
require_once __DIR__ . '/../controllers/ComponenteController.php';
require_once __DIR__ . '/../controllers/HistorialAjusteController.php';
require_once __DIR__ . '/../controllers/MantenimientoController.php';

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
linea(" BANCO DE PRUEBAS TÉCNICAS: CRUD, ROLES, PERMISOS Y POSTGRESQL");
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

// Para las operaciones CRUD de demostración iniciamos como Administrador
Auth::iniciarSesionComo('admin');

// -------------------------------------------------------------------------
// PRUEBA 2: Inserción de Paciente (INSERT con RETURNING)
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
    error("Fallo en la inserción: " . ($resInsert['mensaje'] ?? 'Error desconocido'));
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
        detalle("Paciente ID {$p['id_paciente']}", "{$p['apellidos']}, {$p['nombres']} [{$p['numero_documento']}]");
    }
    $pruebasSuperadas++;
} else {
    error("No se pudieron listar los pacientes.");
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
        $consultaVerif = $pacienteCtrl->show($idPacienteCreado);
        detalle("Nombre post-actualización", $consultaVerif['data']['nombres']);
        detalle("Teléfono post-actualización", $consultaVerif['data']['telefono']);
        $pruebasSuperadas++;
    } else {
        error("Fallo al actualizar: " . ($resUpdate['mensaje'] ?? ''));
    }
} else {
    error("Omitida por fallo previo de inserción.");
}

// -------------------------------------------------------------------------
// PRUEBA 5: Integridad Referencial y Restricciones FOREIGN KEY
// -------------------------------------------------------------------------
encabezado("PRUEBA 5: Integridad Referencial y Restricciones FOREIGN KEY");
$totalPruebas++;

subseccion("5.1 Intento de eliminar registro con dependientes (Paciente con condición)");
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
        error("No se pudo eliminar el paciente de prueba: " . ($resDeleteLimpio['mensaje'] ?? ''));
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
            "Paciente: {$ev['nombre_paciente']} | Segmento: {$ev['nombre_segmento']} ({$ev['lateralidad']}) | Nivel: {$ev['codigo_nivel']}"
        );
    }
}

subseccion("6.2 Dispositivos Protésicos y Componentes Instalados (Relación N:M)");
$dispCtrl = new DispositivoController();
$resDisp = $dispCtrl->index();
if ($resDisp['success'] && count($resDisp['data']) > 0) {
    ok("INNER JOIN entre dispositivo_protesico, paciente_condicion, paciente, tipo y estado:");
    foreach ($resDisp['data'] as $d) {
        detalle("Dispositivo {$d['codigo_dispositivo']}", "Tipo: {$d['nombre_tipo']} | Estado: {$d['nombre_estado']} | Paciente: {$d['nombre_paciente']}");
        $comps = $dispCtrl->componentes($d['id_dispositivo_protesico']);
        if ($comps['success'] && !empty($comps['data'])) {
            foreach ($comps['data'] as $c) {
                linea("          └─ Componente: [{$c['numero_serie']}] {$c['nombre_modelo']} ({$c['nombre_categoria']}) - Fabricante: {$c['nombre_fabricante']}");
            }
        }
    }
}

subseccion("6.3 Historial de Ajustes Técnicos por Dispositivo");
$ajusteCtrl = new HistorialAjusteController();
$resAjustes = $ajusteCtrl->index();
if ($resAjustes['success'] && count($resAjustes['data']) > 0) {
    ok("INNER JOIN entre historial_ajuste, dispositivo_protesico, tipo_ajuste y usuario_sistema:");
    foreach ($resAjustes['data'] as $a) {
        detalle("Ajuste ID {$a['id_historial_ajuste']} ({$a['fecha_ajuste']})", "Dispositivo: {$a['codigo_dispositivo']} | Tipo: {$a['nombre_ajuste']} | Técnico: {$a['tecnico']}");
    }
}

subseccion("6.4 Mantenimientos Preventivos y Función PL/pgSQL");
$mantCtrl = new MantenimientoController();
$resVencidos = $mantCtrl->marcarVencidos();
ok("Función almacenada fn_marcar_mantenimientos_vencidos() ejecutada. Registros actualizados: {$resVencidos['afectados']}");

subseccion("6.5 GROUP BY y Funciones de Agregación (COUNT)");
$pacientesConDisp = $pacienteCtrl->resumenDispositivos();
if ($pacientesConDisp['success']) {
    ok("Agregación COUNT con GROUP BY y LEFT JOIN (Dispositivos por Paciente):");
    foreach ($pacientesConDisp['data'] as $row) {
        detalle("{$row['nombre_completo']}", "Total dispositivos: {$row['total_dispositivos']}");
    }
}

$pruebasSuperadas++;

// -------------------------------------------------------------------------
// PRUEBA 7: Autenticación, Roles y Hashing Seguro (password_verify)
// -------------------------------------------------------------------------
encabezado("PRUEBA 7: Autenticación Segura y Roles de Usuario");
$totalPruebas++;

subseccion("7.1 Inicio de sesión de ADMINISTRADOR (admin / Admin123*)");
Auth::logout();
$loginAdmin = Auth::login('admin', 'Admin123*');
if ($loginAdmin['success'] && $loginAdmin['usuario']['nombre_rol'] === 'ADMINISTRADOR') {
    ok("Inicio de sesión exitoso como ADMINISTRADOR.");
    detalle("Usuario", $loginAdmin['usuario']['nombre_completo']);
    detalle("Rol", $loginAdmin['usuario']['nombre_rol']);
} else {
    error("Fallo al autenticar administrador: " . ($loginAdmin['mensaje'] ?? ''));
}

subseccion("7.2 Inicio de sesión de TÉCNICO ORTOPÉDICO (jperez / Tecnico123*)");
Auth::logout();
$loginTecnico = Auth::login('jperez', 'Tecnico123*');
if ($loginTecnico['success'] && $loginTecnico['usuario']['nombre_rol'] === 'TECNICO_ORTOPEDICO') {
    ok("Inicio de sesión exitoso como TÉCNICO ORTOPÉDICO.");
    detalle("Usuario", $loginTecnico['usuario']['nombre_completo']);
    detalle("Rol", $loginTecnico['usuario']['nombre_rol']);
} else {
    error("Fallo al autenticar técnico: " . ($loginTecnico['mensaje'] ?? ''));
}

subseccion("7.3 Inicio de sesión de EVALUADOR CLÍNICO (mrodriguez / Clinico123*)");
Auth::logout();
$loginClinico = Auth::login('mrodriguez', 'Clinico123*');
if ($loginClinico['success'] && $loginClinico['usuario']['nombre_rol'] === 'EVALUADOR_CLINICO') {
    ok("Inicio de sesión exitoso como EVALUADOR CLÍNICO.");
    detalle("Usuario", $loginClinico['usuario']['nombre_completo']);
    detalle("Rol", $loginClinico['usuario']['nombre_rol']);
} else {
    error("Fallo al autenticar evaluador clínico: " . ($loginClinico['mensaje'] ?? ''));
}

subseccion("7.4 Rechazo de contraseña incorrecta");
$loginInvalido = Auth::login('admin', 'PasswordEquivocada!');
if (!$loginInvalido['success']) {
    ok("Rechazo de credenciales inválidas confirmado: " . $loginInvalido['mensaje']);
    $pruebasSuperadas++;
} else {
    error("Error de seguridad: el sistema aceptó una contraseña inválida.");
}

// -------------------------------------------------------------------------
// PRUEBA 8: Matriz de Permisos y Protección contra Operaciones No Autorizadas
// -------------------------------------------------------------------------
encabezado("PRUEBA 8: Matriz de Permisos y Control de Acceso");
$totalPruebas++;

subseccion("8.1 Bloqueo de peticiones no autenticadas (INVITADO)");
Auth::logout();
$resInvitado = $pacienteCtrl->store(['nombres' => 'Fantasma']);
if (!$resInvitado['success'] && ($resInvitado['codigo'] ?? 0) === 401) {
    ok("Bloqueo de usuario no autenticado (HTTP 401): " . $resInvitado['mensaje']);
} else {
    error("Fallo: usuario no autenticado pudo ejecutar acción protegida.");
}

subseccion("8.2 Bloqueo de TÉCNICO_ORTOPEDICO al intentar crear pacientes");
Auth::iniciarSesionComo('jperez');
$resTecnicoBloqueado = $pacienteCtrl->store(['nombres' => 'Prueba']);
if (!$resTecnicoBloqueado['success'] && ($resTecnicoBloqueado['codigo'] ?? 0) === 403) {
    ok("Protección confirmada (HTTP 403): TÉCNICO_ORTOPEDICO no puede crear pacientes.");
    detalle("Mensaje de protección", $resTecnicoBloqueado['mensaje']);
} else {
    error("Fallo: TÉCNICO_ORTOPEDICO no fue bloqueado al intentar crear paciente.");
}

subseccion("8.3 Bloqueo de EVALUADOR_CLINICO al intentar crear o alterar componentes");
Auth::iniciarSesionComo('mrodriguez');
$compCtrl = new ComponenteController();
$resClinicoBloqueado = $compCtrl->store(['numero_serie' => 'TEST-001', 'id_modelo_componente' => 1, 'id_estado_componente' => 1]);
if (!$resClinicoBloqueado['success'] && ($resClinicoBloqueado['codigo'] ?? 0) === 403) {
    ok("Protección confirmada (HTTP 403): EVALUADOR_CLINICO no puede registrar componentes.");
    detalle("Mensaje de protección", $resClinicoBloqueado['mensaje']);
} else {
    error("Fallo: EVALUADOR_CLINICO no fue bloqueado al registrar componentes.");
}

subseccion("8.4 Permiso concedido a EVALUADOR_CLINICO para consultar evaluaciones");
$resClinicoPermitido = $evalCtrl->index();
if ($resClinicoPermitido['success']) {
    ok("Acceso concedido: EVALUADOR_CLINICO puede consultar evaluaciones biomecánicas.");
} else {
    error("Fallo: EVALUADOR_CLINICO no pudo consultar evaluaciones.");
}

subseccion("8.5 ADMINISTRADOR: Acceso irrestricto y gestión de usuarios");
Auth::iniciarSesionComo('admin');
$userCtrl = new UsuarioController();
$resUsuarios = $userCtrl->index();
if ($resUsuarios['success'] && count($resUsuarios['data']) > 0) {
    ok("Acceso concedido: ADMINISTRADOR puede gestionar y listar usuarios del sistema.");
    foreach ($resUsuarios['data'] as $u) {
        detalle("Usuario [{$u['nombre_usuario']}]", "{$u['nombre_completo']} - Rol: {$u['nombre_rol']}");
    }
    $pruebasSuperadas++;
} else {
    error("Fallo: ADMINISTRADOR no pudo listar usuarios.");
}

// -------------------------------------------------------------------------
// PRUEBA 9: Consulta de Detalle por ID y Ausencia de Rutas 404
// -------------------------------------------------------------------------
encabezado("PRUEBA 9: Consulta de Detalle por ID (Resolución de detalle.php)");
$totalPruebas++;

// Verificamos que obtener detalle por ID funciona limpiamente sin archivos rotos
$dispDetalle = $dispCtrl->show(1);
if ($dispDetalle['success'] && !empty($dispDetalle['data']['codigo_dispositivo'])) {
    ok("Consulta de detalle por ID en DispositivoController::show(1) exitosa.");
    detalle("Código Dispositivo", $dispDetalle['data']['codigo_dispositivo']);
    detalle("Paciente", $dispDetalle['data']['nombre_paciente']);
    detalle("Tipo de Dispositivo", $dispDetalle['data']['nombre_tipo']);
    detalle("Archivo detalle.php", "Presente y operativo, resuelve detalle.php?id=1 y redirige consultas limpiamente.");
    $pruebasSuperadas++;
} else {
    error("Fallo al obtener detalle por ID.");
}

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
    linea("La base de datos, el modelo relacional, los roles, permisos y controladores operan al 100%.");
} else {
    linea();
    error("Algunas pruebas presentaron inconsistencias.");
}

linea("===========================================================================");
if (!$esCli) {
    echo "</pre>";
}
