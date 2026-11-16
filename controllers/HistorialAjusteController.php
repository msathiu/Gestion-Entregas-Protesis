<?php
/**
 * Controlador: HistorialAjusteController
 * Asignatura: Bases de Datos - Ingeniería de Sistemas (ULA)
 * Responsabilidad: Coordinar validaciones, permisos y operaciones CRUD sobre el historial de ajustes
 */

require_once __DIR__ . '/../models/HistorialAjuste.php';
require_once __DIR__ . '/../auth/Auth.php';
require_once __DIR__ . '/../auth/Permisos.php';
require_once __DIR__ . '/../includes/funciones.php';

class HistorialAjusteController {
    private HistorialAjuste $modelo;

    public function __construct() {
        $this->modelo = new HistorialAjuste();
    }

    /**
     * Lista ajustes técnicos realizados
     */
    public function index(?int $idDispositivo = null): array {
        $errorPermiso = Auth::validarPermiso('ajustes', 'listar');
        if ($errorPermiso) return $errorPermiso;

        try {
            $ajustes = $this->modelo->listar($idDispositivo);
            return [
                'success' => true,
                'total'   => count($ajustes),
                'data'    => $ajustes
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
     * Muestra el detalle de un ajuste por ID
     */
    public function show(int $id): array {
        $errorPermiso = Auth::validarPermiso('ajustes', 'ver');
        if ($errorPermiso) return $errorPermiso;

        try {
            $ajuste = $this->modelo->obtenerPorId($id);
            if (!$ajuste) {
                return [
                    'success' => false,
                    'mensaje' => "El registro de ajuste con ID {$id} no fue encontrado."
                ];
            }

            return [
                'success' => true,
                'data'    => $ajuste
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
     * Valida y registra un nuevo ajuste técnico
     */
    public function store(array $datos): array {
        $errorPermiso = Auth::validarPermiso('ajustes', 'crear');
        if ($errorPermiso) return $errorPermiso;

        $requeridos = ['id_dispositivo_protesico', 'id_tipo_ajuste', 'id_usuario_sistema_tecnico', 'descripcion_ajuste'];
        $faltantes = validar_campos_requeridos($datos, $requeridos);

        if (!empty($faltantes)) {
            return [
                'success'   => false,
                'mensaje'   => 'Campos obligatorios incompletos para registrar el ajuste.',
                'faltantes' => $faltantes
            ];
        }

        try {
            $id = $this->modelo->crear($datos);
            return [
                'success' => true,
                'mensaje' => 'Ajuste técnico registrado exitosamente.',
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
     * Valida y actualiza los datos de un ajuste
     */
    public function update(int $id, array $datos): array {
        $errorPermiso = Auth::validarPermiso('ajustes', 'editar');
        if ($errorPermiso) return $errorPermiso;

        $existente = $this->modelo->obtenerPorId($id);
        if (!$existente) {
            return [
                'success' => false,
                'mensaje' => "No se puede actualizar: ajuste con ID {$id} no existe."
            ];
        }

        $requeridos = ['id_dispositivo_protesico', 'id_tipo_ajuste', 'id_usuario_sistema_tecnico', 'fecha_ajuste', 'descripcion_ajuste'];
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
                'mensaje' => $actualizado ? 'Ajuste actualizado exitosamente.' : 'No se realizaron cambios.'
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
     * Elimina un ajuste técnico
     */
    public function destroy(int $id): array {
        $errorPermiso = Auth::validarPermiso('ajustes', 'eliminar');
        if ($errorPermiso) return $errorPermiso;

        $existente = $this->modelo->obtenerPorId($id);
        if (!$existente) {
            return [
                'success' => false,
                'mensaje' => "No se puede eliminar: el ajuste con ID {$id} no existe."
            ];
        }

        try {
            $eliminado = $this->modelo->eliminar($id);
            return [
                'success' => $eliminado,
                'mensaje' => $eliminado ? 'Ajuste eliminado correctamente.' : 'No se pudo eliminar el registro.'
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
     * Resumen de ajustes agrupados por tipo
     */
    public function resumenPorTipo(): array {
        $errorPermiso = Auth::validarPermiso('ajustes', 'resumen');
        if ($errorPermiso) return $errorPermiso;

        try {
            $resumen = $this->modelo->resumenPorTipo();
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
