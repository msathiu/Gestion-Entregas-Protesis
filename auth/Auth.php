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
    /**
     * Caché en memoria para verificar la existencia de la columna clave_hash en PostgreSQL
     */
    private static ?bool $columnaClaveHashExiste = null;

    /**
     * Diccionario de hashes bcrypt predeterminados para los roles del proyecto
     * Permite validar contraseñas de forma segura con password_verify()
     * sin depender de modificaciones al esquema físico de la tabla usuario_sistema
     */
    private static array $hashesPorDefecto = [
        'admin'      => '$2y$10$aY.EiLH4mtA3aBSodXAkK.S7gpll/I0eLZod9Ng1S7oknK7T4O3gO', // Admin123*
        'jperez'     => '$2y$10$CAZ5z9ysBpwbNlk65KW7jeVKZi8gnFzdy8pvtPtJdJVf1deyAdem6', // Tecnico123*
        'mrodriguez' => '$2y$10$FZKXGReVNHhmkfIZG5Ile.a4HgOciu9ZcBpo2teJgdReiPgimAK3S', // Clinico123*
        'sistema'    => '$2y$10$uA9WlGjKqA.bQzH2YnUf9.iX8Ww6Nlm3l1a9rK7C4M4N2W2h8uQW6', // Sistema123*
    ];

    /**
     * Comprueba si la columna clave_hash existe físicamente en usuario_sistema
     */
    public static function tieneColumnaClaveHash(PDO $db): bool {
        if (self::$columnaClaveHashExiste === null) {
            try {
                $stmt = $db->query("
                    SELECT 1 
                    FROM information_schema.columns 
                    WHERE table_name = 'usuario_sistema' 
                      AND column_name = 'clave_hash'
                    LIMIT 1
                ");
                self::$columnaClaveHashExiste = ($stmt->fetchColumn() !== false);
            } catch (Throwable $t) {
                self::$columnaClaveHashExiste = false;
            }
        }
        return self::$columnaClaveHashExiste;
    }

    /**
     * Obtiene el hash bcrypt esperado para un usuario dado
     */
    public static function obtenerHashUsuario(string $nombreUsuario, ?string $hashDb = null): ?string {
        if (!empty($hashDb)) {
            return $hashDb;
        }
        $usuarioLower = strtolower(trim($nombreUsuario));
        if (isset(self::$hashesPorDefecto[$usuarioLower])) {
            return self::$hashesPorDefecto[$usuarioLower];
        }
        // Soporte para usuarios creados dinámicamente si no existe columna física
        $archivoHashes = __DIR__ . '/../config/.user_hashes.json';
        if (file_exists($archivoHashes)) {
            $dinamicos = json_decode(@file_get_contents($archivoHashes), true) ?: [];
            if (!empty($dinamicos[$usuarioLower])) {
                return $dinamicos[$usuarioLower];
            }
        }
        return null;
    }

    /**
     * Almacena el hash bcrypt de un usuario cuando no existe columna en la base de datos
     */
    public static function guardarHashUsuario(string $nombreUsuario, string $hash): void {
        $usuarioLower = strtolower(trim($nombreUsuario));
        self::$hashesPorDefecto[$usuarioLower] = $hash;
        $archivoHashes = __DIR__ . '/../config/.user_hashes.json';
        $dinamicos = [];
        if (file_exists($archivoHashes)) {
            $dinamicos = json_decode(@file_get_contents($archivoHashes), true) ?: [];
        }
        $dinamicos[$usuarioLower] = $hash;
        @file_put_contents($archivoHashes, json_encode($dinamicos, JSON_PRETTY_PRINT));
    }

    private static function iniciarSesionPhp(): void {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
    }

    /**
     * Autentica un usuario verificando credenciales contra PostgreSQL
     * Consulta exclusivamente las columnas existentes en el esquema real
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
            $incluirClaveHash = self::tieneColumnaClaveHash($db);

            // Consulta adaptada: incluye u.clave_hash SOLO si la columna existe en PostgreSQL
            $columnaHash = $incluirClaveHash ? ', u.clave_hash' : '';
            $sql = "SELECT u.id_usuario_sistema, u.id_rol, u.nombre_usuario, u.nombre_completo,
                           u.correo{$columnaHash}, u.activo,
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

            // Validación de contraseña con mecanismo estándar password_verify()
            $hashEsperado = self::obtenerHashUsuario($usuario['nombre_usuario'], $usuario['clave_hash'] ?? null);

            if (empty($hashEsperado) || !password_verify($clave, $hashEsperado)) {
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
