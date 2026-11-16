<?php
/**
 * Controlador: DispositivoController
 * Asignatura: Bases de Datos - Ingeniería de Sistemas (ULA)
 * Responsabilidad: Coordinar validaciones y operaciones CRUD sobre dispositivos protésicos
 */

require_once __DIR__ . '/../models/Dispositivo.php';
require_once __DIR__ . '/../includes/funciones.php';

class DispositivoController {
    private Dispositivo $modelo;

    public function __construct() {
        $this->modelo = new Dispositivo();
    }

    /**
     * Lista los dispositivos protésicos registrados
     */
    public function index(?int $idEstado = null): array {
        try {
            $dispositivos = $this->modelo->listar($idEstado);
            return [
                'success' => true,
                'total'   => count($dispositivos),
                'data'    => $dispositivos
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
     * Muestra la información de un dispositivo protésico específico
     */
    public function show(int $id): array {
        try {
            $dispositivo = $this->modelo->obtenerPorId($id);
            if (!$dispositivo) {
                return [
                    'success' => false,
                    'mensaje' => "El dispositivo con ID {$id} no fue encontrado."
                ];
            }

            return [
                'success' => true,
                'data'    => $dispositivo
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
     * Valida y crea un nuevo dispositivo protésico
     */
    public function store(array $datos): array {
        $requeridos = ['id_paciente_condicion', 'id_evaluacion_biomecanica', 'id_tipo_dispositivo', 'id_estado_dispositivo', 'codigo_dispositivo'];
        $faltantes = validar_campos_requeridos($datos, $requeridos);

        if (!empty($faltantes)) {
            return [
                'success'   => false,
                'mensaje'   => 'Campos obligatorios incompletos para registrar el dispositivo.',
                'faltantes' => $faltantes
            ];
        }

        try {
            $id = $this->modelo->crear($datos);
            return [
                'success' => true,
                'mensaje' => 'Dispositivo protésico registrado exitosamente.',
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
     * Valida y actualiza los datos de un dispositivo protésico
     */
    public function update(int $id, array $datos): array {
        $existente = $this->modelo->obtenerPorId($id);
        if (!$existente) {
            return [
                'success' => false,
                'mensaje' => "No se puede actualizar: dispositivo con ID {$id} no existe."
            ];
        }

        $requeridos = ['id_paciente_condicion', 'id_evaluacion_biomecanica', 'id_tipo_dispositivo', 'id_estado_dispositivo', 'codigo_dispositivo'];
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
                'mensaje' => $actualizado ? 'Dispositivo actualizado exitosamente.' : 'No se realizaron cambios.'
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
     * Elimina un dispositivo protésico respetando restricciones de integridad referencial
     */
    public function destroy(int $id): array {
        $existente = $this->modelo->obtenerPorId($id);
        if (!$existente) {
            return [
                'success' => false,
                'mensaje' => "No se puede eliminar: el dispositivo con ID {$id} no existe."
            ];
        }

        try {
            $eliminado = $this->modelo->eliminar($id);
            return [
                'success' => $eliminado,
                'mensaje' => $eliminado ? 'Dispositivo eliminado correctamente.' : 'No se pudo eliminar el registro.'
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
     * Lista los componentes instalados en el dispositivo
     */
    public function componentes(int $idDispositivo): array {
        try {
            $componentes = $this->modelo->obtenerComponentes($idDispositivo);
            return [
                'success' => true,
                'total'   => count($componentes),
                'data'    => $componentes
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
     * Actualiza el estado operativo del dispositivo
     */
    public function cambiarEstado(int $idDispositivo, int $idNuevoEstado): array {
        try {
            $ok = $this->modelo->cambiarEstado($idDispositivo, $idNuevoEstado);
            return [
                'success' => $ok,
                'mensaje' => $ok ? 'Estado del dispositivo actualizado.' : 'No se pudo actualizar el estado.'
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
}
