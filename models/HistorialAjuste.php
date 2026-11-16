<?php
/**
 * Modelo: Historial de Ajustes
 * Tabla: historial_ajuste
 * Asignatura: Bases de Datos - Ingeniería de Sistemas (ULA)
 */

require_once __DIR__ . '/../config/database.php';

class HistorialAjuste {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Lista ajustes técnicos realizados a dispositivos protésicos
     * Demuestra: SELECT con múltiples INNER JOINs (dispositivo, paciente, tipo de ajuste, técnico) y ORDER BY
     */
    public function listar(?int $idDispositivo = null): array {
        $sql = "SELECT ha.id_historial_ajuste, ha.fecha_ajuste, ha.descripcion_ajuste,
                       dp.id_dispositivo_protesico, dp.codigo_dispositivo,
                       ta.id_tipo_ajuste, ta.nombre_ajuste,
                       u.id_usuario_sistema AS id_tecnico, u.nombre_completo AS tecnico,
                       p.id_paciente, p.numero_documento, p.nombres || ' ' || p.apellidos AS nombre_paciente,
                       ha.id_evaluacion_biomecanica
                FROM historial_ajuste ha
                INNER JOIN dispositivo_protesico dp   ON dp.id_dispositivo_protesico   = ha.id_dispositivo_protesico
                INNER JOIN tipo_ajuste ta             ON ta.id_tipo_ajuste             = ha.id_tipo_ajuste
                INNER JOIN usuario_sistema u          ON u.id_usuario_sistema          = ha.id_usuario_sistema_tecnico
                INNER JOIN paciente_condicion pc      ON pc.id_paciente_condicion      = dp.id_paciente_condicion
                INNER JOIN paciente p                 ON p.id_paciente                 = pc.id_paciente";

        $params = [];
        if ($idDispositivo !== null) {
            $sql .= " WHERE ha.id_dispositivo_protesico = :id_dispositivo";
            $params[':id_dispositivo'] = $idDispositivo;
        }

        $sql .= " ORDER BY ha.fecha_ajuste DESC, ha.id_historial_ajuste DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene el detalle de un ajuste por ID
     * Demuestra: SELECT con WHERE sobre clave primaria
     */
    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT ha.id_historial_ajuste, ha.id_dispositivo_protesico, ha.id_tipo_ajuste,
                       ha.id_evaluacion_biomecanica, ha.id_usuario_sistema_tecnico,
                       ha.fecha_ajuste, ha.descripcion_ajuste,
                       ha.fecha_creacion, ha.fecha_actualizacion,
                       dp.codigo_dispositivo, ta.nombre_ajuste,
                       u.nombre_completo AS tecnico,
                       p.id_paciente, p.numero_documento, p.nombres || ' ' || p.apellidos AS nombre_paciente
                FROM historial_ajuste ha
                INNER JOIN dispositivo_protesico dp ON dp.id_dispositivo_protesico = ha.id_dispositivo_protesico
                INNER JOIN tipo_ajuste ta           ON ta.id_tipo_ajuste           = ha.id_tipo_ajuste
                INNER JOIN usuario_sistema u        ON u.id_usuario_sistema        = ha.id_usuario_sistema_tecnico
                INNER JOIN paciente_condicion pc    ON pc.id_paciente_condicion    = dp.id_paciente_condicion
                INNER JOIN paciente p               ON p.id_paciente               = pc.id_paciente
                WHERE ha.id_historial_ajuste = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $res = $stmt->fetch();

        return $res ?: null;
    }

    /**
     * Registra un nuevo ajuste técnico
     * Demuestra: INSERT con RETURNING en PostgreSQL
     */
    public function crear(array $datos): int {
        $sql = "INSERT INTO historial_ajuste (
                    id_dispositivo_protesico, id_tipo_ajuste, id_evaluacion_biomecanica,
                    id_usuario_sistema_tecnico, fecha_ajuste, descripcion_ajuste
                ) VALUES (
                    :id_dispositivo, :id_tipo_ajuste, :id_evaluacion,
                    :id_tecnico, :fecha_ajuste, :descripcion_ajuste
                ) RETURNING id_historial_ajuste";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_dispositivo'      => $datos['id_dispositivo_protesico'],
            ':id_tipo_ajuste'       => $datos['id_tipo_ajuste'],
            ':id_evaluacion'        => $datos['id_evaluacion_biomecanica'] ?? null,
            ':id_tecnico'           => $datos['id_usuario_sistema_tecnico'],
            ':fecha_ajuste'         => $datos['fecha_ajuste'] ?? date('Y-m-d'),
            ':descripcion_ajuste'   => $datos['descripcion_ajuste'],
        ]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Actualiza la información de un ajuste existente
     * Demuestra: UPDATE con WHERE parametrizado
     */
    public function actualizar(int $id, array $datos): bool {
        $sql = "UPDATE historial_ajuste SET
                    id_dispositivo_protesico   = :id_dispositivo,
                    id_tipo_ajuste            = :id_tipo_ajuste,
                    id_evaluacion_biomecanica = :id_evaluacion,
                    id_usuario_sistema_tecnico = :id_tecnico,
                    fecha_ajuste              = :fecha_ajuste,
                    descripcion_ajuste        = :descripcion_ajuste
                WHERE id_historial_ajuste = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id_dispositivo'      => $datos['id_dispositivo_protesico'],
            ':id_tipo_ajuste'       => $datos['id_tipo_ajuste'],
            ':id_evaluacion'        => $datos['id_evaluacion_biomecanica'] ?? null,
            ':id_tecnico'           => $datos['id_usuario_sistema_tecnico'],
            ':fecha_ajuste'         => $datos['fecha_ajuste'],
            ':descripcion_ajuste'   => $datos['descripcion_ajuste'],
            ':id'                   => $id,
        ]);
    }

    /**
     * Elimina un registro del historial de ajustes
     * Demuestra: DELETE sobre PK
     */
    public function eliminar(int $id): bool {
        $sql = "DELETE FROM historial_ajuste WHERE id_historial_ajuste = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Resumen de ajustes agrupados por tipo
     * Demuestra: GROUP BY y función agregada COUNT
     */
    public function resumenPorTipo(): array {
        $sql = "SELECT ta.id_tipo_ajuste, ta.nombre_ajuste, COUNT(ha.id_historial_ajuste) AS total_ajustes
                FROM tipo_ajuste ta
                LEFT JOIN historial_ajuste ha ON ha.id_tipo_ajuste = ta.id_tipo_ajuste
                GROUP BY ta.id_tipo_ajuste, ta.nombre_ajuste
                ORDER BY total_ajustes DESC, ta.nombre_ajuste ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
}
