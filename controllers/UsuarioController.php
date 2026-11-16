<?php
/**
 * Controlador: UsuarioController

 * Responsabilidad: Gestión administrativa de usuarios y asignación de roles
 */

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../auth/Auth.php';
require_once __DIR__ . '/../auth/Permisos.php';
require_once __DIR__ . '/../includes/funciones.php';

class UsuarioController {
    private Usuario $modelo;

    public function __construct() {
        $this->modelo = new Usuario();
    }

    /**
     * Lista usuarios (exclusivo para ADMINISTRADOR)
     */
    public function index(?bool $soloActivos = null): array {
        $errorPermiso = Auth::validarPermiso('usuarios', 'listar');
        if ($errorPermiso) {
            return $errorPermiso;
        }

        try {
            $usuarios = $this->modelo->listar($soloActivos);
            return [
                'success' => true,
                'total'   => count($usuarios),
                'data'    => $usuarios
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
     * Muestra la información de un usuario específico
     */
    public function show(int $id): array {
        $errorPermiso = Auth::validarPermiso('usuarios', 'ver');
        if ($errorPermiso) {
            return $errorPermiso;
        }

        try {
            $usuario = $this->modelo->obtenerPorId($id);
            if (!$usuario) {
                return [
                    'success' => false,
                    'mensaje' => "El usuario con ID {$id} no fue encontrado."
                ];
            }

            return [
                'success' => true,
                'data'    => $usuario
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
     * Crea un nuevo usuario con rol asignado y contraseña cifrada
     */
    public function store(array $datos): array {
        $errorPermiso = Auth::validarPermiso('usuarios', 'crear');
        if ($errorPermiso) {
            return $errorPermiso;
        }

        $requeridos = ['id_rol', 'nombre_usuario', 'nombre_completo', 'correo', 'clave'];
        $faltantes = validar_campos_requeridos($datos, $requeridos);

        if (!empty($faltantes)) {
            return [
                'success'   => false,
                'mensaje'   => 'Campos obligatorios incompletos para registrar el usuario.',
                'faltantes' => $faltantes
            ];
        }

        try {
            $id = $this->modelo->crear($datos);
            return [
                'success' => true,
                'mensaje' => 'Usuario registrado exitosamente con contraseña cifrada.',
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
     * Actualiza un usuario existente
     */
    public function update(int $id, array $datos): array {
        $errorPermiso = Auth::validarPermiso('usuarios', 'editar');
        if ($errorPermiso) {
            return $errorPermiso;
        }

        $existente = $this->modelo->obtenerPorId($id);
        if (!$existente) {
            return [
                'success' => false,
                'mensaje' => "El usuario con ID {$id} no existe."
            ];
        }

        $requeridos = ['id_rol', 'nombre_completo', 'correo'];
        $faltantes = validar_campos_requeridos($datos, $requeridos);

        if (!empty($faltantes)) {
            return [
                'success'   => false,
                'mensaje'   => 'Campos obligatorios incompletos para actualizar.',
                'faltantes' => $faltantes
            ];
        }

        try {
            $actualizado = $this->modelo->actualizar($id, $datos);
            return [
                'success' => $actualizado,
                'mensaje' => $actualizado ? 'Usuario actualizado exitosamente.' : 'No se realizaron modificaciones.'
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
     * Elimina un usuario verificando integridad referencial
     */
    public function destroy(int $id): array {
        $errorPermiso = Auth::validarPermiso('usuarios', 'eliminar');
        if ($errorPermiso) {
            return $errorPermiso;
        }

        try {
            $eliminado = $this->modelo->eliminar($id);
            return [
                'success' => $eliminado,
                'mensaje' => $eliminado ? 'Usuario eliminado correctamente.' : 'No se pudo eliminar el registro.'
            ];
        } catch (PDOException $e) {
            return [
                'success'  => false,
                'mensaje'  => traducir_error_sqlstate($e),
                'sqlstate' => $e->getCode(),
                'error'    => $e->getMessage()
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'mensaje' => $e->getMessage()
            ];
        }
    }

    /**
     * Lista los roles del sistema
     */
    public function roles(): array {
        try {
            $roles = $this->modelo->listarRoles();
            return [
                'success' => true,
                'data'    => $roles
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
