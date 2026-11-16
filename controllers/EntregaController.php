<?php
/**
 * Controlador: Gestión de Entrega de Piezas Protésicas
 * Asignatura: Bases de Datos - Ingeniería de Sistemas (ULA)
 */

require_once __DIR__ . '/../models/Entrega.php';
require_once __DIR__ . '/../auth/Auth.php';
require_once __DIR__ . '/../includes/funciones.php';

class EntregaController {
    private Entrega $modelo;

    public function __construct() {
        $this->modelo = new Entrega();
    }

    /**
     * Listado del historial de entregas con filtros
     */
    public function index(?string $buscar = null, ?string $estado = null): array {
        $errorPermiso = Auth::validarPermiso('entregas', 'listar');
        if ($errorPermiso) return $errorPermiso;

        try {
            $entregas = $this->modelo->listar($buscar, $estado);
            return [
                'success' => true,
                'total'   => count($entregas),
                'data'    => $entregas
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
     * Consulta el detalle de una entrega por su ID
     */
    public function show(int $id): array {
        $errorPermiso = Auth::validarPermiso('entregas', 'ver');
        if ($errorPermiso) return $errorPermiso;

        try {
            $entrega = $this->modelo->obtenerPorId($id);
            if (!$entrega) {
                return [
                    'success' => false,
                    'mensaje' => "Registro de entrega con ID {$id} no encontrado."
                ];
            }
            return [
                'success' => true,
                'data'    => $entrega
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
     * Registra una nueva entrega de pieza a un paciente
     */
    public function store(array $datos): array {
        $errorPermiso = Auth::validarPermiso('entregas', 'crear');
        if ($errorPermiso) return $errorPermiso;

        if (empty($datos['id_usuario_sistema'])) {
            $usuario = Auth::usuario();
            $datos['id_usuario_sistema'] = $usuario['id_usuario_sistema'] ?? 1;
        }

        $errores = $this->validar($datos);
        if (!empty($errores)) {
            return [
                'success' => false,
                'mensaje' => implode(' | ', $errores),
                'errores' => $errores
            ];
        }

        try {
            $id = $this->modelo->crear($datos);
            return [
                'success' => true,
                'mensaje' => 'Entrega de pieza registrada exitosamente.',
                'id'      => $id
            ];
        } catch (Throwable $e) {
            return [
                'success'  => false,
                'mensaje'  => ($e instanceof PDOException) ? traducir_error_sqlstate($e) : $e->getMessage(),
                'sqlstate' => $e->getCode(),
                'error'    => $e->getMessage()
            ];
        }
    }

    /**
     * Actualiza un registro de entrega
     */
    public function update(int $id, array $datos): array {
        $errorPermiso = Auth::validarPermiso('entregas', 'editar');
        if ($errorPermiso) return $errorPermiso;

        $errores = $this->validar($datos);
        if (!empty($errores)) {
            return [
                'success' => false,
                'mensaje' => implode(' | ', $errores),
                'errores' => $errores
            ];
        }

        try {
            $ok = $this->modelo->actualizar($id, $datos);
            return [
                'success' => $ok,
                'mensaje' => $ok ? 'Registro de entrega actualizado.' : 'No se pudo actualizar la entrega.'
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
     * Elimina una entrega y libera la pieza
     */
    public function destroy(int $id): array {
        $errorPermiso = Auth::validarPermiso('entregas', 'eliminar');
        if ($errorPermiso) return $errorPermiso;

        try {
            $ok = $this->modelo->eliminar($id);
            return [
                'success' => $ok,
                'mensaje' => $ok ? 'Registro de entrega eliminado y pieza devuelta a inventario disponible.' : 'No se pudo eliminar la entrega.'
            ];
        } catch (Throwable $e) {
            return [
                'success'  => false,
                'mensaje'  => ($e instanceof PDOException) ? traducir_error_sqlstate($e) : $e->getMessage(),
                'sqlstate' => $e->getCode(),
                'error'    => $e->getMessage()
            ];
        }
    }

    public function getPacientes(): array {
        return $this->modelo->listarPacientesActivos();
    }

    public function getPiezasDisponibles(?int $incluirPiezaId = null): array {
        return $this->modelo->listarPiezasDisponibles($incluirPiezaId);
    }

    private function validar(array $datos): array {
        $errores = [];

        if (empty($datos['id_paciente']) || (int)$datos['id_paciente'] <= 0) {
            $errores[] = 'Debe seleccionar un paciente registrado.';
        }
        if (empty($datos['id_pieza']) || (int)$datos['id_pieza'] <= 0) {
            $errores[] = 'Debe seleccionar una pieza protésica para entregar.';
        }
        if (empty($datos['fecha_entrega'])) {
            $errores[] = 'La fecha de entrega es obligatoria.';
        }

        return $errores;
    }
}
