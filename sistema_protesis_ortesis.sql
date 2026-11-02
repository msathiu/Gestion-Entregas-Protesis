-- ============================================================================
-- SISTEMA DE GESTIÓN DE ENTREGAS DE PRÓTESIS
-- Base de Datos PostgreSQL 15 - Primera Entrega Académica (3 Tablas Normalizadas)
-- ============================================================================

-- Limpieza previa para reinicialización segura
DROP TABLE IF EXISTS entrega_protesis CASCADE;
DROP TABLE IF EXISTS tipo_protesis CASCADE;
DROP TABLE IF EXISTS paciente CASCADE;

-- ----------------------------------------------------------------------------
-- TABLA 1: PACIENTE
-- Almacena la información de los pacientes receptores de dispositivos protésicos.
-- ----------------------------------------------------------------------------
CREATE TABLE paciente (
    id_paciente SERIAL PRIMARY KEY,
    numero_documento VARCHAR(20) NOT NULL UNIQUE,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    telefono VARCHAR(30),
    correo VARCHAR(100),
    fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ----------------------------------------------------------------------------
-- TABLA 2: TIPO_PROTESIS
-- Catálogo de tipos de prótesis disponibles según segmento anatómico y tecnología.
-- ----------------------------------------------------------------------------
CREATE TABLE tipo_protesis (
    id_tipo_protesis SERIAL PRIMARY KEY,
    nombre_tipo VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT
);

-- ----------------------------------------------------------------------------
-- TABLA 3: ENTREGA_PROTESIS (TABLA CENTRAL DEL CRUD)
-- Registra cada entrega de prótesis realizada a un paciente, garantizando
-- la integridad referencial con las tablas paciente y tipo_protesis.
-- ----------------------------------------------------------------------------
CREATE TABLE entrega_protesis (
    id_entrega SERIAL PRIMARY KEY,
    codigo_protesis VARCHAR(50) NOT NULL UNIQUE,
    id_paciente INT NOT NULL REFERENCES paciente(id_paciente) ON DELETE RESTRICT,
    id_tipo_protesis INT NOT NULL REFERENCES tipo_protesis(id_tipo_protesis) ON DELETE RESTRICT,
    fecha_entrega DATE NOT NULL,
    estado VARCHAR(30) NOT NULL DEFAULT 'ENTREGADO' 
        CHECK (estado IN ('EN_FABRICACION', 'EN_PRUEBAS', 'ENTREGADO', 'DADO_DE_BAJA')),
    observaciones TEXT,
    fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Índices para optimizar las búsquedas y ordenamiento del CRUD
CREATE INDEX idx_entrega_paciente ON entrega_protesis(id_paciente);
CREATE INDEX idx_entrega_estado ON entrega_protesis(estado);
CREATE INDEX idx_entrega_fecha ON entrega_protesis(fecha_entrega);

-- ============================================================================
-- DATOS INICIALES DE DEMOSTRACIÓN (SEMILLA ACADÉMICA)
-- ============================================================================

-- Catálogo de tipos de prótesis
INSERT INTO tipo_protesis (nombre_tipo, descripcion) VALUES
('Prótesis Transtibial (Bajo Rodilla)', 'Prótesis modular para amputación por debajo de la rodilla con socket termoplástico y pie dinámico.'),
('Prótesis Transfemoral (Sobre Rodilla)', 'Prótesis con articulación de rodilla monocéntrica o policéntrica y pilón de fibra de carbono.'),
('Prótesis Transradial (Bajo Codo)', 'Prótesis de miembro superior mioeléctrica o mecánica con encaje anatómico.'),
('Prótesis Transhumeral (Sobre Codo)', 'Prótesis con articulación mecánica de codo bloqueable y terminal intercambiable.');

-- Pacientes iniciales
INSERT INTO paciente (numero_documento, nombres, apellidos, telefono, correo) VALUES
('V-18234567', 'María Elena', 'González Pérez', '0414-7890123', 'maria.gonzalez@email.com'),
('V-15890123', 'Carlos Alberto', 'Rodríguez Silva', '0424-5678901', 'carlos.rodriguez@email.com'),
('V-20456789', 'Ana Sofía', 'Mendoza Castillo', '0412-3456789', 'ana.mendoza@email.com');

-- Entregas iniciales registradas
INSERT INTO entrega_protesis (codigo_protesis, id_paciente, id_tipo_protesis, fecha_entrega, estado, observaciones) VALUES
('PRT-2026-0001', 1, 1, '2026-10-15', 'ENTREGADO', 'Prótesis transtibial derecha adaptada con pie dinámico en fibra de carbono. Alineación estática y dinámica verificada con éxito.'),
('PRT-2026-0002', 2, 2, '2026-10-28', 'EN_PRUEBAS', 'Prótesis transfemoral izquierda en fase de adaptación del socket y ajuste del amortiguador de rodilla.'),
('PRT-2026-0003', 3, 3, '2026-11-01', 'EN_FABRICACION', 'Prótesis transradial derecha en confección en taller ortopédico.');
