<?php
/**
 * Controlador: Gestión de Piezas Protésicas
 * Asignatura: Bases de Datos - Ingeniería de Sistemas (ULA)
 */

require_once __DIR__ . '/../models/Pieza.php';
require_once __DIR__ . '/../auth/Auth.php';
require_once __DIR__ . '/../includes/funciones.php';

class PiezaController {
    private Pieza $modelo;

    public function __construct() {
        $this->modelo = new Pieza();
    }

    /**
     * Listado general de piezas con filtros opcionales
     */
    public function index(?string $buscar = null, ?string $estado = null, ?int $idTipo = null): array {
        $errorPermiso = Auth::validarPermiso('piezas', 'listar');
        if ($errorPermiso) return $errorPermiso;

        try {
            $piezas = $this->modelo->listar($buscar, $estado, $idTipo);
            return [
                'success' => true,
                'total'   => count($piezas),
                'data'    => $piezas
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
     * Consulta el detalle de una pieza por ID
     */
    public function show(int $id): array {
        $errorPermiso = Auth::validarPermiso('piezas', 'ver');
        if ($errorPermiso) return $errorPermiso;

        try {
            $pieza = $this->modelo->obtenerPorId($id);
            if (!$pieza) {
                return [
                    'success' => false,
                    'mensaje' => "Pieza protésica con ID {$id} no encontrada."
                ];
            }
            return [
                'success' => true,
                'data'    => $pieza
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
     * Registra una nueva pieza protésica
     */
    public function store(array $datos): array {
        $errorPermiso = Auth::validarPermiso('piezas', 'crear');
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
            $id = $this->modelo->crear($datos);
            return [
                'success' => true,
                'mensaje' => 'Pieza protésica registrada exitosamente.',
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
     * Modifica una pieza existente
     */
    public function update(int $id, array $datos): array {
        $errorPermiso = Auth::validarPermiso('piezas', 'editar');
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
                'mensaje' => $ok ? 'Pieza actualizada correctamente.' : 'No se pudo actualizar la pieza.'
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
     * Elimina una pieza respetando restricciones de clave foránea
     */
    public function destroy(int $id): array {
        $errorPermiso = Auth::validarPermiso('piezas', 'eliminar');
        if ($errorPermiso) return $errorPermiso;

        try {
            $ok = $this->modelo->eliminar($id);
            return [
                'success' => $ok,
                'mensaje' => $ok ? 'Pieza eliminada correctamente de la base de datos.' : 'No se pudo eliminar la pieza.'
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

    public function getTipos(): array {
        return $this->modelo->listarTipos();
    }

    public function getFabricantes(): array {
        return $this->modelo->listarFabricantes();
    }

    private function validar(array $datos): array {
        $errores = [];

        if (empty($datos['codigo_pieza'])) {
            $errores[] = 'El código de la pieza es obligatorio.';
        }
        if (empty($datos['nombre_pieza'])) {
            $errores[] = 'El nombre o modelo de la pieza es obligatorio.';
        }
        if (empty($datos['id_tipo_pieza']) || (int)$datos['id_tipo_pieza'] <= 0) {
            $errores[] = 'Debe seleccionar un tipo de pieza válido.';
        }
        if (empty($datos['id_fabricante']) || (int)$datos['id_fabricante'] <= 0) {
            $errores[] = 'Debe seleccionar un fabricante válido.';
        }
        if (empty($datos['numero_serie'])) {
            $errores[] = 'El número de serie es obligatorio.';
        }

        return $errores;
    }
}
