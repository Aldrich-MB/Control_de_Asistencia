<?php
// Cambiar contraseña del trabajador logueado
require_once '../includes/config.php';
header('Content-Type: application/json');

// Verificar que el usuario es trabajador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'trabajador') {
    echo json_encode(['success' => false, 'message' => 'No tienes permiso para realizar esta acción']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$password_actual = $input['password_actual'] ?? '';
$password_nueva = $input['password_nueva'] ?? '';

// Validaciones
if (empty($password_actual) || empty($password_nueva)) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
    exit;
}

if (strlen($password_nueva) < 8) {
    echo json_encode(['success' => false, 'message' => 'La nueva contraseña debe tener al menos 8 caracteres']);
    exit;
}

$trabajador_id = $_SESSION['user_id'];

try {
    // Obtener contraseña actual del trabajador
    $sql = "SELECT password FROM trabajadores WHERE id = :id AND activo = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $trabajador_id]);
    $trabajador = $stmt->fetch();
    
    if (!$trabajador) {
        echo json_encode(['success' => false, 'message' => 'Trabajador no encontrado o inactivo']);
        exit;
    }
    
    // Verificar contraseña actual
    if (!password_verify($password_actual, $trabajador['password'])) {
        echo json_encode(['success' => false, 'message' => 'La contraseña actual es incorrecta']);
        exit;
    }
    
    // Generar hash de la nueva contraseña
    $nuevo_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
    
    // Actualizar contraseña
    $sql = "UPDATE trabajadores SET password = :password WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':password' => $nuevo_hash,
        ':id' => $trabajador_id
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Contraseña actualizada correctamente'
    ]);
    
} catch (PDOException $e) {
    error_log("Error en cambiar_password.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al actualizar la contraseña. Intenta más tarde.'
    ]);
}
?>