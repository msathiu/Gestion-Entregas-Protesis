<?php
/**
 * Controlador: EvaluacionController
 * Asignatura: Bases de Datos - Ingeniería de Sistemas (ULA)
 * Responsabilidad: Coordinar validaciones, permisos y operaciones CRUD sobre evaluaciones biomecánicas
 */

require_once __DIR__ . '/../models/Evaluacion.php';
require_once __DIR__ . '/../auth/Auth.php';
require_once __DIR__ . '/../auth/Permisos.php';
require_once __DIR__ . '/../includes/funciones.php';

class EvaluacionController {
    private Evaluacion $modelo;

    public function __construct() {
        $this->modelo = new Evaluacion();
    }

    /**
     * Lista evaluaciones biomecánicas registradas
     */
    public function index(?int $idPaciente = null): array {
        $errorPermiso = Auth::validarPermiso('evaluaciones', 'listar');
        if ($errorPermiso) return $errorPermiso;

        try {
            $evaluaciones = $this->modelo->listar($idPaciente);
            return [
                'success' => true,
                'total'   => count($evaluaciones),
                'data'    => $evaluaciones
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'mensaje' => traducir_error_sqlstate($e),
                'error'   => $e->getMessage()
            ];
        }
    }

    /**
     * Muestra una evaluación biomecánica por ID
     */
    public function show(int $id): array {
        $errorPermiso = Auth::validarPermiso('evaluaciones', 'ver');
        if ($errorPermiso) return $errorPermiso;

        try {
            $evaluacion = $this->modelo->obtenerPorId($id);
            if (!$evaluacion) {
                return [
                    'success' => false,
                    'mensaje' => "La evaluación con ID {$id} no fue encontrada."
                ];
            }

            return [
                'success' => true,
                'data'    => $evaluacion
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'mensaje' => traducir_error_sqlstate($e),
                'error'   => $e->getMessage()
            ];
        }
    }

    /**
     * Valida y registra una nueva evaluación biomecánica
     */
    public function store(array $datos): array {
        $errorPermiso = Auth::validarPermiso('evaluaciones', 'crear');
        if ($errorPermiso) return $errorPermiso;

        $requeridos = ['id_paciente_condicion', 'id_nivel_actividad', 'id_usuario_sistema_evaluador', 'fecha_evaluacion', 'peso_kg', 'altura_cm'];
        $faltantes = validar_campos_requeridos($datos, $requeridos);

        if (!empty($faltantes)) {
            return [
                'success'   => false,
                'mensaje'   => 'Campos obligatorios incompletos para registrar la evaluación.',
                'faltantes' => $faltantes
            ];
        }

        try {
            $id = $this->modelo->crear($datos);
            return [
                'success' => true,
                'mensaje' => 'Evaluación biomecánica registrada exitosamente.',
                'id'      => $id
            ];
        } catch (PDOException $e) {
            return [
                'success'  => false,
                'mensaje'  => traducir_error_sqlstate($e),
                'sqlstate' => $e->getCode(),
                'error'    => $e->getMessage()
            ];
        }
    }

    /**
     * Valida y actualiza los datos de una evaluación
     */
    public function update(int $id, array $datos): array {
        $errorPermiso = Auth::validarPermiso('evaluaciones', 'editar');
        if ($errorPermiso) return $errorPermiso;

        $existente = $this->modelo->obtenerPorId($id);
        if (!$existente) {
            return [
                'success' => false,
                'mensaje' => "No se puede actualizar: evaluación con ID {$id} no existe."
            ];
        }

        $requeridos = ['id_paciente_condicion', 'id_nivel_actividad', 'id_usuario_sistema_evaluador', 'fecha_evaluacion', 'peso_kg', 'altura_cm'];
        $faltantes = validar_campos_requeridos($datos, $requeridos);

        if (!empty($faltantes)) {
            return [
                'success'   => false,
                'mensaje'   => 'Campos obligatorios incompletos.',
                'faltantes' => $faltantes
            ];
        }

        try {
            $actualizado = $this->modelo->actualizar($id, $datos);
            return [
                'success' => $actualizado,
                'mensaje' => $actualizado ? 'Evaluación actualizada exitosamente.' : 'No se realizaron cambios.'
            ];
        } catch (PDOException $e) {
            return [
                'success'  => false,
                'mensaje'  => traducir_error_sqlstate($e),
                'sqlstate' => $e->getCode(),
                'error'    => $e->getMessage()
            ];
        }
    }

    /**
     * Elimina una evaluación biomecánica
     */
    public function destroy(int $id): array {
        $errorPermiso = Auth::validarPermiso('evaluaciones', 'eliminar');
        if ($errorPermiso) return $errorPermiso;

        $existente = $this->modelo->obtenerPorId($id);
        if (!$existente) {
            return [
                'success' => false,
                'mensaje' => "No se puede eliminar: la evaluación con ID {$id} no existe."
            ];
        }

        try {
            $eliminado = $this->modelo->eliminar($id);
            return [
                'success' => $eliminado,
                'mensaje' => $eliminado ? 'Evaluación eliminada correctamente.' : 'No se pudo eliminar el registro.'
            ];
        } catch (PDOException $e) {
            return [
                'success'  => false,
                'mensaje'  => traducir_error_sqlstate($e),
                'sqlstate' => $e->getCode(),
                'error'    => $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene las medidas anatómicas asociadas a una evaluación
     */
    public function medidas(int $idEvaluacion): array {
        $errorPermiso = Auth::validarPermiso('evaluaciones', 'medidas');
        if ($errorPermiso) return $errorPermiso;

        try {
            $medidas = $this->modelo->obtenerMedidas($idEvaluacion);
            return [
                'success' => true,
                'total'   => count($medidas),
                'data'    => $medidas
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'mensaje' => traducir_error_sqlstate($e),
                'error'   => $e->getMessage()
            ];
        }
    }

    /**
     * Registra una medida anatómica en una evaluación
     */
    public function registrarMedida(int $idEvaluacion, int $idTipoMedida, float $valor): array {
        $errorPermiso = Auth::validarPermiso('evaluaciones', 'editar');
        if ($errorPermiso) return $errorPermiso;

        try {
            $idMedida = $this->modelo->registrarMedida($idEvaluacion, $idTipoMedida, $valor);
            return [
                'success' => true,
                'mensaje' => 'Medida anatómica registrada.',
                'id'      => $idMedida
            ];
        } catch (PDOException $e) {
            return [
                'success'  => false,
                'mensaje'  => traducir_error_sqlstate($e),
                'sqlstate' => $e->getCode(),
                'error'    => $e->getMessage()
            ];
        }
    }

    /**
     * Catálogos requeridos para formularios de evaluación
     */
    public function catalogos(): array {
        return [
            'condiciones' => $this->modelo->listarCondicionesPacientes(),
            'niveles'     => $this->modelo->listarNivelesActividad(),
            'tiposMedida' => $this->modelo->listarTiposMedida(),
        ];
    }
}
