# Sistema de Gestión de Entregas de Prótesis

> **Proyecto Académico – Asignatura: Bases de Datos**  
> **Primera Entrega**: Implementación minimalista y funcional de un CRUD relacional para la gestión de entregas de prótesis con **PostgreSQL 15**, **PHP puro (PDO)**, **Bootstrap 5** y **Docker**.

---

## 1. Descripción del Proyecto

Este sistema web permite administrar el registro, seguimiento y control de **entregas de prótesis a pacientes**, interactuando directamente con un motor de base de datos relacional **PostgreSQL 15**.


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

## 7.  Preparación para el Futuro (Posibles Ampliaciones)

La arquitectura modular y normalizada permite incorporar fácilmente nuevas funcionalidades en entregas posteriores sin tener que rediseñar el sistema actual:

1. **Evaluaciones Biomecánicas:** Incorporar una tabla `evaluacion_biomecanica` vinculada a `paciente` para registrar mediciones antropométricas previas a la fabricación.
2. **Componentes y Montaje (N:M):** Añadir tablas `componente` y `entrega_componente` para registrar números de serie específicos de sockets, articulaciones y pies protésicos instalados.
3. **Mantenimientos y Garantías:** Crear una tabla `mantenimiento_preventivo` ligada a `entrega_protesis` para registrar visitas periódicas de ajuste y lubricación.
4. **Módulo de Usuarios y Roles:** Incorporar autenticación con roles (`TECNICO_ORTOPEDICO`, `EVALUADOR_CLINICO`, `ADMIN`) sin alterar las tablas de entregas existentes.
