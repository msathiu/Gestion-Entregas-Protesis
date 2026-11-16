<?php
/**
 * Modelo: Dispositivo Protésico
 * Tabla: dispositivo_protesico
 * Asignatura: Bases de Datos - Ingeniería de Sistemas (ULA)
 */

require_once __DIR__ . '/../config/database.php';

class Dispositivo {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Lista dispositivos con relaciones a paciente, tipo y estado
     * Demuestra: SELECT con múltiples INNER JOIN, WHERE opcional y ORDER BY
     */
    public function listar(?int $idEstado = null): array {
        $sql = "SELECT dp.id_dispositivo_protesico, dp.codigo_dispositivo,
                       dp.fecha_entrega, dp.fecha_baja,
                       p.id_paciente, p.numero_documento, p.nombres || ' ' || p.apellidos AS nombre_paciente,
                       sc.nombre_segmento, pc.lateralidad,
                       td.id_tipo_dispositivo, td.nombre_tipo, td.categoria_dispositivo,
                       ed.id_estado_dispositivo, ed.nombre_estado
                FROM dispositivo_protesico dp
                INNER JOIN paciente_condicion pc ON pc.id_paciente_condicion = dp.id_paciente_condicion
                INNER JOIN paciente p            ON p.id_paciente            = pc.id_paciente
                INNER JOIN segmento_corporal sc  ON sc.id_segmento_corporal  = pc.id_segmento_corporal
                INNER JOIN tipo_dispositivo td   ON td.id_tipo_dispositivo   = dp.id_tipo_dispositivo
                INNER JOIN estado_dispositivo ed ON ed.id_estado_dispositivo = dp.id_estado_dispositivo";

        $params = [];
        if ($idEstado !== null) {
            $sql .= " WHERE dp.id_estado_dispositivo = :id_estado";
            $params[':id_estado'] = $idEstado;
        }

        $sql .= " ORDER BY dp.fecha_entrega DESC NULLS LAST, dp.id_dispositivo_protesico DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene los datos detallados de un dispositivo
     * Demuestra: SELECT con JOIN a paciente, evaluación, tipo y estado
     */
    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT dp.id_dispositivo_protesico, dp.id_paciente_condicion, dp.id_evaluacion_biomecanica,
                       dp.id_tipo_dispositivo, dp.id_estado_dispositivo, dp.codigo_dispositivo,
                       dp.fecha_entrega, dp.fecha_baja,
                       dp.fecha_creacion, dp.fecha_actualizacion,
                       p.id_paciente, p.numero_documento, p.nombres || ' ' || p.apellidos AS nombre_paciente,
                       p.telefono, p.correo,
                       sc.nombre_segmento, pc.lateralidad,
                       td.nombre_tipo, td.categoria_dispositivo,
                       ed.nombre_estado,
                       eb.fecha_evaluacion, eb.peso_kg
                FROM dispositivo_protesico dp
                INNER JOIN paciente_condicion pc   ON pc.id_paciente_condicion    = dp.id_paciente_condicion
                INNER JOIN paciente p              ON p.id_paciente               = pc.id_paciente
                INNER JOIN segmento_corporal sc    ON sc.id_segmento_corporal     = pc.id_segmento_corporal
                INNER JOIN tipo_dispositivo td     ON td.id_tipo_dispositivo      = dp.id_tipo_dispositivo
                INNER JOIN estado_dispositivo ed   ON ed.id_estado_dispositivo    = dp.id_estado_dispositivo
                INNER JOIN evaluacion_biomecanica eb ON eb.id_evaluacion_biomecanica = dp.id_evaluacion_biomecanica
                WHERE dp.id_dispositivo_protesico = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $disp = $stmt->fetch();

        return $disp ?: null;
    }

    /**
     * Registra un nuevo dispositivo en la base de datos
     * Demuestra: INSERT con claves foráneas cruzadas y validación de unicidad
     */
    public function crear(array $datos): int {
        $sql = "INSERT INTO dispositivo_protesico (
                    id_paciente_condicion, id_evaluacion_biomecanica, id_tipo_dispositivo,
                    id_estado_dispositivo, codigo_dispositivo,
                    fecha_entrega, fecha_baja
                ) VALUES (
                    :id_paciente_condicion, :id_evaluacion_biomecanica, :id_tipo_dispositivo,
                    :id_estado_dispositivo, :codigo_dispositivo,
                    :fecha_entrega, :fecha_baja
                ) RETURNING id_dispositivo_protesico";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_paciente_condicion'     => $datos['id_paciente_condicion'],
            ':id_evaluacion_biomecanica' => $datos['id_evaluacion_biomecanica'],
            ':id_tipo_dispositivo'       => $datos['id_tipo_dispositivo'],
            ':id_estado_dispositivo'     => $datos['id_estado_dispositivo'],
            ':codigo_dispositivo'        => strtoupper(trim($datos['codigo_dispositivo'])),
            ':fecha_entrega'             => $datos['fecha_entrega'] ?? null,
            ':fecha_baja'                => $datos['fecha_baja'] ?? null,
        ]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Actualiza la información de un dispositivo
     * Demuestra: UPDATE preparado
     */
    public function actualizar(int $id, array $datos): bool {
        $sql = "UPDATE dispositivo_protesico SET
                    id_tipo_dispositivo       = :id_tipo_dispositivo,
                    id_estado_dispositivo     = :id_estado_dispositivo,
                    codigo_dispositivo        = :codigo_dispositivo,
                    fecha_entrega             = :fecha_entrega,
                    fecha_baja                = :fecha_baja
                WHERE id_dispositivo_protesico = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id_tipo_dispositivo'   => $datos['id_tipo_dispositivo'],
            ':id_estado_dispositivo' => $datos['id_estado_dispositivo'],
            ':codigo_dispositivo'    => strtoupper(trim($datos['codigo_dispositivo'])),
            ':fecha_entrega'         => $datos['fecha_entrega'] ?? null,
            ':fecha_baja'            => $datos['fecha_baja'] ?? null,
            ':id'                    => $id,
        ]);
    }

    /**
     * Actualiza el estado operativo del dispositivo
     * Demuestra: UPDATE directo sobre columna FK de estado
     */
    public function cambiarEstado(int $id, int $idNuevoEstado): bool {
        $sql = "UPDATE dispositivo_protesico 
                SET id_estado_dispositivo = :id_estado 
                WHERE id_dispositivo_protesico = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id_estado' => $idNuevoEstado,
            ':id'        => $id,
        ]);
    }

    /**
     * Elimina un dispositivo respetando integridad referencial
     * Demuestra: DELETE
     */
    public function eliminar(int $id): bool {
        $sql = "DELETE FROM dispositivo_protesico WHERE id_dispositivo_protesico = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Consulta los componentes físicos asociados al dispositivo (relación N:M)
     * Demuestra: INNER JOIN a tabla intermedia dispositivo_componente, componente y modelo_componente
     */
    public function obtenerComponentes(int $idDispositivo): array {
        $sql = "SELECT dc.id_dispositivo_componente, dc.fecha_instalacion, dc.fecha_retiro, dc.motivo_retiro,
                       c.id_componente, c.numero_serie, c.fecha_adquisicion, c.horas_desgaste_acumuladas,
                       mc.nombre_modelo, mc.vida_util_horas,
                       cc.nombre_categoria,
                       f.nombre_fabricante
                FROM dispositivo_componente dc
                INNER JOIN componente c           ON c.id_componente           = dc.id_componente
                INNER JOIN modelo_componente mc   ON mc.id_modelo_componente   = c.id_modelo_componente
                INNER JOIN categoria_componente cc ON cc.id_categoria_componente = mc.id_categoria_componente
                INNER JOIN fabricante f           ON f.id_fabricante           = mc.id_fabricante
                WHERE dc.id_dispositivo_protesico = :id
                ORDER BY dc.fecha_instalacion DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $idDispositivo]);
        return $stmt->fetchAll();
    }

    /**
     * Catálogo: Tipos de dispositivos protésicos y ortésicos
     */
    public function listarTipos(): array {
        $sql = "SELECT id_tipo_dispositivo, nombre_tipo, categoria_dispositivo 
                FROM tipo_dispositivo 
                ORDER BY id_tipo_dispositivo ASC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Catálogo: Estados operativos del dispositivo
     */
    public function listarEstados(): array {
        $sql = "SELECT id_estado_dispositivo, nombre_estado 
                FROM estado_dispositivo 
                ORDER BY id_estado_dispositivo ASC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Evaluaciones clínicas disponibles para asociar a una orden de dispositivo
     */
    public function listarEvaluacionesDisponibles(): array {
        $sql = "SELECT eb.id_evaluacion_biomecanica, eb.id_paciente_condicion, eb.fecha_evaluacion,
                       p.numero_documento, p.nombres || ' ' || p.apellidos AS nombre_paciente,
                       sc.nombre_segmento, pc.lateralidad
                FROM evaluacion_biomecanica eb
                INNER JOIN paciente_condicion pc ON pc.id_paciente_condicion = eb.id_paciente_condicion
                INNER JOIN paciente p            ON p.id_paciente            = pc.id_paciente
                INNER JOIN segmento_corporal sc  ON sc.id_segmento_corporal  = pc.id_segmento_corporal
                ORDER BY eb.fecha_evaluacion DESC, eb.id_evaluacion_biomecanica DESC";
        return $this->db->query($sql)->fetchAll();
    }
}
