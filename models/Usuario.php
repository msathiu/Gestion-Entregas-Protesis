<?php
/**
 * Modelo: Usuario del Sistema
 * Tabla: usuario_sistema
 * Asignatura: Bases de Datos - Ingeniería de Sistemas (ULA)
 */

require_once __DIR__ . '/../config/database.php';

class Usuario {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Lista los usuarios del sistema junto a su rol
     * Demuestra: SELECT con INNER JOIN a la tabla 'rol'
     */
    public function listar(?bool $soloActivos = null): array {
        $sql = "SELECT u.id_usuario_sistema, u.id_rol, u.nombre_usuario, u.nombre_completo,
                       u.correo, u.activo, u.fecha_creacion, u.fecha_actualizacion,
                       r.nombre_rol, r.descripcion AS descripcion_rol
                FROM usuario_sistema u
                INNER JOIN rol r ON r.id_rol = u.id_rol";

        $params = [];
        if ($soloActivos !== null) {
            $sql .= " WHERE u.activo = :activo";
            $params[':activo'] = $soloActivos;
        }

        $sql .= " ORDER BY u.id_usuario_sistema ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene los datos de un usuario por su ID
     */
    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT u.id_usuario_sistema, u.id_rol, u.nombre_usuario, u.nombre_completo,
                       u.correo, u.activo, u.fecha_creacion, u.fecha_actualizacion,
                       r.nombre_rol, r.descripcion AS descripcion_rol
                FROM usuario_sistema u
                INNER JOIN rol r ON r.id_rol = u.id_rol
                WHERE u.id_usuario_sistema = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $res = $stmt->fetch();

        return $res ?: null;
    }

    /**
     * Obtiene un usuario con su hash de contraseña para autenticación
     */
    public function obtenerPorNombreUsuario(string $nombreUsuario): ?array {
        $sql = "SELECT u.id_usuario_sistema, u.id_rol, u.nombre_usuario, u.nombre_completo,
                       u.correo, u.clave_hash, u.activo,
                       r.nombre_rol, r.descripcion AS descripcion_rol
                FROM usuario_sistema u
                INNER JOIN rol r ON r.id_rol = u.id_rol
                WHERE u.nombre_usuario = :usuario";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':usuario' => trim($nombreUsuario)]);
        $res = $stmt->fetch();

        return $res ?: null;
    }

    /**
     * Registra un nuevo usuario con contraseña cifrada (password_hash)
     */
    public function crear(array $datos): int {
        $claveHash = password_hash($datos['clave'], PASSWORD_DEFAULT);

        $sql = "INSERT INTO usuario_sistema (
                    id_rol, nombre_usuario, nombre_completo, correo, clave_hash, activo
                ) VALUES (
                    :id_rol, :nombre_usuario, :nombre_completo, :correo, :clave_hash, :activo
                ) RETURNING id_usuario_sistema";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_rol'          => $datos['id_rol'],
            ':nombre_usuario'  => strtolower(trim($datos['nombre_usuario'])),
            ':nombre_completo' => trim($datos['nombre_completo']),
            ':correo'          => strtolower(trim($datos['correo'])),
            ':clave_hash'      => $claveHash,
            ':activo'          => $datos['activo'] ?? true
        ]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Actualiza la información de un usuario
     */
    public function actualizar(int $id, array $datos): bool {
        $campos = [
            'id_rol'          => $datos['id_rol'],
            'nombre_completo' => trim($datos['nombre_completo']),
            'correo'          => strtolower(trim($datos['correo'])),
            'activo'          => $datos['activo'] ?? true,
        ];

        $sql = "UPDATE usuario_sistema SET
                    id_rol          = :id_rol,
                    nombre_completo = :nombre_completo,
                    correo          = :correo,
                    activo          = :activo";

        $params = [
            ':id_rol'          => $campos['id_rol'],
            ':nombre_completo' => $campos['nombre_completo'],
            ':correo'          => $campos['correo'],
            ':activo'          => $campos['activo'],
            ':id'              => $id
        ];

        // Si se suministra una nueva contraseña, se recalcula el hash
        if (!empty($datos['clave'])) {
            $sql .= ", clave_hash = :clave_hash";
            $params[':clave_hash'] = password_hash($datos['clave'], PASSWORD_DEFAULT);
        }

        $sql .= " WHERE id_usuario_sistema = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Elimina un usuario (o lo desactiva si tiene dependencias en bitácora)
     */
    public function eliminar(int $id): bool {
        // Protección para usuario sistema básico
        if ($id === 1) {
            throw new Exception("El usuario base del sistema no puede ser eliminado.");
        }

        $sql = "DELETE FROM usuario_sistema WHERE id_usuario_sistema = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Consulta el catálogo de roles disponibles
     */
    public function listarRoles(): array {
        $sql = "SELECT id_rol, nombre_rol, descripcion FROM rol ORDER BY id_rol ASC";
        return $this->db->query($sql)->fetchAll();
    }
}
