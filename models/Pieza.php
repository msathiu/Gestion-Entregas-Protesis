<?php
/**
 * Modelo: Pieza Protésica (Gestión de Piezas)
 * Asignatura: Bases de Datos - Ingeniería de Sistemas (ULA)
 *
 * Cumple con las tres primeras formas normales:
 * - 1FN: Atributos atómicos, clave primaria única id_pieza.
 * - 2FN: Dependencia funcional completa de la clave primaria.
 * - 3FN: Sin dependencias transitivas (id_tipo_pieza e id_fabricante referencian catálogos).
 */

require_once __DIR__ . '/../config/database.php';

class Pieza {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Consulta el listado de piezas aplicando filtros directamente en PostgreSQL
     * Demuestra: SELECT con INNER JOIN, WHERE dinámico y ORDER BY
     */
    public function listar(?string $buscar = null, ?string $estado = null, ?int $idTipo = null): array {
        $sql = "SELECT p.id_pieza, p.codigo_pieza, p.nombre_pieza, p.numero_serie, 
                       p.estado, p.costo, p.descripcion, p.fecha_registro,
                       tp.id_tipo_pieza, tp.nombre_tipo,
                       f.id_fabricante, f.nombre_fabricante
                FROM pieza p
                INNER JOIN tipo_pieza tp ON tp.id_tipo_pieza = p.id_tipo_pieza
                INNER JOIN fabricante f  ON f.id_fabricante = p.id_fabricante
                WHERE 1=1";

        $params = [];

        if (!empty($buscar)) {
            $sql .= " AND (p.codigo_pieza ILIKE :buscar OR p.nombre_pieza ILIKE :buscar OR p.numero_serie ILIKE :buscar)";
            $params[':buscar'] = '%' . trim($buscar) . '%';
        }

        if (!empty($estado)) {
            $sql .= " AND p.estado = :estado";
            $params[':estado'] = trim($estado);
        }

        if (!empty($idTipo) && $idTipo > 0) {
            $sql .= " AND p.id_tipo_pieza = :id_tipo";
            $params[':id_tipo'] = $idTipo;
        }

        $sql .= " ORDER BY p.id_pieza DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Consulta una pieza por su ID con datos relacionados
     * Demuestra: SELECT con INNER JOIN por clave primaria
     */
    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT p.*, tp.nombre_tipo, f.nombre_fabricante
                FROM pieza p
                INNER JOIN tipo_pieza tp ON tp.id_tipo_pieza = p.id_tipo_pieza
                INNER JOIN fabricante f  ON f.id_fabricante = p.id_fabricante
                WHERE p.id_pieza = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /**
     * Registra una nueva pieza protésica
     * Demuestra: INSERT con cláusula RETURNING id_pieza
     */
    public function crear(array $datos): int {
        $sql = "INSERT INTO pieza (
                    codigo_pieza, nombre_pieza, id_tipo_pieza, id_fabricante, 
                    numero_serie, estado, costo, descripcion, fecha_registro
                ) VALUES (
                    :codigo_pieza, :nombre_pieza, :id_tipo_pieza, :id_fabricante, 
                    :numero_serie, :estado, :costo, :descripcion, :fecha_registro
                ) RETURNING id_pieza";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':codigo_pieza'   => strtoupper(trim($datos['codigo_pieza'])),
            ':nombre_pieza'   => trim($datos['nombre_pieza']),
            ':id_tipo_pieza'  => (int)$datos['id_tipo_pieza'],
            ':id_fabricante'  => (int)$datos['id_fabricante'],
            ':numero_serie'   => strtoupper(trim($datos['numero_serie'])),
            ':estado'         => $datos['estado'] ?? 'DISPONIBLE',
            ':costo'          => (float)($datos['costo'] ?? 0),
            ':descripcion'    => !empty($datos['descripcion']) ? trim($datos['descripcion']) : null,
            ':fecha_registro' => !empty($datos['fecha_registro']) ? trim($datos['fecha_registro']) : date('Y-m-d')
        ]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Modifica los datos de una pieza existente
     * Demuestra: UPDATE preparado
     */
    public function actualizar(int $id, array $datos): bool {
        $sql = "UPDATE pieza SET
                    codigo_pieza  = :codigo_pieza,
                    nombre_pieza  = :nombre_pieza,
                    id_tipo_pieza = :id_tipo_pieza,
                    id_fabricante = :id_fabricante,
                    numero_serie  = :numero_serie,
                    estado        = :estado,
                    costo         = :costo,
                    descripcion   = :descripcion
                WHERE id_pieza = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':codigo_pieza'  => strtoupper(trim($datos['codigo_pieza'])),
            ':nombre_pieza'  => trim($datos['nombre_pieza']),
            ':id_tipo_pieza' => (int)$datos['id_tipo_pieza'],
            ':id_fabricante' => (int)$datos['id_fabricante'],
            ':numero_serie'  => strtoupper(trim($datos['numero_serie'])),
            ':estado'        => $datos['estado'] ?? 'DISPONIBLE',
            ':costo'         => (float)($datos['costo'] ?? 0),
            ':descripcion'   => !empty($datos['descripcion']) ? trim($datos['descripcion']) : null,
            ':id'            => $id
        ]);
    }

    /**
     * Elimina una pieza de la base de datos
     * Demuestra: DELETE con protección de integridad referencial
     */
    public function eliminar(int $id): bool {
        $sql = "DELETE FROM pieza WHERE id_pieza = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Catálogo: Tipos de piezas protésicas
     */
    public function listarTipos(): array {
        $sql = "SELECT id_tipo_pieza, nombre_tipo FROM tipo_pieza ORDER BY nombre_tipo ASC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Catálogo: Fabricantes registrados
     */
    public function listarFabricantes(): array {
        $sql = "SELECT id_fabricante, nombre_fabricante FROM fabricante ORDER BY nombre_fabricante ASC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Total de piezas agrupadas por estado (para métricas de dashboard)
     * Demuestra: GROUP BY y COUNT() en PostgreSQL
     */
    public function contarPorEstado(): array {
        $sql = "SELECT estado, COUNT(*) AS total FROM pieza GROUP BY estado ORDER BY estado ASC";
        return $this->db->query($sql)->fetchAll();
    }
}
