<?php
/**
 * Módulo de Permisos por Rol
 * Asignatura: Bases de Datos - Ingeniería de Sistemas (ULA)
 *
 * Implementa una matriz de control de acceso simple y directa en PHP puro:
 * - ADMINISTRADOR: Acceso total al sistema y gestión de usuarios.
 * - TECNICO_ORTOPEDICO: Gestión de componentes, ajustes y mantenimientos.
 * - EVALUADOR_CLINICO: Pacientes, evaluaciones biomecánicas y medidas clínicas.
 */

class Permisos {
    public const ROL_ADMINISTRADOR      = 'ADMINISTRADOR';
    public const ROL_TECNICO_ORTOPEDICO = 'TECNICO_ORTOPEDICO';
    public const ROL_EVALUADOR_CLINICO  = 'EVALUADOR_CLINICO';

    /**
     * Matriz de permisos declarativa por rol, módulo y acción
     */
    private static array $matriz = [
        self::ROL_ADMINISTRADOR => [
            'usuarios'       => ['listar', 'ver', 'crear', 'editar', 'eliminar'],
            'pacientes'      => ['listar', 'ver', 'crear', 'editar', 'eliminar', 'condiciones'],
            'evaluaciones'   => ['listar', 'ver', 'crear', 'editar', 'eliminar', 'medidas'],
            'dispositivos'   => ['listar', 'ver', 'crear', 'editar', 'eliminar', 'componentes', 'cambiar_estado'],
            'componentes'    => ['listar', 'ver', 'crear', 'editar', 'eliminar', 'instalar', 'compatibles'],
            'ajustes'        => ['listar', 'ver', 'crear', 'editar', 'eliminar', 'resumen'],
            'mantenimientos' => ['listar', 'ver', 'crear', 'editar', 'eliminar', 'marcar_vencidos', 'resumen'],
        ],

        self::ROL_TECNICO_ORTOPEDICO => [
            'usuarios'       => [], // Sin acceso a administración de usuarios
            'pacientes'      => ['listar', 'ver', 'condiciones'], // Solo consulta clínica
            'evaluaciones'   => ['listar', 'ver', 'medidas'],    // Solo consulta de historiales
            'dispositivos'   => ['listar', 'ver', 'componentes', 'cambiar_estado'],
            'componentes'    => ['listar', 'ver', 'crear', 'editar', 'eliminar', 'instalar', 'compatibles'],
            'ajustes'        => ['listar', 'ver', 'crear', 'editar', 'resumen'],
            'mantenimientos' => ['listar', 'ver', 'crear', 'editar', 'eliminar', 'marcar_vencidos', 'resumen'],
        ],

        self::ROL_EVALUADOR_CLINICO => [
            'usuarios'       => [], // Sin acceso a administración de usuarios
            'pacientes'      => ['listar', 'ver', 'crear', 'editar', 'condiciones'],
            'evaluaciones'   => ['listar', 'ver', 'crear', 'editar', 'medidas'],
            'dispositivos'   => ['listar', 'ver', 'componentes'], // Consulta de dispositivos del paciente
            'componentes'    => ['listar', 'ver', 'compatibles'], // Solo lectura de catálogo
            'ajustes'        => ['listar', 'ver', 'resumen'],     // Solo consulta histórica
            'mantenimientos' => ['listar', 'ver', 'resumen'],     // Solo consulta de estado
        ]
    ];

    /**
     * Verifica si un rol específico tiene autorización para ejecutar una acción en un módulo
     */
    public static function puede(?string $rol, string $modulo, string $accion): bool {
        if (!$rol || !isset(self::$matriz[$rol])) {
            return false;
        }

        $modulo = strtolower(trim($modulo));
        $accion = strtolower(trim($accion));

        $accionesPermitidas = self::$matriz[$rol][$modulo] ?? [];
        return in_array($accion, $accionesPermitidas, true);
    }

    /**
     * Retorna la lista completa de acciones permitidas para un rol en un módulo
     */
    public static function accionesPermitidas(?string $rol, string $modulo): array {
        if (!$rol || !isset(self::$matriz[$rol])) {
            return [];
        }
        return self::$matriz[$rol][strtolower($modulo)] ?? [];
    }

    /**
     * Retorna la matriz de permisos para documentación o visualización
     */
    public static function obtenerMatriz(): array {
        return self::$matriz;
    }
}
