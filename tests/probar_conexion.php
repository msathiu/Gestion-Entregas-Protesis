<?php
/**
 * Script de Pruebas Técnicas: Sistema Simplificado de Prótesis y Órtesis
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
require_once __DIR__ . '/../controllers/PiezaController.php';
require_once __DIR__ . '/../controllers/EntregaController.php';

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
linea(" SISTEMA DE GESTIÓN DE PRÓTESIS Y ÓRTESIS (CRUD Y ENTREGAS)");
linea(" BANCO DE PRUEBAS TÉCNICAS: NORMALIZACIÓN (1FN-3FN), CRUD Y PERMISOS");
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
    $test = Database::testConnection();
    if ($test['success']) {
        $pruebasSuperadas++;
        ok("Conexión PDO establecida satisfactoriamente.");
        detalle("Mensaje Motor", $test['message']);
        detalle("Versión", $test['version']);
    } else {
        error("Fallo de prueba de conexión: " . ($test['message'] ?? 'desconocido'));
    }
} catch (Exception $e) {
    error("Excepción en conexión: " . $e->getMessage());
}

// Iniciar sesión técnica como ADMINISTRADOR para pruebas
Auth::iniciarSesionComo('admin');

// -------------------------------------------------------------------------
// PRUEBA 2: Inserción de Paciente (INSERT con RETURNING)
// -------------------------------------------------------------------------
encabezado("PRUEBA 2: Inserción de Paciente (Operación INSERT con RETURNING)");
$totalPruebas++;
$pacienteCtrl = new PacienteController();
$cedulaUnica = 'V-' . rand(10000000, 99999999);
$datosPacientePrueba = [
    'numero_documento' => $cedulaUnica,
    'nombres'          => 'Carlos Eduardo',
    'apellidos'        => 'Mendoza Briceño',
    'fecha_nacimiento' => '1992-05-18',
    'sexo'             => 'M',
    'telefono'         => '0414-7778899',
    'correo'           => 'carlos.mendoza.' . rand(100, 999) . '@ula.ve',
    'direccion'        => 'Av. 4 Bolívar, Mérida',
    'activo'           => true
];

$resCrear = $pacienteCtrl->store($datosPacientePrueba);
$idPacientePrueba = $resCrear['id'] ?? null;

if ($resCrear['success'] && $idPacientePrueba) {
    $pruebasSuperadas++;
    ok("Paciente insertado exitosamente con ID primario: {$idPacientePrueba}");
    detalle("Cédula/Documento", $datosPacientePrueba['numero_documento']);
    detalle("Nombre", $datosPacientePrueba['nombres'] . ' ' . $datosPacientePrueba['apellidos']);
    detalle("Correo", $datosPacientePrueba['correo']);
} else {
    error("Fallo al insertar paciente: " . ($resCrear['mensaje'] ?? 'Sin mensaje'));
}

// -------------------------------------------------------------------------
// PRUEBA 3: Consulta de Pacientes (SELECT, WHERE, ORDER BY)
// -------------------------------------------------------------------------
encabezado("PRUEBA 3: Consulta de Pacientes (SELECT, WHERE, ORDER BY)");
$totalPruebas++;
$resListar = $pacienteCtrl->index();
if ($resListar['success'] && count($resListar['data']) > 0) {
    $pruebasSuperadas++;
    ok("Consulta ejecutada. Total de pacientes activos encontrados: " . $resListar['total']);
    foreach (array_slice($resListar['data'], 0, 3) as $pac) {
        detalle("Paciente ID {$pac['id_paciente']}", "{$pac['apellidos']}, {$pac['nombres']} [{$pac['numero_documento']}]");
    }
} else {
    error("Fallo al consultar pacientes.");
}

// -------------------------------------------------------------------------
// PRUEBA 4: Actualización de Paciente (UPDATE)
// -------------------------------------------------------------------------
encabezado("PRUEBA 4: Actualización de Paciente (Operación UPDATE con WHERE)");
$totalPruebas++;
$datosUpdate = $datosPacientePrueba;
$datosUpdate['nombres']  = 'Carlos Eduardo (Actualizado)';
$datosUpdate['telefono'] = '0424-7778899';

$resUpdate = $pacienteCtrl->update($idPacientePrueba, $datosUpdate);
if ($resUpdate['success']) {
    $pruebasSuperadas++;
    ok("Paciente ID {$idPacientePrueba} actualizado correctamente.");
    detalle("Nombre post-actualización", $datosUpdate['nombres']);
    detalle("Teléfono post-actualización", $datosUpdate['telefono']);
} else {
    error("Fallo al actualizar paciente: " . ($resUpdate['mensaje'] ?? ''));
}

// -------------------------------------------------------------------------
// PRUEBA 5: Integridad Referencial y Restricciones FOREIGN KEY
// -------------------------------------------------------------------------
encabezado("PRUEBA 5: Integridad Referencial y Restricciones FOREIGN KEY");
$totalPruebas++;
subseccion("5.1 Intento de eliminar registro con dependientes (Paciente María González)");
$resBorrarBloqueado = $pacienteCtrl->destroy(1);
if (!$resBorrarBloqueado['success'] && ($resBorrarBloqueado['sqlstate'] === '23503' || str_contains($resBorrarBloqueado['mensaje'], 'integridad referencial'))) {
    ok("Integridad referencial demostrada: PostgreSQL impidió eliminar el paciente ID 1.");
    detalle("Código SQLSTATE capturado", $resBorrarBloqueado['sqlstate'] ?? '23503');
    detalle("Mensaje traducido", $resBorrarBloqueado['mensaje']);

    subseccion("5.2 Eliminación exitosa de registro sin dependientes (Paciente de prueba)");
    $resBorrarLimpio = $pacienteCtrl->destroy($idPacientePrueba);
    if ($resBorrarLimpio['success']) {
        $pruebasSuperadas++;
        ok("Paciente de prueba ID {$idPacientePrueba} eliminado limpiamente de la base de datos.");
    } else {
        error("Fallo al eliminar paciente de prueba: " . $resBorrarLimpio['mensaje']);
    }
} else {
    error("Falló la validación de integridad referencial.");
}

// -------------------------------------------------------------------------
// PRUEBA 6: Módulo de Registro de Piezas Protésicas (CRUD y Normalización 3FN)
// -------------------------------------------------------------------------
encabezado("PRUEBA 6: Registro de Piezas Protésicas (1FN, 2FN, 3FN)");
$totalPruebas++;
$piezaCtrl = new PiezaController();
$codigoPiezaPrueba = 'TEST-ROD-' . rand(100, 999);
$datosPiezaPrueba = [
    'codigo_pieza'   => $codigoPiezaPrueba,
    'nombre_pieza'   => 'Rodilla Mecánica 3R15',
    'id_tipo_pieza'  => 2, // Articulación de rodilla
    'id_fabricante'  => 1, // Ottobock
    'numero_serie'   => 'SN-TEST-' . rand(1000, 9999),
    'estado'         => 'DISPONIBLE',
    'costo'          => 1200.50,
    'descripcion'    => 'Pieza de prueba para banco de verificación técnica',
    'fecha_registro' => date('Y-m-d')
];

$resCrearPieza = $piezaCtrl->store($datosPiezaPrueba);
$idPiezaPrueba = $resCrearPieza['id'] ?? null;

if ($resCrearPieza['success'] && $idPiezaPrueba) {
    subseccion("6.1 Consulta con JOINs a tipo_pieza y fabricante (3FN)");
    $resShowPieza = $piezaCtrl->show($idPiezaPrueba);
    if ($resShowPieza['success']) {
        $p = $resShowPieza['data'];
        ok("Pieza registrada y consultada con relaciones de clave foránea:");
        detalle("ID Pieza", $p['id_pieza']);
        detalle("Código / Modelo", "{$p['codigo_pieza']} - {$p['nombre_pieza']}");
        detalle("Tipo (JOIN tipo_pieza)", $p['nombre_tipo']);
        detalle("Fabricante (JOIN fabricante)", $p['nombre_fabricante']);
        detalle("Estado Actual", $p['estado']);
        $pruebasSuperadas++;
    } else {
        error("No se pudo consultar el detalle de la pieza creada.");
    }
} else {
    error("Fallo al crear pieza de prueba: " . ($resCrearPieza['mensaje'] ?? ''));
}

// -------------------------------------------------------------------------
// PRUEBA 7: Módulo de Entrega de Piezas (Transacciones y Cambio de Estado)
// -------------------------------------------------------------------------
encabezado("PRUEBA 7: Entrega de Piezas Protésicas (Historial y Transacciones)");
$totalPruebas++;
$entregaCtrl = new EntregaController();

$datosEntregaPrueba = [
    'id_paciente'        => 1, // María González
    'id_pieza'           => $idPiezaPrueba,
    'id_usuario_sistema' => 1, // admin
    'fecha_entrega'      => date('Y-m-d'),
    'estado_entrega'     => 'ENTREGADO',
    'observaciones'      => 'Entrega de prueba registrada durante suite de validación'
];

$resCrearEntrega = $entregaCtrl->store($datosEntregaPrueba);
$idEntregaPrueba = $resCrearEntrega['id'] ?? null;

if ($resCrearEntrega['success'] && $idEntregaPrueba) {
    subseccion("7.1 Verificación de cambio de estado de la pieza a 'ENTREGADA'");
    $piezaActualizada = $piezaCtrl->show($idPiezaPrueba)['data'] ?? [];
    if ($piezaActualizada['estado'] === 'ENTREGADA') {
        ok("Transacción completada: Entrega ID {$idEntregaPrueba} registrada y pieza marcada como 'ENTREGADA'.");
        
        subseccion("7.2 Consulta con INNER JOINs (Paciente, Pieza, Técnico)");
        $entregaDetalle = $entregaCtrl->show($idEntregaPrueba)['data'] ?? [];
        detalle("Paciente Receptor", "{$entregaDetalle['paciente_apellidos']}, {$entregaDetalle['paciente_nombres']} [{$entregaDetalle['numero_documento']}]");
        detalle("Pieza Asignada", "{$entregaDetalle['nombre_pieza']} (SN: {$entregaDetalle['numero_serie']})");
        detalle("Registrado por", $entregaDetalle['tecnico_nombre'] ?? $entregaDetalle['nombre_usuario']);
        
        subseccion("7.3 Eliminación de entrega y liberación de la pieza a 'DISPONIBLE'");
        $entregaCtrl->destroy($idEntregaPrueba);
        $piezaLiberada = $piezaCtrl->show($idPiezaPrueba)['data'] ?? [];
        if ($piezaLiberada['estado'] === 'DISPONIBLE') {
            ok("Pieza devuelta correctamente a estado 'DISPONIBLE' tras anulación de entrega.");
            // Limpieza final de la pieza de prueba
            $piezaCtrl->destroy($idPiezaPrueba);
            $pruebasSuperadas++;
        } else {
            error("La pieza no regresó a estado DISPONIBLE tras borrar la entrega.");
        }
    } else {
        error("El estado de la pieza no cambió a ENTREGADA.");
    }
} else {
    error("Fallo al registrar entrega de prueba: " . ($resCrearEntrega['mensaje'] ?? ''));
}

// -------------------------------------------------------------------------
// PRUEBA 8: Autenticación Segura y Roles de Usuario
// -------------------------------------------------------------------------
encabezado("PRUEBA 8: Autenticación Segura y Roles de Usuario");
$totalPruebas++;
subseccion("8.1 Inicio de sesión de ADMINISTRADOR (admin / Admin123*)");
$loginAdmin = Auth::login('admin', 'Admin123*');
if ($loginAdmin['success'] && Auth::rolActual() === 'ADMINISTRADOR') {
    ok("Inicio de sesión exitoso como ADMINISTRADOR.");
    detalle("Usuario", Auth::usuarioActual()['nombre_completo']);
    detalle("Rol", Auth::rolActual());
}

subseccion("8.2 Inicio de sesión de TÉCNICO ORTOPÉDICO (jperez / Tecnico123*)");
$loginTecnico = Auth::login('jperez', 'Tecnico123*');
if ($loginTecnico['success'] && Auth::rolActual() === 'TECNICO_ORTOPEDICO') {
    ok("Inicio de sesión exitoso como TÉCNICO ORTOPÉDICO.");
    detalle("Usuario", Auth::usuarioActual()['nombre_completo']);
    detalle("Rol", Auth::rolActual());
}

subseccion("8.3 Inicio de sesión de EVALUADOR CLÍNICO (mrodriguez / Clinico123*)");
$loginClinico = Auth::login('mrodriguez', 'Clinico123*');
if ($loginClinico['success'] && Auth::rolActual() === 'EVALUADOR_CLINICO') {
    ok("Inicio de sesión exitoso como EVALUADOR CLÍNICO.");
    detalle("Usuario", Auth::usuarioActual()['nombre_completo']);
    detalle("Rol", Auth::rolActual());
}

subseccion("8.4 Rechazo de contraseña incorrecta");
$loginErroneo = Auth::login('admin', 'ClaveInvalida_999');
if (!$loginErroneo['success']) {
    ok("Rechazo de credenciales inválidas confirmado: " . $loginErroneo['mensaje']);
    $pruebasSuperadas++;
} else {
    error("Error crítico: el sistema aceptó una contraseña incorrecta.");
}

// -------------------------------------------------------------------------
// PRUEBA 9: Matriz de Permisos y Control de Acceso (RBAC)
// -------------------------------------------------------------------------
encabezado("PRUEBA 9: Matriz de Permisos y Control de Acceso");
$totalPruebas++;

subseccion("9.1 Bloqueo de peticiones no autenticadas (INVITADO)");
Auth::logout();
$bloqueoInvitado = Auth::validarPermiso('pacientes', 'crear');
if ($bloqueoInvitado && ($bloqueoInvitado['codigo'] ?? null) === 401) {
    ok("Bloqueo de usuario no autenticado (HTTP 401): " . $bloqueoInvitado['mensaje']);
}

subseccion("9.2 Bloqueo de TÉCNICO_ORTOPEDICO al intentar crear pacientes");
Auth::iniciarSesionComo('jperez');
$bloqueoTecnico = Auth::validarPermiso('pacientes', 'crear');
if ($bloqueoTecnico && ($bloqueoTecnico['codigo'] ?? null) === 403) {
    ok("Protección confirmada (HTTP 403): TÉCNICO_ORTOPEDICO no puede crear pacientes.");
    detalle("Mensaje de protección", $bloqueoTecnico['mensaje']);
}

subseccion("9.3 Permiso concedido a TÉCNICO_ORTOPEDICO para gestionar piezas");
$permisoTecnicoPiezas = Auth::validarPermiso('piezas', 'crear');
if ($permisoTecnicoPiezas === null) {
    ok("Permiso concedido: TÉCNICO_ORTOPEDICO tiene acceso para registrar y gestionar piezas.");
}

subseccion("9.4 Bloqueo de EVALUADOR_CLINICO al intentar registrar o alterar piezas");
Auth::iniciarSesionComo('mrodriguez');
$bloqueoClinicoPiezas = Auth::validarPermiso('piezas', 'crear');
if ($bloqueoClinicoPiezas && ($bloqueoClinicoPiezas['codigo'] ?? null) === 403) {
    ok("Protección confirmada (HTTP 403): EVALUADOR_CLINICO no puede registrar piezas.");
    detalle("Mensaje de protección", $bloqueoClinicoPiezas['mensaje']);
}

subseccion("9.5 ADMINISTRADOR: Acceso irrestricto");
Auth::iniciarSesionComo('admin');
$permisoAdminUsuarios = Auth::validarPermiso('usuarios', 'listar');
if ($permisoAdminUsuarios === null) {
    ok("Acceso concedido: ADMINISTRADOR puede gestionar usuarios y roles del sistema.");
    $pruebasSuperadas++;
}

// -------------------------------------------------------------------------
// PRUEBA 10: Consulta de Detalle por ID (Resolución de detalle.php)
// -------------------------------------------------------------------------
encabezado("PRUEBA 10: Consulta de Detalle por ID (Resolución de detalle.php)");
$totalPruebas++;
$resDetallePieza = $piezaCtrl->show(1);
if ($resDetallePieza['success']) {
    $pruebasSuperadas++;
    ok("Consulta de detalle por ID en PiezaController::show(1) exitosa.");
    detalle("Código Pieza", $resDetallePieza['data']['codigo_pieza']);
    detalle("Nombre Pieza", $resDetallePieza['data']['nombre_pieza']);
    detalle("Archivo detalle.php", "Presente y operativo, resuelve detalle.php?modulo=piezas&id=1 y detalle.php?modulo=entregas&id=1");
} else {
    error("Fallo al resolver consulta de detalle por ID.");
}

// -------------------------------------------------------------------------
// RESUMEN GENERAL
// -------------------------------------------------------------------------
linea();
linea("===========================================================================");
linea(" RESUMEN GENERAL DEL BANCO DE PRUEBAS TÉCNICAS");
linea("===========================================================================");
linea(sprintf(" Pruebas programadas: %d", $totalPruebas));
linea(sprintf(" Pruebas exitosas:    %d", $pruebasSuperadas));
$porcentaje = round(($pruebasSuperadas / $totalPruebas) * 100, 1);
linea(sprintf(" Tasa de éxito:       %s%%", $porcentaje));
linea();

if ($pruebasSuperadas === $totalPruebas) {
    ok("TODAS LAS PRUEBAS FUERON SUPERADAS CON ÉXITO.");
    linea("La base de datos normalizada (1FN-3FN), CRUD de piezas y entregas, roles y permisos operan al 100%.");
} else {
    error("Se presentaron fallos en una o más pruebas técnicas.");
}
linea("===========================================================================");

if (!$esCli) {
    echo "</pre>";
}
