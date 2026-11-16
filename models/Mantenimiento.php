<?php
/**
 * Modelo: Mantenimiento Preventivo
 * Tabla: mantenimiento_preventivo
 * Asignatura: Bases de Datos - Ingeniería de Sistemas (ULA)
 */

require_once __DIR__ . '/../config/database.php';

class Mantenimiento {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Lista mantenimientos preventivos con sus relaciones
     * Demuestra: SELECT con múltiples INNER JOINs, LEFT JOIN (técnico asignado opcional), WHERE y ORDER BY
     */
    public function listar(?int $idEstado = null): array {
        $sql = "SELECT mp.id_mantenimiento_preventivo, mp.fecha_programada, mp.fecha_realizacion,
                       mp.observaciones,
                       dp.id_dispositivo_protesico, dp.codigo_dispositivo,
                       tm.id_tipo_mantenimiento, tm.nombre_mantenimiento,
                       em.id_estado_mantenimiento, em.nombre_estado,
                       u.id_usuario_sistema AS id_tecnico, u.nombre_completo AS tecnico,
                       p.id_paciente, p.numero_documento, p.nombres || ' ' || p.apellidos AS nombre_paciente
                FROM mantenimiento_preventivo mp
                INNER JOIN dispositivo_protesico dp   ON dp.id_dispositivo_protesico   = mp.id_dispositivo_protesico
                INNER JOIN tipo_mantenimiento tm      ON tm.id_tipo_mantenimiento      = mp.id_tipo_mantenimiento
                INNER JOIN estado_mantenimiento em    ON em.id_estado_mantenimiento    = mp.id_estado_mantenimiento
                LEFT JOIN  usuario_sistema u          ON u.id_usuario_sistema          = mp.id_usuario_sistema_tecnico
                INNER JOIN paciente_condicion pc      ON pc.id_paciente_condicion      = dp.id_paciente_condicion
                INNER JOIN paciente p                 ON p.id_paciente                 = pc.id_paciente";

        $params = [];
        if ($idEstado !== null) {
            $sql .= " WHERE mp.id_estado_mantenimiento = :id_estado";
            $params[':id_estado'] = $idEstado;
        }

        $sql .= " ORDER BY mp.fecha_programada ASC, mp.id_mantenimiento_preventivo DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene el detalle de un mantenimiento por su clave primaria
     * Demuestra: SELECT con JOINs y filtrado sobre PK
     */
    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT mp.id_mantenimiento_preventivo, mp.id_dispositivo_protesico,
                       mp.id_tipo_mantenimiento, mp.id_estado_mantenimiento,
                       mp.id_usuario_sistema_tecnico, mp.fecha_programada,
                       mp.fecha_realizacion, mp.observaciones,
                       mp.fecha_creacion, mp.fecha_actualizacion,
                       dp.codigo_dispositivo, tm.nombre_mantenimiento, em.nombre_estado,
                       u.nombre_completo AS tecnico,
                       p.id_paciente, p.numero_documento, p.nombres || ' ' || p.apellidos AS nombre_paciente
                FROM mantenimiento_preventivo mp
                INNER JOIN dispositivo_protesico dp ON dp.id_dispositivo_protesico = mp.id_dispositivo_protesico
                INNER JOIN tipo_mantenimiento tm    ON tm.id_tipo_mantenimiento    = mp.id_tipo_mantenimiento
                INNER JOIN estado_mantenimiento em  ON em.id_estado_mantenimiento  = mp.id_estado_mantenimiento
                LEFT JOIN  usuario_sistema u        ON u.id_usuario_sistema        = mp.id_usuario_sistema_tecnico
                INNER JOIN paciente_condicion pc    ON pc.id_paciente_condicion    = dp.id_paciente_condicion
                INNER JOIN paciente p               ON p.id_paciente               = pc.id_paciente
                WHERE mp.id_mantenimiento_preventivo = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $res = $stmt->fetch();

        return $res ?: null;
    }

    /**
     * Programa un nuevo mantenimiento preventivo
     * Demuestra: INSERT INTO con RETURNING
     */
    public function crear(array $datos): int {
        $sql = "INSERT INTO mantenimiento_preventivo (
                    id_dispositivo_protesico, id_tipo_mantenimiento,
                    id_estado_mantenimiento, id_usuario_sistema_tecnico,
                    fecha_programada, fecha_realizacion, observaciones
                ) VALUES (
                    :id_dispositivo, :id_tipo_mantenimiento,
                    :id_estado_mantenimiento, :id_tecnico,
                    :fecha_programada, :fecha_realizacion, :observaciones
                ) RETURNING id_mantenimiento_preventivo";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_dispositivo'         => $datos['id_dispositivo_protesico'],
            ':id_tipo_mantenimiento'  => $datos['id_tipo_mantenimiento'],
            ':id_estado_mantenimiento'=> $datos['id_estado_mantenimiento'],
            ':id_tecnico'             => $datos['id_usuario_sistema_tecnico'] ?? null,
            ':fecha_programada'       => $datos['fecha_programada'],
            ':fecha_realizacion'      => $datos['fecha_realizacion'] ?? null,
            ':observaciones'          => $datos['observaciones'] ?? null,
        ]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Actualiza un mantenimiento preventivo
     * Demuestra: UPDATE con WHERE y parámetros preparados
     */
    public function actualizar(int $id, array $datos): bool {
        $sql = "UPDATE mantenimiento_preventivo SET
                    id_dispositivo_protesico   = :id_dispositivo,
                    id_tipo_mantenimiento      = :id_tipo_mantenimiento,
                    id_estado_mantenimiento    = :id_estado_mantenimiento,
                    id_usuario_sistema_tecnico = :id_tecnico,
                    fecha_programada           = :fecha_programada,
                    fecha_realizacion          = :fecha_realizacion,
                    observaciones              = :observaciones
                WHERE id_mantenimiento_preventivo = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id_dispositivo'         => $datos['id_dispositivo_protesico'],
            ':id_tipo_mantenimiento'  => $datos['id_tipo_mantenimiento'],
            ':id_estado_mantenimiento'=> $datos['id_estado_mantenimiento'],
            ':id_tecnico'             => $datos['id_usuario_sistema_tecnico'] ?? null,
            ':fecha_programada'       => $datos['fecha_programada'],
            ':fecha_realizacion'      => $datos['fecha_realizacion'] ?? null,
            ':observaciones'          => $datos['observaciones'] ?? null,
            ':id'                     => $id,
        ]);
    }

    /**
     * Elimina un mantenimiento preventivo
     * Demuestra: DELETE sobre PK
     */
    public function eliminar(int $id): bool {
        $sql = "DELETE FROM mantenimiento_preventivo WHERE id_mantenimiento_preventivo = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Invoca la función PL/pgSQL almacenada en PostgreSQL para actualizar mantenimientos vencidos
     * Demuestra: Llamada a stored functions/procedures de PostgreSQL desde PHP PDO
     */
    public function marcarVencidos(): int {
        $sql = "SELECT fn_marcar_mantenimientos_vencidos()";
        $stmt = $this->db->query($sql);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Resumen de mantenimientos por estado
     * Demuestra: GROUP BY con COUNT y ordenamiento
     */
    public function resumenPorEstado(): array {
        $sql = "SELECT em.id_estado_mantenimiento, em.nombre_estado, COUNT(mp.id_mantenimiento_preventivo) AS total
                FROM estado_mantenimiento em
                LEFT JOIN mantenimiento_preventivo mp ON mp.id_estado_mantenimiento = em.id_estado_mantenimiento
                GROUP BY em.id_estado_mantenimiento, em.nombre_estado
                ORDER BY em.id_estado_mantenimiento ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
}
