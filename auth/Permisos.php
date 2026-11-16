<?php
/**
 * Módulo de Permisos por Rol
 * Asignatura: Bases de Datos - Ingeniería de Sistemas (ULA)
 *
 * Implementa una matriz de control de acceso simple y directa en PHP puro:
 * - ADMINISTRADOR: Acceso total al sistema y gestión de usuarios.
 * - TECNICO_ORTOPEDICO: Gestión de piezas protésicas y entregas; consulta clínica.
 * - EVALUADOR_CLINICO: Gestión de pacientes y evaluaciones biomecánicas; consulta de piezas/entregas.
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
            'usuarios'     => ['listar', 'ver', 'crear', 'editar', 'eliminar'],
            'pacientes'    => ['listar', 'ver', 'crear', 'editar', 'eliminar', 'condiciones'],
            'evaluaciones' => ['listar', 'ver', 'crear', 'editar', 'eliminar', 'medidas'],
            'piezas'       => ['listar', 'ver', 'crear', 'editar', 'eliminar'],
            'entregas'     => ['listar', 'ver', 'crear', 'editar', 'eliminar'],
        ],

        self::ROL_TECNICO_ORTOPEDICO => [
            'usuarios'     => [], // Sin acceso a administración de usuarios
            'pacientes'    => ['listar', 'ver', 'condiciones'], // Solo consulta clínica
            'evaluaciones' => ['listar', 'ver', 'medidas'],    // Solo consulta de evaluaciones
            'piezas'       => ['listar', 'ver', 'crear', 'editar', 'eliminar'], // Gestión de piezas
            'entregas'     => ['listar', 'ver', 'crear', 'editar', 'eliminar'], // Gestión de entregas
        ],

        self::ROL_EVALUADOR_CLINICO => [
            'usuarios'     => [], // Sin acceso a administración de usuarios
            'pacientes'    => ['listar', 'ver', 'crear', 'editar', 'condiciones'], // Gestión clínica
            'evaluaciones' => ['listar', 'ver', 'crear', 'editar', 'eliminar', 'medidas'], // Evaluaciones
            'piezas'       => ['listar', 'ver'], // Solo consulta de piezas
            'entregas'     => ['listar', 'ver'], // Solo consulta de historial de entregas
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

        if (!isset(self::$matriz[$rol][$modulo])) {
            return false;
        }

        return in_array($accion, self::$matriz[$rol][$modulo], true);
    }

    /**
     * Retorna todos los módulos a los que el rol tiene al menos permiso de lectura ('listar')
     */
    public static function modulosDisponibles(?string $rol): array {
        if (!$rol || !isset(self::$matriz[$rol])) {
            return [];
        }

        $disponibles = [];
        foreach (self::$matriz[$rol] as $modulo => $acciones) {
            if (in_array('listar', $acciones, true)) {
                $disponibles[] = $modulo;
            }
        }

        return $disponibles;
    }
}
