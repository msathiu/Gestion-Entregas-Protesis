<?php
/**
 * Modelo: Paciente
 * Tabla: paciente

 */

require_once __DIR__ . '/../config/database.php';

class Paciente {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Lista pacientes con ordenamiento y filtro opcional
     * Demuestra: SELECT, WHERE, ORDER BY
     */
    public function listar(?bool $soloActivos = true): array {
        $sql = "SELECT id_paciente, numero_documento, nombres, apellidos,
                       fecha_nacimiento, sexo, telefono, correo, direccion, activo,
                       fecha_creacion, fecha_actualizacion
                FROM paciente";

        if ($soloActivos) {
            $sql .= " WHERE activo = TRUE";
        }

        $sql .= " ORDER BY apellidos ASC, nombres ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene un paciente por su clave primaria
     * Demuestra: SELECT con WHERE sobre PK
     */
    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT id_paciente, numero_documento, nombres, apellidos,
                       fecha_nacimiento, sexo, telefono, correo, direccion, activo,
                       fecha_creacion, fecha_actualizacion
                FROM paciente
                WHERE id_paciente = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $resultado = $stmt->fetch();

        return $resultado ?: null;
    }

    /**
     * Inserta un nuevo paciente en la base de datos
     * Demuestra: INSERT INTO con parámetros preparados y RETURNING
     */
    public function crear(array $datos): int {
        $sql = "INSERT INTO paciente (
                    numero_documento, nombres, apellidos, fecha_nacimiento,
                    sexo, telefono, correo, direccion, activo
                ) VALUES (
                    :numero_documento, :nombres, :apellidos, :fecha_nacimiento,
                    :sexo, :telefono, :correo, :direccion, :activo
                ) RETURNING id_paciente";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':numero_documento' => $datos['numero_documento'],
            ':nombres'          => $datos['nombres'],
            ':apellidos'        => $datos['apellidos'],
            ':fecha_nacimiento' => $datos['fecha_nacimiento'],
            ':sexo'             => $datos['sexo'],
            ':telefono'         => $datos['telefono'] ?? null,
            ':correo'           => $datos['correo'],
            ':direccion'        => $datos['direccion'] ?? null,
            ':activo'           => $datos['activo'] ?? true,
        ]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Actualiza la información de un paciente
     * Demuestra: UPDATE con WHERE y parámetros preparados
     */
    public function actualizar(int $id, array $datos): bool {
        $sql = "UPDATE paciente SET
                    numero_documento = :numero_documento,
                    nombres          = :nombres,
                    apellidos        = :apellidos,
                    fecha_nacimiento = :fecha_nacimiento,
                    sexo             = :sexo,
                    telefono         = :telefono,
                    correo           = :correo,
                    direccion        = :direccion,
                    activo           = :activo
                WHERE id_paciente = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':numero_documento' => $datos['numero_documento'],
            ':nombres'          => $datos['nombres'],
            ':apellidos'        => $datos['apellidos'],
            ':fecha_nacimiento' => $datos['fecha_nacimiento'],
            ':sexo'             => $datos['sexo'],
            ':telefono'         => $datos['telefono'] ?? null,
            ':correo'           => $datos['correo'],
            ':direccion'        => $datos['direccion'] ?? null,
            ':activo'           => $datos['activo'] ?? true,
            ':id'               => $id,
        ]);
    }

    /**
     * Elimina un paciente de la base de datos
     * Demuestra: DELETE con control de integridad referencial (FK constraint)
     */
    public function eliminar(int $id): bool {
        $sql = "DELETE FROM paciente WHERE id_paciente = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Consulta las condiciones clínicas y amputaciones del paciente
     * Demuestra: INNER JOIN múltiple (paciente_condicion -> segmento_corporal, causa_condicion)
     */
    public function obtenerCondiciones(int $idPaciente): array {
        $sql = "SELECT pc.id_paciente_condicion, pc.lateralidad, pc.fecha_condicion, pc.observaciones,
                       sc.nombre_segmento, cc.nombre_causa
                FROM paciente_condicion pc
                INNER JOIN segmento_corporal sc ON sc.id_segmento_corporal = pc.id_segmento_corporal
                INNER JOIN causa_condicion cc   ON cc.id_causa_condicion   = pc.id_causa_condicion
                WHERE pc.id_paciente = :id
                ORDER BY pc.fecha_condicion DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $idPaciente]);
        return $stmt->fetchAll();
    }

    /**
     * Demuestra: GROUP BY con función de agregación COUNT y LEFT JOIN
     * Cantidad de dispositivos asignados por paciente
     */
    public function resumenDispositivosPorPaciente(): array {
        $sql = "SELECT p.id_paciente, p.numero_documento,
                       p.nombres || ' ' || p.apellidos AS nombre_completo,
                       COUNT(dp.id_dispositivo_protesico) AS total_dispositivos
                FROM paciente p
                LEFT JOIN paciente_condicion pc  ON pc.id_paciente = p.id_paciente
                LEFT JOIN dispositivo_protesico dp ON dp.id_paciente_condicion = pc.id_paciente_condicion
                GROUP BY p.id_paciente, p.numero_documento, p.nombres, p.apellidos
                ORDER BY total_dispositivos DESC, p.apellidos ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
}
