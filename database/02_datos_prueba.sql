
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
