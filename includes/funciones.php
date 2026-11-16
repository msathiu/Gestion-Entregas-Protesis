<?php
/**
 * Funciones de Utilidad General del Sistema

 */

/**
 * Emite una respuesta en formato JSON estructurada
 */
function json_response(array $data, int $statusCode = 200): void {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8', true, $statusCode);
    }
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Valida que una lista de campos obligatorios esté presente y no vacía
 */
function validar_campos_requeridos(array $datos, array $campos): array {
    $faltantes = [];
    foreach ($campos as $campo) {
        if (!isset($datos[$campo]) || trim((string)$datos[$campo]) === '') {
            $faltantes[] = $campo;
        }
    }
    return $faltantes;
}

/**
 * Sanitiza valores de entrada básicos
 */
function limpiar_cadena(?string $valor): string {
    return trim((string)($valor ?? ''));
}

/**
 * Formatea fechas a formato legible
 */
function formatear_fecha(?string $fecha, string $formato = 'd/m/Y'): string {
    if (!$fecha) return '-';
    $timestamp = strtotime($fecha);
    return $timestamp ? date($formato, $timestamp) : $fecha;
}

/**
 * Traduce códigos de error SQLSTATE de PostgreSQL a explicaciones legibles
 */
function traducir_error_sqlstate(PDOException $e): string {
    $codigo = $e->getCode();
    $mensaje = $e->getMessage();

    return match ($codigo) {
        '23505' => 'Violación de restricción UNIQUE: Ya existe un registro con ese valor único en la base de datos.',
        '23503' => 'Violación de restricción FOREIGN KEY: La operación viola la integridad referencial (registro dependiente o inexistente).',
        '23502' => 'Violación de restricción NOT NULL: Un campo obligatorio no puede ser nulo.',
        '23514' => 'Violación de restricción CHECK: Uno de los valores no cumple con la regla de validación definida en la tabla.',
        '42P01' => 'Error de esquema: La tabla o relación especificada no existe en PostgreSQL.',
        default => "Error en la base de datos ({$codigo}): {$mensaje}"
    };
}
