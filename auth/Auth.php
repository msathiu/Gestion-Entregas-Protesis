<?php
/**
 * Servicio de Autenticación y Manejo de Sesión
 *
 * Emplea funciones nativas de PHP:
 * - password_hash() / password_verify()
 * - Manejo de sesión nativo PHP
 * - Configuración del usuario en PostgreSQL para auditoría
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Permisos.php';

class Auth {
    private static function iniciarSesionPhp(): void {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
    }

    /**
     * Autentica un usuario verificando credenciales contra PostgreSQL
     */
    public static function login(string $nombreUsuario, string $clave): array {
        self::iniciarSesionPhp();

        $nombreUsuario = trim($nombreUsuario);
        if ($nombreUsuario === '' || $clave === '') {
            return [
                'success' => false,
                'mensaje' => 'Debe ingresar el nombre de usuario y la contraseña.'
            ];
        }

        try {
            $db = Database::getConnection();
            $sql = "SELECT u.id_usuario_sistema, u.id_rol, u.nombre_usuario, u.nombre_completo,
                           u.correo, u.clave_hash, u.activo,
                           r.nombre_rol, r.descripcion AS descripcion_rol
                    FROM usuario_sistema u
                    INNER JOIN rol r ON r.id_rol = u.id_rol
                    WHERE u.nombre_usuario = :usuario";

            $stmt = $db->prepare($sql);
            $stmt->execute([':usuario' => $nombreUsuario]);
            $usuario = $stmt->fetch();

            if (!$usuario) {
                return [
                    'success' => false,
                    'mensaje' => 'Credenciales inválidas: el usuario no existe.'
                ];
            }

            if (!$usuario['activo']) {
                return [
                    'success' => false,
                    'mensaje' => 'La cuenta de usuario se encuentra inactiva. Contacte al Administrador.'
                ];
            }

            if (empty($usuario['clave_hash']) || !password_verify($clave, $usuario['clave_hash'])) {
                return [
                    'success' => false,
                    'mensaje' => 'Credenciales inválidas: contraseña incorrecta.'
                ];
            }

            // Datos de sesión (sin exponer el hash de la clave)
            $datosSesion = [
                'id_usuario_sistema' => (int)$usuario['id_usuario_sistema'],
                'id_rol'             => (int)$usuario['id_rol'],
                'nombre_usuario'     => $usuario['nombre_usuario'],
                'nombre_completo'    => $usuario['nombre_completo'],
                'correo'             => $usuario['correo'],
                'nombre_rol'         => $usuario['nombre_rol'],
                'descripcion_rol'    => $usuario['descripcion_rol']
            ];

            $_SESSION['usuario'] = $datosSesion;

            // Sincronizar usuario activo con PostgreSQL para triggers de auditoría
            Database::setUsuarioAuditoria($datosSesion['id_usuario_sistema']);

            return [
                'success' => true,
                'mensaje' => "Bienvenido {$usuario['nombre_completo']}.",
                'usuario' => $datosSesion
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'mensaje' => 'Error durante el proceso de autenticación: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cierra la sesión activa
     */
    public static function logout(): void {
        self::iniciarSesionPhp();
        unset($_SESSION['usuario']);
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    /**
     * Retorna los datos del usuario autenticado en la sesión actual
     */
    public static function usuarioActual(): ?array {
        self::iniciarSesionPhp();
        return $_SESSION['usuario'] ?? null;
    }

    /**
     * Retorna el rol del usuario autenticado o 'INVITADO' si no hay sesión
     */
    public static function rolActual(): string {
        $usuario = self::usuarioActual();
        return $usuario['nombre_rol'] ?? 'INVITADO';
    }

    /**
     * Verifica si hay una sesión activa
     */
    public static function estaAutenticado(): bool {
        return self::usuarioActual() !== null;
    }

    /**
     * Inicia sesión programática para pruebas o ejecución CLI
     */
    public static function iniciarSesionComo(string $nombreUsuario): bool {
        self::iniciarSesionPhp();
        try {
            $db = Database::getConnection();
            $sql = "SELECT u.id_usuario_sistema, u.id_rol, u.nombre_usuario, u.nombre_completo,
                           u.correo, u.activo, r.nombre_rol, r.descripcion AS descripcion_rol
                    FROM usuario_sistema u
                    INNER JOIN rol r ON r.id_rol = u.id_rol
                    WHERE u.nombre_usuario = :usuario AND u.activo = TRUE";

            $stmt = $db->prepare($sql);
            $stmt->execute([':usuario' => $nombreUsuario]);
            $usuario = $stmt->fetch();

            if ($usuario) {
                $_SESSION['usuario'] = [
                    'id_usuario_sistema' => (int)$usuario['id_usuario_sistema'],
                    'id_rol'             => (int)$usuario['id_rol'],
                    'nombre_usuario'     => $usuario['nombre_usuario'],
                    'nombre_completo'    => $usuario['nombre_completo'],
                    'correo'             => $usuario['correo'],
                    'nombre_rol'         => $usuario['nombre_rol'],
                    'descripcion_rol'    => $usuario['descripcion_rol']
                ];
                Database::setUsuarioAuditoria($usuario['id_usuario_sistema']);
                return true;
            }
            return false;
        } catch (Throwable $t) {
            return false;
        }
    }

    /**
     * Valida permisos para el usuario autenticado actual y devuelve array estructurado de error si no procede
     */
    public static function validarPermiso(string $modulo, string $accion): ?array {
        if (!self::estaAutenticado()) {
            return [
                'success' => false,
                'codigo'  => 401,
                'mensaje' => "No autenticado: Se requiere iniciar sesión para realizar la acción '{$accion}' en el módulo '{$modulo}'."
            ];
        }

        $rol = self::rolActual();
        if (!Permisos::puede($rol, $modulo, $accion)) {
            return [
                'success' => false,
                'codigo'  => 403,
                'mensaje' => "Acceso denegado: el rol '{$rol}' no tiene autorización para realizar la acción '{$accion}' en el módulo '{$modulo}'."
            ];
        }
        return null;
    }
}
