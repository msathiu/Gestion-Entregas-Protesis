# Sistema de Gestión de Adaptaciones y Mantenimiento de Prótesis y Órtesis

> **Proyecto Académico – Asignatura: Bases de Datos**  
> **Carrera:** Ingeniería de Sistemas  
> **Institución:** Universidad de Los Andes (ULA)  
> **Fase 1:** Base Técnica, Arquitectura Backend, Conectividad PostgreSQL y Lógica CRUD (Sin interfaz gráfica)

---

## 1. Descripción del Proyecto

Este proyecto académico implementa la arquitectura backend y la capa de acceso a datos para un **Sistema de Gestión de Adaptaciones y Mantenimiento de Prótesis y Órtesis**.

El objetivo primordial es demostrar el dominio y aplicación práctica de los conceptos fundamentales de la asignatura **Bases de Datos**:
- Modelado relacional normalizado.
- Integridad referencial (claves primarias, foráneas y restricciones `CHECK` / `UNIQUE`).
- Transacciones y operaciones CRUD (`INSERT`, `SELECT`, `UPDATE`, `DELETE`) usando consultas preparadas contra inyección SQL.
- Consultas relacionales complejas mediante `INNER JOIN`, `LEFT JOIN`, `GROUP BY` y funciones de agregación.
- Manejo de excepciones y traducción de códigos `SQLSTATE` de PostgreSQL.
- Invocación de funciones almacenadas en PL/pgSQL y soporte para triggers de auditoría.

---

## 2. Tecnologías Obligatorias

- **Base de Datos:** PostgreSQL 15 (Alpine Linux).
- **Lenguaje Backend:** PHP 8.2 puro (sin frameworks ni librerías externas).
- **Capa de Abstracción:** PDO (`pdo_pgsql`) implementado bajo el patrón Singleton.
- **Entorno de Despliegue:** Docker y Docker Compose.

---

## 3. Estructura del Proyecto

El código está organizado de forma clara y modular, separando las responsabilidades de configuración, persistencia de datos (Modelos) y lógica de negocio/validación (Controladores):

```text
sistema_protesis_ortesis/
│
├── config/
│   └── database.php                # Conexión Singleton PDO a PostgreSQL y utilidades de sesión
│
├── database/
│   ├── 01_schema.sql               # DDL original: 29 tablas, restricciones, triggers y vistas
│   └── 02_datos_prueba.sql         # Datos iniciales de demostración y pruebas
│
├── models/
│   ├── Paciente.php                # Operaciones CRUD sobre la tabla 'paciente' y consultas relacionales
│   ├── Evaluacion.php              # CRUD sobre 'evaluacion_biomecanica' y medidas anatómicas
│   ├── Dispositivo.php             # CRUD sobre 'dispositivo_protesico' y componentes vinculados
│   ├── Componente.php              # CRUD sobre 'componente' físico, compatibilidad e instalación
│   ├── HistorialAjuste.php         # CRUD sobre 'historial_ajuste' y resumen por tipo
│   └── Mantenimiento.php           # CRUD sobre 'mantenimiento_preventivo' y ejecución de PL/pgSQL
│
├── controllers/
│   ├── PacienteController.php      # Validación de entradas y coordinación del modelo Paciente
│   ├── EvaluacionController.php    # Validación y coordinación de evaluaciones biomecánicas
│   ├── DispositivoController.php   # Validación y coordinación de dispositivos protésicos
│   ├── ComponenteController.php    # Validación y gestión de componentes
│   ├── HistorialAjusteController.php # Validación y gestión del historial de ajustes técnicos
│   └── MantenimientoController.php # Validación, control de estados y llamadas a stored functions
│
├── includes/
│   └── funciones.php               # Utilidades generales, validadores y traducción de SQLSTATE
│
├── tests/
│   └── probar_conexion.php         # Banco automatizado de pruebas CRUD y validación de restricciones
│
├── Dockerfile                      # Imagen PHP 8.2 con Apache y extensión pdo_pgsql
├── docker-compose.yml              # Orquestación de contenedores (app y db PostgreSQL)
├── .env.example                    # Plantilla de variables de entorno
├── index.php                       # Punto de entrada informativo del sistema y métricas
└── README.md                       # Documentación técnica del proyecto
```

---

## 4. Base de Datos Relacional (PostgreSQL)

El sistema utiliza el modelo relacional completo definido en `sistema_protesis_ortesis.sql` (29 tablas normalizadas):

### Principales Entidades del Núcleo:
1. **`paciente`**: Registro de pacientes con restricciones `UNIQUE` sobre cédula/documento y correo electrónico.
2. **`paciente_condicion`**: Relaciona al paciente con su segmento corporal amputado y la causa de la condición.
3. **`evaluacion_biomecanica`**: Evaluaciones clínicas (peso, altura, nivel de actividad K1–K4) y medidas anatómicas (`medida_anatomica`).
4. **`dispositivo_protesico`**: Dispositivo entregado o en proceso, vinculado a la evaluación y tipo de prótesis/órtesis.
5. **`modelo_componente` y `componente`**: Catálogo de piezas físicas por número de serie, fabricante y horas de desgaste.
6. **`dispositivo_componente`**: Relación N:M que gestiona qué componentes están instalados en cada prótesis con histórico de retiros.
7. **`historial_ajuste`**: Ajustes realizados en taller/consulta con detalle técnico y responsable.
8. **`mantenimiento_preventivo`**: Mantenimientos periódicos programados y control de estados (PROGRAMADO, REALIZADO, VENCIDO).
9. **`bitacora_auditoria`**: Auditoría automática de cambios mediante triggers PL/pgSQL.

---

## 5. Conceptos de Bases de Datos Demostrados

| Concepto SQL | Implementación en el Proyecto |
| :--- | :--- |
| **SELECT con filtros y orden** | Métodos `listar()` en modelos con cláusulas `WHERE` y `ORDER BY`. |
| **INSERT con RETURNING** | Métodos `crear()` que aprovechan `RETURNING id_*` de PostgreSQL para obtener la clave primaria generada. |
| **UPDATE con WHERE** | Métodos `actualizar()` usando parámetros preparados para evitar inyección SQL. |
| **DELETE y Claves Foráneas** | Método `destroy()` de `PacienteController` demostrando la captura del error `SQLSTATE 23503` al intentar eliminar registros con dependencias activas. |
| **INNER JOIN múltiple** | Conexión de `evaluacion_biomecanica` $\leftrightarrow$ `paciente_condicion` $\leftrightarrow$ `paciente` $\leftrightarrow$ `segmento_corporal` $\leftrightarrow$ `nivel_actividad`. |
| **LEFT JOIN y Agregación** | Métodos `resumenDispositivosPorPaciente()` y `resumenPorEstado()` usando `COUNT()` y `GROUP BY`. |
| **Relación N:M** | Asociación de componentes a prótesis mediante la tabla intermedia `dispositivo_componente`. |
| **Stored Functions (PL/pgSQL)** | Ejecución de `fn_marcar_mantenimientos_vencidos()` desde `Mantenimiento::marcarVencidos()`. |
| **Triggers y Auditoría** | Inicialización de sesión `SET app.id_usuario_sistema` en `Database::getConnection()` para nutrir triggers de auditoría. |

---

## 6. Configuración y Ejecución con Docker

### Requisitos Previos:
- Docker Engine y Docker Compose instalados.

### 1. Iniciar los contenedores:
```bash
docker compose up -d
```

### 2. Verificar el estado de los contenedores:
```bash
docker compose ps
```
Debe observar `protesis_app` (puerto 8080) y `protesis_db` (puerto 5433) en estado *Up / running*.

### 3. Ejecutar el banco de pruebas automatizado:
Para verificar la conexión con PostgreSQL, las 4 operaciones CRUD, el control de integridad referencial y las consultas JOIN:
```bash
docker exec -it protesis_app php tests/probar_conexion.php
```

### 4. Consultar el estado del sistema desde el navegador o curl:
- **Resumen en texto plano:** `http://localhost:8080/`
- **Resumen en formato JSON:** `http://localhost:8080/?format=json`
- **Resultados de pruebas en navegador:** `http://localhost:8080/tests/probar_conexion.php`

### 5. Detener el entorno:
```bash
docker compose down
```

---

## 7. Pruebas Realizadas y Resultados

El script `tests/probar_conexion.php` ejecuta de forma automatizada 6 pruebas técnicas:

1. **Conexión a PostgreSQL:** Conexión exitosa a PostgreSQL 15 con PDO.
2. **INSERT:** Registro de nuevo paciente obteniendo su ID primario mediante `RETURNING`.
3. **SELECT:** Consulta de pacientes con ordenamiento alfabético y filtrado por estado activo.
4. **UPDATE:** Modificación de datos de contacto y verificación de persistencia.
5. **Integridad Referencial:** 
   - Verificación de rechazo con código `SQLSTATE 23503` al intentar borrar un paciente con registros relacionados.
   - Eliminación limpia del registro de prueba sin dependencias.
6. **Consultas Relacionales (JOINs y Agrupaciones):**
   - Demostración de `INNER JOIN` entre evaluaciones, pacientes y niveles de actividad.
   - Demostración de relación N:M entre prótesis y piezas/componentes instalados.
   - Demostración de `GROUP BY` con `COUNT()` para métricas de dispositivos por paciente y estados de mantenimiento.
   - Ejecución de la función almacenada `fn_marcar_mantenimientos_vencidos()`.

**Tasa de éxito del banco de pruebas:** **100% (6/6 superadas).**
