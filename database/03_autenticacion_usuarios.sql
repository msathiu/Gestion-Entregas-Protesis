/* =====================================================================
   MIGRACIÓN: AUTENTICACIÓN Y ROLES DE USUARIO
   Asignatura: Bases de Datos - Ingeniería de Sistemas (ULA)
   ===================================================================== */

-- 1. Agregar columna de contraseña cifrada con algoritmo seguro (BCRYPT / Argon2)
ALTER TABLE usuario_sistema
ADD COLUMN IF NOT EXISTS clave_hash VARCHAR(255);

-- 2. Asegurar que los roles requeridos existan en la tabla 'rol'
INSERT INTO rol (nombre_rol, descripcion)
VALUES 
    ('ADMINISTRADOR',      'Acceso total al sistema y administración de usuarios'),
    ('TECNICO_ORTOPEDICO', 'Gestión de componentes, ajustes y mantenimientos preventivos'),
    ('EVALUADOR_CLINICO',  'Registro de pacientes, evaluaciones biomecánicas y medidas')
ON CONFLICT (nombre_rol) DO UPDATE 
SET descripcion = EXCLUDED.descripcion;

-- 3. Actualizar contraseña del usuario 'sistema' (id 1)
-- Clave demo: Sistema123*
UPDATE usuario_sistema 
SET clave_hash = '$2y$10$uA9WlGjKqA.bQzH2YnUf9.iX8Ww6Nlm3l1a9rK7C4M4N2W2h8uQW6'
WHERE nombre_usuario = 'sistema' AND (clave_hash IS NULL OR clave_hash = '');

-- 4. Actualizar contraseña del técnico ortopédico 'jperez' (id 2)
-- Clave demo: Tecnico123*
UPDATE usuario_sistema 
SET clave_hash = '$2y$10$CAZ5z9ysBpwbNlk65KW7jeVKZi8gnFzdy8pvtPtJdJVf1deyAdem6'
WHERE nombre_usuario = 'jperez';

-- 5. Crear usuario 'admin' (Rol: ADMINISTRADOR) si no existe
-- Clave demo: Admin123*
INSERT INTO usuario_sistema (id_rol, nombre_usuario, nombre_completo, correo, clave_hash, activo)
VALUES (
    (SELECT id_rol FROM rol WHERE nombre_rol = 'ADMINISTRADOR'),
    'admin',
    'Administrador del Sistema',
    'admin@protesis.local',
    '$2y$10$aY.EiLH4mtA3aBSodXAkK.S7gpll/I0eLZod9Ng1S7oknK7T4O3gO',
    TRUE
)
ON CONFLICT (nombre_usuario) DO UPDATE
SET clave_hash = EXCLUDED.clave_hash,
    id_rol = EXCLUDED.id_rol;

-- 6. Crear usuario 'mrodriguez' (Rol: EVALUADOR_CLINICO) si no existe
-- Clave demo: Clinico123*
INSERT INTO usuario_sistema (id_rol, nombre_usuario, nombre_completo, correo, clave_hash, activo)
VALUES (
    (SELECT id_rol FROM rol WHERE nombre_rol = 'EVALUADOR_CLINICO'),
    'mrodriguez',
    'Dra. Mariana Rodríguez',
    'mrodriguez@protesis.local',
    '$2y$10$FZKXGReVNHhmkfIZG5Ile.a4HgOciu9ZcBpo2teJgdReiPgimAK3S',
    TRUE
)
ON CONFLICT (nombre_usuario) DO UPDATE
SET clave_hash = EXCLUDED.clave_hash,
    id_rol = EXCLUDED.id_rol;
