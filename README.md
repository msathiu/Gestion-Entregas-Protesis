# Sistema de Gestión de Entregas de Prótesis

> **Proyecto Académico – Asignatura: Bases de Datos**  
> **Primera Entrega**: Implementación minimalista y funcional de un CRUD relacional para la gestión de entregas de prótesis con **PostgreSQL 15**, **PHP puro (PDO)**, **Bootstrap 5** y **Docker**.

---

## 1. Descripción del Proyecto

Este sistema web permite administrar el registro, seguimiento y control de **entregas de prótesis a pacientes**, interactuando directamente con un motor de base de datos relacional **PostgreSQL 15**.

El proyecto está diseñado bajo un enfoque académico estricto que prioriza la simplicidad, la integridad referencial y las buenas prácticas de normalización, cumpliendo con:
- Un modelo de datos exacto de **3 tablas normalizadas**.
- Un máximo de **10 archivos de implementación** en PHP.
- Una arquitectura clara, sin frameworks ni sobrecargas empresariales, preparada para ampliaciones en entregas posteriores.

---

## 2. Tecnologías Utilizadas

- **Base de Datos:** PostgreSQL 15 (Alpine Linux).
- **Lenguaje Backend:** PHP 8.2 (Puro, sin frameworks ni CMS).
- **Conector a Base de Datos:** Extensión PDO (`pdo_pgsql`) con sentencias preparadas contra inyecciones SQL.
- **Frontend:** HTML5, CSS3 y Bootstrap 5.3 (archivos locales, no requiere Internet).
- **Contenedores:** Docker y Docker Compose.

---

## 3. Requisitos de Instalación

Únicamente se requiere tener instalado en el equipo anfitrión:
- **Docker Desktop** (en Windows/Mac) o **Docker Engine + Docker Compose** (en Linux).
- Git (opcional, para clonar el repositorio).

*No es necesario tener instalados localmente PHP, Apache ni PostgreSQL; todo se ejecuta dentro de los contenedores Docker.*

---

## 4. Pasos para Configurar y Ejecutar el Proyecto

1. **Ubíquese en el directorio del proyecto:**
   ```bash
   cd /ruta/al/proyecto
   ```

2. **Verifique el archivo de variables de entorno `.env`:**
   El proyecto incluye un archivo `.env` preconfigurado listo para usar:
   ```env
   DB_HOST=db
   DB_PORT=5433
   DB_NAME=protesis_ortesis
   DB_USER=postgres
   DB_PASSWORD=postgres_secure_password
   APP_PORT=8080
   ```

---

## 5. Configuración y Ejecución mediante Docker

### Iniciar el sistema:
```bash
docker compose up -d --build
```
PostgreSQL levantará automáticamente el esquema relacional (`database/schema.sql`) y sembrará los registros iniciales de prueba.

### Acceder a la aplicación web:
Abra cualquier navegador e ingrese a:
```text
http://localhost:8080
```
La aplicación está lista para interactuar inmediatamente, sin requerir inicios de sesión complejos.

### Detener los contenedores:
```bash
docker compose down
```

### Reiniciar limpiando volúmenes (base de datos desde cero):
```bash
docker compose down -v && docker compose up -d
```

### Ejecutar las pruebas automatizadas del CRUD en consola:
```bash
docker exec protesis_app php /var/www/html/test.php
```

---

## 6. Estructura Simplificada de Archivos

El proyecto cumple estrictamente con el límite de **10 archivos de implementación**:

```text
BaseDatos/
├── .env                  # Variables de entorno para Docker
├── Dockerfile            # Imagen PHP 8.2 con Apache y pdo_pgsql
├── docker-compose.yml    # Orquestación de servicios (app + db)
├── README.md             # Documentación técnica de instalación y uso
├── sistema_protesis_ortesis.sql # Script SQL de respaldo del esquema
│
├── database/
│   └── schema.sql        # DDL de PostgreSQL (3 tablas, claves e inserciones)
│
├── assets/               # Dependencias de interfaz locales (Bootstrap 5)
│   ├── css/ (bootstrap.min.css, bootstrap-icons.min.css)
│   └── js/  (bootstrap.bundle.min.js)
│
└── [ARCHIVOS DE IMPLEMENTACIÓN - EXACTAMENTE 10]
    ├── 1. config.php     # Conexión Singleton PDO a PostgreSQL y utilidades (XSS, CSRF, flash)
    ├── 2. header.php     # Layout: encabezado HTML5 y barra de navegación superior
    ├── 3. footer.php     # Layout: pie de página y scripts JS
    ├── 4. index.php      # [CRUD - Consultar] Listado de entregas, filtros y métricas
    ├── 5. crear.php      # [CRUD - Crear] Formulario de registro de nueva entrega
    ├── 6. detalle.php    # [CRUD - Consultar Detalle] Ficha técnica del paciente y prótesis
    ├── 7. editar.php     # [CRUD - Actualizar] Modificación de datos de una entrega
    ├── 8. eliminar.php   # [CRUD - Eliminar] Confirmación de baja lógica o eliminación física
    ├── 9. pacientes.php  # [Catálogo de Soporte] Consulta y alta de pacientes
    └── 10. test.php      # [Pruebas] Verificación automatizada en consola del CRUD y PostgreSQL
```

---

## 7. Descripción de las 3 Tablas y sus Relaciones

El modelo relacional está normalizado en Tercera Forma Normal (3FN) y consta de exactamente **3 tablas**:

```text
+---------------------+           +------------------------+
|      paciente       | 1       N |    entrega_protesis    |
+---------------------+-----------+------------------------+
| PK id_paciente      |           | PK id_entrega          |
| UQ numero_documento |           | UQ codigo_protesis     |
|    nombres          |           | FK id_paciente         |
|    apellidos        |           | FK id_tipo_protesis    |
|    telefono         |           |    fecha_entrega       |
|    correo           |           | CK estado              |
+---------------------+           |    observaciones       |
                                  +------------------------+
+---------------------+                      | N
|    tipo_protesis    | 1                    |
+---------------------+----------------------+
| PK id_tipo_protesis |
| UQ nombre_tipo      |
|    descripcion      |
+---------------------+
```

### 1. `paciente` (Entidad Fuerte)
- **`id_paciente` (SERIAL, PK):** Clave primaria autoincremental.
- **`numero_documento` (VARCHAR(20), UNIQUE):** Cédula o pasaporte único del paciente.
- **`nombres` y `apellidos` (VARCHAR(100), NOT NULL):** Datos de filiación.
- **`telefono` y `correo` (VARCHAR):** Canales de contacto del paciente.

### 2. `tipo_protesis` (Catálogo Normalizado)
- **`id_tipo_protesis` (SERIAL, PK):** Clave primaria autoincremental.
- **`nombre_tipo` (VARCHAR(100), UNIQUE):** Tipo de prótesis (Transtibial, Transfemoral, Mioeléctrica, etc.).
- **`descripcion` (TEXT):** Especificación biomecánica o técnica del segmento.

### 3. `entrega_protesis` (Tabla Central del CRUD)
- **`id_entrega` (SERIAL, PK):** Identificador único del registro de entrega.
- **`codigo_protesis` (VARCHAR(50), UNIQUE):** Código asignado a la pieza entregada (ej. `PRT-2026-0001`).
- **`id_paciente` (INT, FK):** Referencia obligatoria a `paciente(id_paciente)` con regla `ON DELETE RESTRICT`. Bloquea la eliminación de un paciente si tiene entregas asociadas, preservando la integridad referencial (`SQLSTATE 23503`).
- **`id_tipo_protesis` (INT, FK):** Referencia obligatoria a `tipo_protesis(id_tipo_protesis)` con regla `ON DELETE RESTRICT`.
- **`fecha_entrega` (DATE, NOT NULL):** Fecha en la que el paciente recibe el dispositivo adaptado.
- **`estado` (VARCHAR(30), CHECK):** Restricción que garantiza valores válidos: `'EN_FABRICACION'`, `'EN_PRUEBAS'`, `'ENTREGADO'`, `'DADO_DE_BAJA'`.
- **`observaciones` (TEXT):** Notas clínicas y técnicas de la entrega.

---

## 8. Funcionalidades CRUD Disponibles

| Operación | Archivo | Descripción |
| :--- | :--- | :--- |
| **1. Consultar (Listado)** | [`index.php`](file:///index.php) | Muestra todas las entregas registradas mediante `INNER JOIN` entre las 3 tablas. Incluye buscador por código, cédula o nombre, filtro interactivo por estado y tarjetas de conteo métrico. |
| **2. Consultar (Detalle)** | [`detalle.php`](file:///detalle.php) | Ficha detallada que cruza la información de la entrega, los datos de contacto del paciente receptor y la descripción técnica del tipo de prótesis. |
| **3. Crear** | [`crear.php`](file:///crear.php) | Formulario con generación de código sugerido (`PRT-YYYY-XXXX`), selección de paciente, selección de tipo de prótesis, validación de código único y guardado en PostgreSQL. |
| **4. Actualizar** | [`editar.php`](file:///editar.php) | Permite modificar cualquiera de los atributos de una entrega existente validando la unicidad del código modificado. |
| **5. Eliminar** | [`eliminar.php`](file:///eliminar.php) | Pantalla de confirmación con doble modalidad: <br>a) **Baja Lógica:** Marca el estado como `DADO_DE_BAJA` preservando el historial. <br>b) **Eliminación Física:** Ejecuta `DELETE` permanente en la base de datos. |

---

## 9. Preparación para el Futuro (Posibles Ampliaciones)

La arquitectura modular y normalizada permite incorporar fácilmente nuevas funcionalidades en entregas posteriores sin tener que rediseñar el sistema actual:

1. **Evaluaciones Biomecánicas:** Incorporar una tabla `evaluacion_biomecanica` vinculada a `paciente` para registrar mediciones antropométricas previas a la fabricación.
2. **Componentes y Montaje (N:M):** Añadir tablas `componente` y `entrega_componente` para registrar números de serie específicos de sockets, articulaciones y pies protésicos instalados.
3. **Mantenimientos y Garantías:** Crear una tabla `mantenimiento_preventivo` ligada a `entrega_protesis` para registrar visitas periódicas de ajuste y lubricación.
4. **Módulo de Usuarios y Roles:** Incorporar autenticación con roles (`TECNICO_ORTOPEDICO`, `EVALUADOR_CLINICO`, `ADMIN`) sin alterar las tablas de entregas existentes.
