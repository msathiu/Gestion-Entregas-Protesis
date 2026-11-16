<?php
/**
 * Controlador: PacienteController
 * Asignatura: Bases de Datos - Ingeniería de Sistemas (ULA)
 * Responsabilidad: Coordinar validaciones y operaciones CRUD sobre pacientes
 */

require_once __DIR__ . '/../models/Paciente.php';
require_once __DIR__ . '/../includes/funciones.php';

class PacienteController {
    private Paciente $modelo;

    public function __construct() {
        $this->modelo = new Paciente();
    }

    /**
     * Lista pacientes registrados
     */
    public function index(?bool $soloActivos = true): array {
        try {
            $pacientes = $this->modelo->listar($soloActivos);
            return [
                'success' => true,
                'total'   => count($pacientes),
                'data'    => $pacientes
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
     * Muestra la información de un paciente específico
     */
    public function show(int $id): array {
        try {
            $paciente = $this->modelo->obtenerPorId($id);
            if (!$paciente) {
                return [
                    'success' => false,
                    'mensaje' => "El paciente con ID {$id} no fue encontrado."
                ];
            }

            return [
                'success' => true,
                'data'    => $paciente
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
     * Valida y registra un nuevo paciente
     */
    public function store(array $datos): array {
        $requeridos = ['numero_documento', 'nombres', 'apellidos', 'fecha_nacimiento', 'sexo', 'correo'];
        $faltantes = validar_campos_requeridos($datos, $requeridos);

        if (!empty($faltantes)) {
            return [
                'success' => false,
                'mensaje' => 'Campos obligatorios incompletos.',
                'faltantes' => $faltantes
            ];
        }

        try {
            $id = $this->modelo->crear($datos);
            return [
                'success' => true,
                'mensaje' => 'Paciente registrado exitosamente.',
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
     * Valida y actualiza los datos de un paciente
     */
    public function update(int $id, array $datos): array {
        $existente = $this->modelo->obtenerPorId($id);
        if (!$existente) {
            return [
                'success' => false,
                'mensaje' => "No se puede actualizar: paciente con ID {$id} no existe."
            ];
        }

        $requeridos = ['numero_documento', 'nombres', 'apellidos', 'fecha_nacimiento', 'sexo', 'correo'];
        $faltantes = validar_campos_requeridos($datos, $requeridos);

        if (!empty($faltantes)) {
            return [
                'success' => false,
                'mensaje' => 'Campos obligatorios incompletos para actualizar.',
                'faltantes' => $faltantes
            ];
        }

        try {
            $actualizado = $this->modelo->actualizar($id, $datos);
            return [
                'success' => $actualizado,
                'mensaje' => $actualizado ? 'Paciente actualizado exitosamente.' : 'No se realizaron cambios.'
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
     * Elimina un paciente verificando restricciones de integridad referencial
     */
    public function destroy(int $id): array {
        $existente = $this->modelo->obtenerPorId($id);
        if (!$existente) {
            return [
                'success' => false,
                'mensaje' => "No se puede eliminar: el paciente con ID {$id} no existe."
            ];
        }

        try {
            $eliminado = $this->modelo->eliminar($id);
            return [
                'success' => $eliminado,
                'mensaje' => $eliminado ? 'Paciente eliminado de la base de datos.' : 'No se pudo eliminar el registro.'
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
     * Consulta las condiciones clínicas y amputaciones asociadas a un paciente
     */
    public function condiciones(int $idPaciente): array {
        try {
            $condiciones = $this->modelo->obtenerCondiciones($idPaciente);
            return [
                'success' => true,
                'total'   => count($condiciones),
                'data'    => $condiciones
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
     * Consulta el reporte agregado de dispositivos por paciente
     */
    public function resumenDispositivos(): array {
        try {
            $resumen = $this->modelo->resumenDispositivosPorPaciente();
            return [
                'success' => true,
                'total'   => count($resumen),
                'data'    => $resumen
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'mensaje' => traducir_error_sqlstate($e),
                'error'   => $e->getMessage()
            ];
        }
    }
}
