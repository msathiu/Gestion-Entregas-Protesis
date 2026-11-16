<?php
/**
 * Controlador: ComponenteController
 * Asignatura: Bases de Datos - Ingeniería de Sistemas (ULA)
 * Responsabilidad: Coordinar validaciones, permisos y operaciones CRUD sobre componentes y piezas
 */

require_once __DIR__ . '/../models/Componente.php';
require_once __DIR__ . '/../auth/Auth.php';
require_once __DIR__ . '/../auth/Permisos.php';
require_once __DIR__ . '/../includes/funciones.php';

class ComponenteController {
    private Componente $modelo;

    public function __construct() {
        $this->modelo = new Componente();
    }

    /**
     * Lista componentes protésicos
     */
    public function index(?int $idEstado = null): array {
        $errorPermiso = Auth::validarPermiso('componentes', 'listar');
        if ($errorPermiso) return $errorPermiso;

        try {
            $componentes = $this->modelo->listar($idEstado);
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
     * Muestra la información de un componente específico
     */
    public function show(int $id): array {
        $errorPermiso = Auth::validarPermiso('componentes', 'ver');
        if ($errorPermiso) return $errorPermiso;

        try {
            $componente = $this->modelo->obtenerPorId($id);
            if (!$componente) {
                return [
                    'success' => false,
                    'mensaje' => "El componente con ID {$id} no fue encontrado."
                ];
            }

            return [
                'success' => true,
                'data'    => $componente
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
     * Valida y crea un nuevo componente físico
     */
    public function store(array $datos): array {
        $errorPermiso = Auth::validarPermiso('componentes', 'crear');
        if ($errorPermiso) return $errorPermiso;

        $requeridos = ['id_modelo_componente', 'id_estado_componente', 'numero_serie'];
        $faltantes = validar_campos_requeridos($datos, $requeridos);

        if (!empty($faltantes)) {
            return [
                'success'   => false,
                'mensaje'   => 'Campos obligatorios incompletos para registrar el componente.',
                'faltantes' => $faltantes
            ];
        }

        try {
            $id = $this->modelo->crear($datos);
            return [
                'success' => true,
                'mensaje' => 'Componente registrado exitosamente.',
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
     * Valida y actualiza los datos de un componente
     */
    public function update(int $id, array $datos): array {
        $errorPermiso = Auth::validarPermiso('componentes', 'editar');
        if ($errorPermiso) return $errorPermiso;

        $existente = $this->modelo->obtenerPorId($id);
        if (!$existente) {
            return [
                'success' => false,
                'mensaje' => "No se puede actualizar: componente con ID {$id} no existe."
            ];
        }

        $requeridos = ['id_modelo_componente', 'id_estado_componente', 'numero_serie', 'fecha_adquisicion', 'horas_desgaste_acumuladas'];
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
                'mensaje' => $actualizado ? 'Componente actualizado exitosamente.' : 'No se realizaron cambios.'
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
     * Elimina un componente respetando restricciones de integridad referencial
     */
    public function destroy(int $id): array {
        $errorPermiso = Auth::validarPermiso('componentes', 'eliminar');
        if ($errorPermiso) return $errorPermiso;

        $existente = $this->modelo->obtenerPorId($id);
        if (!$existente) {
            return [
                'success' => false,
                'mensaje' => "No se puede eliminar: el componente con ID {$id} no existe."
            ];
        }

        try {
            $eliminado = $this->modelo->eliminar($id);
            return [
                'success' => $eliminado,
                'mensaje' => $eliminado ? 'Componente eliminado correctamente.' : 'No se pudo eliminar el registro.'
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
     * Asocia e instala un componente en un dispositivo protésico
     */
    public function instalar(int $idComponente, int $idDispositivo, ?string $fechaInstalacion = null): array {
        $errorPermiso = Auth::validarPermiso('componentes', 'instalar');
        if ($errorPermiso) return $errorPermiso;

        try {
            $idDC = $this->modelo->instalarEnDispositivo($idComponente, $idDispositivo, $fechaInstalacion);
            return [
                'success' => true,
                'mensaje' => 'Componente instalado exitosamente en el dispositivo.',
                'id_dispositivo_componente' => $idDC
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
     * Consulta piezas y modelos compatibles
     */
    public function compatibles(int $idModelo): array {
        $errorPermiso = Auth::validarPermiso('componentes', 'compatibles');
        if ($errorPermiso) return $errorPermiso;

        try {
            $compatibles = $this->modelo->obtenerCompatibles($idModelo);
            return [
                'success' => true,
                'total'   => count($compatibles),
                'data'    => $compatibles
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
