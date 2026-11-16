# Sistema de Gestión de Adaptaciones y Mantenimiento de Prótesis y Órtesis

> **Proyecto Académico – Asignatura: Bases de Datos**  
> **Carrera:** Ingeniería de Sistemas  
> **Institución:** Universidad de Los Andes (ULA)  
> **Fase 1:** Base Técnica, Arquitectura Backend, Conectividad PostgreSQL, Control de Acceso y Lógica CRUD (Sin interfaz gráfica)

---

## 1. Descripción del Proyecto

Este proyecto académico implementa la arquitectura backend y la capa de acceso a datos para un **Sistema de Gestión de Adaptaciones y Mantenimiento de Prótesis y Órtesis**.

El objetivo primordial es demostrar el dominio y aplicación práctica de los conceptos fundamentales de la asignatura **Bases de Datos**:
- Modelado relacional normalizado con claves primarias, foráneas y restricciones `CHECK` / `UNIQUE`.
- Transacciones y operaciones CRUD (`INSERT`, `SELECT`, `UPDATE`, `DELETE`) usando consultas preparadas contra inyección SQL.
- Consultas relacionales complejas mediante `INNER JOIN`, `LEFT JOIN`, `GROUP BY` y funciones de agregación.
- Manejo de excepciones y traducción de códigos `SQLSTATE` de PostgreSQL.
- Control de acceso por roles de usuario (RBAC declarativo simple en PHP).
- Almacenamiento seguro de credenciales con algoritmos de derivación de claves (`password_hash` y `password_verify`).
- Invocación de funciones almacenadas en PL/pgSQL y soporte para triggers de auditoría.

---

## 2. Tecnologías Obligatorias

- **Base de Datos:** PostgreSQL 15 (Alpine Linux).
- **Lenguaje Backend:** PHP 8.2 puro (sin frameworks ni librerías externas).
- **Capa de Abstracción:** PDO (`pdo_pgsql`) implementado bajo el patrón Singleton.
- **Entorno de Despliegue:** Docker y Docker Compose.

---

## 3. Estructura del Proyecto

El código está organizado de forma clara y modular, separando las responsabilidades de configuración, persistencia de datos (Modelos), control de acceso (Auth) y lógica de negocio/validación (Controladores):

```text
sistema_protesis_ortesis/
│
├── config/
│   └── database.php                # Conexión Singleton PDO a PostgreSQL y utilidades de sesión
│
├── database/
│   ├── 01_schema.sql               # DDL original: 29 tablas, restricciones, triggers y vistas
│   ├── 02_datos_prueba.sql         # Datos iniciales de demostración clínica y dispositivos
│   └── 03_autenticacion_usuarios.sql # Migración de contraseñas seguras y cuentas demo
│
├── auth/
│   ├── Auth.php                    # Servicio de autenticación, sesión y verificación de claves
│   └── Permisos.php                # Matriz declarativa de permisos por rol
│
├── models/
│   ├── Usuario.php                 # CRUD sobre 'usuario_sistema' y catálogo de roles
│   ├── Paciente.php                # Operaciones CRUD sobre 'paciente' y consultas clínicas
│   ├── Evaluacion.php              # CRUD sobre 'evaluacion_biomecanica' y medidas anatómicas
│   ├── Dispositivo.php             # CRUD sobre 'dispositivo_protesico' y componentes vinculados
│   ├── Componente.php              # CRUD sobre 'componente' físico, compatibilidad e instalación
│   ├── HistorialAjuste.php         # CRUD sobre 'historial_ajuste' y resumen por tipo
│   └── Mantenimiento.php           # CRUD sobre 'mantenimiento_preventivo' y ejecución de PL/pgSQL
│
├── controllers/
│   ├── UsuarioController.php       # Gestión administrativa de usuarios (solo Administrador)
│   ├── AuthController.php          # Login, logout y consulta de perfil/permisos
│   ├── PacienteController.php      # Validación, permisos y coordinación de pacientes
│   ├── EvaluacionController.php    # Validación y coordinación de evaluaciones biomecánicas
│   ├── DispositivoController.php   # Validación y coordinación de dispositivos protésicos
│   ├── ComponenteController.php    # Validación y gestión de componentes físicos
│   ├── HistorialAjusteController.php # Validación y gestión de ajustes técnicos
│   └── MantenimientoController.php # Validación, control de estados y llamadas a stored functions
│
├── includes/
│   └── funciones.php               # Utilidades generales, validadores y traducción de SQLSTATE
│
├── tests/
│   └── probar_conexion.php         # Banco integral de 9 pruebas automatizadas (CRUD, Roles, Permisos)
│
├── detalle.php                     # Endpoint para resolución de consultas directas por ID
├── index.php                       # Punto de entrada informativo, rutas por ID y métricas
├── favicon.ico                     # Icono estándar para evitar errores 404 en navegadores
├── Dockerfile                      # Imagen PHP 8.2 con Apache y extensión pdo_pgsql
├── docker-compose.yml              # Orquestación de contenedores (app y db PostgreSQL)
├── .env.example                    # Plantilla de variables de entorno
└── README.md                       # Documentación técnica del proyecto
```

---

## 4. Roles de Usuario y Matriz de Permisos

El sistema contempla 3 roles claramente definidos, gestionados en la tabla `rol` y validados mediante comprobaciones simples en PHP:

### 1. `ADMINISTRADOR`
- Acceso completo a todos los módulos del sistema.
- Gestión de usuarios y asignación de roles.
- Operaciones `listar`, `ver`, `crear`, `editar` y `eliminar` en todos los módulos.

### 2. `TECNICO_ORTOPEDICO`
- Consultar pacientes y dispositivos (`listar`, `ver`).
- Gestionar componentes (`listar`, `ver`, `crear`, `editar`, `eliminar`, `instalar`, `compatibles`).
- Registrar y modificar ajustes técnicos (`listar`, `ver`, `crear`, `editar`).
- Gestionar mantenimientos preventivos (`listar`, `ver`, `crear`, `editar`, `eliminar`, `marcar_vencidos`).
- *Restricción:* No puede crear o eliminar pacientes ni evaluaciones; no puede administrar usuarios.

### 3. `EVALUADOR_CLINICO`
- Registrar y consultar pacientes (`listar`, `ver`, `crear`, `editar`).
- Crear y modificar evaluaciones biomecánicas y medidas anatómicas (`listar`, `ver`, `crear`, `editar`, `medidas`).
- Consultar dispositivos asociados a pacientes (`listar`, `ver`).
- *Restricción:* No puede alterar componentes, ajustes de taller, mantenimientos ni usuarios.

### Cuentas Demo Preconfiguradas:

| Usuario | Rol Asignado | Contraseña Demo | Correo Electrónico |
| :--- | :--- | :--- | :--- |
| **`admin`** | `ADMINISTRADOR` | `Admin123*` | `admin@protesis.local` |
| **`jperez`** | `TECNICO_ORTOPEDICO` | `Tecnico123*` | `jperez@protesis.local` |
| **`mrodriguez`** | `EVALUADOR_CLINICO` | `Clinico123*` | `mrodriguez@protesis.local` |

> **Nota de Seguridad:** Las contraseñas nunca se almacenan en texto plano. Se procesan con `password_hash($clave, PASSWORD_DEFAULT)` utilizando el algoritmo BCRYPT y se verifican con `password_verify()`.

---

## 5. Resolución de Errores y Rutas

- **Eliminación de Error 404 en `detalle.php`:** Se implementó `detalle.php` como script de consulta de detalle por ID (`detalle.php?id=1` o `detalle.php?modulo=pacientes&id=1`). Adicionalmente, `index.php` admite parámetros de consulta directa (`index.php?modulo=pacientes&id=1`).
- **Eliminación de Error 404 en `favicon.ico`:** Incorporación de archivo favicon en la raíz.
- **Ausencia de Vistas HTML:** Todas las respuestas de consulta se entregan de forma limpia en texto plano formateado o en formato JSON estructurado (`?format=json`).

---

## 6. Conceptos de Bases de Datos Demostrados

| Concepto SQL | Implementación en el Proyecto |
| :--- | :--- |
| **SELECT con filtros y orden** | Métodos `listar()` en modelos con cláusulas `WHERE` y `ORDER BY`. |
| **INSERT con `RETURNING`** | Empleado en todos los métodos `crear()` para retornar la clave primaria generada. |
| **UPDATE con parámetros** | Sentencias preparadas para modificación de registros garantizando protección contra inyección SQL. |
| **Integridad Referencial (FK)** | Método `destroy()` capturando el error `SQLSTATE 23503` al intentar eliminar registros con dependientes. |
| **INNER JOIN múltiple** | Conexión de `evaluacion_biomecanica` $\leftrightarrow$ `paciente_condicion` $\leftrightarrow$ `paciente` $\leftrightarrow$ `segmento_corporal` $\leftrightarrow$ `nivel_actividad`. |
| **LEFT JOIN y Agregación** | Métodos `resumenDispositivosPorPaciente()` y `resumenPorEstado()` usando `COUNT()` y `GROUP BY`. |
| **Relación N:M** | Asociación de piezas físicas a prótesis mediante la tabla intermedia `dispositivo_componente`. |
| **Stored Functions (PL/pgSQL)** | Invocación de `fn_marcar_mantenimientos_vencidos()` desde `Mantenimiento::marcarVencidos()`. |
| **Triggers y Auditoría** | Inicialización de sesión `SET app.id_usuario_sistema` en `Database::getConnection()` para nutrir triggers de auditoría. |

---

## 7. Ejecución y Verificación con Docker

### Iniciar los contenedores:
```bash
docker compose up -d
```

### Ejecutar el banco de pruebas completo:
```bash
docker exec -it protesis_app php tests/probar_conexion.php
```

El script ejecuta 9 pruebas técnicas automatizadas:
1. Conexión con PostgreSQL y versión del motor.
2. Inserción de paciente con `RETURNING`.
3. Consulta de pacientes con `WHERE` y `ORDER BY`.
4. Actualización de datos de paciente (`UPDATE`).
5. Integridad referencial: rechazo de eliminación con `SQLSTATE 23503` y borrado limpio de registro sin dependencias.
6. Consultas relacionales avanzadas (`INNER JOIN`, `LEFT JOIN`, `GROUP BY`, función PL/pgSQL).
7. Autenticación segura con `password_verify()` para los 3 roles y rechazo de clave errónea.
8. Matriz de permisos: bloqueo a `INVITADO` (HTTP 401), bloqueo a `TECNICO_ORTOPEDICO` en módulo no autorizado (HTTP 403), bloqueo a `EVALUADOR_CLINICO` en componentes (HTTP 403) y autorización plena a `ADMINISTRADOR`.
9. Consulta de detalle por ID y verificación de ausencia de rutas 404.

**Tasa de éxito del banco de pruebas:** **100% (9/9 superadas).**

---

### Consultas desde el Navegador o curl:
- **Estado general del sistema:** `http://localhost:8080/`
- **Estado en formato JSON:** `http://localhost:8080/?format=json`
- **Detalle de un dispositivo por ID:** `http://localhost:8080/detalle.php?id=1`
- **Detalle de un paciente por ID:** `http://localhost:8080/?modulo=pacientes&id=1`
- **Ejecución de pruebas vía web:** `http://localhost:8080/tests/probar_conexion.php`
