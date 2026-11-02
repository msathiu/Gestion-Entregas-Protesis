<?php
/**
 * Configuración Global, Conexión a Base de Datos PostgreSQL y Utilidades
 * Asignatura: Bases de Datos - CRUD de Gestión de Entregas de Prótesis
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('APP_NAME', 'Sistema de Gestión de Entregas de Prótesis');

/**
 * Retorna la instancia única de conexión PDO a PostgreSQL
 */
function get_db(): PDO {
    static $db = null;
    if ($db === null) {
        $host = getenv('DB_HOST') ?: 'db';
        $port = getenv('DB_PORT') ?: '5433';
        $name = getenv('DB_NAME') ?: 'protesis_ortesis';
        $user = getenv('DB_USER') ?: 'postgres';
        $pass = getenv('DB_PASSWORD') ?: 'postgres_secure_password';

        $dsn = "pgsql:host={$host};port={$port};dbname={$name};";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $db = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            die("<div style='font-family:sans-serif;padding:2rem;background:#fee2e2;color:#991b1b;border-radius:8px;'>
                    <h3>Error de Conexión a PostgreSQL</h3>
                    <p>{$e->getMessage()}</p>
                 </div>");
        }
    }
    return $db;
}

/**
 * Escapa cadenas para prevenir ataques XSS
 */
function e(?string $value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Formatea fechas a formato legible (D/M/AAAA)
 */
function format_date(?string $date): string {
    if (!$date) return '-';
    $ts = strtotime($date);
    return $ts ? date('d/m/Y', $ts) : e($date);
}

/**
 * Renderiza un badge visual de Bootstrap para el estado de la prótesis
 */
function badge_status(string $status): string {
    $classes = [
        'ENTREGADO'      => 'bg-success text-white',
        'EN_PRUEBAS'     => 'bg-warning text-dark',
        'EN_FABRICACION' => 'bg-info text-dark',
        'DADO_DE_BAJA'   => 'bg-danger text-white',
    ];
    $labels = [
        'ENTREGADO'      => 'Entregado',
        'EN_PRUEBAS'     => 'En Pruebas',
        'EN_FABRICACION' => 'En Fabricación',
        'DADO_DE_BAJA'   => 'Dado de Baja',
    ];
    $cls = $classes[$status] ?? 'bg-secondary text-white';
    $lbl = $labels[$status] ?? e($status);
    return "<span class=\"badge {$cls}\">{$lbl}</span>";
}

/**
 * Gestión de mensajes Flash
 */
function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function render_flashes(): string {
    if (empty($_SESSION['flash'])) return '';
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $type = e($f['type']);
    $msg = e($f['message']);
    return "<div class=\"alert alert-{$type} alert-dismissible fade show\" role=\"alert\">
                {$msg}
                <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>
            </div>";
}

/**
 * Protección contra ataques CSRF
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function verify_csrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die("Token CSRF inválido. Recargue la página e intente nuevamente.");
    }
}

/**
 * Helpers para solicitudes HTTP
 */
function is_post(): bool {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function redirect(string $url): void {
    header("Location: {$url}");
    exit;
}

