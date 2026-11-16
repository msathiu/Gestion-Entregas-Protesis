<?php
/**
 * Modelo: Entrega de Piezas Protésicas
 * Asignatura: Bases de Datos - Ingeniería de Sistemas (ULA)
 *
 * Cumple con las tres primeras formas normales:
 * - 1FN: Atributos atómicos, clave primaria única id_entrega.
 * - 2FN: Dependencia funcional completa respecto a id_entrega.
 * - 3FN: No duplica datos del paciente ni de la pieza; establece relaciones mediante FKs
 *        hacia paciente, pieza y usuario_sistema.
 */

require_once __DIR__ . '/../config/database.php';

class Entrega {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Consulta el historial de entregas con datos relacionados
     * Demuestra: SELECT con múltiples INNER JOIN, WHERE y ORDER BY
     */
    public function listar(?string $buscar = null, ?string $estado = null): array {
        $sql = "SELECT ep.id_entrega, ep.fecha_entrega, ep.estado_entrega, 
                       ep.observaciones, ep.fecha_registro,
                       p.id_paciente, p.numero_documento, p.nombres, p.apellidos,
                       pz.id_pieza, pz.codigo_pieza, pz.nombre_pieza, pz.numero_serie,
                       tp.nombre_tipo, f.nombre_fabricante,
                       u.id_usuario_sistema, u.nombre_completo AS tecnico_nombre, u.nombre_usuario
                FROM entrega_pieza ep
                INNER JOIN paciente p          ON p.id_paciente = ep.id_paciente
                INNER JOIN pieza pz            ON pz.id_pieza = ep.id_pieza
                INNER JOIN tipo_pieza tp       ON tp.id_tipo_pieza = pz.id_tipo_pieza
                INNER JOIN fabricante f        ON f.id_fabricante = pz.id_fabricante
                INNER JOIN usuario_sistema u   ON u.id_usuario_sistema = ep.id_usuario_sistema
                WHERE 1=1";

        $params = [];

        if (!empty($buscar)) {
            $sql .= " AND (p.nombres ILIKE :buscar 
                        OR p.apellidos ILIKE :buscar 
                        OR p.numero_documento ILIKE :buscar 
                        OR pz.nombre_pieza ILIKE :buscar 
                        OR pz.codigo_pieza ILIKE :buscar 
                        OR pz.numero_serie ILIKE :buscar)";
            $params[':buscar'] = '%' . trim($buscar) . '%';
        }

        if (!empty($estado)) {
            $sql .= " AND ep.estado_entrega = :estado";
            $params[':estado'] = trim($estado);
        }

        $sql .= " ORDER BY ep.fecha_entrega DESC, ep.id_entrega DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Consulta el detalle de una entrega específica por su ID
     */
    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT ep.*,
                       p.numero_documento, p.nombres AS paciente_nombres, 
                       p.apellidos AS paciente_apellidos, p.telefono, p.correo,
                       pz.codigo_pieza, pz.nombre_pieza, pz.numero_serie, pz.costo,
                       tp.nombre_tipo, f.nombre_fabricante,
                       u.nombre_completo AS tecnico_nombre, u.nombre_usuario
                FROM entrega_pieza ep
                INNER JOIN paciente p        ON p.id_paciente = ep.id_paciente
                INNER JOIN pieza pz          ON pz.id_pieza = ep.id_pieza
                INNER JOIN tipo_pieza tp     ON tp.id_tipo_pieza = pz.id_tipo_pieza
                INNER JOIN fabricante f      ON f.id_fabricante = pz.id_fabricante
                INNER JOIN usuario_sistema u ON u.id_usuario_sistema = ep.id_usuario_sistema
                WHERE ep.id_entrega = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /**
     * Registra una nueva entrega de pieza a un paciente
     * Demuestra: Transacciones en PostgreSQL (BEGIN, COMMIT) y actualización de estado
     */
    public function crear(array $datos): int {
        $this->db->beginTransaction();
        try {
            $sql = "INSERT INTO entrega_pieza (
                        id_paciente, id_pieza, id_usuario_sistema, 
                        fecha_entrega, estado_entrega, observaciones
                    ) VALUES (
                        :id_paciente, :id_pieza, :id_usuario_sistema, 
                        :fecha_entrega, :estado_entrega, :observaciones
                    ) RETURNING id_entrega";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id_paciente'        => (int)$datos['id_paciente'],
                ':id_pieza'           => (int)$datos['id_pieza'],
                ':id_usuario_sistema' => (int)$datos['id_usuario_sistema'],
                ':fecha_entrega'      => !empty($datos['fecha_entrega']) ? trim($datos['fecha_entrega']) : date('Y-m-d'),
                ':estado_entrega'     => $datos['estado_entrega'] ?? 'ENTREGADO',
                ':observaciones'      => !empty($datos['observaciones']) ? trim($datos['observaciones']) : null
            ]);

            $idEntrega = (int)$stmt->fetchColumn();

            // Actualizar la pieza a ENTREGADA
            $stmtPieza = $this->db->prepare("UPDATE pieza SET estado = 'ENTREGADA' WHERE id_pieza = :id_pieza");
            $stmtPieza->execute([':id_pieza' => (int)$datos['id_pieza']]);

            $this->db->commit();
            return $idEntrega;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Actualiza los datos de una entrega existente
     */
    public function actualizar(int $id, array $datos): bool {
        $sql = "UPDATE entrega_pieza SET
                    id_paciente    = :id_paciente,
                    id_pieza       = :id_pieza,
                    fecha_entrega  = :fecha_entrega,
                    estado_entrega = :estado_entrega,
                    observaciones  = :observaciones
                WHERE id_entrega = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id_paciente'    => (int)$datos['id_paciente'],
            ':id_pieza'       => (int)$datos['id_pieza'],
            ':fecha_entrega'  => trim($datos['fecha_entrega']),
            ':estado_entrega' => $datos['estado_entrega'] ?? 'ENTREGADO',
            ':observaciones'  => !empty($datos['observaciones']) ? trim($datos['observaciones']) : null,
            ':id'             => $id
        ]);
    }

    /**
     * Elimina un registro de entrega y devuelve la pieza al estado DISPONIBLE
     */
    public function eliminar(int $id): bool {
        $this->db->beginTransaction();
        try {
            // Obtener el ID de la pieza antes de borrar
            $stmtId = $this->db->prepare("SELECT id_pieza FROM entrega_pieza WHERE id_entrega = :id");
            $stmtId->execute([':id' => $id]);
            $idPieza = $stmtId->fetchColumn();

            $sql = "DELETE FROM entrega_pieza WHERE id_entrega = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);

            if ($idPieza) {
                // Verificar si la pieza no tiene otra entrega activa
                $stmtCheck = $this->db->prepare("SELECT COUNT(*) FROM entrega_pieza WHERE id_pieza = :id_pieza");
                $stmtCheck->execute([':id_pieza' => $idPieza]);
                if ((int)$stmtCheck->fetchColumn() === 0) {
                    $stmtUpdate = $this->db->prepare("UPDATE pieza SET estado = 'DISPONIBLE' WHERE id_pieza = :id_pieza");
                    $stmtUpdate->execute([':id_pieza' => $idPieza]);
                }
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Listado de pacientes activos para poblar el dropdown de entregas
     */
    public function listarPacientesActivos(): array {
        $sql = "SELECT id_paciente, numero_documento, nombres, apellidos 
                FROM paciente 
                WHERE activo = true 
                ORDER BY apellidos, nombres ASC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Listado de piezas disponibles para entrega (o la pieza actualmente seleccionada al editar)
     */
    public function listarPiezasDisponibles(?int $incluirPiezaId = null): array {
        $sql = "SELECT p.id_pieza, p.codigo_pieza, p.nombre_pieza, p.numero_serie, tp.nombre_tipo
                FROM pieza p
                INNER JOIN tipo_pieza tp ON tp.id_tipo_pieza = p.id_tipo_pieza
                WHERE p.estado = 'DISPONIBLE'";

        $params = [];
        if (!empty($incluirPiezaId) && $incluirPiezaId > 0) {
            $sql .= " OR p.id_pieza = :incluir";
            $params[':incluir'] = $incluirPiezaId;
        }

        $sql .= " ORDER BY p.nombre_pieza ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
