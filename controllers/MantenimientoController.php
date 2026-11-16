<?php
/**
 * Controlador: MantenimientoController
 * Asignatura: Bases de Datos - Ingeniería de Sistemas (ULA)
 * Responsabilidad: Coordinar validaciones, permisos y operaciones CRUD sobre mantenimiento preventivo
 */

require_once __DIR__ . '/../models/Mantenimiento.php';
require_once __DIR__ . '/../auth/Auth.php';
require_once __DIR__ . '/../auth/Permisos.php';
require_once __DIR__ . '/../includes/funciones.php';

class MantenimientoController {
    private Mantenimiento $modelo;

    public function __construct() {
        $this->modelo = new Mantenimiento();
    }

    /**
     * Lista mantenimientos preventivos
     */
    public function index(?int $idEstado = null): array {
        $errorPermiso = Auth::validarPermiso('mantenimientos', 'listar');
        if ($errorPermiso) return $errorPermiso;

        try {
            $mantenimientos = $this->modelo->listar($idEstado);
            return [
                'success' => true,
                'total'   => count($mantenimientos),
                'data'    => $mantenimientos
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
     * Muestra el detalle de un mantenimiento por ID
     */
    public function show(int $id): array {
        $errorPermiso = Auth::validarPermiso('mantenimientos', 'ver');
        if ($errorPermiso) return $errorPermiso;

        try {
            $mantenimiento = $this->modelo->obtenerPorId($id);
            if (!$mantenimiento) {
                return [
                    'success' => false,
                    'mensaje' => "El mantenimiento con ID {$id} no fue encontrado."
                ];
            }

            return [
                'success' => true,
                'data'    => $mantenimiento
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
     * Valida y programa un nuevo mantenimiento
     */
    public function store(array $datos): array {
        $errorPermiso = Auth::validarPermiso('mantenimientos', 'crear');
        if ($errorPermiso) return $errorPermiso;

        $requeridos = ['id_dispositivo_protesico', 'id_tipo_mantenimiento', 'id_estado_mantenimiento', 'fecha_programada'];
        $faltantes = validar_campos_requeridos($datos, $requeridos);

        if (!empty($faltantes)) {
            return [
                'success'   => false,
                'mensaje'   => 'Campos obligatorios incompletos para registrar el mantenimiento.',
                'faltantes' => $faltantes
            ];
        }

        try {
            $id = $this->modelo->crear($datos);
            return [
                'success' => true,
                'mensaje' => 'Mantenimiento preventivo programado exitosamente.',
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
     * Valida y actualiza un mantenimiento
     */
    public function update(int $id, array $datos): array {
        $errorPermiso = Auth::validarPermiso('mantenimientos', 'editar');
        if ($errorPermiso) return $errorPermiso;

        $existente = $this->modelo->obtenerPorId($id);
        if (!$existente) {
            return [
                'success' => false,
                'mensaje' => "No se puede actualizar: mantenimiento con ID {$id} no existe."
            ];
        }

        $requeridos = ['id_dispositivo_protesico', 'id_tipo_mantenimiento', 'id_estado_mantenimiento', 'fecha_programada'];
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
                'mensaje' => $actualizado ? 'Mantenimiento actualizado exitosamente.' : 'No se realizaron cambios.'
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
     * Elimina un mantenimiento preventivo
     */
    public function destroy(int $id): array {
        $errorPermiso = Auth::validarPermiso('mantenimientos', 'eliminar');
        if ($errorPermiso) return $errorPermiso;

        $existente = $this->modelo->obtenerPorId($id);
        if (!$existente) {
            return [
                'success' => false,
                'mensaje' => "No se puede eliminar: el mantenimiento con ID {$id} no existe."
            ];
        }

        try {
            $eliminado = $this->modelo->eliminar($id);
            return [
                'success' => $eliminado,
                'mensaje' => $eliminado ? 'Mantenimiento eliminado correctamente.' : 'No se pudo eliminar el registro.'
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
     * Ejecuta el procedimiento almacenado para marcar mantenimientos atrasados/vencidos
     */
    public function marcarVencidos(): array {
        $errorPermiso = Auth::validarPermiso('mantenimientos', 'marcar_vencidos');
        if ($errorPermiso) return $errorPermiso;

        try {
            $afectados = $this->modelo->marcarVencidos();
            return [
                'success'   => true,
                'mensaje'   => 'Procedimiento de verificación ejecutado.',
                'afectados' => $afectados
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
     * Resumen de mantenimientos clasificados por estado
     */
    public function resumenPorEstado(): array {
        $errorPermiso = Auth::validarPermiso('mantenimientos', 'resumen');
        if ($errorPermiso) return $errorPermiso;

        try {
            $resumen = $this->modelo->resumenPorEstado();
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
