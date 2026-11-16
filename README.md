# Sistema de Gestión de Adaptaciones y Mantenimiento de Prótesis y Órtesis

> **Proyecto Académico – Asignatura: Bases de Datos**  
> **Carrera:** Ingeniería de Sistemas  
> **Institución:** Universidad de Los Andes (ULA)  
> **Fase 1:** Base Técnica, Arquitectura Backend, Conectividad PostgreSQL, Control de Acceso y Lógica CRUD  
> **Fase 2:** Interfaz Web Completa en PHP Puro, HTML5, CSS3 y Bootstrap 5 (Diseño Minimalista Turquesa)

---

## 1. Descripción del Proyecto

Este proyecto académico implementa un sistema integral para la **Gestión de Adaptaciones y Mantenimiento de Prótesis y Órtesis**, conectando una interfaz web moderna y minimalista con una base de datos relacional normalizada en **PostgreSQL 15** a través de **PHP 8.2 puro** mediante PDO.

El sistema fue diseñado como proyecto de la asignatura **Bases de Datos** para demostrar:
- **Modelado relacional normalizado:** 29 tablas con claves primarias, claves foráneas, restricciones de integridad referencial (`ON DELETE RESTRICT`), dominios `CHECK` y claves alternas `UNIQUE`.
- **Operaciones CRUD completas:** Registro (`INSERT` con cláusula `RETURNING`), consulta (`SELECT` con filtros y ordenamiento), actualización (`UPDATE` preparado) y eliminación segura (`DELETE` con control de integridad referencial).
- **Consultas relacionales avanzadas:** Empleo de `INNER JOIN`, `LEFT JOIN`, `GROUP BY` y funciones agregadas (`COUNT`, `SUM`).
- **Integridad y transacciones:** Captura y traducción de excepciones `PDOException` y códigos `SQLSTATE` de PostgreSQL (p. ej., `23503` para violaciones de clave foránea y `23505` para duplicidad de claves únicas).
- **Seguridad y control de acceso (RBAC):** Autenticación de sesiones en PHP puro, verificación de contraseñas mediante `password_hash()` / `password_verify()` (BCRYPT) y control de acceso según roles (`ADMINISTRADOR`, `TECNICO_ORTOPEDICO`, `EVALUADOR_CLINICO`).
- **Procedimientos almacenados y triggers:** Ejecución de funciones en **PL/pgSQL** (`fn_marcar_mantenimientos_vencidos()`) e inyección de contexto de auditoría de sesión (`SET app.id_usuario_sistema`).
- **Interfaz visual minimalista:** Diseñada en HTML5, CSS3 y Bootstrap 5 con paleta institucional turquesa, menús laterales dinámicos según rol y mensajes informativos en tiempo real.

---

## 2. Tecnologías Empleadas

- **Base de Datos:** PostgreSQL 15 (Alpine Linux).
- **Lenguaje Backend:** PHP 8.2 puro (sin frameworks ni librerías pesadas).
- **Capa de Abstracción de Datos:** PDO (`pdo_pgsql`) bajo el patrón Singleton.
- **Frontend:** HTML5, CSS3, Bootstrap 5.3 (recursos locales), Bootstrap Icons.
- **Entorno de Contenedores:** Docker y Docker Compose.

---

## 3. Identidad Visual y Diseño de la Interfaz

La interfaz sigue un diseño minimalista, limpio y profesional, estructurado para uso clínico y técnico:

| Elemento | Color / Valor | Propósito |
| :--- | :--- | :--- |
| **Fondo Principal** | `#FFFFFF` / `#F8FAFC` | Superficies limpias y descanso visual |
| **Fondo Secundario** | `#E8F7F7` | Resaltado de tarjetas, badges y estados activos |
| **Turquesa Principal** | `#20B8B2` | Botones de acción, enlaces activos e iconos |
| **Turquesa Oscuro** | `#147D80` | Encabezados, títulos de módulos y branding |
| **Texto Principal** | `#263B43` | Tipografía legible de alto contraste |
| **Bordes Suaves** | `#E2E8F0` | Separadores y bordes sutiles de tablas y tarjetas |

### Componentes de Navegación:
- **Barra Superior (Topbar):** Muestra el nombre institucional, identificación del usuario autenticado, badge con su rol y botón de cierre de sesión.
- **Menú Lateral (Sidebar):** Menú vertical limpio que despliega únicamente los módulos autorizados para el rol en sesión mediante la matriz declarativa de permisos.
- **Alertas y Retroalimentación:** Notificaciones de éxito y mensajes claros de error (incluyendo advertencias de integridad referencial traducidas de PostgreSQL).
- **Formularios Intuitivos:** Creados sin modales invasivos; dropdowns alimentados en tiempo real con datos relacionales de la base de datos.

---

## 4. Roles de Usuario y Credenciales de Demostración

El sistema contempla 3 roles con privilegios diferenciados:

| Usuario | Rol Asignado | Contraseña Demo | Perfil y Alcance de Permisos |
| :--- | :--- | :--- | :--- |
| **`admin`** | `ADMINISTRADOR` | `Admin123*` | **Acceso Total:** Administra usuarios y roles, gestiona pacientes, evaluaciones, dispositivos, componentes, ajustes y mantenimientos. |
| **`jperez`** | `TECNICO_ORTOPEDICO` | `Tecnico123*` | **Taller y Prótesis:** Consulta pacientes y dispositivos; administra componentes físicos, registra ajustes técnicos, gestiona mantenimientos y ejecuta la función PL/pgSQL de vencimientos. |
| **`mrodriguez`** | `EVALUADOR_CLINICO` | `Clinico123*` | **Área Clínica:** Registra y consulta pacientes, gestiona evaluaciones biomecánicas y registra mediciones anatómicas; consulta dispositivos asignados. |

> **Nota de Seguridad:** Las contraseñas están almacenadas en `usuario_sistema.clave_hash` mediante BCRYPT (`PASSWORD_DEFAULT`). Ninguna clave se almacena en texto plano.

---

## 5. Módulos y Vistas del Sistema

```text
sistema_protesis_ortesis/
│
├── config/
│   └── database.php                # Conexión Singleton PDO a PostgreSQL y auditoría de sesión
│
├── database/
│   ├── 01_schema.sql               # Esquema relacional: 29 tablas, vistas, triggers y PL/pgSQL
│   ├── 02_datos_prueba.sql         # Datos iniciales clínicos, protésicos y de componentes
│   └── 03_autenticacion_usuarios.sql # Tabla usuario_sistema con contraseñas BCRYPT
│
├── auth/
│   ├── Auth.php                    # Servicio de inicio, validación y cierre de sesión
│   └── Permisos.php                # Matriz declarativa de permisos por rol (RBAC)
│
├── models/
│   ├── Usuario.php                 # Persistencia y roles de usuario_sistema
│   ├── Paciente.php                # CRUD de pacientes y condiciones clínicas
│   ├── Evaluacion.php              # CRUD de evaluaciones biomecánicas y medidas
│   ├── Dispositivo.php             # CRUD de prótesis y piezas instaladas (N:M)
│   ├── Componente.php              # CRUD de componentes físicos e inventario
│   ├── HistorialAjuste.php         # Historial y registro de ajustes técnicos
│   └── Mantenimiento.php           # Mantenimientos y llamada a función PL/pgSQL
│
├── controllers/
│   ├── UsuarioController.php       # Controlador exclusivo del Administrador
│   ├── AuthController.php          # Controlador de autenticación
│   ├── PacienteController.php      # Lógica de negocio y validación de pacientes
│   ├── EvaluacionController.php    # Coordinación de evaluaciones y medidas
│   ├── DispositivoController.php   # Control de dispositivos y estados operativos
│   ├── ComponenteController.php    # Gestión de componentes físicos
│   ├── HistorialAjusteController.php # Control de ajustes técnicos
│   └── MantenimientoController.php # Control de mantenimientos preventivos
│
├── includes/
│   ├── auth_check.php              # Middleware de verificación de sesión y permisos
│   ├── header.php                  # Encabezado HTML5, topbar, alertas y branding
│   ├── sidebar.php                 # Menú lateral dinámico según rol
│   ├── footer.php                  # Pie de página y carga de scripts Bootstrap 5
│   └── funciones.php               # Validadores y traductor de códigos SQLSTATE
│
├── assets/
│   ├── css/
│   │   ├── app.css                 # Estilos personalizados (paleta turquesa)
│   │   ├── bootstrap.min.css       # Bootstrap 5.3 (recurso local)
│   │   ├── bootstrap-icons.min.css # Bootstrap Icons (recurso local)
│   │   └── fonts/                  # Fuentes tipográficas de iconos locales
│   └── js/
│       └── bootstrap.bundle.min.js # Bundle JavaScript de Bootstrap 5
│
├── login.php                       # Pantalla de autenticación con botones demo
├── logout.php                      # Cierre de sesión y destrucción segura de datos
├── index.php                       # Dashboard con métricas globales y accesos rápidos
├── pacientes.php                   # Gestión integral de pacientes y condiciones clínicas
├── evaluaciones.php                # Evaluaciones biomecánicas, K-levels y medidas
├── dispositivos.php                # Gestión de prótesis/órtesis y cambio de estados
├── componentes.php                 # Inventario de piezas, horas de uso e instalación
├── ajustes.php                     # Bitácora de ajustes de socket y alineación
├── mantenimientos.php              # Mantenimientos y disparador de función PL/pgSQL
├── usuarios.php                    # Administración de usuarios y roles (solo Admin)
├── detalle.php                     # Endpoint compatible para resolución directa por ID
├── tests/
│   └── probar_conexion.php         # Banco de 9 pruebas técnicas automatizadas
│
├── Dockerfile                      # Contenedor PHP 8.2 Apache + pdo_pgsql
├── docker-compose.yml              # Orquestación de servicios (app y PostgreSQL)
└── README.md                       # Documentación técnica completa
```

---

## 6. Conceptos Demostrados de Bases de Datos

| Concepto SQL / BD | Implementación en el Proyecto |
| :--- | :--- |
| **SELECT con filtros y orden** | Métodos `listar()` con cláusulas `WHERE`, `ORDER BY` y filtros activos. |
| **INSERT con `RETURNING`** | Empleado en todos los métodos de inserción para recuperar la clave primaria generada. |
| **UPDATE con parámetros** | Consultas preparadas en PDO que previenen ataques de inyección SQL. |
| **Integridad Referencial (FK)** | Demostrado al intentar eliminar un paciente o componente con relaciones activas (`SQLSTATE 23503`). |
| **Consultas Multitabla (JOIN)** | Consultas que conectan `evaluacion_biomecanica` $\leftrightarrow$ `paciente_condicion` $\leftrightarrow$ `paciente` $\leftrightarrow$ `segmento_corporal` $\leftrightarrow$ `nivel_actividad`. |
| **Relación N:M** | Implementada entre `dispositivo_protesico` y `componente` a través de `dispositivo_componente`. |
| **Agregaciones (`GROUP BY`, `COUNT`)** | Resúmenes de dispositivos por paciente y estados de componentes en los modelos. |
| **Funciones Almacenadas (PL/pgSQL)** | Ejecución de `fn_marcar_mantenimientos_vencidos()` desde la interfaz web y CLI. |
| **Auditoría con Triggers** | Asignación de variable de sesión `app.id_usuario_sistema` en cada conexión activa. |

---

## 7. Instrucciones de Despliegue y Uso

### 1. Iniciar los contenedores:
```bash
docker compose up -d
```

### 2. Acceder a la Interfaz Web:
Abra el navegador en:
```text
http://localhost:8080/login.php
```
Puede utilizar los botones de **acceso rápido** en la pantalla de inicio de sesión para ingresar directamente con cualquiera de los tres roles (`admin`, `jperez` o `mrodriguez`).

### 3. Ejecutar el Banco de Pruebas Técnicas (CLI):
```bash
docker exec -it protesis_app php tests/probar_conexion.php
```

El script ejecuta 9 pruebas técnicas automatizadas:
1. Conexión con PostgreSQL 15 y verificación de versión.
2. Inserción de paciente con `RETURNING`.
3. Consulta de pacientes con `WHERE` y `ORDER BY`.
4. Actualización de datos de paciente (`UPDATE`).
5. Integridad referencial: rechazo de eliminación con dependientes (`SQLSTATE 23503`) y borrado limpio de registro huérfano.
6. Consultas relacionales avanzadas (`INNER JOIN`, `LEFT JOIN`, `GROUP BY`, función PL/pgSQL).
7. Autenticación segura con `password_verify()` para los 3 roles y rechazo de contraseña inválida.
8. Matriz de permisos: bloqueo a invitados (HTTP 401), bloqueo por rol no autorizado (HTTP 403) y autorización plena de administrador.
9. Resolución de detalle por ID sin errores 404.

**Tasa de éxito del banco de pruebas:** **100% (9/9 pruebas superadas).**
