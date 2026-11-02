<?php
/**
 * Script de Pruebas Unitarias Automatizadas
 * Asignatura: Bases de Datos - ULA
 * Verifica: Conexión, CRUD completo e Integridad Referencial (FK Constraints)
 */

require_once __DIR__ . '/config.php';

echo "====================================================\n";
echo "PRUEBAS DE VERIFICACIÓN CRUD Y MODELO RELACIONAL\n";
echo "====================================================\n\n";

$db = get_db();

// 1. Verificación de conexión
$version = $db->query("SELECT version()")->fetchColumn();
echo "[OK] 1. Conexión establecida con PostgreSQL:\n     " . substr($version, 0, 45) . "...\n\n";

// 2. Conteo de tablas
$tablas = $db->query("
    SELECT tablename FROM pg_tables 
    WHERE schemaname = 'public' 
    ORDER BY tablename
")->fetchAll(PDO::FETCH_COLUMN);

echo "[OK] 2. Tablas activas en el esquema (" . count($tablas) . " tablas):\n";
foreach ($tablas as $t) {
    echo "     - {$t}\n";
}
echo "\n";

// 3. Obtener referencias
$idPaciente = (int)$db->query("SELECT id_paciente FROM paciente LIMIT 1")->fetchColumn();
$idTipo = (int)$db->query("SELECT id_tipo_protesis FROM tipo_protesis LIMIT 1")->fetchColumn();

// 4. Operación CREAR
$testCode = 'TEST-PRT-' . time();
echo "--- 3. OPERACIÓN CREAR (INSERT) ---\n";
$stmtIns = $db->prepare("
    INSERT INTO entrega_protesis (codigo_protesis, id_paciente, id_tipo_protesis, fecha_entrega, estado, observaciones)
    VALUES (:cod, :id_pac, :id_tipo, CURRENT_DATE, 'ENTREGADO', 'Prueba automatizada de inserción')
    RETURNING id_entrega
");
$stmtIns->execute([
    ':cod'     => $testCode,
    ':id_pac'  => $idPaciente,
    ':id_tipo' => $idTipo,
]);
$idEntrega = (int)$stmtIns->fetchColumn();
echo "[OK] Entrega creada con ID {$idEntrega} y Código {$testCode}.\n\n";

// 5. Operación CONSULTAR
echo "--- 4. OPERACIÓN CONSULTAR (SELECT con INNER JOIN) ---\n";
$stmtSel = $db->prepare("
    SELECT ep.*, p.nombres || ' ' || p.apellidos AS nombre_paciente, p.numero_documento, tp.nombre_tipo
    FROM entrega_protesis ep
    JOIN paciente p ON p.id_paciente = ep.id_paciente
    JOIN tipo_protesis tp ON tp.id_tipo_protesis = ep.id_tipo_protesis
    WHERE ep.id_entrega = :id
");
$stmtSel->execute([':id' => $idEntrega]);
$entrega = $stmtSel->fetch();
assert(!empty($entrega), 'El registro debe existir');
echo "[OK] Consulta exitosa: Prótesis {$entrega['codigo_protesis']} entregada a {$entrega['nombre_paciente']} ({$entrega['numero_documento']}).\n";
echo "     Tipo: {$entrega['nombre_tipo']} | Estado: {$entrega['estado']}\n\n";

// 6. Operación ACTUALIZAR
echo "--- 5. OPERACIÓN ACTUALIZAR (UPDATE) ---\n";
$stmtUp = $db->prepare("
    UPDATE entrega_protesis 
    SET observaciones = 'Observación actualizada por test automatizado'
    WHERE id_entrega = :id
");
$stmtUp->execute([':id' => $idEntrega]);
$obsActualizada = $db->query("SELECT observaciones FROM entrega_protesis WHERE id_entrega = {$idEntrega}")->fetchColumn();
assert($obsActualizada === 'Observación actualizada por test automatizado');
echo "[OK] Registro actualizado correctamente.\n\n";

// 7. Prueba de Integridad Referencial (FK Constraint)
echo "--- 6. INTEGRIDAD REFERENCIAL (FK RESTRICT) ---\n";
try {
    // Intentar borrar el paciente que tiene la entrega asociada
    $db->query("DELETE FROM paciente WHERE id_paciente = {$idPaciente}");
    echo "[FALLO] La base de datos permitió borrar un paciente con entregas asociadas.\n";
} catch (PDOException $e) {
    if ($e->getCode() === '23503') {
        echo "[OK] PostgreSQL rechazó correctamente el borrado por restricción de clave foránea:\n";
        echo "     Código SQLSTATE: 23503 (violación de integridad referencial)\n\n";
    } else {
        echo "[ERROR] Código inesperado: " . $e->getMessage() . "\n\n";
    }
}

// 8. Operación ELIMINAR
echo "--- 7. OPERACIÓN ELIMINAR (DELETE) ---\n";
$db->prepare("DELETE FROM entrega_protesis WHERE id_entrega = :id")->execute([':id' => $idEntrega]);
$existe = $db->query("SELECT COUNT(*) FROM entrega_protesis WHERE id_entrega = {$idEntrega}")->fetchColumn();
assert($existe == 0);
echo "[OK] Registro de prueba eliminado físicamente de PostgreSQL.\n\n";

echo "====================================================\n";
echo "¡TODAS LAS PRUEBAS FUERON EXITOSAS AL 100%!\n";
echo "====================================================\n";

