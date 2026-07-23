<?php
// Pantalla de autenticación para administradores

// Incluir configuración
require_once '../includes/config.php';

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['admin_id']) && isset($_SESSION['admin_usuario'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

// Procesar el formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($usuario) || empty($password)) {
        $error = 'Por favor, completa todos los campos';
    } else {
        try {
            // Buscar administrador por usuario (usando columna "password")
            $sql = "SELECT id, usuario, password FROM admins WHERE usuario = :usuario";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':usuario' => $usuario]);
            $admin = $stmt->fetch();
            
            // Verificar contraseña
            if ($admin && password_verify($password, $admin['password'])) {
                // Login exitoso
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_usuario'] = $admin['usuario'];
                
                // Redirigir al dashboard
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Usuario o contraseña incorrectos';
            }
        } catch (PDOException $e) {
            error_log("Error en login.php: " . $e->getMessage());
            $error = 'Error al iniciar sesión. Intenta más tarde.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Control de Asistencia</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="login-wrap">

<div class="login-card">
    <div class="login-logo">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="#fdbcb4">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/>
        </svg>
        <h2>Panel Administrativo</h2>
        <p>Control de Asistencia</p>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error show" style="display:flex; margin-bottom: 1.5rem;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="campo">
            <label for="usuario">Usuario</label>
            <input type="text" id="usuario" name="usuario" placeholder="Ingresa tu usuario" required autofocus>
        </div>
        
        <div class="campo">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" placeholder="Ingresa tu contraseña" required>
        </div>
        
        <button type="submit" class="btn btn-primary btn-full">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                <polyline points="10 17 15 12 10 7"/>
                <line x1="15" x2="3" y1="12" y2="12"/>
            </svg>
            Iniciar sesión
        </button>
        
        <hr class="div">
        
        <div style="text-align: center; font-size: 13px; color: #9ca3af; margin-top: 1rem;">
            <a href="../index.php" style="color: #fdbcb4; text-decoration: none;">← Volver al registro de asistencia</a>
        </div>
    </form>
</div>

</body>
</html>