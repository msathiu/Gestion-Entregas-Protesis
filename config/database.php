<?php
/**
 * Configuración y Conexión a la Base de Datos PostgreSQL
 * Patrón: Singleton con PDO
 */

class Database {
    private static ?PDO $instance = null;

    /**
     * Obtiene la instancia única de conexión PDO a PostgreSQL
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $host = getenv('DB_HOST') ?: 'db';
            $port = getenv('DB_PORT') ?: '5433';
            $name = getenv('DB_NAME') ?: 'protesis_ortesis';
            $user = getenv('DB_USER') ?: 'postgres';
            $pass = getenv('DB_PASSWORD') ?: 'postgres_secure_password';

            $dsn = "pgsql:host={$host};port={$port};dbname={$name};";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);

                // Configurar usuario de aplicación por defecto para auditoría en PostgreSQL
                // (Requerido por los triggers de bitácora y auditoría del esquema)
                self::$instance->exec("SET app.id_usuario_sistema = '1';");
            } catch (PDOException $e) {
                throw new Exception("Error al conectar con la base de datos PostgreSQL: " . $e->getMessage(), (int)$e->getCode());
            }
        }

        return self::$instance;
    }

    /**
     * Establece el ID de usuario activo para los triggers de auditoría
     */
    public static function setUsuarioAuditoria(int $idUsuario): void {
        $db = self::getConnection();
        $db->exec("SET app.id_usuario_sistema = '{$idUsuario}';");
    }

    /**
     * Prueba de conectividad simple
     */
    public static function testConnection(): array {
        try {
            $db = self::getConnection();
            $version = $db->query("SELECT version()")->fetchColumn();
            return [
                'success' => true,
                'message' => 'Conexión exitosa a PostgreSQL',
                'version' => $version
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}

/**
 * Función auxiliar para obtener la conexión PDO rápidamente
 */
function get_db(): PDO {
    return Database::getConnection();
}
