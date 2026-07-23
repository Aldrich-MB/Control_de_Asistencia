<?php
// includes/config.php - Configuración central del sistema

// Reportar todos los errores (solo en desarrollo)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Iniciar sesión para todo el sistema
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==================== CONSTANTES ====================

// Base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');
define('DB_NAME', 'tu_base_datos');

// Zona horaria de México
date_default_timezone_set('America/Mexico_City');

// ==================== URLs del sistema ====================

// Detectar protocolo
$protocol = 'https';
$host = $_SERVER['HTTP_HOST'];

if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
    $protocol = 'http';
}

define('BASE_URL', $protocol . "://" . $host . "/");
define('BASE_PATH', dirname(__DIR__) . '/');

// ==================== FORZAR HTTPS EN PRODUCCIÓN ====================

if (strpos($_SERVER['HTTP_HOST'], 'localhost') === false && 
    strpos($_SERVER['HTTP_HOST'], '127.0.0.1') === false && 
    (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off')) {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}

// ==================== COORDENADAS DE LA OFICINA ====================

define('OFICINA_LAT', 99.999999999999999);
define('OFICINA_LNG', -99.99999999999999);
define('RADIO_PERMITIDO_METROS', 00);

// ==================== CONEXIÓN PDO ====================

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    die("Error de conexión a la base de datos:<br>" . $e->getMessage());
}

// ==================== FUNCIÓN HELPER ====================

function obtenerIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}
?>