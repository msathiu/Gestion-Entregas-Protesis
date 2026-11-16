<?php
/**
 * Modelo: Evaluación Biomecánica
 * Tabla: evaluacion_biomecanica

 */

require_once __DIR__ . '/../config/database.php';

class Evaluacion {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Lista evaluaciones clínicas asociadas a pacientes
     * Demuestra: SELECT con INNER JOIN a paciente, paciente_condicion y nivel_actividad
     */
    public function listar(?int $idPaciente = null): array {
        $sql = "SELECT eb.id_evaluacion_biomecanica, eb.fecha_evaluacion, eb.peso_kg, eb.altura_cm,
                       eb.observaciones,
                       p.id_paciente, p.numero_documento, p.nombres || ' ' || p.apellidos AS nombre_paciente,
                       sc.nombre_segmento, pc.lateralidad,
                       na.codigo_nivel, na.nombre_nivel, na.factor_desgaste,
                       u.nombre_completo AS evaluador
                FROM evaluacion_biomecanica eb
                INNER JOIN paciente_condicion pc   ON pc.id_paciente_condicion    = eb.id_paciente_condicion
                INNER JOIN paciente p              ON p.id_paciente               = pc.id_paciente
                INNER JOIN segmento_corporal sc    ON sc.id_segmento_corporal     = pc.id_segmento_corporal
                INNER JOIN nivel_actividad na      ON na.id_nivel_actividad       = eb.id_nivel_actividad
                INNER JOIN usuario_sistema u       ON u.id_usuario_sistema        = eb.id_usuario_sistema_evaluador";

        $params = [];
        if ($idPaciente !== null) {
            $sql .= " WHERE p.id_paciente = :id_paciente";
            $params[':id_paciente'] = $idPaciente;
        }

        $sql .= " ORDER BY eb.fecha_evaluacion DESC, eb.id_evaluacion_biomecanica DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene una evaluación detallada por clave primaria
     * Demuestra: SELECT con JOINs y filtrado WHERE sobre PK
     */
    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT eb.id_evaluacion_biomecanica, eb.id_paciente_condicion, eb.id_nivel_actividad,
                       eb.id_usuario_sistema_evaluador, eb.fecha_evaluacion, eb.peso_kg, eb.altura_cm,
                       eb.observaciones, eb.fecha_creacion, eb.fecha_actualizacion,
                       p.id_paciente, p.numero_documento, p.nombres || ' ' || p.apellidos AS nombre_paciente,
                       sc.nombre_segmento, pc.lateralidad,
                       na.codigo_nivel, na.nombre_nivel, na.factor_desgaste,
                       u.nombre_completo AS evaluador
                FROM evaluacion_biomecanica eb
                INNER JOIN paciente_condicion pc   ON pc.id_paciente_condicion    = eb.id_paciente_condicion
                INNER JOIN paciente p              ON p.id_paciente               = pc.id_paciente
                INNER JOIN segmento_corporal sc    ON sc.id_segmento_corporal     = pc.id_segmento_corporal
                INNER JOIN nivel_actividad na      ON na.id_nivel_actividad       = eb.id_nivel_actividad
                INNER JOIN usuario_sistema u       ON u.id_usuario_sistema        = eb.id_usuario_sistema_evaluador
                WHERE eb.id_evaluacion_biomecanica = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $eval = $stmt->fetch();

        return $eval ?: null;
    }

    /**
     * Registra una nueva evaluación clínica
     * Demuestra: INSERT con claves foráneas hacia condición, nivel de actividad y usuario
     */
    public function crear(array $datos): int {
        $sql = "INSERT INTO evaluacion_biomecanica (
                    id_paciente_condicion, id_nivel_actividad, id_usuario_sistema_evaluador,
                    fecha_evaluacion, peso_kg, altura_cm, observaciones
                ) VALUES (
                    :id_paciente_condicion, :id_nivel_actividad, :id_usuario_sistema_evaluador,
                    :fecha_evaluacion, :peso_kg, :altura_cm, :observaciones
                ) RETURNING id_evaluacion_biomecanica";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_paciente_condicion'        => $datos['id_paciente_condicion'],
            ':id_nivel_actividad'           => $datos['id_nivel_actividad'],
            ':id_usuario_sistema_evaluador' => $datos['id_usuario_sistema_evaluador'],
            ':fecha_evaluacion'             => $datos['fecha_evaluacion'] ?? date('Y-m-d'),
            ':peso_kg'                      => $datos['peso_kg'],
            ':altura_cm'                    => $datos['altura_cm'],
            ':observaciones'                => $datos['observaciones'] ?? null,
        ]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Actualiza los datos de una evaluación
     * Demuestra: UPDATE preparado
     */
    public function actualizar(int $id, array $datos): bool {
        $sql = "UPDATE evaluacion_biomecanica SET
                    id_nivel_actividad           = :id_nivel_actividad,
                    id_usuario_sistema_evaluador = :id_usuario_sistema_evaluador,
                    fecha_evaluacion             = :fecha_evaluacion,
                    peso_kg                      = :peso_kg,
                    altura_cm                    = :altura_cm,
                    observaciones                = :observaciones
                WHERE id_evaluacion_biomecanica = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id_nivel_actividad'           => $datos['id_nivel_actividad'],
            ':id_usuario_sistema_evaluador' => $datos['id_usuario_sistema_evaluador'],
            ':fecha_evaluacion'             => $datos['fecha_evaluacion'],
            ':peso_kg'                      => $datos['peso_kg'],
            ':altura_cm'                    => $datos['altura_cm'],
            ':observaciones'                => $datos['observaciones'] ?? null,
            ':id'                           => $id,
        ]);
    }

    /**
     * Elimina una evaluación biomecánica
     * Demuestra: DELETE
     */
    public function eliminar(int $id): bool {
        $sql = "DELETE FROM evaluacion_biomecanica WHERE id_evaluacion_biomecanica = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Consulta las medidas anatómicas tomadas en esta evaluación
     * Demuestra: INNER JOIN a tipo_medida
     */
    public function obtenerMedidas(int $idEvaluacion): array {
        $sql = "SELECT ma.id_medida_anatomica, ma.valor_medida, tm.nombre_medida, tm.unidad_medida, tm.descripcion
                FROM medida_anatomica ma
                INNER JOIN tipo_medida tm ON tm.id_tipo_medida = ma.id_tipo_medida
                WHERE ma.id_evaluacion_biomecanica = :id
                ORDER BY tm.nombre_medida ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $idEvaluacion]);
        return $stmt->fetchAll();
    }

    /**
     * Catálogo: Pacientes y sus condiciones para listas desplegables
     */
    public function listarCondicionesPacientes(): array {
        $sql = "SELECT pc.id_paciente_condicion, p.id_paciente, p.numero_documento,
                       p.nombres || ' ' || p.apellidos AS nombre_paciente,
                       sc.nombre_segmento, pc.lateralidad
                FROM paciente_condicion pc
                INNER JOIN paciente p           ON p.id_paciente           = pc.id_paciente
                INNER JOIN segmento_corporal sc ON sc.id_segmento_corporal = pc.id_segmento_corporal
                WHERE p.activo = TRUE
                ORDER BY p.apellidos ASC, p.nombres ASC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Catálogo: Niveles de actividad K-Level
     */
    public function listarNivelesActividad(): array {
        $sql = "SELECT id_nivel_actividad, codigo_nivel, nombre_nivel, factor_desgaste 
                FROM nivel_actividad 
                ORDER BY id_nivel_actividad ASC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Catálogo: Tipos de medidas anatómicas
     */
    public function listarTiposMedida(): array {
        $sql = "SELECT id_tipo_medida, nombre_medida, unidad_medida, descripcion 
                FROM tipo_medida 
                ORDER BY id_tipo_medida ASC";
        return $this->db->query($sql)->fetchAll();
    }
}
