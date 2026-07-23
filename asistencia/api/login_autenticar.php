<?php
// Autenticar usuario (trabajador o admin) y redirigir

// Incluir configuración
require_once '../includes/config.php';

// Configurar cabecera para JSON
header('Content-Type: application/json');

// Solo aceptar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

// Obtener datos del cuerpo de la petición
$input = json_decode(file_get_contents('php://input'), true);
$identificador = trim($input['identificador'] ?? '');
$password = $input['password'] ?? '';

// Validar campos requeridos
if (empty($identificador) || empty($password)) {
    echo json_encode([
        'success' => false,
        'message' => 'Usuario y contraseña son requeridos'
    ]);
    exit;
}

try {
    // 1. Buscar en trabajadores (por CVE)
    $sql_trabajador = "SELECT 
                        id, 
                        cve, 
                        CONCAT(nombre, ' ', apellidoP, ' ', COALESCE(apellidoM, '')) AS nombre_completo,
                        password,
                        activo
                    FROM trabajadores 
                    WHERE cve = :identificador";
    
    $stmt = $pdo->prepare($sql_trabajador);
    $stmt->execute([':identificador' => $identificador]);
    $trabajador = $stmt->fetch();
    
    // Verificar si es trabajador y está activo
    if ($trabajador && $trabajador['activo'] == 1) {
        // Verificar contraseña
        if (password_verify($password, $trabajador['password'])) {
            // Login exitoso como trabajador
            $_SESSION['user_id'] = $trabajador['id'];
            $_SESSION['user_nombre'] = trim(preg_replace('/\s+/', ' ', $trabajador['nombre_completo']));
            $_SESSION['user_cve'] = $trabajador['cve'];
            $_SESSION['user_rol'] = 'trabajador';
            
            echo json_encode([
                'success' => true,
                'redirect' => '/asistencia/index.php',  // Ruta absoluta corregida
                'message' => 'Bienvenido ' . $_SESSION['user_nombre']
            ]);
            exit;
        }
    }
    
    // 2. Buscar en admins (por usuario)
    $sql_admin = "SELECT id, usuario, password FROM admins WHERE usuario = :identificador";
    $stmt = $pdo->prepare($sql_admin);
    $stmt->execute([':identificador' => $identificador]);
    $admin = $stmt->fetch();
    
    if ($admin) {
        // Verificar contraseña
        if (password_verify($password, $admin['password'])) {
            // Login exitoso como admin
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['user_nombre'] = $admin['usuario'];
            $_SESSION['user_rol'] = 'admin';
            
            echo json_encode([
                'success' => true,
                'redirect' => '/asistencia/admin/dashboard.php',  // Ruta absoluta corregida
                'message' => 'Bienvenido Administrador ' . $_SESSION['user_nombre']
            ]);
            exit;
        }
    }
    
    // 3. Credenciales incorrectas
    echo json_encode([
        'success' => false,
        'message' => 'Verifica tu CVE/usuario o contraseña.'
    ]);
    
} catch (PDOException $e) {
    error_log("Error en login_autenticar.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al iniciar sesión. Intenta más tarde.'
    ]);
}
?>