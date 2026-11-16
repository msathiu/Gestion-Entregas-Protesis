<?php
/**
 * Modelo: Componente Protésico
 * Tabla: componente
 * Asignatura: Bases de Datos - Ingeniería de Sistemas (ULA)
 */

require_once __DIR__ . '/../config/database.php';

class Componente {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Lista piezas y componentes con su modelo, categoría, fabricante y estado
     * Demuestra: INNER JOINs múltiples, LEFT JOIN para dispositivo instalado, WHERE y ORDER BY
     */
    public function listar(?int $idEstado = null): array {
        $sql = "SELECT c.id_componente, c.numero_serie, c.fecha_adquisicion, c.horas_desgaste_acumuladas,
                       mc.id_modelo_componente, mc.nombre_modelo, mc.vida_util_horas, mc.peso_maximo_kg,
                       cat.id_categoria_componente, cat.nombre_categoria,
                       f.id_fabricante, f.nombre_fabricante,
                       ec.id_estado_componente, ec.nombre_estado,
                       dc.id_dispositivo_protesico, dp.codigo_dispositivo
                FROM componente c
                INNER JOIN modelo_componente mc       ON mc.id_modelo_componente    = c.id_modelo_componente
                INNER JOIN categoria_componente cat   ON cat.id_categoria_componente = mc.id_categoria_componente
                INNER JOIN fabricante f              ON f.id_fabricante             = mc.id_fabricante
                INNER JOIN estado_componente ec      ON ec.id_estado_componente     = c.id_estado_componente
                LEFT JOIN dispositivo_componente dc  ON dc.id_componente           = c.id_componente AND dc.fecha_retiro IS NULL
                LEFT JOIN dispositivo_protesico dp   ON dp.id_dispositivo_protesico = dc.id_dispositivo_protesico";

        $params = [];
        if ($idEstado !== null) {
            $sql .= " WHERE c.id_estado_componente = :id_estado";
            $params[':id_estado'] = $idEstado;
        }

        $sql .= " ORDER BY c.id_componente ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene el detalle de un componente por su clave primaria
     * Demuestra: SELECT con JOINs filtrado por PK
     */
    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT c.id_componente, c.id_modelo_componente, c.id_estado_componente,
                       c.numero_serie, c.fecha_adquisicion, c.horas_desgaste_acumuladas,
                       c.fecha_creacion, c.fecha_actualizacion,
                       mc.nombre_modelo, mc.vida_util_horas, mc.peso_maximo_kg,
                       cat.nombre_categoria, f.nombre_fabricante, ec.nombre_estado,
                       dc.id_dispositivo_protesico, dp.codigo_dispositivo
                FROM componente c
                INNER JOIN modelo_componente mc       ON mc.id_modelo_componente    = c.id_modelo_componente
                INNER JOIN categoria_componente cat   ON cat.id_categoria_componente = mc.id_categoria_componente
                INNER JOIN fabricante f              ON f.id_fabricante             = mc.id_fabricante
                INNER JOIN estado_componente ec      ON ec.id_estado_componente     = c.id_estado_componente
                LEFT JOIN dispositivo_componente dc  ON dc.id_componente           = c.id_componente AND dc.fecha_retiro IS NULL
                LEFT JOIN dispositivo_protesico dp   ON dp.id_dispositivo_protesico = dc.id_dispositivo_protesico
                WHERE c.id_componente = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $res = $stmt->fetch();

        return $res ?: null;
    }

    /**
     * Inserta un nuevo componente físico
     * Demuestra: INSERT INTO con RETURNING de PK
     */
    public function crear(array $datos): int {
        $sql = "INSERT INTO componente (
                    id_modelo_componente, id_estado_componente, numero_serie,
                    fecha_adquisicion, horas_desgaste_acumuladas
                ) VALUES (
                    :id_modelo_componente, :id_estado_componente, :numero_serie,
                    :fecha_adquisicion, :horas_desgaste_acumuladas
                ) RETURNING id_componente";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_modelo_componente'      => $datos['id_modelo_componente'],
            ':id_estado_componente'      => $datos['id_estado_componente'],
            ':numero_serie'              => $datos['numero_serie'],
            ':fecha_adquisicion'         => $datos['fecha_adquisicion'] ?? date('Y-m-d'),
            ':horas_desgaste_acumuladas' => $datos['horas_desgaste_acumuladas'] ?? 0,
        ]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Actualiza la información de un componente
     * Demuestra: UPDATE con WHERE y parámetros preparados
     */
    public function actualizar(int $id, array $datos): bool {
        $sql = "UPDATE componente SET
                    id_modelo_componente      = :id_modelo_componente,
                    id_estado_componente      = :id_estado_componente,
                    numero_serie              = :numero_serie,
                    fecha_adquisicion         = :fecha_adquisicion,
                    horas_desgaste_acumuladas = :horas_desgaste_acumuladas
                WHERE id_componente = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id_modelo_componente'      => $datos['id_modelo_componente'],
            ':id_estado_componente'      => $datos['id_estado_componente'],
            ':numero_serie'              => $datos['numero_serie'],
            ':fecha_adquisicion'         => $datos['fecha_adquisicion'],
            ':horas_desgaste_acumuladas' => $datos['horas_desgaste_acumuladas'],
            ':id'                        => $id,
        ]);
    }

    /**
     * Elimina un componente físico
     * Demuestra: DELETE sujeto a restricciones de clave foránea (FK)
     */
    public function eliminar(int $id): bool {
        $sql = "DELETE FROM componente WHERE id_componente = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Vincula un componente a un dispositivo protésico
     * Demuestra: INSERT en tabla asociativa de relación N:M (dispositivo_componente)
     */
    public function instalarEnDispositivo(int $idComponente, int $idDispositivo, ?string $fechaInstalacion = null): int {
        $sql = "INSERT INTO dispositivo_componente (
                    id_dispositivo_protesico, id_componente, fecha_instalacion
                ) VALUES (
                    :id_dispositivo, :id_componente, :fecha_instalacion
                ) RETURNING id_dispositivo_componente";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_dispositivo'    => $idDispositivo,
            ':id_componente'     => $idComponente,
            ':fecha_instalacion' => $fechaInstalacion ?? date('Y-m-d')
        ]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Lista modelos de componentes compatibles con un modelo dado
     * Demuestra: Consulta reflexiva N:M mediante compatibilidad_modelo
     */
    public function obtenerCompatibles(int $idModelo): array {
        $sql = "SELECT cm.id_compatibilidad_modelo, cm.descripcion,
                       mc.id_modelo_componente AS id_modelo_compatible,
                       mc.nombre_modelo AS modelo_compatible,
                       f.nombre_fabricante
                FROM compatibilidad_modelo cm
                INNER JOIN modelo_componente mc ON mc.id_modelo_componente = cm.id_modelo_componente_compatible
                INNER JOIN fabricante f         ON f.id_fabricante         = mc.id_fabricante
                WHERE cm.id_modelo_componente = :id_modelo";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_modelo' => $idModelo]);
        return $stmt->fetchAll();
    }

    /**
     * Catálogo: Modelos de componentes con fabricante y categoría
     */
    public function listarModelos(): array {
        $sql = "SELECT mc.id_modelo_componente, mc.nombre_modelo, mc.vida_util_horas,
                       f.nombre_fabricante, cat.nombre_categoria
                FROM modelo_componente mc
                INNER JOIN fabricante f              ON f.id_fabricante             = mc.id_fabricante
                INNER JOIN categoria_componente cat  ON cat.id_categoria_componente = mc.id_categoria_componente
                ORDER BY mc.nombre_modelo ASC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Catálogo: Estados físicos del componente
     */
    public function listarEstados(): array {
        $sql = "SELECT id_estado_componente, nombre_estado 
                FROM estado_componente 
                ORDER BY id_estado_componente ASC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Dispositivos aptos para recibir instalación de piezas
     */
    public function listarDispositivosActivos(): array {
        $sql = "SELECT dp.id_dispositivo_protesico, dp.codigo_dispositivo,
                       p.nombres || ' ' || p.apellidos AS paciente
                FROM dispositivo_protesico dp
                INNER JOIN paciente_condicion pc ON pc.id_paciente_condicion = dp.id_paciente_condicion
                INNER JOIN paciente p            ON p.id_paciente            = pc.id_paciente
                ORDER BY dp.codigo_dispositivo ASC";
        return $this->db->query($sql)->fetchAll();
    }
}
