<?php
//Cierre de sesión unificado (trabajadores y administradores)

// Incluir configuración
require_once 'includes/config.php';

// Verificar si hay sesión activa antes de cerrar
$sesion_activa = isset($_SESSION['user_id']) && isset($_SESSION['user_rol']);

// Guardar el rol para personalizar el mensaje (opcional)
$rol = $_SESSION['user_rol'] ?? '';

// Destruir la sesión completamente
$_SESSION = array();

// Si se usa cookie de sesión, eliminarla
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destruir la sesión
session_destroy();

// Redirigir al login con mensaje de éxito
$mensaje = '';
if ($sesion_activa) {
    if ($rol === 'admin') {
        $mensaje = 'Has cerrado sesión correctamente.';
    } else {
        $mensaje = 'Tu sesión ha finalizado. ¡Hasta pronto!';
    }
}

// Redirigir al login con mensaje (usando URL parameter)
header('Location: login.php?logout=success&mensaje=' . urlencode($mensaje));
exit;
?>