/* =====================================================================
   SISTEMA DE GESTIÓN DE ADAPTACIONES Y MANTENIMIENTO DE PRÓTESIS Y ÓRTESIS
   Motor: PostgreSQL 13+
   Materia: Base de Datos - Ingeniería de Sistemas (ULA)

   CONVENCIONES DE NOMBRES (se aplican a TODO el esquema)
   -------------------------------------------------------------------
   1. Todo en minúsculas, snake_case, en español, sin tildes ni eñes.
   2. Tablas en singular:            paciente, componente, ...
   3. Clave primaria:                id_<nombre_de_la_tabla>
   4. Clave foránea:                 mismo nombre que la PK que referencia
                                     (si hay dos roles, se agrega sufijo:
                                      id_usuario_sistema_tecnico).
   5. Fechas:                        fecha_<evento>   (DATE o TIMESTAMP)
   6. Booleanos:                     activo, atendida
   7. Columnas de auditoría (en TODAS las tablas de negocio):
        fecha_creacion, fecha_actualizacion,
        usuario_creacion, usuario_actualizacion
   8. Constraints:  pk_ / fk_ / uq_ / ck_ ; índices: idx_ ; triggers: trg_

   NORMALIZACIÓN
   -------------------------------------------------------------------
   1FN: todos los atributos son atómicos; no hay grupos repetidos
        (las medidas anatómicas van en filas, no en columnas medida1..n).
   2FN: toda tabla con clave compuesta/única natural depende de la clave
        completa; se usan PK subrogadas y UNIQUE para las claves naturales.
   3FN: no hay dependencias transitivas; los datos descriptivos viven en
        catálogos (estado_*, tipo_*, nivel_*, categoria_*, fabricante...).

   CÓMO SE REGISTRA EL USUARIO QUE HACE CADA CAMBIO
   -------------------------------------------------------------------
   La aplicación (o el estudiante en psql) ejecuta al iniciar sesión:
       SELECT set_config('app.id_usuario_sistema', '2', false);
   Los triggers leen ese valor y lo guardan en usuario_creacion /
   usuario_actualizacion y en bitacora_auditoria. Si no se define,
   se usa el usuario 1 = 'sistema'.
   ===================================================================== */

-- Descomentar para reiniciar el esquema completo:
-- DROP SCHEMA public CASCADE; CREATE SCHEMA public;

/* =====================================================================
   1. SEGURIDAD: ROLES Y USUARIOS
   ===================================================================== */

CREATE TABLE rol (
    id_rol                  SERIAL PRIMARY KEY,
    nombre_rol              VARCHAR(50)  NOT NULL,
    descripcion             VARCHAR(200),
    fecha_creacion          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion        INTEGER   NOT NULL DEFAULT 1,
    usuario_actualizacion   INTEGER   NOT NULL DEFAULT 1,
    CONSTRAINT uq_rol_nombre_rol UNIQUE (nombre_rol)
);

CREATE TABLE usuario_sistema (
    id_usuario_sistema      SERIAL PRIMARY KEY,
    id_rol                  INTEGER      NOT NULL REFERENCES rol(id_rol),
    nombre_usuario          VARCHAR(50)  NOT NULL,
    nombre_completo         VARCHAR(150) NOT NULL,
    correo                  VARCHAR(120) NOT NULL,
    activo                  BOOLEAN      NOT NULL DEFAULT TRUE,
    fecha_creacion          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion        INTEGER   NOT NULL DEFAULT 1,
    usuario_actualizacion   INTEGER   NOT NULL DEFAULT 1,
    CONSTRAINT uq_usuario_sistema_nombre_usuario UNIQUE (nombre_usuario),
    CONSTRAINT uq_usuario_sistema_correo UNIQUE (correo)
);

/* =====================================================================
   2. CATÁLOGOS (garantizan la 3FN)
   ===================================================================== */

CREATE TABLE causa_condicion (
    id_causa_condicion      SERIAL PRIMARY KEY,
    nombre_causa            VARCHAR(80)  NOT NULL,
    descripcion             VARCHAR(200),
    fecha_creacion          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion        INTEGER   NOT NULL DEFAULT 1,
    usuario_actualizacion   INTEGER   NOT NULL DEFAULT 1,
    CONSTRAINT uq_causa_condicion_nombre_causa UNIQUE (nombre_causa)
);

CREATE TABLE segmento_corporal (
    id_segmento_corporal    SERIAL PRIMARY KEY,
    nombre_segmento         VARCHAR(80)  NOT NULL,
    descripcion             VARCHAR(200),
    fecha_creacion          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion        INTEGER   NOT NULL DEFAULT 1,
    usuario_actualizacion   INTEGER   NOT NULL DEFAULT 1,
    CONSTRAINT uq_segmento_corporal_nombre_segmento UNIQUE (nombre_segmento)
);

-- Nivel de actividad (escala K0-K4). El factor multiplica las horas de uso
-- para obtener las "horas de desgaste" de cada componente.
CREATE TABLE nivel_actividad (
    id_nivel_actividad      SERIAL PRIMARY KEY,
    codigo_nivel            VARCHAR(5)   NOT NULL,
    nombre_nivel            VARCHAR(80)  NOT NULL,
    descripcion             VARCHAR(250),
    factor_desgaste         NUMERIC(4,2) NOT NULL,
    fecha_creacion          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion        INTEGER   NOT NULL DEFAULT 1,
    usuario_actualizacion   INTEGER   NOT NULL DEFAULT 1,
    CONSTRAINT uq_nivel_actividad_codigo_nivel UNIQUE (codigo_nivel),
    CONSTRAINT ck_nivel_actividad_factor_desgaste CHECK (factor_desgaste > 0)
);

CREATE TABLE tipo_medida (
    id_tipo_medida          SERIAL PRIMARY KEY,
    nombre_medida           VARCHAR(80)  NOT NULL,
    unidad_medida           VARCHAR(10)  NOT NULL,
    descripcion             VARCHAR(200),
    fecha_creacion          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion        INTEGER   NOT NULL DEFAULT 1,
    usuario_actualizacion   INTEGER   NOT NULL DEFAULT 1,
    CONSTRAINT uq_tipo_medida_nombre_medida UNIQUE (nombre_medida)
);

CREATE TABLE tipo_dispositivo (
    id_tipo_dispositivo     SERIAL PRIMARY KEY,
    nombre_tipo             VARCHAR(80)  NOT NULL,
    categoria_dispositivo   VARCHAR(10)  NOT NULL,
    descripcion             VARCHAR(200),
    fecha_creacion          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion        INTEGER   NOT NULL DEFAULT 1,
    usuario_actualizacion   INTEGER   NOT NULL DEFAULT 1,
    CONSTRAINT uq_tipo_dispositivo_nombre_tipo UNIQUE (nombre_tipo),
    CONSTRAINT ck_tipo_dispositivo_categoria CHECK (categoria_dispositivo IN ('PROTESIS','ORTESIS'))
);

CREATE TABLE estado_dispositivo (
    id_estado_dispositivo   SERIAL PRIMARY KEY,
    nombre_estado           VARCHAR(50)  NOT NULL,
    fecha_creacion          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion        INTEGER   NOT NULL DEFAULT 1,
    usuario_actualizacion   INTEGER   NOT NULL DEFAULT 1,
    CONSTRAINT uq_estado_dispositivo_nombre_estado UNIQUE (nombre_estado)
);

CREATE TABLE categoria_componente (
    id_categoria_componente SERIAL PRIMARY KEY,
    nombre_categoria        VARCHAR(80)  NOT NULL,   -- socket, articulacion, sensor, pie, liner...
    descripcion             VARCHAR(200),
    fecha_creacion          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion        INTEGER   NOT NULL DEFAULT 1,
    usuario_actualizacion   INTEGER   NOT NULL DEFAULT 1,
    CONSTRAINT uq_categoria_componente_nombre_categoria UNIQUE (nombre_categoria)
);

CREATE TABLE fabricante (
    id_fabricante           SERIAL PRIMARY KEY,
    nombre_fabricante       VARCHAR(100) NOT NULL,
    pais_origen             VARCHAR(60),
    fecha_creacion          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion        INTEGER   NOT NULL DEFAULT 1,
    usuario_actualizacion   INTEGER   NOT NULL DEFAULT 1,
    CONSTRAINT uq_fabricante_nombre_fabricante UNIQUE (nombre_fabricante)
);

CREATE TABLE estado_componente (
    id_estado_componente    SERIAL PRIMARY KEY,
    nombre_estado           VARCHAR(50)  NOT NULL,
    fecha_creacion          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion        INTEGER   NOT NULL DEFAULT 1,
    usuario_actualizacion   INTEGER   NOT NULL DEFAULT 1,
    CONSTRAINT uq_estado_componente_nombre_estado UNIQUE (nombre_estado)
);

CREATE TABLE tipo_ajuste (
    id_tipo_ajuste          SERIAL PRIMARY KEY,
    nombre_ajuste           VARCHAR(80)  NOT NULL,
    descripcion             VARCHAR(200),
    fecha_creacion          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion        INTEGER   NOT NULL DEFAULT 1,
    usuario_actualizacion   INTEGER   NOT NULL DEFAULT 1,
    CONSTRAINT uq_tipo_ajuste_nombre_ajuste UNIQUE (nombre_ajuste)
);

CREATE TABLE tipo_mantenimiento (
    id_tipo_mantenimiento   SERIAL PRIMARY KEY,
    nombre_mantenimiento    VARCHAR(80)  NOT NULL,
    frecuencia_dias         INTEGER      NOT NULL,
    descripcion             VARCHAR(200),
    fecha_creacion          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion        INTEGER   NOT NULL DEFAULT 1,
    usuario_actualizacion   INTEGER   NOT NULL DEFAULT 1,
    CONSTRAINT uq_tipo_mantenimiento_nombre_mantenimiento UNIQUE (nombre_mantenimiento),
    CONSTRAINT ck_tipo_mantenimiento_frecuencia_dias CHECK (frecuencia_dias > 0)
);

CREATE TABLE estado_mantenimiento (
    id_estado_mantenimiento SERIAL PRIMARY KEY,
    nombre_estado           VARCHAR(50)  NOT NULL,
    fecha_creacion          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion        INTEGER   NOT NULL DEFAULT 1,
    usuario_actualizacion   INTEGER   NOT NULL DEFAULT 1,
    CONSTRAINT uq_estado_mantenimiento_nombre_estado UNIQUE (nombre_estado)
);

-- Umbrales de alerta por desgaste (ADVERTENCIA = 80 %, CRITICA = 100 %)
CREATE TABLE nivel_alerta (
    id_nivel_alerta         SERIAL PRIMARY KEY,
    nombre_nivel            VARCHAR(30)  NOT NULL,
    porcentaje_umbral       NUMERIC(5,2) NOT NULL,
    fecha_creacion          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion        INTEGER   NOT NULL DEFAULT 1,
    usuario_actualizacion   INTEGER   NOT NULL DEFAULT 1,
    CONSTRAINT uq_nivel_alerta_nombre_nivel UNIQUE (nombre_nivel),
    CONSTRAINT uq_nivel_alerta_porcentaje_umbral UNIQUE (porcentaje_umbral),
    CONSTRAINT ck_nivel_alerta_porcentaje_umbral CHECK (porcentaje_umbral > 0)
);

/* =====================================================================
   3. PACIENTES Y EVALUACIONES BIOMECÁNICAS
   ===================================================================== */

CREATE TABLE paciente (
    id_paciente             SERIAL PRIMARY KEY,
    numero_documento        VARCHAR(20)  NOT NULL,
    nombres                 VARCHAR(100) NOT NULL,
    apellidos               VARCHAR(100) NOT NULL,
    fecha_nacimiento        DATE         NOT NULL,
    sexo                    CHAR(1)      NOT NULL,
    telefono                VARCHAR(20),
    correo                  VARCHAR(120),
    direccion               VARCHAR(250),
    activo                  BOOLEAN      NOT NULL DEFAULT TRUE,
    fecha_creacion          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion        INTEGER   NOT NULL DEFAULT 1,
    usuario_actualizacion   INTEGER   NOT NULL DEFAULT 1,
    CONSTRAINT uq_paciente_numero_documento UNIQUE (numero_documento),
    CONSTRAINT ck_paciente_sexo CHECK (sexo IN ('M','F')),
    CONSTRAINT ck_paciente_fecha_nacimiento CHECK (fecha_nacimiento <= CURRENT_DATE)
);

-- Un paciente puede tener más de una condición (p. ej. dos miembros afectados)
CREATE TABLE paciente_condicion (
    id_paciente_condicion   SERIAL PRIMARY KEY,
    id_paciente             INTEGER     NOT NULL REFERENCES paciente(id_paciente),
    id_segmento_corporal    INTEGER     NOT NULL REFERENCES segmento_corporal(id_segmento_corporal),
    id_causa_condicion      INTEGER     NOT NULL REFERENCES causa_condicion(id_causa_condicion),
    lateralidad             VARCHAR(10) NOT NULL,
    fecha_condicion         DATE,       -- fecha de amputación; NULL si es congénita
    observaciones           VARCHAR(300),
    fecha_creacion          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion        INTEGER   NOT NULL DEFAULT 1,
    usuario_actualizacion   INTEGER   NOT NULL DEFAULT 1,
    CONSTRAINT uq_paciente_condicion UNIQUE (id_paciente, id_segmento_corporal, lateralidad),
    CONSTRAINT ck_paciente_condicion_lateralidad CHECK (lateralidad IN ('IZQUIERDO','DERECHO','BILATERAL'))
);

CREATE TABLE evaluacion_biomecanica (
    id_evaluacion_biomecanica SERIAL PRIMARY KEY,
    id_paciente_condicion   INTEGER      NOT NULL REFERENCES paciente_condicion(id_paciente_condicion),
    id_nivel_actividad      INTEGER      NOT NULL REFERENCES nivel_actividad(id_nivel_actividad),
    id_usuario_sistema_evaluador INTEGER NOT NULL REFERENCES usuario_sistema(id_usuario_sistema),
    fecha_evaluacion        DATE         NOT NULL DEFAULT CURRENT_DATE,
    peso_kg                 NUMERIC(5,2) NOT NULL,
    altura_cm               NUMERIC(5,1) NOT NULL,
    observaciones           VARCHAR(500),
    fecha_creacion          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion        INTEGER   NOT NULL DEFAULT 1,
    usuario_actualizacion   INTEGER   NOT NULL DEFAULT 1,
    CONSTRAINT uq_evaluacion_biomecanica UNIQUE (id_paciente_condicion, fecha_evaluacion),
    CONSTRAINT ck_evaluacion_biomecanica_peso_kg CHECK (peso_kg > 0),
    CONSTRAINT ck_evaluacion_biomecanica_altura_cm CHECK (altura_cm > 0)
);

-- Variaciones anatómicas en el tiempo: una fila por medida y por evaluación
CREATE TABLE medida_anatomica (
    id_medida_anatomica     SERIAL PRIMARY KEY,
    id_evaluacion_biomecanica INTEGER     NOT NULL REFERENCES evaluacion_biomecanica(id_evaluacion_biomecanica),
    id_tipo_medida          INTEGER       NOT NULL REFERENCES tipo_medida(id_tipo_medida),
    valor_medida            NUMERIC(8,2)  NOT NULL,
    fecha_creacion          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion        INTEGER   NOT NULL DEFAULT 1,
    usuario_actualizacion   INTEGER   NOT NULL DEFAULT 1,
    CONSTRAINT uq_medida_anatomica UNIQUE (id_evaluacion_biomecanica, id_tipo_medida),
    CONSTRAINT ck_medida_anatomica_valor_medida CHECK (valor_medida > 0)
);

/* =====================================================================
   4. COMPONENTES Y DISPOSITIVOS
   ===================================================================== */

CREATE TABLE modelo_componente (
    id_modelo_componente    SERIAL PRIMARY KEY,
    id_categoria_componente INTEGER      NOT NULL REFERENCES categoria_componente(id_categoria_componente),
    id_fabricante           INTEGER      NOT NULL REFERENCES fabricante(id_fabricante),
    nombre_modelo           VARCHAR(100) NOT NULL,
    vida_util_horas         NUMERIC(10,2) NOT NULL,   -- horas de desgaste que soporta
    peso_maximo_kg          NUMERIC(5,1),
    fecha_creacion          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion        INTEGER   NOT NULL DEFAULT 1,
    usuario_actualizacion   INTEGER   NOT NULL DEFAULT 1,
    CONSTRAINT uq_modelo_componente UNIQUE (id_fabricante, nombre_modelo),
    CONSTRAINT ck_modelo_componente_vida_util_horas CHECK (vida_util_horas > 0)
);

-- Relación compleja: qué modelos pueden reemplazar a otros / acoplarse entre sí
CREATE TABLE compatibilidad_modelo (
    id_compatibilidad_modelo SERIAL PRIMARY KEY,
    id_modelo_componente    INTEGER NOT NULL REFERENCES modelo_componente(id_modelo_componente),
    id_modelo_componente_compatible INTEGER NOT NULL REFERENCES modelo_componente(id_modelo_componente),
    descripcion             VARCHAR(200),
    fecha_creacion          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion        INTEGER   NOT NULL DEFAULT 1,
    usuario_actualizacion   INTEGER   NOT NULL DEFAULT 1,
    CONSTRAINT uq_compatibilidad_modelo UNIQUE (id_modelo_componente, id_modelo_componente_compatible),
    CONSTRAINT ck_compatibilidad_modelo_distintos CHECK (id_modelo_componente <> id_modelo_componente_compatible)
);

-- Pieza física individual (identificada por número de serie)
CREATE TABLE componente (
    id_componente           SERIAL PRIMARY KEY,
    id_modelo_componente    INTEGER      NOT NULL REFERENCES modelo_componente(id_modelo_componente),
    id_estado_componente    INTEGER      NOT NULL REFERENCES estado_componente(id_estado_componente),
    numero_serie            VARCHAR(60)  NOT NULL,
    fecha_adquisicion       DATE         NOT NULL DEFAULT CURRENT_DATE,
    horas_desgaste_acumuladas NUMERIC(10,2) NOT NULL DEFAULT 0,
    fecha_creacion          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion        INTEGER   NOT NULL DEFAULT 1,
    usuario_actualizacion   INTEGER   NOT NULL DEFAULT 1,
    CONSTRAINT uq_componente_numero_serie UNIQUE (numero_serie),
    CONSTRAINT ck_componente_horas_desgaste CHECK (horas_desgaste_acumuladas >= 0)
);

CREATE TABLE dispositivo_protesico (
    id_dispositivo_protesico SERIAL PRIMARY KEY,
    id_paciente_condicion   INTEGER     NOT NULL REFERENCES paciente_condicion(id_paciente_condicion),
    id_evaluacion_biomecanica INTEGER   NOT NULL REFERENCES evaluacion_biomecanica(id_evaluacion_biomecanica),
    id_tipo_dispositivo     INTEGER     NOT NULL REFERENCES tipo_dispositivo(id_tipo_dispositivo),
    id_estado_dispositivo   INTEGER     NOT NULL REFERENCES estado_dispositivo(id_estado_dispositivo),
    codigo_dispositivo      VARCHAR(30) NOT NULL,
    fecha_entrega           DATE,
    fecha_baja              DATE,
    fecha_creacion          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion        INTEGER   NOT NULL DEFAULT 1,
    usuario_actualizacion   INTEGER   NOT NULL DEFAULT 1,
    CONSTRAINT uq_dispositivo_protesico_codigo UNIQUE (codigo_dispositivo),
    CONSTRAINT ck_dispositivo_protesico_fechas CHECK (fecha_baja IS NULL OR fecha_entrega IS NULL OR fecha_baja >= fecha_entrega)
);

-- Componentes instalados en cada dispositivo (con historial de reemplazos)
CREATE TABLE dispositivo_componente (
    id_dispositivo_componente SERIAL PRIMARY KEY,
    id_dispositivo_protesico INTEGER   NOT NULL REFERENCES dispositivo_protesico(id_dispositivo_protesico),
    id_componente           INTEGER    NOT NULL REFERENCES componente(id_componente),
    fecha_instalacion       DATE       NOT NULL DEFAULT CURRENT_DATE,
    fecha_retiro            DATE,
    motivo_retiro           VARCHAR(200),
    fecha_creacion          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion        INTEGER   NOT NULL DEFAULT 1,
    usuario_actualizacion   INTEGER   NOT NULL DEFAULT 1,
    CONSTRAINT uq_dispositivo_componente UNIQUE (id_dispositivo_protesico, id_componente, fecha_instalacion),
    CONSTRAINT ck_dispositivo_componente_fechas CHECK (fecha_retiro IS NULL OR fecha_retiro >= fecha_instalacion)
);
-- Una pieza física solo puede estar instalada en un dispositivo a la vez
CREATE UNIQUE INDEX uq_dispositivo_componente_instalado
    ON dispositivo_componente (id_componente) WHERE fecha_retiro IS NULL;

/* =====================================================================
   5. AJUSTES, MANTENIMIENTO, USO Y ALERTAS
   ===================================================================== */

CREATE TABLE historial_ajuste (
    id_historial_ajuste     SERIAL PRIMARY KEY,
    id_dispositivo_protesico INTEGER    NOT NULL REFERENCES dispositivo_protesico(id_dispositivo_protesico),
    id_tipo_ajuste          INTEGER     NOT NULL REFERENCES tipo_ajuste(id_tipo_ajuste),
    id_evaluacion_biomecanica INTEGER   REFERENCES evaluacion_biomecanica(id_evaluacion_biomecanica),
    id_usuario_sistema_tecnico INTEGER  NOT NULL REFERENCES usuario_sistema(id_usuario_sistema),
    fecha_ajuste            DATE        NOT NULL DEFAULT CURRENT_DATE,
    descripcion_ajuste      VARCHAR(500) NOT NULL,
    fecha_creacion          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion        INTEGER   NOT NULL DEFAULT 1,
    usuario_actualizacion   INTEGER   NOT NULL DEFAULT 1
);

CREATE TABLE mantenimiento_preventivo (
    id_mantenimiento_preventivo SERIAL PRIMARY KEY,
    id_dispositivo_protesico INTEGER    NOT NULL REFERENCES dispositivo_protesico(id_dispositivo_protesico),
    id_tipo_mantenimiento   INTEGER     NOT NULL REFERENCES tipo_mantenimiento(id_tipo_mantenimiento),
    id_estado_mantenimiento INTEGER     NOT NULL REFERENCES estado_mantenimiento(id_estado_mantenimiento),
    id_usuario_sistema_tecnico INTEGER  REFERENCES usuario_sistema(id_usuario_sistema),
    fecha_programada        DATE        NOT NULL,
    fecha_realizacion       DATE,
    observaciones           VARCHAR(500),
    fecha_creacion          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion        INTEGER   NOT NULL DEFAULT 1,
    usuario_actualizacion   INTEGER   NOT NULL DEFAULT 1
);

-- Horas de uso diario reportadas: alimentan el cálculo de desgaste
CREATE TABLE registro_uso (
    id_registro_uso         SERIAL PRIMARY KEY,
    id_dispositivo_protesico INTEGER    NOT NULL REFERENCES dispositivo_protesico(id_dispositivo_protesico),
    fecha_uso               DATE        NOT NULL,
    horas_uso               NUMERIC(4,1) NOT NULL,
    fecha_creacion          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion        INTEGER   NOT NULL DEFAULT 1,
    usuario_actualizacion   INTEGER   NOT NULL DEFAULT 1,
    CONSTRAINT uq_registro_uso UNIQUE (id_dispositivo_protesico, fecha_uso),
    CONSTRAINT ck_registro_uso_horas_uso CHECK (horas_uso > 0 AND horas_uso <= 24)
);

CREATE TABLE alerta_desgaste (
    id_alerta_desgaste      SERIAL PRIMARY KEY,
    id_dispositivo_componente INTEGER   NOT NULL REFERENCES dispositivo_componente(id_dispositivo_componente),
    id_nivel_alerta         INTEGER     NOT NULL REFERENCES nivel_alerta(id_nivel_alerta),
    porcentaje_desgaste     NUMERIC(6,2) NOT NULL,   -- foto del % al momento de generar la alerta
    mensaje                 VARCHAR(300) NOT NULL,
    atendida                BOOLEAN     NOT NULL DEFAULT FALSE,
    fecha_atencion          TIMESTAMP,
    fecha_creacion          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion        INTEGER   NOT NULL DEFAULT 1,
    usuario_actualizacion   INTEGER   NOT NULL DEFAULT 1
);
-- Evita alertas duplicadas del mismo nivel mientras no se atienda la anterior
CREATE UNIQUE INDEX uq_alerta_desgaste_pendiente
    ON alerta_desgaste (id_dispositivo_componente, id_nivel_alerta) WHERE NOT atendida;

/* =====================================================================
   6. BITÁCORA DE AUDITORÍA (consultas por usuario, fecha y acción)
   Nota: esta tabla es la propia auditoría; usa fecha_accion e
   id_usuario_sistema en lugar de las 4 columnas de auditoría genéricas.
   ===================================================================== */

CREATE TABLE bitacora_auditoria (
    id_bitacora_auditoria   BIGSERIAL PRIMARY KEY,
    nombre_tabla            VARCHAR(63)  NOT NULL,
    id_registro             BIGINT       NOT NULL,
    accion                  VARCHAR(10)  NOT NULL,
    id_usuario_sistema      INTEGER      NOT NULL REFERENCES usuario_sistema(id_usuario_sistema),
    fecha_accion            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    datos_anteriores        JSONB,
    datos_nuevos            JSONB,
    CONSTRAINT ck_bitacora_auditoria_accion CHECK (accion IN ('INSERT','UPDATE','DELETE'))
);

/* =====================================================================
   7. DATOS SEMILLA MÍNIMOS (antes de activar triggers y FKs de auditoría)
   ===================================================================== */

INSERT INTO rol (nombre_rol, descripcion) VALUES
    ('ADMINISTRADOR',      'Acceso total al sistema'),
    ('TECNICO_ORTOPEDICO', 'Realiza ajustes y mantenimientos'),
    ('EVALUADOR_CLINICO',  'Realiza evaluaciones biomecánicas');

-- Usuario id 1: usado por defecto cuando no se define app.id_usuario_sistema
INSERT INTO usuario_sistema (id_rol, nombre_usuario, nombre_completo, correo)
VALUES (1, 'sistema', 'Usuario del Sistema', 'sistema@protesis.local');

/* =====================================================================
   8. FUNCIONES Y TRIGGERS DE AUDITORÍA
   ===================================================================== */

-- 8.1 Usuario de la sesión actual
CREATE OR REPLACE FUNCTION fn_usuario_actual() RETURNS INTEGER AS $$
DECLARE
    v_valor TEXT;
BEGIN
    v_valor := current_setting('app.id_usuario_sistema', true);
    IF v_valor IS NULL OR v_valor = '' THEN
        RETURN 1;   -- usuario 'sistema'
    END IF;
    RETURN v_valor::INTEGER;
END;
$$ LANGUAGE plpgsql STABLE;

-- 8.2 Llena fecha/usuario de creación y actualización automáticamente
CREATE OR REPLACE FUNCTION fn_auditoria_columnas() RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        NEW.fecha_creacion        := CURRENT_TIMESTAMP;
        NEW.fecha_actualizacion   := CURRENT_TIMESTAMP;
        NEW.usuario_creacion      := fn_usuario_actual();
        NEW.usuario_actualizacion := fn_usuario_actual();
    ELSE
        NEW.fecha_creacion        := OLD.fecha_creacion;   -- no se puede alterar
        NEW.usuario_creacion      := OLD.usuario_creacion; -- no se puede alterar
        NEW.fecha_actualizacion   := CURRENT_TIMESTAMP;
        NEW.usuario_actualizacion := fn_usuario_actual();
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- 8.3 Registra cada INSERT / UPDATE / DELETE en la bitácora
--     TG_ARGV[0] = nombre de la columna PK de la tabla (id_<tabla>)
CREATE OR REPLACE FUNCTION fn_bitacora() RETURNS TRIGGER AS $$
DECLARE
    v_pk TEXT := TG_ARGV[0];
BEGIN
    IF TG_OP = 'INSERT' THEN
        INSERT INTO bitacora_auditoria (nombre_tabla, id_registro, accion, id_usuario_sistema, datos_nuevos)
        VALUES (TG_TABLE_NAME, (to_jsonb(NEW)->>v_pk)::BIGINT, 'INSERT', fn_usuario_actual(), to_jsonb(NEW));
    ELSIF TG_OP = 'UPDATE' THEN
        INSERT INTO bitacora_auditoria (nombre_tabla, id_registro, accion, id_usuario_sistema, datos_anteriores, datos_nuevos)
        VALUES (TG_TABLE_NAME, (to_jsonb(NEW)->>v_pk)::BIGINT, 'UPDATE', fn_usuario_actual(), to_jsonb(OLD), to_jsonb(NEW));
    ELSE
        INSERT INTO bitacora_auditoria (nombre_tabla, id_registro, accion, id_usuario_sistema, datos_anteriores)
        VALUES (TG_TABLE_NAME, (to_jsonb(OLD)->>v_pk)::BIGINT, 'DELETE', fn_usuario_actual(), to_jsonb(OLD));
    END IF;
    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

-- 8.4 Aplica FKs de auditoría y triggers a TODAS las tablas de negocio
--     (gracias a la convención PK = id_<tabla> se puede automatizar)
DO $$
DECLARE
    r RECORD;
BEGIN
    FOR r IN
        SELECT DISTINCT table_name
        FROM information_schema.columns
        WHERE table_schema = 'public' AND column_name = 'usuario_creacion'
    LOOP
        EXECUTE format('ALTER TABLE %I ADD CONSTRAINT fk_%s_usuario_creacion
                        FOREIGN KEY (usuario_creacion) REFERENCES usuario_sistema(id_usuario_sistema)',
                       r.table_name, r.table_name);
        EXECUTE format('ALTER TABLE %I ADD CONSTRAINT fk_%s_usuario_actualizacion
                        FOREIGN KEY (usuario_actualizacion) REFERENCES usuario_sistema(id_usuario_sistema)',
                       r.table_name, r.table_name);
        EXECUTE format('CREATE TRIGGER trg_%s_auditoria_columnas
                        BEFORE INSERT OR UPDATE ON %I
                        FOR EACH ROW EXECUTE FUNCTION fn_auditoria_columnas()',
                       r.table_name, r.table_name);
        EXECUTE format('CREATE TRIGGER trg_%s_bitacora
                        AFTER INSERT OR UPDATE OR DELETE ON %I
                        FOR EACH ROW EXECUTE FUNCTION fn_bitacora(%L)',
                       r.table_name, r.table_name, 'id_' || r.table_name);
    END LOOP;
END;
$$;

/* =====================================================================
   9. ALERTAS AUTOMÁTICAS POR DESGASTE SEGÚN LA ACTIVIDAD DEL USUARIO
   Al registrar horas de uso:
     horas_desgaste = horas_uso x factor_desgaste (nivel de actividad
     de la evaluación biomecánica más reciente del paciente)
   Se acumula en cada componente instalado y se genera alerta si se
   alcanza algún umbral de nivel_alerta (80 % / 100 %).
   ===================================================================== */

CREATE OR REPLACE FUNCTION fn_calcular_desgaste() RETURNS TRIGGER AS $$
DECLARE
    v_factor     NUMERIC;
    v_acumulado  NUMERIC;
    v_porcentaje NUMERIC;
    r            RECORD;
    v_nivel      RECORD;
BEGIN
    SELECT na.factor_desgaste INTO v_factor
    FROM dispositivo_protesico d
    JOIN evaluacion_biomecanica e ON e.id_paciente_condicion = d.id_paciente_condicion
    JOIN nivel_actividad na       ON na.id_nivel_actividad   = e.id_nivel_actividad
    WHERE d.id_dispositivo_protesico = NEW.id_dispositivo_protesico
    ORDER BY e.fecha_evaluacion DESC, e.id_evaluacion_biomecanica DESC
    LIMIT 1;

    v_factor := COALESCE(v_factor, 1);

    FOR r IN
        SELECT dc.id_dispositivo_componente, c.id_componente, c.numero_serie, mc.vida_util_horas
        FROM dispositivo_componente dc
        JOIN componente c         ON c.id_componente = dc.id_componente
        JOIN modelo_componente mc ON mc.id_modelo_componente = c.id_modelo_componente
        WHERE dc.id_dispositivo_protesico = NEW.id_dispositivo_protesico
          AND dc.fecha_retiro IS NULL
    LOOP
        UPDATE componente
           SET horas_desgaste_acumuladas = horas_desgaste_acumuladas + (NEW.horas_uso * v_factor)
         WHERE id_componente = r.id_componente
        RETURNING horas_desgaste_acumuladas INTO v_acumulado;

        v_porcentaje := ROUND(v_acumulado / r.vida_util_horas * 100, 2);

        SELECT * INTO v_nivel
        FROM nivel_alerta
        WHERE porcentaje_umbral <= v_porcentaje
        ORDER BY porcentaje_umbral DESC
        LIMIT 1;

        IF FOUND THEN
            INSERT INTO alerta_desgaste (id_dispositivo_componente, id_nivel_alerta, porcentaje_desgaste, mensaje)
            VALUES (r.id_dispositivo_componente, v_nivel.id_nivel_alerta, v_porcentaje,
                    format('Componente %s alcanzó %s%% de su vida útil (nivel %s)',
                           r.numero_serie, v_porcentaje, v_nivel.nombre_nivel))
            ON CONFLICT (id_dispositivo_componente, id_nivel_alerta) WHERE NOT atendida DO NOTHING;
        END IF;
    END LOOP;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_registro_uso_desgaste
AFTER INSERT ON registro_uso
FOR EACH ROW EXECUTE FUNCTION fn_calcular_desgaste();

-- Marca como VENCIDO todo mantenimiento programado no realizado a tiempo.
-- Ejecutar a diario (pg_cron, tarea programada o manualmente).
CREATE OR REPLACE FUNCTION fn_marcar_mantenimientos_vencidos() RETURNS INTEGER AS $$
DECLARE
    v_filas INTEGER;
BEGIN
    UPDATE mantenimiento_preventivo
       SET id_estado_mantenimiento = (SELECT id_estado_mantenimiento FROM estado_mantenimiento WHERE nombre_estado = 'VENCIDO')
     WHERE fecha_programada < CURRENT_DATE
       AND fecha_realizacion IS NULL
       AND id_estado_mantenimiento = (SELECT id_estado_mantenimiento FROM estado_mantenimiento WHERE nombre_estado = 'PROGRAMADO');
    GET DIAGNOSTICS v_filas = ROW_COUNT;
    RETURN v_filas;
END;
$$ LANGUAGE plpgsql;

/* =====================================================================
   10. ÍNDICES
   ===================================================================== */

CREATE INDEX idx_paciente_condicion_id_paciente        ON paciente_condicion (id_paciente);
CREATE INDEX idx_evaluacion_biomecanica_condicion      ON evaluacion_biomecanica (id_paciente_condicion, fecha_evaluacion);
CREATE INDEX idx_dispositivo_protesico_condicion       ON dispositivo_protesico (id_paciente_condicion);
CREATE INDEX idx_dispositivo_componente_dispositivo    ON dispositivo_componente (id_dispositivo_protesico);
CREATE INDEX idx_historial_ajuste_dispositivo          ON historial_ajuste (id_dispositivo_protesico, fecha_ajuste);
CREATE INDEX idx_mantenimiento_preventivo_dispositivo  ON mantenimiento_preventivo (id_dispositivo_protesico, fecha_programada);
CREATE INDEX idx_registro_uso_dispositivo              ON registro_uso (id_dispositivo_protesico, fecha_uso);
CREATE INDEX idx_bitacora_auditoria_usuario            ON bitacora_auditoria (id_usuario_sistema);
CREATE INDEX idx_bitacora_auditoria_fecha              ON bitacora_auditoria (fecha_accion);
CREATE INDEX idx_bitacora_auditoria_accion             ON bitacora_auditoria (accion);
CREATE INDEX idx_bitacora_auditoria_tabla_registro     ON bitacora_auditoria (nombre_tabla, id_registro);

/* =====================================================================
   11. DATOS DE CATÁLOGO
   ===================================================================== */

INSERT INTO causa_condicion (nombre_causa) VALUES
    ('Traumática'), ('Vascular / Diabética'), ('Oncológica'), ('Infecciosa'), ('Congénita');

INSERT INTO segmento_corporal (nombre_segmento) VALUES
    ('Transtibial'), ('Transfemoral'), ('Desarticulación de rodilla'),
    ('Transradial'), ('Transhumeral'), ('Pie parcial');

INSERT INTO nivel_actividad (codigo_nivel, nombre_nivel, descripcion, factor_desgaste) VALUES
    ('K0', 'Sin capacidad de deambulación', 'No deambula ni transfiere con seguridad', 0.25),
    ('K1', 'Deambulador doméstico',         'Superficies planas a cadencia fija',       0.50),
    ('K2', 'Deambulador limitado en comunidad', 'Barreras de bajo nivel (bordillos, escalones)', 0.75),
    ('K3', 'Deambulador comunitario',       'Cadencia variable, terrenos variados',     1.25),
    ('K4', 'Deportista / alto impacto',     'Actividad de alto impacto o deportiva',    1.75);

INSERT INTO tipo_medida (nombre_medida, unidad_medida, descripcion) VALUES
    ('Longitud del muñón',            'cm', 'Desde la línea articular hasta el extremo distal'),
    ('Circunferencia proximal',       'cm', 'Medida en el tercio proximal del muñón'),
    ('Circunferencia media',          'cm', 'Medida en el tercio medio del muñón'),
    ('Circunferencia distal',         'cm', 'Medida en el extremo distal del muñón'),
    ('Volumen estimado del muñón',    'ml', 'Estimación volumétrica');

INSERT INTO tipo_dispositivo (nombre_tipo, categoria_dispositivo) VALUES
    ('Prótesis transtibial',  'PROTESIS'),
    ('Prótesis transfemoral', 'PROTESIS'),
    ('Prótesis transradial',  'PROTESIS'),
    ('Órtesis tobillo-pie (AFO)', 'ORTESIS'),
    ('Órtesis rodilla-tobillo-pie (KAFO)', 'ORTESIS');

INSERT INTO estado_dispositivo (nombre_estado) VALUES
    ('EN_FABRICACION'), ('EN_PRUEBAS'), ('ENTREGADO'), ('EN_MANTENIMIENTO'), ('DADO_DE_BAJA');

INSERT INTO categoria_componente (nombre_categoria) VALUES
    ('Socket'), ('Articulación de rodilla'), ('Pie protésico'), ('Sensor'), ('Liner'), ('Pilón');

INSERT INTO fabricante (nombre_fabricante, pais_origen) VALUES
    ('Ottobock', 'Alemania'), ('Össur', 'Islandia'), ('Blatchford', 'Reino Unido');

INSERT INTO estado_componente (nombre_estado) VALUES
    ('EN_STOCK'), ('INSTALADO'), ('EN_REPARACION'), ('DESCARTADO');

INSERT INTO tipo_ajuste (nombre_ajuste) VALUES
    ('Alineación'), ('Ajuste de socket'), ('Cambio de liner'), ('Calibración de sensor'), ('Ajuste de altura');

INSERT INTO tipo_mantenimiento (nombre_mantenimiento, frecuencia_dias, descripcion) VALUES
    ('Revisión general',        90,  'Inspección visual y funcional'),
    ('Lubricación y limpieza',  60,  'Limpieza de articulaciones y lubricación'),
    ('Revisión de sensores',    180, 'Verificación y calibración de sensores');

INSERT INTO estado_mantenimiento (nombre_estado) VALUES
    ('PROGRAMADO'), ('REALIZADO'), ('VENCIDO'), ('CANCELADO');

INSERT INTO nivel_alerta (nombre_nivel, porcentaje_umbral) VALUES
    ('ADVERTENCIA', 80.00), ('CRITICA', 100.00);

/* =====================================================================
   12. VISTAS DE CONSULTA
   ===================================================================== */

-- Evolución de las medidas anatómicas del paciente y su variación
CREATE OR REPLACE VIEW vista_evolucion_medidas AS
SELECT p.id_paciente,
       p.nombres || ' ' || p.apellidos AS nombre_paciente,
       sc.nombre_segmento,
       pc.lateralidad,
       e.fecha_evaluacion,
       tm.nombre_medida,
       tm.unidad_medida,
       ma.valor_medida,
       ma.valor_medida - LAG(ma.valor_medida) OVER (
            PARTITION BY pc.id_paciente_condicion, tm.id_tipo_medida
            ORDER BY e.fecha_evaluacion) AS variacion_respecto_anterior
FROM medida_anatomica ma
JOIN evaluacion_biomecanica e ON e.id_evaluacion_biomecanica = ma.id_evaluacion_biomecanica
JOIN paciente_condicion pc    ON pc.id_paciente_condicion    = e.id_paciente_condicion
JOIN paciente p               ON p.id_paciente               = pc.id_paciente
JOIN segmento_corporal sc     ON sc.id_segmento_corporal     = pc.id_segmento_corporal
JOIN tipo_medida tm           ON tm.id_tipo_medida           = ma.id_tipo_medida;

-- Componentes instalados actualmente, con porcentaje de desgaste
CREATE OR REPLACE VIEW vista_componentes_instalados AS
SELECT d.codigo_dispositivo,
       p.nombres || ' ' || p.apellidos AS nombre_paciente,
       cc.nombre_categoria,
       f.nombre_fabricante,
       mc.nombre_modelo,
       c.numero_serie,
       dc.fecha_instalacion,
       c.horas_desgaste_acumuladas,
       mc.vida_util_horas,
       ROUND(c.horas_desgaste_acumuladas / mc.vida_util_horas * 100, 2) AS porcentaje_desgaste
FROM dispositivo_componente dc
JOIN dispositivo_protesico d  ON d.id_dispositivo_protesico = dc.id_dispositivo_protesico
JOIN paciente_condicion pc    ON pc.id_paciente_condicion   = d.id_paciente_condicion
JOIN paciente p               ON p.id_paciente              = pc.id_paciente
JOIN componente c             ON c.id_componente            = dc.id_componente
JOIN modelo_componente mc     ON mc.id_modelo_componente    = c.id_modelo_componente
JOIN categoria_componente cc  ON cc.id_categoria_componente = mc.id_categoria_componente
JOIN fabricante f             ON f.id_fabricante            = mc.id_fabricante
WHERE dc.fecha_retiro IS NULL;

-- Alertas de desgaste sin atender
CREATE OR REPLACE VIEW vista_alertas_pendientes AS
SELECT a.id_alerta_desgaste,
       d.codigo_dispositivo,
       p.nombres || ' ' || p.apellidos AS nombre_paciente,
       na.nombre_nivel AS nivel_alerta,
       a.porcentaje_desgaste,
       a.mensaje,
       a.fecha_creacion AS fecha_alerta
FROM alerta_desgaste a
JOIN nivel_alerta na          ON na.id_nivel_alerta         = a.id_nivel_alerta
JOIN dispositivo_componente dc ON dc.id_dispositivo_componente = a.id_dispositivo_componente
JOIN dispositivo_protesico d  ON d.id_dispositivo_protesico = dc.id_dispositivo_protesico
JOIN paciente_condicion pc    ON pc.id_paciente_condicion   = d.id_paciente_condicion
JOIN paciente p               ON p.id_paciente              = pc.id_paciente
WHERE NOT a.atendida
ORDER BY a.porcentaje_desgaste DESC;

-- Mantenimientos pendientes o vencidos
CREATE OR REPLACE VIEW vista_mantenimientos_pendientes AS
SELECT d.codigo_dispositivo,
       p.nombres || ' ' || p.apellidos AS nombre_paciente,
       tm.nombre_mantenimiento,
       mp.fecha_programada,
       CURRENT_DATE - mp.fecha_programada AS dias_de_retraso,
       em.nombre_estado
FROM mantenimiento_preventivo mp
JOIN dispositivo_protesico d  ON d.id_dispositivo_protesico = mp.id_dispositivo_protesico
JOIN paciente_condicion pc    ON pc.id_paciente_condicion   = d.id_paciente_condicion
JOIN paciente p               ON p.id_paciente              = pc.id_paciente
JOIN tipo_mantenimiento tm    ON tm.id_tipo_mantenimiento   = mp.id_tipo_mantenimiento
JOIN estado_mantenimiento em  ON em.id_estado_mantenimiento = mp.id_estado_mantenimiento
WHERE mp.fecha_realizacion IS NULL
  AND em.nombre_estado IN ('PROGRAMADO','VENCIDO');

-- Bitácora legible (usuario con nombre)
CREATE OR REPLACE VIEW vista_bitacora AS
SELECT b.id_bitacora_auditoria,
       b.fecha_accion,
       u.nombre_usuario,
       b.accion,
       b.nombre_tabla,
       b.id_registro,
       b.datos_anteriores,
       b.datos_nuevos
FROM bitacora_auditoria b
JOIN usuario_sistema u ON u.id_usuario_sistema = b.id_usuario_sistema;

/* =====================================================================
   13. CONSULTAS POR USUARIO, FECHA Y ACCIÓN
   Función general: cualquier parámetro en NULL significa "sin filtro".
   ===================================================================== */

CREATE OR REPLACE FUNCTION fn_consultar_bitacora(
    p_id_usuario_sistema INTEGER   DEFAULT NULL,
    p_fecha_desde        TIMESTAMP DEFAULT NULL,
    p_fecha_hasta        TIMESTAMP DEFAULT NULL,
    p_accion             VARCHAR   DEFAULT NULL
)
RETURNS TABLE (
    id_bitacora_auditoria BIGINT,
    fecha_accion          TIMESTAMP,
    nombre_usuario        VARCHAR,
    accion                VARCHAR,
    nombre_tabla          VARCHAR,
    id_registro           BIGINT,
    datos_anteriores      JSONB,
    datos_nuevos          JSONB
) AS $$
    SELECT b.id_bitacora_auditoria, b.fecha_accion, u.nombre_usuario, b.accion,
           b.nombre_tabla, b.id_registro, b.datos_anteriores, b.datos_nuevos
    FROM bitacora_auditoria b
    JOIN usuario_sistema u ON u.id_usuario_sistema = b.id_usuario_sistema
    WHERE (p_id_usuario_sistema IS NULL OR b.id_usuario_sistema = p_id_usuario_sistema)
      AND (p_fecha_desde        IS NULL OR b.fecha_accion >= p_fecha_desde)
      AND (p_fecha_hasta        IS NULL OR b.fecha_accion <= p_fecha_hasta)
      AND (p_accion             IS NULL OR b.accion = UPPER(p_accion))
    ORDER BY b.fecha_accion DESC, b.id_bitacora_auditoria DESC;
$$ LANGUAGE sql STABLE;

/* ---- Ejemplos de uso (descomentar para probar) -----------------------

-- a) Por usuario
SELECT * FROM fn_consultar_bitacora(p_id_usuario_sistema => 2);

-- b) Por rango de fechas
SELECT * FROM fn_consultar_bitacora(p_fecha_desde => '2026-11-01', p_fecha_hasta => '2026-11-30 23:59:59');

-- c) Por acción
SELECT * FROM fn_consultar_bitacora(p_accion => 'UPDATE');

-- d) Combinada: usuario + fecha + acción
SELECT * FROM fn_consultar_bitacora(2, CURRENT_DATE::timestamp, NULL, 'INSERT');

-- e) Resumen: cuántas acciones hizo cada usuario por día
SELECT u.nombre_usuario, b.fecha_accion::date AS fecha, b.accion, COUNT(*) AS total_acciones
FROM bitacora_auditoria b
JOIN usuario_sistema u ON u.id_usuario_sistema = b.id_usuario_sistema
GROUP BY u.nombre_usuario, b.fecha_accion::date, b.accion
ORDER BY fecha DESC, u.nombre_usuario;

-- f) Historia completa de un registro (quién lo creó/modificó y cuándo)
SELECT * FROM vista_bitacora WHERE nombre_tabla = 'paciente' AND id_registro = 1 ORDER BY fecha_accion;

-- g) Qué campos cambiaron en los UPDATE de una tabla
SELECT b.fecha_accion, u.nombre_usuario, b.id_registro, k.key AS campo,
       b.datos_anteriores->>k.key AS valor_anterior, b.datos_nuevos->>k.key AS valor_nuevo
FROM bitacora_auditoria b
JOIN usuario_sistema u ON u.id_usuario_sistema = b.id_usuario_sistema
CROSS JOIN LATERAL jsonb_object_keys(b.datos_nuevos) AS k(key)
WHERE b.accion = 'UPDATE' AND b.nombre_tabla = 'componente'
  AND b.datos_anteriores->>k.key IS DISTINCT FROM b.datos_nuevos->>k.key
  AND k.key NOT IN ('fecha_actualizacion','usuario_actualizacion');
---------------------------------------------------------------------- */

/* =====================================================================
   14. DEMOSTRACIÓN (flujo completo con datos de prueba)
   ===================================================================== */

-- Usuario técnico que "opera" en esta sesión
INSERT INTO usuario_sistema (id_rol, nombre_usuario, nombre_completo, correo)
VALUES ((SELECT id_rol FROM rol WHERE nombre_rol = 'TECNICO_ORTOPEDICO'),
        'jperez', 'Juan Pérez', 'jperez@protesis.local');

SELECT set_config('app.id_usuario_sistema',
                  (SELECT id_usuario_sistema::text FROM usuario_sistema WHERE nombre_usuario = 'jperez'),
                  false);

-- Paciente y su condición
INSERT INTO paciente (numero_documento, nombres, apellidos, fecha_nacimiento, sexo, telefono, correo)
VALUES ('V-12345678', 'María', 'González', '1985-04-12', 'F', '0414-1234567', 'maria@correo.com');

INSERT INTO paciente_condicion (id_paciente, id_segmento_corporal, id_causa_condicion, lateralidad, fecha_condicion)
VALUES ((SELECT id_paciente FROM paciente WHERE numero_documento = 'V-12345678'),
        (SELECT id_segmento_corporal FROM segmento_corporal WHERE nombre_segmento = 'Transtibial'),
        (SELECT id_causa_condicion FROM causa_condicion WHERE nombre_causa = 'Traumática'),
        'DERECHO', '2024-06-10');

-- Dos evaluaciones en fechas distintas (para ver variación de medidas)
INSERT INTO evaluacion_biomecanica (id_paciente_condicion, id_nivel_actividad, id_usuario_sistema_evaluador,
                                    fecha_evaluacion, peso_kg, altura_cm, observaciones)
VALUES (1, (SELECT id_nivel_actividad FROM nivel_actividad WHERE codigo_nivel = 'K2'),
        (SELECT id_usuario_sistema FROM usuario_sistema WHERE nombre_usuario = 'jperez'),
        CURRENT_DATE - 120, 68.5, 165.0, 'Evaluación inicial'),
       (1, (SELECT id_nivel_actividad FROM nivel_actividad WHERE codigo_nivel = 'K3'),
        (SELECT id_usuario_sistema FROM usuario_sistema WHERE nombre_usuario = 'jperez'),
        CURRENT_DATE - 10, 66.0, 165.0, 'Control: mejora de movilidad, pérdida de volumen');

INSERT INTO medida_anatomica (id_evaluacion_biomecanica, id_tipo_medida, valor_medida) VALUES
    (1, (SELECT id_tipo_medida FROM tipo_medida WHERE nombre_medida = 'Longitud del muñón'),      14.50),
    (1, (SELECT id_tipo_medida FROM tipo_medida WHERE nombre_medida = 'Circunferencia proximal'), 38.00),
    (2, (SELECT id_tipo_medida FROM tipo_medida WHERE nombre_medida = 'Longitud del muñón'),      14.50),
    (2, (SELECT id_tipo_medida FROM tipo_medida WHERE nombre_medida = 'Circunferencia proximal'), 36.20);

-- Dispositivo y componentes
INSERT INTO dispositivo_protesico (id_paciente_condicion, id_evaluacion_biomecanica, id_tipo_dispositivo,
                                   id_estado_dispositivo, codigo_dispositivo, fecha_entrega)
VALUES (1, 2,
        (SELECT id_tipo_dispositivo FROM tipo_dispositivo WHERE nombre_tipo = 'Prótesis transtibial'),
        (SELECT id_estado_dispositivo FROM estado_dispositivo WHERE nombre_estado = 'ENTREGADO'),
        'PRT-2026-0001', CURRENT_DATE - 8);

INSERT INTO modelo_componente (id_categoria_componente, id_fabricante, nombre_modelo, vida_util_horas, peso_maximo_kg) VALUES
    ((SELECT id_categoria_componente FROM categoria_componente WHERE nombre_categoria = 'Pie protésico'),
     (SELECT id_fabricante FROM fabricante WHERE nombre_fabricante = 'Ottobock'), 'Taleo', 2000, 100),
    ((SELECT id_categoria_componente FROM categoria_componente WHERE nombre_categoria = 'Sensor'),
     (SELECT id_fabricante FROM fabricante WHERE nombre_fabricante = 'Össur'), 'Sensor de carga S1', 80, NULL);
     -- vida útil de 80 h solo para que la demo dispare alertas rápidamente

INSERT INTO compatibilidad_modelo (id_modelo_componente, id_modelo_componente_compatible, descripcion)
VALUES (1, 2, 'El sensor S1 se acopla al pie Taleo');

INSERT INTO componente (id_modelo_componente, id_estado_componente, numero_serie) VALUES
    (1, (SELECT id_estado_componente FROM estado_componente WHERE nombre_estado = 'INSTALADO'), 'PIE-TAL-0001'),
    (2, (SELECT id_estado_componente FROM estado_componente WHERE nombre_estado = 'INSTALADO'), 'SEN-S1-0001');

INSERT INTO dispositivo_componente (id_dispositivo_protesico, id_componente, fecha_instalacion) VALUES
    (1, 1, CURRENT_DATE - 8),
    (1, 2, CURRENT_DATE - 8);

-- Ajuste y mantenimiento
INSERT INTO historial_ajuste (id_dispositivo_protesico, id_tipo_ajuste, id_evaluacion_biomecanica,
                              id_usuario_sistema_tecnico, descripcion_ajuste)
VALUES (1, (SELECT id_tipo_ajuste FROM tipo_ajuste WHERE nombre_ajuste = 'Ajuste de socket'), 2,
        (SELECT id_usuario_sistema FROM usuario_sistema WHERE nombre_usuario = 'jperez'),
        'Se agregó una media de ajuste por reducción de volumen del muñón');

INSERT INTO mantenimiento_preventivo (id_dispositivo_protesico, id_tipo_mantenimiento, id_estado_mantenimiento, fecha_programada)
VALUES (1, (SELECT id_tipo_mantenimiento FROM tipo_mantenimiento WHERE nombre_mantenimiento = 'Revisión general'),
           (SELECT id_estado_mantenimiento FROM estado_mantenimiento WHERE nombre_estado = 'PROGRAMADO'),
           CURRENT_DATE - 2);   -- ya atrasado a propósito

SELECT fn_marcar_mantenimientos_vencidos();

-- Uso diario: 20 h x factor K3 (1.25) = 25 h de desgaste por día -> el sensor (80 h)
-- genera ADVERTENCIA (>=80 %) el día 4 y CRITICA (>=100 %) el día 5
INSERT INTO registro_uso (id_dispositivo_protesico, fecha_uso, horas_uso)
SELECT 1, CURRENT_DATE - g, 20 FROM generate_series(5, 1, -1) AS g;

/* ---- Verificación rápida ----
SELECT * FROM vista_alertas_pendientes;
SELECT * FROM vista_componentes_instalados;
SELECT * FROM vista_mantenimientos_pendientes;
SELECT * FROM vista_evolucion_medidas ORDER BY id_paciente, nombre_medida, fecha_evaluacion;
SELECT * FROM fn_consultar_bitacora(p_accion => 'INSERT') LIMIT 20;
------------------------------- */
