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
            $port = getenv('DB_PORT') ?: '5432'; // Por defecto 5432 en docker/postgres
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
                self::$instance->exec("SET app.id_usuario_sistema = '1';");

                // Asegurar existencia de las tablas de registro y asignación de piezas
                self::asegurarTablasProtesis(self::$instance);

                // Asegurar usuarios base en usuario_sistema utilizando columnas existentes
                self::asegurarUsuariosBase(self::$instance);
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

    /**
     * Asegura la creación de las tablas de registro y asignación de piezas protésicas
     * Garantiza compatibilidad sin que fallen las consultas
     */
    private static function asegurarTablasProtesis(PDO $db): void {
        try {
            $existe = $db->query("SELECT to_regclass('public.pieza')")->fetchColumn();
            if ($existe !== null) {
                return;
            }

            $sql = <<<SQL
            -- Catálogo: Tipo de Pieza Protésica
            CREATE TABLE IF NOT EXISTS tipo_pieza (
                id_tipo_pieza           SERIAL PRIMARY KEY,
                nombre_tipo             VARCHAR(100) NOT NULL UNIQUE,
                descripcion             VARCHAR(250),
                fecha_creacion          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                fecha_actualizacion     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                usuario_creacion        INTEGER NOT NULL DEFAULT 1,
                usuario_actualizacion   INTEGER NOT NULL DEFAULT 1
            );

            INSERT INTO tipo_pieza (nombre_tipo, descripcion) VALUES
                ('Prótesis Transtibial', 'Prótesis modular para amputación bajo rodilla'),
                ('Prótesis Transfemoral', 'Prótesis con articulación sobre rodilla'),
                ('Prótesis Transradial', 'Prótesis de miembro superior bajo codo'),
                ('Pie Protésico Dinámico', 'Pie de fibra de carbono para almacenamiento y retorno de energía'),
                ('Articulación de Rodilla', 'Rodilla policéntrica hidráulica con amortiguación'),
                ('Socket Termoplástico', 'Encaje anatómico a medida para el muñón')
            ON CONFLICT (nombre_tipo) DO NOTHING;

            -- Asegurar fabricante si la tabla existe
            DO $$
            BEGIN
                IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'fabricante') THEN
                    INSERT INTO fabricante (nombre_fabricante, pais_origen) VALUES
                        ('Ottobock', 'Alemania'),
                        ('Össur', 'Islandia'),
                        ('Blatchford', 'Reino Unido')
                    ON CONFLICT (nombre_fabricante) DO NOTHING;
                END IF;
            END $$;

            -- Tabla 1: Registro de Piezas Protésicas
            CREATE TABLE IF NOT EXISTS pieza (
                id_pieza                SERIAL PRIMARY KEY,
                codigo_pieza            VARCHAR(50) NOT NULL UNIQUE,
                nombre_pieza            VARCHAR(150) NOT NULL,
                id_tipo_pieza           INTEGER NOT NULL REFERENCES tipo_pieza(id_tipo_pieza) ON DELETE RESTRICT,
                id_fabricante           INTEGER NOT NULL REFERENCES fabricante(id_fabricante) ON DELETE RESTRICT,
                numero_serie            VARCHAR(60) NOT NULL UNIQUE,
                estado                  VARCHAR(30) NOT NULL DEFAULT 'DISPONIBLE'
                                        CHECK (estado IN ('DISPONIBLE', 'ENTREGADA', 'EN_MANTENIMIENTO', 'DE_BAJA')),
                costo                   NUMERIC(10,2) NOT NULL DEFAULT 0 CHECK (costo >= 0),
                descripcion             TEXT,
                fecha_registro          DATE NOT NULL DEFAULT CURRENT_DATE,
                fecha_creacion          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                fecha_actualizacion     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                usuario_creacion        INTEGER NOT NULL DEFAULT 1,
                usuario_actualizacion   INTEGER NOT NULL DEFAULT 1
            );

            INSERT INTO pieza (codigo_pieza, nombre_pieza, id_tipo_pieza, id_fabricante, numero_serie, estado, costo, descripcion, fecha_registro)
            VALUES 
                ('PZ-OTT-001', 'Pie Dinámico Taleo en Fibra de Carbono', 
                 (SELECT id_tipo_pieza FROM tipo_pieza WHERE nombre_tipo = 'Pie Protésico Dinámico' LIMIT 1),
                 (SELECT id_fabricante FROM fabricante WHERE nombre_fabricante = 'Ottobock' LIMIT 1),
                 'PIE-TAL-0001', 'ENTREGADA', 1250.00, 'Pie dinámico de alto impacto con retorno elástico', CURRENT_DATE - 15),
                ('PZ-OSS-002', 'Rodilla Monocéntrica Hidráulica', 
                 (SELECT id_tipo_pieza FROM tipo_pieza WHERE nombre_tipo = 'Articulación de Rodilla' LIMIT 1),
                 (SELECT id_fabricante FROM fabricante WHERE nombre_fabricante = 'Össur' LIMIT 1),
                 'ROD-OSS-0002', 'DISPONIBLE', 3400.00, 'Articulación con control de fase de balanceo', CURRENT_DATE - 30),
                ('PZ-BLA-003', 'Socket Transtibial Confort', 
                 (SELECT id_tipo_pieza FROM tipo_pieza WHERE nombre_tipo = 'Socket Termoplástico' LIMIT 1),
                 (SELECT id_fabricante FROM fabricante WHERE nombre_fabricante = 'Blatchford' LIMIT 1),
                 'SCK-BLA-0003', 'DISPONIBLE', 890.00, 'Encaje anatómico con sistema de suspensión', CURRENT_DATE - 10)
            ON CONFLICT (numero_serie) DO NOTHING;

            -- Tabla 2: Asignación y Entrega de Piezas Protésicas
            CREATE TABLE IF NOT EXISTS entrega_pieza (
                id_entrega              SERIAL PRIMARY KEY,
                id_paciente             INTEGER NOT NULL REFERENCES paciente(id_paciente) ON DELETE RESTRICT,
                id_pieza                INTEGER NOT NULL REFERENCES pieza(id_pieza) ON DELETE RESTRICT,
                id_usuario_sistema      INTEGER NOT NULL REFERENCES usuario_sistema(id_usuario_sistema) ON DELETE RESTRICT,
                fecha_entrega           DATE NOT NULL DEFAULT CURRENT_DATE,
                estado_entrega          VARCHAR(30) NOT NULL DEFAULT 'ENTREGADO'
                                        CHECK (estado_entrega IN ('ENTREGADO', 'EN_PRUEBAS', 'DEVUELTO', 'DADO_DE_BAJA')),
                observaciones           TEXT,
                fecha_registro          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                fecha_creacion          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                fecha_actualizacion     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                usuario_creacion        INTEGER NOT NULL DEFAULT 1,
                usuario_actualizacion   INTEGER NOT NULL DEFAULT 1
            );

            INSERT INTO entrega_pieza (id_paciente, id_pieza, id_usuario_sistema, fecha_entrega, estado_entrega, observaciones)
            VALUES (
                (SELECT id_paciente FROM paciente ORDER BY id_paciente ASC LIMIT 1),
                (SELECT id_pieza FROM pieza WHERE numero_serie = 'PIE-TAL-0001' LIMIT 1),
                (SELECT id_usuario_sistema FROM usuario_sistema WHERE nombre_usuario = 'jperez' LIMIT 1),
                CURRENT_DATE - 8,
                'ENTREGADO',
                'Asignación y entrega inicial verificada en taller ortopédico.'
            )
            ON CONFLICT DO NOTHING;
SQL;
            $db->exec($sql);
        } catch (Throwable $e) {
            error_log("Aviso en asegurarTablasProtesis: " . $e->getMessage());
        }
    }

    /**
     * Asegura la presencia de los usuarios base (admin, mrodriguez) sin alterar
     * ni agregar columnas a la tabla usuario_sistema
     */
    private static function asegurarUsuariosBase(PDO $db): void {
        try {
            // Asegurar roles base
            $db->exec("
                INSERT INTO rol (nombre_rol, descripcion) VALUES 
                    ('ADMINISTRADOR',      'Acceso total al sistema y administración de usuarios'),
                    ('TECNICO_ORTOPEDICO', 'Gestión de piezas protésicas y entregas'),
                    ('EVALUADOR_CLINICO',  'Evaluaciones biomecánicas y registro de pacientes')
                ON CONFLICT (nombre_rol) DO NOTHING;

                -- Asegurar usuario administrador usando solo columnas existentes
                INSERT INTO usuario_sistema (id_rol, nombre_usuario, nombre_completo, correo, activo)
                SELECT (SELECT id_rol FROM rol WHERE nombre_rol = 'ADMINISTRADOR' LIMIT 1),
                       'admin', 'Administrador del Sistema', 'admin@protesis.local', TRUE
                WHERE NOT EXISTS (SELECT 1 FROM usuario_sistema WHERE nombre_usuario = 'admin');

                -- Asegurar usuario evaluador clínico usando solo columnas existentes
                INSERT INTO usuario_sistema (id_rol, nombre_usuario, nombre_completo, correo, activo)
                SELECT (SELECT id_rol FROM rol WHERE nombre_rol = 'EVALUADOR_CLINICO' LIMIT 1),
                       'mrodriguez', 'Dra. Mariana Rodríguez', 'mrodriguez@protesis.local', TRUE
                WHERE NOT EXISTS (SELECT 1 FROM usuario_sistema WHERE nombre_usuario = 'mrodriguez');
            ");
        } catch (Throwable $e) {
            error_log("Aviso en asegurarUsuariosBase: " . $e->getMessage());
        }
    }
} // <-- Fin de la clase Database

/**
 * Función auxiliar para obtener la conexión PDO rápidamente
 */
function get_db(): PDO {
    return Database::getConnection();
}