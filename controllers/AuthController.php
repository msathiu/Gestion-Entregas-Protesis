<?php
/**
 * Controlador: AuthController
 * Asignatura: Bases de Datos - Ingeniería de Sistemas (ULA)
 * Responsabilidad: Gestión de inicio/cierre de sesión y verificación de permisos del usuario activo
 */

require_once __DIR__ . '/../auth/Auth.php';
require_once __DIR__ . '/../auth/Permisos.php';
require_once __DIR__ . '/../includes/funciones.php';

class AuthController {
    /**
     * Inicia sesión validando credenciales
     */
    public function login(string $usuario, string $clave): array {
        return Auth::login($usuario, $clave);
    }

    /**
     * Cierra la sesión activa
     */
    public function logout(): array {
        Auth::logout();
        return [
            'success' => true,
            'mensaje' => 'Sesión finalizada correctamente.'
        ];
    }

    /**
     * Retorna el perfil del usuario autenticado en la sesión
     */
    public function perfil(): array {
        if (!Auth::estaAutenticado()) {
            return [
                'success' => false,
                'codigo'  => 401,
                'mensaje' => 'No hay una sesión activa.'
            ];
        }

        $usuario = Auth::usuarioActual();
        return [
            'success' => true,
            'usuario' => $usuario,
            'rol'     => $usuario['nombre_rol'],
        ];
    }

    /**
     * Comprueba si el usuario autenticado tiene permiso para una acción
     */
    public function comprobarPermiso(string $modulo, string $accion): array {
        $rol = Auth::rolActual();
        $permitido = Permisos::puede($rol, $modulo, $accion);

        return [
            'success'   => true,
            'rol'       => $rol,
            'modulo'    => $modulo,
            'accion'    => $accion,
            'permitido' => $permitido
        ];
    }
}
